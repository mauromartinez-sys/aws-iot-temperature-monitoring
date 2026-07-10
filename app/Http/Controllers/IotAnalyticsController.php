<?php

namespace App\Http\Controllers;

use App\Models\IotReading;
use App\Models\IotSensor;
use App\Models\CameraEvent;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IotAnalyticsController extends Controller
{
    private function getDynamoClient(): DynamoDbClient
    {
        return new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);
    }


    private function sensorCatalog(): array
    {
        $nameMap = IotSensor::displayNameMap();
        $settings = IotSensor::settingsMap();

        return [
            'sensor_demo_001' => [
                'sensor_id' => 'sensor_demo_001',
                'camera_key' => 'camara_1',
                'camera_id' => IotSensor::displayNameFor('camara_1', $nameMap),
                'location' => $settings['camara_1']['location'] ?? 'Planta Demo',
                'description' => $settings['camara_1']['description'] ?? 'Cámara de conservación general',
                'alarm_min' => $settings['camara_1']['alarm_min'] ?? null,
                'alarm_max' => $settings['camara_1']['alarm_max'] ?? null,
            ],
            'sensor_demo_002' => [
                'sensor_id' => 'sensor_demo_002',
                'camera_key' => 'camara_2',
                'camera_id' => IotSensor::displayNameFor('camara_2', $nameMap),
                'location' => $settings['camara_2']['location'] ?? 'Planta Demo',
                'description' => $settings['camara_2']['description'] ?? 'Cámara de tránsito / operación frecuente',
                'alarm_min' => $settings['camara_2']['alarm_min'] ?? null,
                'alarm_max' => $settings['camara_2']['alarm_max'] ?? null,
            ],
            'sensor_demo_003' => [
                'sensor_id' => 'sensor_demo_003',
                'camera_key' => 'camara_3',
                'camera_id' => IotSensor::displayNameFor('camara_3', $nameMap),
                'location' => $settings['camara_3']['location'] ?? 'Planta Demo',
                'description' => $settings['camara_3']['description'] ?? 'Cámara crítica / alta sensibilidad',
                'alarm_min' => $settings['camara_3']['alarm_min'] ?? null,
                'alarm_max' => $settings['camara_3']['alarm_max'] ?? null,
            ],
        ];
    }

    private function resolveFilters(Request $request): array
    {
        $appTimezone = config('app.timezone', 'America/Argentina/Buenos_Aires');
        $now = now($appTimezone);

        $range = $request->input('range', 'last_24h');

        $toLocal = $request->filled('to')
            ? Carbon::parse($request->input('to'), $appTimezone)
            : $now->copy();

        $fromLocal = match ($range) {
            'last_1h' => $toLocal->copy()->subHour(),
            'last_6h' => $toLocal->copy()->subHours(6),
            'last_12h' => $toLocal->copy()->subHours(12),
            'last_24h' => $toLocal->copy()->subDay(),
            'last_7d' => $toLocal->copy()->subDays(7),
            'last_30d' => $toLocal->copy()->subDays(30),
            'custom' => $request->filled('from')
                ? Carbon::parse($request->input('from'), $appTimezone)
                : $toLocal->copy()->subDay(),
            default => $toLocal->copy()->subDay(),
        };

        if ($fromLocal->greaterThan($toLocal)) {
            [$fromLocal, $toLocal] = [$toLocal, $fromLocal];
        }

        $availableSensors = $this->sensorCatalog();
        $availableSensorIds = array_keys($availableSensors);

        $selectedSensors = $request->input('sensors', $availableSensorIds);
        $selectedSensors = is_array($selectedSensors) ? $selectedSensors : [$selectedSensors];
        $selectedSensors = array_values(array_filter($selectedSensors, fn ($id) => isset($availableSensors[$id])));

        if (empty($selectedSensors)) {
            $selectedSensors = $availableSensorIds;
        }

        return [
            'range' => $range,
            'from_local' => $fromLocal,
            'to_local' => $toLocal,
            'from_utc' => $fromLocal->copy()->utc(),
            'to_utc' => $toLocal->copy()->utc(),
            'selected_sensors' => $selectedSensors,
        ];
    }

    private function getAnalyticsData(Request $request, bool $forExport = false): array
    {
        $filters = $this->resolveFilters($request);
        $client = $this->getDynamoClient();
        $marshaler = new Marshaler();
        $sensors = $this->sensorCatalog();
        $sensorNameMap = IotSensor::displayNameMap();

        $fromIso = $filters['from_utc']->toISOString();
        $toIso = $filters['to_utc']->toISOString();

        // Importante: el paginado visual de la tabla no reduce el costo/tiempo de la consulta a DynamoDB.
        // Para que la pantalla cargue rápido, el dashboard de informes trae una muestra operativa.
        // Las exportaciones permiten un límite mayor.
        $maxItemsPerSensor = $forExport
            ? (int) env('IOT_ANALYTICS_EXPORT_MAX_ITEMS_PER_SENSOR', 5000)
            : (int) env('IOT_ANALYTICS_MAX_ITEMS_PER_SENSOR', 500);

        $rows = [];
        $seriesBySensor = [];
        $errors = [];

        foreach ($filters['selected_sensors'] as $sensorId) {
            $seriesBySensor[$sensorId] = [
                'label' => $sensors[$sensorId]['camera_id'] ?? $sensorId,
                'points' => [],
            ];

            try {
                $result = $client->query([
                    'TableName' => env('DYNAMODB_IOT_TABLE'),
                    'KeyConditionExpression' => 'sensor_id = :sensor_id AND #ts BETWEEN :from AND :to',
                    'ExpressionAttributeNames' => [
                        '#ts' => 'timestamp',
                    ],
                    'ExpressionAttributeValues' => [
                        ':sensor_id' => ['S' => $sensorId],
                        ':from' => ['S' => $fromIso],
                        ':to' => ['S' => $toIso],
                    ],
                    'ScanIndexForward' => false,
                    'Limit' => $maxItemsPerSensor,
                ]);
            } catch (\Throwable $e) {
                $errors[] = "No se pudo consultar {$sensorId}: " . $e->getMessage();
                continue;
            }

            foreach ($result['Items'] ?? [] as $rawItem) {
                $item = $marshaler->unmarshalItem($rawItem);
                $row = IotReading::normalize($item, $sensors[$sensorId] ?? [], $sensorNameMap);
                $rows[] = $row;

                $seriesBySensor[$sensorId]['points'][] = [
                    'timestamp' => $row['timestamp'],
                    'label' => $row['time_label'],
                    'temperature' => $row['temperature'],
                    'humidity' => $row['humidity'],
                ];
            }
        }

        usort($rows, fn ($a, $b) => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));

        $rows = $this->attachOperationalEvents($rows, $filters['from_local'], $filters['to_local'], (int) env('IOT_OPERATION_MATCH_WINDOW_MINUTES', 10), $filters['selected_sensors'], $sensors);

        $chart = $this->buildChartPayload($seriesBySensor);
        $summary = $this->buildSummary($rows, $filters['selected_sensors']);
        $alarms = array_values(array_filter($rows, fn ($row) => $row['status_class'] !== 'success'));

        return [
            'sensors' => $sensors,
            'selectedSensors' => $filters['selected_sensors'],
            'range' => $filters['range'],
            'from' => $filters['from_local'],
            'to' => $filters['to_local'],
            'rows' => $rows,
            'alarms' => $alarms,
            'summary' => $summary,
            'chart' => $chart,
            'errors' => $errors,
        ];
    }


    private function attachOperationalEvents(
        array $rows,
        Carbon $fromLocal,
        Carbon $toLocal,
        int $windowMinutes = 10,
        array $selectedSensors = [],
        array $sensors = []
    ): array {
        $selectedSensorKeys = $this->operationalSensorKeys($selectedSensors, $sensors);

        if (empty($selectedSensorKeys)) {
            $selectedSensorKeys = collect($rows)
                ->flatMap(fn (array $row) => [$row['sensor_id'] ?? null, $row['camera_key'] ?? null])
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (empty($selectedSensorKeys)) {
            return array_map(fn (array $row) => $this->withEmptyOperationalMatch($row), $rows);
        }

        $fromMatch = $fromLocal->copy()->subMinutes($windowMinutes);
        $toMatch = $toLocal->copy()->addMinutes($windowMinutes);

        $events = CameraEvent::query()
            ->with('user')
            ->whereIn('sensor_key', $selectedSensorKeys)
            ->whereBetween('created_at', [$fromMatch, $toMatch])
            ->orderBy('created_at')
            ->get();

        if ($events->isEmpty()) {
            return array_map(fn (array $row) => $this->withEmptyOperationalMatch($row), $rows);
        }

        $eventsBySensor = $events->groupBy('sensor_key');
        $eventTypeLabels = CameraEvent::eventTypes();
        $matchedEventIds = [];

        $rows = array_map(function (array $row) use ($eventsBySensor, $eventTypeLabels, $windowMinutes, &$matchedEventIds) {
            $row = $this->withEmptyOperationalMatch($row);

            // El match operativo solo aplica a lecturas donde la puerta figura abierta.
            if (empty($row['door_open']) || empty($row['timestamp'])) {
                $row['operation_status'] = empty($row['door_open']) ? 'not_applicable' : 'without_record';
                $row['operation_status_label'] = empty($row['door_open']) ? 'No aplica' : 'Sin registro';
                return $row;
            }

            $cameraKey = $row['camera_key'] ?? null;
            $sensorId = $row['sensor_id'] ?? null;

            $candidateEvents = collect()
                ->merge($cameraKey && $eventsBySensor->has($cameraKey) ? $eventsBySensor->get($cameraKey) : collect())
                ->merge($sensorId && $eventsBySensor->has($sensorId) ? $eventsBySensor->get($sensorId) : collect())
                ->unique('id');

            if ($candidateEvents->isEmpty()) {
                return $row;
            }

            try {
                $readingTime = Carbon::parse($row['timestamp'])->timezone(config('app.timezone'));
            } catch (\Throwable $e) {
                return $row;
            }

            $bestEvent = null;
            $bestDiff = null;

            foreach ($candidateEvents as $event) {
                $eventTime = $event->created_at instanceof Carbon
                    ? $event->created_at->copy()->timezone(config('app.timezone'))
                    : Carbon::parse($event->created_at)->timezone(config('app.timezone'));

                $diff = abs($readingTime->diffInSeconds($eventTime, false));

                if ($diff <= ($windowMinutes * 60) && ($bestDiff === null || $diff < $bestDiff)) {
                    $bestEvent = $event;
                    $bestDiff = $diff;
                }
            }

            if (!$bestEvent) {
                return $row;
            }

            $matchedEventIds[] = $bestEvent->id;

            return $this->withOperationalMatch($row, $bestEvent, $eventTypeLabels, $bestDiff);
        }, $rows);

        // Agregamos los registros operativos que no tuvieron una lectura asociada.
        // Esto permite auditar aperturas manuales aunque el sensor no haya reportado en ese rango.
        foreach ($events as $event) {
            if (in_array($event->id, $matchedEventIds, true)) {
                continue;
            }

            $eventTime = $event->created_at instanceof Carbon
                ? $event->created_at->copy()->timezone(config('app.timezone'))
                : Carbon::parse($event->created_at)->timezone(config('app.timezone'));

            if ($eventTime->lt($fromLocal) || $eventTime->gt($toLocal)) {
                continue;
            }

            $rows[] = $this->operationOnlyRow($event, $eventTypeLabels, $sensors);
        }

        usort($rows, fn ($a, $b) => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));

        return $rows;
    }

    private function operationalSensorKeys(array $selectedSensors, array $sensors): array
    {
        return collect($selectedSensors)
            ->flatMap(function ($sensorId) use ($sensors) {
                $sensor = $sensors[$sensorId] ?? [];

                return [
                    $sensorId,
                    $sensor['sensor_id'] ?? null,
                    $sensor['camera_key'] ?? null,
                ];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function withOperationalMatch(array $row, CameraEvent $event, array $eventTypeLabels, ?int $diffSeconds = null): array
    {
        $user = $event->user;
        $userName = $user?->name ?? $user?->email ?? 'Usuario no identificado';

        $row['operation_status'] = 'with_record';
        $row['operation_status_label'] = 'Justificada';
        $row['operation_event_id'] = $event->id;
        $row['operation_event_type'] = $event->event_type;
        $row['operation_event_type_label'] = $eventTypeLabels[$event->event_type] ?? ucfirst(str_replace('_', ' ', $event->event_type));
        $row['operation_reason'] = $event->reason;
        $row['operation_notes'] = $event->notes;
        $row['operation_user'] = $userName;
        $row['operation_created_at'] = $event->created_at
            ? $event->created_at->copy()->timezone(config('app.timezone'))->format('d/m/Y H:i:s')
            : null;
        $row['operation_diff_minutes'] = $diffSeconds !== null ? round($diffSeconds / 60, 1) : null;
        $row['is_operation_only'] = false;

        return $row;
    }

    private function operationOnlyRow(CameraEvent $event, array $eventTypeLabels, array $sensors): array
    {
        $sensorInfo = $this->sensorInfoForOperationalEvent($event->sensor_key, $sensors);
        $eventTime = $event->created_at
            ? $event->created_at->copy()->timezone(config('app.timezone'))
            : now(config('app.timezone'));

        $user = $event->user;
        $userName = $user?->name ?? $user?->email ?? 'Usuario no identificado';

        return [
            'sensor_id' => $sensorInfo['sensor_id'],
            'camera_key' => $sensorInfo['camera_key'],
            'camera_id' => $sensorInfo['camera_id'],
            'timestamp' => $eventTime->toISOString(),
            'timestamp_label' => $eventTime->format('d/m/Y H:i:s'),
            'time_label' => $eventTime->format('H:i'),
            'temperature' => null,
            'humidity' => null,
            'door_open' => true,
            'battery' => null,
            'signal_rssi' => null,
            'signal_snr' => null,
            'source' => 'manual_operation',
            'status' => 'Sin lectura asociada',
            'status_class' => 'offline',
            'is_operation_only' => true,

            'operation_status' => 'with_record',
            'operation_status_label' => 'Registro sin lectura',
            'operation_event_id' => $event->id,
            'operation_event_type' => $event->event_type,
            'operation_event_type_label' => $eventTypeLabels[$event->event_type] ?? ucfirst(str_replace('_', ' ', $event->event_type)),
            'operation_reason' => $event->reason,
            'operation_notes' => $event->notes,
            'operation_user' => $userName,
            'operation_created_at' => $eventTime->format('d/m/Y H:i:s'),
            'operation_diff_minutes' => null,
        ];
    }

    private function sensorInfoForOperationalEvent(string $sensorKey, array $sensors): array
    {
        foreach ($sensors as $sensorId => $sensor) {
            if (($sensor['camera_key'] ?? null) === $sensorKey || ($sensor['sensor_id'] ?? null) === $sensorKey || $sensorId === $sensorKey) {
                return [
                    'sensor_id' => $sensor['sensor_id'] ?? $sensorId,
                    'camera_key' => $sensor['camera_key'] ?? $sensorKey,
                    'camera_id' => $sensor['camera_id'] ?? $sensorKey,
                ];
            }
        }

        return [
            'sensor_id' => $sensorKey,
            'camera_key' => $sensorKey,
            'camera_id' => IotSensor::displayNameFor($sensorKey),
        ];
    }

    private function withEmptyOperationalMatch(array $row): array
    {
        $row['operation_status'] = !empty($row['door_open']) ? 'without_record' : 'not_applicable';
        $row['operation_status_label'] = !empty($row['door_open']) ? 'Sin registro' : 'No aplica';
        $row['operation_event_id'] = null;
        $row['operation_event_type'] = null;
        $row['operation_event_type_label'] = null;
        $row['operation_reason'] = null;
        $row['operation_notes'] = null;
        $row['operation_user'] = null;
        $row['operation_created_at'] = null;
        $row['operation_diff_minutes'] = null;
        $row['is_operation_only'] = false;

        return $row;
    }


    private function buildChartPayload(array $seriesBySensor): array
    {
        $labels = [];

        foreach ($seriesBySensor as $series) {
            foreach ($series['points'] as $point) {
                $labels[$point['timestamp']] = $point['label'];
            }
        }

        ksort($labels);
        $labelKeys = array_keys($labels);

        $datasets = [];
        foreach ($seriesBySensor as $sensorId => $series) {
            $valuesByTimestamp = [];
            foreach ($series['points'] as $point) {
                $valuesByTimestamp[$point['timestamp']] = $point['temperature'];
            }

            $datasets[] = [
                'label' => $series['label'],
                'data' => array_map(fn ($timestamp) => $valuesByTimestamp[$timestamp] ?? null, $labelKeys),
                'borderWidth' => 2,
                'tension' => 0.35,
                'pointRadius' => 2,
                'spanGaps' => true,
            ];
        }

        return [
            'labels' => array_values($labels),
            'datasets' => $datasets,
        ];
    }

    private function buildSummary(array $rows, array $selectedSensors): array
    {
        $sensorRows = array_values(array_filter($rows, fn ($r) => empty($r['is_operation_only'])));

        $temperatures = array_values(array_filter(array_map(fn ($r) => $r['temperature'] ?? null, $sensorRows), fn ($v) => $v !== null));
        $humidity = array_values(array_filter(array_map(fn ($r) => $r['humidity'] ?? null, $sensorRows), fn ($v) => $v !== null));

        $avgTemp = count($temperatures) ? round(array_sum($temperatures) / count($temperatures), 1) : null;
        $maxTemp = count($temperatures) ? max($temperatures) : null;
        $minTemp = count($temperatures) ? min($temperatures) : null;
        $avgHumidity = count($humidity) ? round(array_sum($humidity) / count($humidity), 0) : null;

        $criticalCount = count(array_filter($sensorRows, fn ($r) => ($r['status_class'] ?? '') === 'danger'));
        $warningCount = count(array_filter($sensorRows, fn ($r) => ($r['status_class'] ?? '') === 'warning'));
        $doorOpenCount = count(array_filter($sensorRows, fn ($r) => !empty($r['door_open'])));

        return [
            'readings' => count($sensorRows),
            'sensors' => count($selectedSensors),
            'avg_temp' => $avgTemp,
            'max_temp' => $maxTemp,
            'min_temp' => $minTemp,
            'avg_humidity' => $avgHumidity,
            'critical_count' => $criticalCount,
            'warning_count' => $warningCount,
            'door_open_count' => $doorOpenCount,
            'operational' => $this->buildOperationalSummary($rows),
        ];
    }

    private function buildOperationalSummary(array $rows): array
    {
        $sensorDoorOpenRows = array_values(array_filter($rows, fn ($row) => empty($row['is_operation_only']) && !empty($row['door_open'])));

        $justifiedOpenings = count(array_filter($sensorDoorOpenRows, fn ($row) => ($row['operation_status'] ?? null) === 'with_record'));
        $openingsWithoutRecord = count(array_filter($sensorDoorOpenRows, fn ($row) => ($row['operation_status'] ?? null) === 'without_record'));
        $operationOnlyRows = array_values(array_filter($rows, fn ($row) => !empty($row['is_operation_only'])));

        $uniqueOperationalEvents = [];
        foreach ($rows as $row) {
            $eventId = $row['operation_event_id'] ?? null;
            if (!$eventId) {
                continue;
            }

            $uniqueOperationalEvents[(string) $eventId] = [
                'reason' => $row['operation_reason'] ?? null,
                'type' => $row['operation_event_type_label'] ?? null,
            ];
        }

        $reasonCounts = [];
        foreach ($uniqueOperationalEvents as $event) {
            $reason = trim((string) ($event['reason'] ?: $event['type'] ?: 'Sin motivo'));
            $reasonCounts[$reason] = ($reasonCounts[$reason] ?? 0) + 1;
        }

        arsort($reasonCounts);

        $mostFrequentReason = null;
        $mostFrequentReasonCount = 0;

        if (!empty($reasonCounts)) {
            $mostFrequentReason = array_key_first($reasonCounts);
            $mostFrequentReasonCount = $reasonCounts[$mostFrequentReason];
        }

        $detectedOpenings = count($sensorDoorOpenRows);

        return [
            'detected_openings' => $detectedOpenings,
            'registered_openings' => count($uniqueOperationalEvents),
            'justified_openings' => $justifiedOpenings,
            'without_record' => $openingsWithoutRecord,
            'records_without_reading' => count($operationOnlyRows),
            'most_frequent_reason' => $mostFrequentReason,
            'most_frequent_reason_count' => $mostFrequentReasonCount,
            'justification_rate' => $detectedOpenings > 0 ? round(($justifiedOpenings / $detectedOpenings) * 100) : null,
        ];
    }

    public function index(Request $request)
    {
        $data = $this->getAnalyticsData($request);

        $data['fromInput'] = $data['from']->format('Y-m-d\TH:i');
        $data['toInput'] = $data['to']->format('Y-m-d\TH:i');

        return view('iot.analytics', $data);
    }

    public function dataForScheduledReport(Request $request): array
    {
        return $this->getAnalyticsData($request, true);
    }

    public function exportCsv(Request $request)
    {
        $data = $this->getAnalyticsData($request, true);
        $filename = 'analisis_iot_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Fecha', 'Camara', 'Sensor', 'Cliente', 'Sitio', 'Temperatura',
                'Humedad', 'Puerta', 'Bateria', 'RSSI', 'SNR', 'Origen', 'Estado', 'Registro operativo', 'Motivo operativo', 'Usuario operativo', 'Observacion operativa',
            ], ';');

            foreach ($data['rows'] as $row) {
                fputcsv($file, [
                    $row['timestamp_label'],
                    $row['camera_id'],
                    $row['sensor_id'],
                    $row['client_id'],
                    $row['site_id'],
                    $row['temperature'],
                    $row['humidity'],
                    $row['door_open'] ? 'Abierta' : 'Cerrada',
                    $row['battery'],
                    $row['signal_rssi'],
                    $row['signal_snr'],
                    $row['source'],
                    $row['status'],
                    $row['operation_status_label'] ?? '—',
                    $row['operation_reason'] ?? '—',
                    $row['operation_user'] ?? '—',
                    $row['operation_notes'] ?? '—',
                ], ';');
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getAnalyticsData($request, true);
        $filename = 'analisis_iot_' . now()->format('Ymd_His') . '.xls';

        return response()
            ->view('iot.exports.analytics-excel', $data)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getAnalyticsData($request, true);

        $pdf = Pdf::loadView('iot.exports.analytics-pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('informe_iot_' . now()->format('Ymd_His') . '.pdf');
    }
}
