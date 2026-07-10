<?php

namespace App\Console\Commands;

use App\Models\CameraEvent;
use App\Models\IotSensor;
use App\Models\ScheduledReport;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;

class ProcessScheduledReports extends Command
{
    protected $signature = 'reports:process-scheduled {--report_id= : Procesa un reporte puntual por ID}';

    protected $description = 'Procesa reportes automáticos ejecutivos de IoT';

    public function handle(): int
    {
        $query = ScheduledReport::query()
            ->where('active', true)
            ->whereNotNull('next_run_at');

        if ($this->option('report_id')) {
            $query->where('id', $this->option('report_id'));
        } else {
            $query->where('next_run_at', '<=', now());
        }

        $reports = $query->get();

        if ($reports->isEmpty()) {
            $this->info('No hay reportes pendientes.');
            return Command::SUCCESS;
        }

        foreach ($reports as $report) {
            try {
                $this->info("Procesando reporte ejecutivo: {$report->name}");

                $periodTo = now(config('app.timezone', 'America/Argentina/Buenos_Aires'));
                $periodFrom = $this->calculateFromDate($report->frequency, $periodTo);

                $structuredSummary = $this->buildStructuredSummary($report, $periodFrom, $periodTo);

                $attachments = $this->buildExecutiveAttachments($report, $structuredSummary);

                if (empty($attachments)) {
                    throw new \Exception('El reporte no tiene PDF ni Excel habilitado.');
                }

                $this->sendReportEmail($report, $structuredSummary, $attachments);

                $report->update([
                    'last_sent_at' => now(),
                    'next_run_at' => $this->calculateNextRun($report->frequency),
                ]);

                $this->info("Reporte enviado correctamente: {$report->name}");

                unset($structuredSummary, $attachments);
                gc_collect_cycles();
            } catch (\Throwable $e) {
                $this->error("Error procesando {$report->name}: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }

    private function buildStructuredSummary(ScheduledReport $report, Carbon $fromLocal, Carbon $toLocal): array
    {
        $catalog = $this->sensorCatalog();

        $cameraKeys = collect($report->camera_ids ?? [])
            ->filter()
            ->values()
            ->all();

        if (empty($cameraKeys)) {
            throw new \Exception('El reporte no tiene cámaras configuradas.');
        }

        $sensorIds = $this->mapCameraKeysToSensorIds($cameraKeys);

        $fromUtc = $fromLocal->copy()->timezone('UTC')->toISOString();
        $toUtc = $toLocal->copy()->timezone('UTC')->toISOString();

        $cameraSummaries = [];

        foreach ($sensorIds as $sensorId) {
            $sensorInfo = $catalog[$sensorId] ?? [
                'sensor_id' => $sensorId,
                'camera_key' => $sensorId,
                'display_name' => $sensorId,
                'location' => null,
                'alarm_min' => null,
                'alarm_max' => null,
            ];

            $cameraSummary = $this->emptyCameraSummary($sensorInfo);

            $this->aggregateDynamoReadings(
                $sensorId,
                $fromUtc,
                $toUtc,
                $fromLocal,
                $toLocal,
                $cameraSummary
            );

            $this->aggregateOperationalEvents(
                $sensorInfo,
                $fromLocal,
                $toLocal,
                $cameraSummary
            );

            $cameraSummary = $this->finalizeCameraSummary($cameraSummary);

            $cameraSummaries[] = $cameraSummary;
        }

        $global = $this->buildGlobalSummary($cameraSummaries);

        return [
            'report' => [
                'id' => $report->id,
                'name' => $report->name,
                'frequency' => $report->frequency,
                'include_ai_summary' => (bool) $report->include_ai_summary,
                'generated_at' => now(config('app.timezone', 'America/Argentina/Buenos_Aires'))->format('d/m/Y H:i'),
            ],
            'period' => [
                'from' => $fromLocal->format('d/m/Y H:i'),
                'to' => $toLocal->format('d/m/Y H:i'),
                'label' => $this->frequencyLabel($report->frequency),
            ],
            'global' => $global,
            'cameras' => $cameraSummaries,

            // Campo preparado para el siguiente paso:
            // acá después vamos a guardar la respuesta de Bedrock/OpenAI.
            'ai_summary' => null,
        ];
    }

    private function aggregateDynamoReadings(
        string $sensorId,
        string $fromUtc,
        string $toUtc,
        Carbon $fromLocal,
        Carbon $toLocal,
        array &$summary
    ): void {
        $client = $this->getDynamoClient();
        $marshaler = new Marshaler();

        $lastEvaluatedKey = null;
        $page = 0;

        $maxPages = (int) env('IOT_SCHEDULED_REPORT_MAX_DYNAMO_PAGES', 200);
        $pageLimit = (int) env('IOT_SCHEDULED_REPORT_DYNAMO_PAGE_LIMIT', 1000);

        do {
            $page++;

            $params = [
                'TableName' => env('DYNAMODB_IOT_TABLE'),
                'KeyConditionExpression' => 'sensor_id = :sensor_id AND #ts BETWEEN :from AND :to',
                'ExpressionAttributeNames' => [
                    '#ts' => 'timestamp',
                ],
                'ExpressionAttributeValues' => [
                    ':sensor_id' => ['S' => $sensorId],
                    ':from' => ['S' => $fromUtc],
                    ':to' => ['S' => $toUtc],
                ],
                'ScanIndexForward' => true,
                'Limit' => $pageLimit,
            ];

            if ($lastEvaluatedKey) {
                $params['ExclusiveStartKey'] = $lastEvaluatedKey;
            }

            $result = $client->query($params);

            foreach ($result['Items'] ?? [] as $rawItem) {
                $item = $marshaler->unmarshalItem($rawItem);
                $this->aggregateReadingItem($item, $fromLocal, $toLocal, $summary);
            }

            $lastEvaluatedKey = $result['LastEvaluatedKey'] ?? null;

            unset($result);
        } while ($lastEvaluatedKey && $page < $maxPages);

        if ($lastEvaluatedKey) {
            $summary['warnings'][] = 'El reporte alcanzó el límite de páginas configurado. Puede haber lecturas no analizadas.';
        }
    }

    private function aggregateReadingItem(array $item, Carbon $fromLocal, Carbon $toLocal, array &$summary): void
    {
        $summary['readings']++;

        $timestamp = $item['timestamp'] ?? null;

        $readingLocal = null;

        if ($timestamp) {
            try {
                $readingLocal = Carbon::parse($timestamp)->timezone(config('app.timezone', 'America/Argentina/Buenos_Aires'));
                $dayKey = $readingLocal->format('Y-m-d');
                $dayLabel = $readingLocal->locale('es')->isoFormat('dddd D/M');

                if (!isset($summary['daily'][$dayKey])) {
                    $summary['daily'][$dayKey] = [
                        'date' => $dayKey,
                        'label' => ucfirst($dayLabel),
                        'readings' => 0,
                        'door_open_readings' => 0,
                        'out_of_range_readings' => 0,
                        'below_min_readings' => 0,
                        'above_max_readings' => 0,
                    ];
                }

                $summary['daily'][$dayKey]['readings']++;
            } catch (\Throwable $e) {
                $readingLocal = null;
                $dayKey = null;
            }
        } else {
            $dayKey = null;
        }

        $temperature = $this->firstNumeric($item, [
            'temperature',
            'temperature_c',
            'temp',
            'temp_c',
            'temperature_ch1',
            'temp_ch1',
        ]);

        if ($temperature !== null) {
            $summary['temperature_count']++;
            $summary['temperature_sum'] += $temperature;
            $summary['temperature_min'] = $summary['temperature_min'] === null ? $temperature : min($summary['temperature_min'], $temperature);
            $summary['temperature_max'] = $summary['temperature_max'] === null ? $temperature : max($summary['temperature_max'], $temperature);

            $alarmMin = $summary['alarm_min'];
            $alarmMax = $summary['alarm_max'];

            if ($alarmMin !== null && $temperature < $alarmMin) {
                $summary['below_min_readings']++;

                if ($dayKey && isset($summary['daily'][$dayKey])) {
                    $summary['daily'][$dayKey]['below_min_readings']++;
                    $summary['daily'][$dayKey]['out_of_range_readings']++;
                }
            }

            if ($alarmMax !== null && $temperature > $alarmMax) {
                $summary['above_max_readings']++;

                if ($dayKey && isset($summary['daily'][$dayKey])) {
                    $summary['daily'][$dayKey]['above_max_readings']++;
                    $summary['daily'][$dayKey]['out_of_range_readings']++;
                }
            }

            if (
                ($alarmMin !== null && $temperature < $alarmMin)
                || ($alarmMax !== null && $temperature > $alarmMax)
            ) {
                $summary['out_of_range_readings']++;
            }
        }

        $doorOpen = $this->detectDoorOpen($item);

        if ($doorOpen) {
            $summary['door_open_readings']++;

            if ($dayKey && isset($summary['daily'][$dayKey])) {
                $summary['daily'][$dayKey]['door_open_readings']++;
            }
        }

        $battery = $this->firstNumeric($item, ['battery', 'battery_level', 'battery_percent']);

        if ($battery !== null) {
            $summary['battery_count']++;
            $summary['battery_sum'] += $battery;
            $summary['battery_min'] = $summary['battery_min'] === null ? $battery : min($summary['battery_min'], $battery);
        }
    }

    private function aggregateOperationalEvents(array $sensorInfo, Carbon $fromLocal, Carbon $toLocal, array &$summary): void
    {
        if (!class_exists(CameraEvent::class)) {
            return;
        }

        $sensorKeys = collect([
            $sensorInfo['camera_key'] ?? null,
            $sensorInfo['sensor_id'] ?? null,
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($sensorKeys)) {
            return;
        }

        try {
            $events = CameraEvent::query()
                ->whereIn('sensor_key', $sensorKeys)
                ->whereBetween('created_at', [$fromLocal, $toLocal])
                ->orderBy('created_at')
                ->get();
        } catch (\Throwable $e) {
            $summary['warnings'][] = 'No se pudieron consultar registros operativos: ' . $e->getMessage();
            return;
        }

        foreach ($events as $event) {
            $summary['registered_openings']++;

            $eventType = $event->event_type ?? 'sin_tipo';
            $summary['event_types'][$eventType] = ($summary['event_types'][$eventType] ?? 0) + 1;

            try {
                $eventLocal = $event->created_at instanceof Carbon
                    ? $event->created_at->copy()->timezone(config('app.timezone', 'America/Argentina/Buenos_Aires'))
                    : Carbon::parse($event->created_at)->timezone(config('app.timezone', 'America/Argentina/Buenos_Aires'));

                $dayKey = $eventLocal->format('Y-m-d');
                $dayLabel = ucfirst($eventLocal->locale('es')->isoFormat('dddd D/M'));

                if (!isset($summary['daily'][$dayKey])) {
                    $summary['daily'][$dayKey] = [
                        'date' => $dayKey,
                        'label' => $dayLabel,
                        'readings' => 0,
                        'door_open_readings' => 0,
                        'out_of_range_readings' => 0,
                        'below_min_readings' => 0,
                        'above_max_readings' => 0,
                    ];
                }

                $summary['daily'][$dayKey]['registered_openings'] = ($summary['daily'][$dayKey]['registered_openings'] ?? 0) + 1;
            } catch (\Throwable $e) {
                // Si una fecha operativa viene inválida, no frenamos todo el reporte.
            }
        }
    }

    private function finalizeCameraSummary(array $summary): array
    {
        $readings = max((int) $summary['readings'], 0);

        $summary['temperature_avg'] = $summary['temperature_count'] > 0
            ? round($summary['temperature_sum'] / $summary['temperature_count'], 2)
            : null;

        $summary['battery_avg'] = $summary['battery_count'] > 0
            ? round($summary['battery_sum'] / $summary['battery_count'], 1)
            : null;

        $summary['door_open_percentage'] = $readings > 0
            ? round(($summary['door_open_readings'] / $readings) * 100, 2)
            : 0;

        $summary['out_of_range_percentage'] = $readings > 0
            ? round(($summary['out_of_range_readings'] / $readings) * 100, 2)
            : 0;

        $summary['daily'] = collect($summary['daily'])
            ->map(function (array $day) {
                $readings = max((int) ($day['readings'] ?? 0), 0);

                $day['door_open_percentage'] = $readings > 0
                    ? round((($day['door_open_readings'] ?? 0) / $readings) * 100, 2)
                    : 0;

                $day['out_of_range_percentage'] = $readings > 0
                    ? round((($day['out_of_range_readings'] ?? 0) / $readings) * 100, 2)
                    : 0;

                return $day;
            })
            ->sortBy('date')
            ->values()
            ->all();

        $summary['day_with_most_door_openings'] = collect($summary['daily'])
            ->sortByDesc(fn ($day) => (int) ($day['door_open_readings'] ?? 0) + (int) ($day['registered_openings'] ?? 0))
            ->first();

        $summary['day_with_most_out_of_range'] = collect($summary['daily'])
            ->sortByDesc(fn ($day) => (int) ($day['out_of_range_readings'] ?? 0))
            ->first();

        $summary['status'] = $this->cameraStatus($summary);

        unset(
            $summary['temperature_sum'],
            $summary['temperature_count'],
            $summary['battery_sum'],
            $summary['battery_count']
        );

        return $summary;
    }

    private function buildGlobalSummary(array $cameraSummaries): array
    {
        $totalReadings = collect($cameraSummaries)->sum('readings');
        $totalDoorOpen = collect($cameraSummaries)->sum('door_open_readings');
        $totalOutOfRange = collect($cameraSummaries)->sum('out_of_range_readings');
        $totalRegisteredOpenings = collect($cameraSummaries)->sum('registered_openings');

        $criticalCameras = collect($cameraSummaries)
            ->where('status', 'critical')
            ->pluck('camera_name')
            ->values()
            ->all();

        $attentionCameras = collect($cameraSummaries)
            ->where('status', 'attention')
            ->pluck('camera_name')
            ->values()
            ->all();

        $globalStatus = 'normal';

        if (!empty($criticalCameras)) {
            $globalStatus = 'critical';
        } elseif (!empty($attentionCameras)) {
            $globalStatus = 'attention';
        }

        return [
            'status' => $globalStatus,
            'status_label' => match ($globalStatus) {
                'critical' => 'Crítico',
                'attention' => 'Atención',
                default => 'Normal',
            },
            'total_cameras' => count($cameraSummaries),
            'total_readings' => $totalReadings,
            'total_door_open_readings' => $totalDoorOpen,
            'total_registered_openings' => $totalRegisteredOpenings,
            'total_out_of_range_readings' => $totalOutOfRange,
            'door_open_percentage' => $totalReadings > 0 ? round(($totalDoorOpen / $totalReadings) * 100, 2) : 0,
            'out_of_range_percentage' => $totalReadings > 0 ? round(($totalOutOfRange / $totalReadings) * 100, 2) : 0,
            'critical_cameras' => $criticalCameras,
            'attention_cameras' => $attentionCameras,
        ];
    }

    private function emptyCameraSummary(array $sensorInfo): array
    {
        return [
            'sensor_id' => $sensorInfo['sensor_id'] ?? null,
            'camera_key' => $sensorInfo['camera_key'] ?? null,
            'camera_name' => $sensorInfo['display_name'] ?? $sensorInfo['camera_id'] ?? $sensorInfo['sensor_id'] ?? 'Cámara sin identificar',
            'location' => $sensorInfo['location'] ?? null,
            'alarm_min' => $sensorInfo['alarm_min'] ?? null,
            'alarm_max' => $sensorInfo['alarm_max'] ?? null,

            'readings' => 0,
            'temperature_sum' => 0,
            'temperature_count' => 0,
            'temperature_avg' => null,
            'temperature_min' => null,
            'temperature_max' => null,

            'battery_sum' => 0,
            'battery_count' => 0,
            'battery_avg' => null,
            'battery_min' => null,

            'door_open_readings' => 0,
            'door_open_percentage' => 0,

            'registered_openings' => 0,
            'event_types' => [],

            'out_of_range_readings' => 0,
            'out_of_range_percentage' => 0,
            'below_min_readings' => 0,
            'above_max_readings' => 0,

            'daily' => [],
            'day_with_most_door_openings' => null,
            'day_with_most_out_of_range' => null,

            'status' => 'normal',
            'warnings' => [],
        ];
    }

    private function cameraStatus(array $summary): string
    {
        if (($summary['out_of_range_percentage'] ?? 0) >= 10 || ($summary['above_max_readings'] ?? 0) >= 20) {
            return 'critical';
        }

        if (($summary['out_of_range_percentage'] ?? 0) >= 3 || ($summary['door_open_percentage'] ?? 0) >= 15) {
            return 'attention';
        }

        return 'normal';
    }

    private function buildExecutiveAttachments(ScheduledReport $report, array $summary): array
    {
        $timestamp = now()->format('Ymd_His');
        $safeName = str($report->name)->slug('_')->limit(50, '')->toString();

        $attachments = [];

        if ($report->send_pdf) {
            $pdf = Pdf::loadHTML($this->buildExecutivePdfHtml($summary))
                ->setPaper('a4', 'portrait');

            $pdfBinary = $pdf->output();

            $reportsDir = base_path('reports');
            if (!File::exists($reportsDir)) {
                File::makeDirectory($reportsDir, 0755, true);
            }

            $pdfPath = $reportsDir . DIRECTORY_SEPARATOR . "reporte_ejecutivo_iot_{$safeName}_{$timestamp}.pdf";
            File::put($pdfPath, $pdfBinary);

            $this->info('PDF generado: ' . $pdfPath);

            $attachments[] = [
                'data' => $pdfBinary,
                'name' => "reporte_ejecutivo_iot_{$safeName}_{$timestamp}.pdf",
                'mime' => 'application/pdf',
            ];
        }

        if ($report->send_excel) {
            $attachments[] = [
                'data' => $this->buildExecutiveCsv($summary),
                'name' => "reporte_ejecutivo_iot_{$safeName}_{$timestamp}.csv",
                'mime' => 'text/csv',
            ];
        }

        return $attachments;
    }

    private function buildExecutivePdfHtml(array $summary): string
    {
        $statusColor = match ($summary['global']['status']) {
            'critical' => '#dc2626',
            'attention' => '#d97706',
            default => '#16a34a',
        };

        $cameraRows = collect($summary['cameras'])
            ->map(function (array $camera) {
                $doorDay = $camera['day_with_most_door_openings']['label'] ?? 'Sin datos';
                $rangeDay = $camera['day_with_most_out_of_range']['label'] ?? 'Sin datos';

                return '
                    <tr>
                        <td><strong>' . e($camera['camera_name']) . '</strong></td>
                        <td>' . e($camera['readings']) . '</td>
                        <td>' . e($this->formatNumber($camera['temperature_avg'])) . ' °C</td>
                        <td>' . e($this->formatNumber($camera['temperature_min'])) . ' °C</td>
                        <td>' . e($this->formatNumber($camera['temperature_max'])) . ' °C</td>
                        <td>' . e($camera['door_open_readings']) . ' (' . e($camera['door_open_percentage']) . '%)</td>
                        <td>' . e($camera['out_of_range_readings']) . ' (' . e($camera['out_of_range_percentage']) . '%)</td>
                        <td>' . e($doorDay) . '</td>
                        <td>' . e($rangeDay) . '</td>
                    </tr>
                ';
            })
            ->implode('');

        $cameraInsightBlocks = collect($summary['cameras'])
            ->map(function (array $camera) {
                return '
                    <div class="camera-block">
                        <h3>' . e($camera['camera_name']) . '</h3>
                        <p>' . e($this->buildCameraPlainInsight($camera)) . '</p>
                    </div>
                ';
            })
            ->implode('');

        return '
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <style>
                    body {
                        font-family: DejaVu Sans, sans-serif;
                        color: #0f172a;
                        font-size: 11px;
                        line-height: 1.45;
                    }

                    .header {
                        border-bottom: 3px solid #0f172a;
                        padding-bottom: 12px;
                        margin-bottom: 18px;
                    }

                    h1 {
                        margin: 0;
                        font-size: 22px;
                    }

                    h2 {
                        font-size: 15px;
                        margin: 18px 0 8px;
                    }

                    h3 {
                        font-size: 13px;
                        margin: 0 0 4px;
                    }

                    .muted {
                        color: #64748b;
                    }

                    .status {
                        display: inline-block;
                        padding: 6px 10px;
                        border-radius: 12px;
                        background: ' . $statusColor . ';
                        color: white;
                        font-weight: bold;
                    }

                    .grid {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 12px 0;
                    }

                    .grid th {
                        background: #0f172a;
                        color: white;
                        padding: 7px;
                        text-align: left;
                        font-size: 9px;
                    }

                    .grid td {
                        border: 1px solid #e2e8f0;
                        padding: 7px;
                        font-size: 9px;
                    }

                    .kpis {
                        width: 100%;
                        margin-top: 12px;
                    }

                    .kpi {
                        display: inline-block;
                        width: 23%;
                        border: 1px solid #e2e8f0;
                        border-radius: 10px;
                        padding: 10px;
                        margin-right: 1%;
                        vertical-align: top;
                    }

                    .kpi-label {
                        font-size: 9px;
                        color: #64748b;
                    }

                    .kpi-value {
                        font-size: 16px;
                        font-weight: bold;
                        margin-top: 4px;
                    }

                    .camera-block {
                        border: 1px solid #e2e8f0;
                        border-left: 4px solid #0ea5e9;
                        padding: 10px;
                        margin: 8px 0;
                        border-radius: 8px;
                    }

                    .page-break {
                        page-break-before: always;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Reporte IoT</h1>
                    <div class="muted">
                        Reporte: ' . e($summary['report']['name']) . ' |
                        Período: ' . e($summary['period']['from']) . ' a ' . e($summary['period']['to']) . ' |
                        Generado: ' . e($summary['report']['generated_at']) . '
                    </div>
                </div>

                <p><span class="status">Estado global: ' . e($summary['global']['status_label']) . '</span></p>

                <div class="kpis">
                    <div class="kpi">
                        <div class="kpi-label">Cámaras analizadas</div>
                        <div class="kpi-value">' . e($summary['global']['total_cameras']) . '</div>
                    </div>

                    <div class="kpi">
                        <div class="kpi-label">Lecturas analizadas</div>
                        <div class="kpi-value">' . e($summary['global']['total_readings']) . '</div>
                    </div>

                    <div class="kpi">
                        <div class="kpi-label">Aperturas sensor</div>
                        <div class="kpi-value">' . e($summary['global']['door_open_percentage']) . '%</div>
                    </div>

                    <div class="kpi">
                        <div class="kpi-label">Fuera de rango</div>
                        <div class="kpi-value">' . e($summary['global']['out_of_range_percentage']) . '%</div>
                    </div>
                </div>

                <h2>Resumen por cámara</h2>

                <table class="grid">
                    <thead>
                        <tr>
                            <th>Cámara</th>
                            <th>Lecturas</th>
                            <th>Prom.</th>
                            <th>Mín.</th>
                            <th>Máx.</th>
                            <th>Aperturas</th>
                            <th>Fuera rango</th>
                            <th>Día + aperturas</th>
                            <th>Día + desvíos</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $cameraRows . '
                    </tbody>
                </table>

                <div class="page-break"></div>

                <h2>Resumen operativo inteligente</h2>
                ' . $cameraInsightBlocks . '
            </body>
            </html>
        ';
    }

    private function buildExecutiveCsv(array $summary): string
    {
        $lines = [];
        $lines[] = 'Camara,Lecturas,Temperatura promedio,Temperatura minima,Temperatura maxima,Aperturas sensor,Porcentaje aperturas,Registros operativos,Fuera de rango,Porcentaje fuera de rango,Debajo minimo,Encima maximo,Estado';

        foreach ($summary['cameras'] as $camera) {
            $lines[] = implode(',', [
                $this->csvValue($camera['camera_name']),
                $camera['readings'],
                $this->formatNumber($camera['temperature_avg']),
                $this->formatNumber($camera['temperature_min']),
                $this->formatNumber($camera['temperature_max']),
                $camera['door_open_readings'],
                $camera['door_open_percentage'] . '%',
                $camera['registered_openings'],
                $camera['out_of_range_readings'],
                $camera['out_of_range_percentage'] . '%',
                $camera['below_min_readings'],
                $camera['above_max_readings'],
                $camera['status'],
            ]);
        }

        return "\xEF\xBB\xBF" . implode("\n", $lines);
    }

    private function sendReportEmail(ScheduledReport $report, array $summary, array $attachments): void
    {
        $recipients = $report->recipients ?? [];

        if (empty($recipients)) {
            throw new \Exception('El reporte no tiene destinatarios configurados.');
        }

        Mail::send([], [], function ($message) use ($report, $summary, $attachments, $recipients) {
            $message->to($recipients)
                ->subject('Reporte IoT - ' . $report->name)
                ->html($this->buildEmailBody($report, $summary));

            foreach ($attachments as $attachment) {
                $message->attachData(
                    $attachment['data'],
                    $attachment['name'],
                    ['mime' => $attachment['mime']]
                );
            }
        });
    }

    private function buildEmailBody(ScheduledReport $report, array $summary): string
    {
        return '
            <h2>Reporte IoT</h2>
            <p><strong>Reporte:</strong> ' . e($report->name) . '</p>
            <p><strong>Período:</strong> ' . e($summary['period']['from']) . ' a ' . e($summary['period']['to']) . '</p>
            <p><strong>Estado global:</strong> ' . e($summary['global']['status_label']) . '</p>
            <p><strong>Cámaras analizadas:</strong> ' . e($summary['global']['total_cameras']) . '</p>
            <p><strong>Lecturas analizadas:</strong> ' . e($summary['global']['total_readings']) . '</p>
            <p><strong>Aperturas detectadas por sensor:</strong> ' . e($summary['global']['total_door_open_readings']) . ' (' . e($summary['global']['door_open_percentage']) . '%)</p>
            <p><strong>Lecturas fuera de rango:</strong> ' . e($summary['global']['total_out_of_range_readings']) . ' (' . e($summary['global']['out_of_range_percentage']) . '%)</p>
            <br>
            <p>Se adjunta el reporte ejecutivo generado automáticamente desde el sistema IoT.</p>
        ';
    }

    private function buildCameraPlainInsight(array $camera): string
    {
        $parts = [];

        $parts[] = 'La cámara ' . $camera['camera_name'] . ' registró ' . $camera['readings'] . ' lecturas en el período analizado.';

        if ($camera['temperature_avg'] !== null) {
            $parts[] = 'La temperatura promedio fue de ' . $this->formatNumber($camera['temperature_avg']) . ' °C, con mínima de ' . $this->formatNumber($camera['temperature_min']) . ' °C y máxima de ' . $this->formatNumber($camera['temperature_max']) . ' °C.';
        } else {
            $parts[] = 'No se detectaron lecturas de temperatura válidas.';
        }

        $parts[] = 'Se detectaron ' . $camera['door_open_readings'] . ' lecturas con puerta abierta, equivalentes al ' . $camera['door_open_percentage'] . '% del total.';

        if (!empty($camera['day_with_most_door_openings'])) {
            $day = $camera['day_with_most_door_openings'];
            $parts[] = 'El día con mayor actividad de puerta fue ' . ($day['label'] ?? 'sin datos') . ', con ' . (($day['door_open_readings'] ?? 0) + ($day['registered_openings'] ?? 0)) . ' eventos detectados.';
        }

        $parts[] = 'Hubo ' . $camera['out_of_range_readings'] . ' lecturas fuera de rango, equivalentes al ' . $camera['out_of_range_percentage'] . '%.';

        if (!empty($camera['day_with_most_out_of_range'])) {
            $day = $camera['day_with_most_out_of_range'];
            $parts[] = 'El día con más desvíos fue ' . ($day['label'] ?? 'sin datos') . ', con ' . ($day['out_of_range_readings'] ?? 0) . ' lecturas fuera de rango.';
        }

        if ($camera['status'] === 'critical') {
            $parts[] = 'Estado sugerido: crítico. Se recomienda revisar operación, aperturas y rango térmico.';
        } elseif ($camera['status'] === 'attention') {
            $parts[] = 'Estado sugerido: atención. Se recomienda monitorear la evolución en el próximo período.';
        } else {
            $parts[] = 'Estado sugerido: normal. No se observan desvíos relevantes según las reglas actuales.';
        }

        return implode(' ', $parts);
    }

    private function getDynamoClient(): DynamoDbClient
    {
        return new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', env('AWS_REGION', 'us-east-1')),
            'version' => 'latest',
        ]);
    }

    private function sensorCatalog(): array
    {
        $nameMap = IotSensor::displayNameMap();
        $settings = IotSensor::settingsMap();

        $fallback = [
            'camara_1' => 'sensor_demo_001',
            'camara_2' => 'sensor_demo_002',
            'camara_3' => 'sensor_demo_003',
        ];

        $catalog = [];

        foreach ($fallback as $cameraKey => $sensorId) {
            $catalog[$sensorId] = [
                'sensor_id' => $sensorId,
                'camera_key' => $cameraKey,
                'display_name' => IotSensor::displayNameFor($cameraKey, $nameMap),
                'location' => $settings[$cameraKey]['location'] ?? null,
                'alarm_min' => $settings[$cameraKey]['alarm_min'] ?? null,
                'alarm_max' => $settings[$cameraKey]['alarm_max'] ?? null,
            ];
        }

        return $catalog;
    }

    private function mapCameraKeysToSensorIds(array $cameraKeys): array
    {
        $map = [
            'camara_1' => 'sensor_demo_001',
            'camara_2' => 'sensor_demo_002',
            'camara_3' => 'sensor_demo_003',
        ];

        return collect($cameraKeys)
            ->map(fn ($key) => $map[$key] ?? $key)
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    private function calculateFromDate(string $frequency, Carbon $to): Carbon
    {
        return match ($frequency) {
            'weekly' => $to->copy()->subWeek(),
            'biweekly' => $to->copy()->subWeeks(2),
            'monthly' => $to->copy()->subMonth(),
            default => $to->copy()->subWeek(),
        };
    }

    private function calculateNextRun(string $frequency): Carbon
    {
        return match ($frequency) {
            'weekly' => now()->addWeek(),
            'biweekly' => now()->addWeeks(2),
            'monthly' => now()->addMonth(),
            default => now()->addWeek(),
        };
    }

    private function frequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'weekly' => 'Semanal',
            'biweekly' => 'Quincenal',
            'monthly' => 'Mensual',
            default => 'Personalizado',
        };
    }

    private function firstNumeric(array $item, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && is_numeric($item[$key])) {
                return (float) $item[$key];
            }
        }

        return null;
    }

    private function detectDoorOpen(array $item): bool
    {
        foreach (['door_open', 'doorOpen', 'door_is_open', 'open'] as $key) {
            if (!array_key_exists($key, $item)) {
                continue;
            }

            $value = $item[$key];

            if (is_bool($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int) $value === 1;
            }

            if (is_string($value)) {
                return in_array(strtolower($value), ['1', 'true', 'open', 'opened', 'abierta', 'abierto'], true);
            }
        }

        foreach (['door_status', 'door', 'status'] as $key) {
            if (!array_key_exists($key, $item) || !is_string($item[$key])) {
                continue;
            }

            if (in_array(strtolower($item[$key]), ['open', 'opened', 'abierta', 'abierto'], true)) {
                return true;
            }
        }

        return false;
    }

    private function formatNumber($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 2, ',', '.');
    }

    private function csvValue($value): string
    {
        $value = (string) $value;
        $value = str_replace('"', '""', $value);

        return '"' . $value . '"';
    }
}
