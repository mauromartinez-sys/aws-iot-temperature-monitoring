<?php

namespace App\Http\Controllers;

use App\Models\IotSensor;
use App\Models\IotAlarmEvent;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Carbon\Carbon;

class IotDashboardController extends Controller
{


    

    private function appTimezone(): string
    {
        return config('app.timezone', 'America/Argentina/Buenos_Aires');
    }

    private function formatLocalTime(?string $timestamp, string $format = 'H:i'): string
    {
        if (!$timestamp) {
            return '';
        }

        try {
            return Carbon::parse($timestamp)->timezone($this->appTimezone())->format($format);
        } catch (\Throwable $e) {
            return '';
        }
    }

private function formatLastActivity(?string $timestamp): array
    {
        if (!$timestamp) {
            return [
                'minutes' => null,
                'label' => '—',
                'datetime_label' => '—',
            ];
        }

        try {
            $appTimezone = $this->appTimezone();
            $lastSeen = Carbon::parse($timestamp)->timezone($appTimezone);
            $now = now($appTimezone);
            $secondsAgo = max(0, $lastSeen->diffInSeconds($now));

            if ($secondsAgo < 60) {
                $label = '1 min';
            } elseif ($secondsAgo < 3600) {
                $minutes = max(1, (int) floor($secondsAgo / 60));
                $label = $minutes . ' min';
            } elseif ($secondsAgo < 86400) {
                $hours = max(1, (int) floor($secondsAgo / 3600));
                $label = $hours . ' h';
            } else {
                $days = max(1, (int) floor($secondsAgo / 86400));
                $label = $days . ' ' . ($days === 1 ? 'día' : 'días');
            }

            return [
                'minutes' => round($secondsAgo / 60, 2),
                'label' => $label,
                'datetime_label' => $lastSeen->format('d/m/Y H:i:s'),
            ];
        } catch (\Throwable $e) {
            return [
                'minutes' => null,
                'label' => '—',
                'datetime_label' => '—',
            ];
        }
    }



    private function getSensorData()
    {
        $client = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);

        $marshaler = new Marshaler();

        // Fuente única de verdad: los sensores visibles del dashboard salen del panel de Configuración.
        // Si un sensor se desactiva, cambia de orden o se agrega uno real, el dashboard lo refleja.
        $sensorNameMap = IotSensor::displayNameMap(true);
        $sensorSettings = IotSensor::settingsMap(true);

        $sensors = IotSensor::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('sensor_key')
            ->get()
            ->map(function (IotSensor $sensor) {
                $description = $sensor->description
                    ?: ($sensor->camera_type ?: 'Cámara frigorífica monitoreada');

                return [
                    'sensor_id' => $sensor->sensor_key,
                    'camera_key' => $sensor->sensor_key,
                    'name' => $sensor->display_name ?: $sensor->sensor_key,
                    'display_name' => $sensor->display_name ?: $sensor->sensor_key,
                    'location' => $sensor->location ?: 'Sin ubicación asignada',
                    'description' => $description,
                    'camera_type' => $sensor->camera_type,
                    'alarm_min' => $sensor->alarm_min !== null ? (float) $sensor->alarm_min : null,
                    'alarm_max' => $sensor->alarm_max !== null ? (float) $sensor->alarm_max : null,
                    'display_order' => (int) $sensor->display_order,
                ];
            })
            ->values()
            ->all();

        $readings = [];
        $chartData = [];
        $alerts = [];

        foreach ($sensors as $sensor) {
            $sensorId = $sensor['sensor_id'];

            try {
                $result = $client->query([
                    'TableName' => env('DYNAMODB_IOT_TABLE'),
                    'KeyConditionExpression' => 'sensor_id = :sensor_id',
                    'ExpressionAttributeValues' => [
                        ':sensor_id' => ['S' => $sensorId],
                    ],
                    'ScanIndexForward' => false,
                    'Limit' => 60,
                ]);
            } catch (\Exception $e) {
                $result = ['Items' => []];
            }

            $items = [];

            foreach ($result['Items'] ?? [] as $rawItem) {
                $items[] = $marshaler->unmarshalItem($rawItem);
            }

            if (empty($items)) {
                $readings[] = [
                    'sensor_id' => $sensorId,
                    'camera_key' => $sensor['camera_key'],
                    'camera_id' => $sensor['name'],
                    'display_name' => $sensor['display_name'] ?? $sensor['name'],
                    'location' => $sensor['location'],
                    'temperature' => null,
                    'humidity' => null,
                    'battery' => null,
                    'door_open' => false,
                    'signal_rssi' => null,
                    'signal_snr' => null,
                    'timestamp' => null,
                    'status' => 'SIN DATOS',
                    'status_class' => 'offline',
                    'status_icon' => '●',
                    'status_message' => 'Sin lecturas recientes',
                    'minutes_ago' => null,
                    'minutes_ago_label' => '—',
                    'last_seen_at_label' => '—',
                ];

                $chartData[$sensorId] = [
                    'name' => $sensor['name'],
                    'labels' => [],
                    'temperatures' => [],
                    'humidity' => [],
                ];

                continue;
            }

            $latest = $items[0];

            $temperature = isset($latest['temperature']) ? (float) $latest['temperature'] : null;
            $humidity = isset($latest['humidity']) ? (int) $latest['humidity'] : null;
            $battery = isset($latest['battery']) ? (int) $latest['battery'] : null;
            $doorOpen = !empty($latest['door_open']);
            $lastSeen = $latest['timestamp'] ?? null;

            $cameraKey = $sensor['camera_key'];
            $latest['sensor_id'] = $sensorId;
            $latest['camera_key'] = $cameraKey;
            $latest['camera_id'] = $sensor['name'];
            $latest['display_name'] = $sensor['display_name'] ?? $sensor['name'];
            $latest['location'] = $sensor['location'];
            $latest['temperature'] = $temperature;
            $latest['humidity'] = $humidity;
            $latest['battery'] = $battery;
            $latest['door_open'] = $doorOpen;
            $latest['signal_rssi'] = $latest['signal_rssi'] ?? null;
            $latest['signal_snr'] = $latest['signal_snr'] ?? null;

            $latest['status'] = 'NORMAL';
            $latest['status_class'] = 'success';
            $latest['status_icon'] = '●';
            $latest['status_message'] = 'Operando dentro del rango esperado';

            $alarmMin = $sensor['alarm_min'] ?? null;
            $alarmMax = $sensor['alarm_max'] ?? null;

            if ($temperature !== null && $alarmMax !== null && $temperature >= (float) $alarmMax) {
                $latest['status'] = 'CRÍTICO';
                $latest['status_class'] = 'danger';
                $latest['status_icon'] = '●';
                $latest['status_message'] = 'Temperatura crítica detectada';
            } elseif ($temperature !== null && $alarmMin !== null && $temperature <= (float) $alarmMin) {
                $latest['status'] = 'ALERTA BAJA';
                $latest['status_class'] = 'warning';
                $latest['status_icon'] = '●';
                $latest['status_message'] = 'Temperatura por debajo del rango recomendado';
            } elseif ($temperature !== null && $alarmMax === null && $temperature >= 12) {
                $latest['status'] = 'CRÍTICO';
                $latest['status_class'] = 'danger';
                $latest['status_icon'] = '●';
                $latest['status_message'] = 'Temperatura crítica detectada';
            } elseif ($temperature !== null && $alarmMax === null && $temperature >= 8) {
                $latest['status'] = 'ALERTA';
                $latest['status_class'] = 'warning';
                $latest['status_icon'] = '●';
                $latest['status_message'] = 'Temperatura por encima del rango recomendado';
            }

            if ($doorOpen) {
                $latest['status'] = 'PUERTA ABIERTA';
                $latest['status_class'] = 'warning';
                $latest['status_icon'] = '●';
                $latest['status_message'] = 'Apertura de puerta detectada';
            }

            if ($battery !== null && $battery < 20) {
                $latest['status'] = 'BATERÍA BAJA';
                $latest['status_class'] = 'warning';
                $latest['status_icon'] = '●';
                $latest['status_message'] = 'Nivel de batería bajo';
            }

            if ($lastSeen) {
                $lastActivity = $this->formatLastActivity($lastSeen);
                $minutesAgo = $lastActivity['minutes'];
                $latest['minutes_ago'] = $minutesAgo;
                $latest['minutes_ago_label'] = $lastActivity['label'];
                $latest['last_seen_at_label'] = $lastActivity['datetime_label'];

                if ($minutesAgo !== null && $minutesAgo > 30) {
                    $latest['status'] = 'SIN SEÑAL';
                    $latest['status_class'] = 'offline';
                    $latest['status_icon'] = '●';
                    $latest['status_message'] = 'El sensor no reporta hace más de 30 minutos';
                }
            } else {
                $latest['minutes_ago'] = null;
                $latest['minutes_ago_label'] = '—';
                $latest['last_seen_at_label'] = '—';
            }

            if ($latest['status_class'] !== 'success') {
                $alerts[] = [
                    'camera_id' => $latest['camera_id'],
                    'sensor_id' => $latest['sensor_id'],
                    'type' => $latest['status'],
                    'message' => $latest['status_message'],
                    'temperature' => $latest['temperature'],
                    'timestamp' => $this->formatLocalTime($latest['timestamp'] ?? null, 'd/m H:i:s'),
                ];
            }

            $readings[] = $latest;

            $history = array_reverse($items);

            $chartData[$sensorId] = [
                'name' => $latest['camera_id'],
                'labels' => array_map(function ($i) {
                    return $this->formatLocalTime($i['timestamp'] ?? null, 'H:i');
                }, $history),
                'temperatures' => array_map(function ($i) {
                    return isset($i['temperature']) ? (float) $i['temperature'] : null;
                }, $history),
                'humidity' => array_map(function ($i) {
                    return isset($i['humidity']) ? (int) $i['humidity'] : null;
                }, $history),
            ];
        }

        $validTemperatures = array_values(array_filter(array_map(function ($reading) {
            return $reading['temperature'];
        }, $readings), fn($value) => $value !== null));

        $avgTemp = count($validTemperatures)
            ? round(array_sum($validTemperatures) / count($validTemperatures), 1)
            : null;

        $maxTemp = count($validTemperatures)
            ? round(max($validTemperatures), 1)
            : null;

        $onlineSensors = collect($readings)
            ->filter(fn($reading) => $reading['status_class'] !== 'offline')
            ->count();

        $openDoors = collect($readings)
            ->filter(fn($reading) => !empty($reading['door_open']))
            ->count();

        $lowestBattery = collect($readings)
            ->pluck('battery')
            ->filter(fn($value) => $value !== null)
            ->min();

        $activeAlarmEvents = array_map(
            fn (array $event) => $this->formatDashboardAlarmEvent($event),
            $this->activeAlarmEvents(12)
        );

        $criticalAlarmCount = collect($activeAlarmEvents)
            ->filter(fn ($event) => ($event['severity'] ?? 'warning') === 'critical')
            ->count();

        $acknowledgedAlarmCount = collect($activeAlarmEvents)
            ->filter(fn ($event) => ($event['status'] ?? 'open') === 'acknowledged')
            ->count();

        return [
            'readings' => $readings,
            'chartData' => $chartData,
            'alerts' => $alerts,
            'activeAlarmEvents' => $activeAlarmEvents,
            'sensorNameMap' => $sensorNameMap,
            'summary' => [
                'avg_temp' => $avgTemp,
                'max_temp' => $maxTemp,
                'active_alerts' => count($activeAlarmEvents),
                'critical_alarms' => $criticalAlarmCount,
                'acknowledged_alarms' => $acknowledgedAlarmCount,
                'online_sensors' => $onlineSensors,
                'total_sensors' => count($sensors),
                'open_doors' => $openDoors,
                'lowest_battery' => $lowestBattery,
            ],
        ];
    }

    private function activeAlarmEvents(int $limit = 12): array
    {
        try {
            if (method_exists(IotAlarmEvent::class, 'active')) {
                return IotAlarmEvent::active($limit);
            }

            if (!method_exists(IotAlarmEvent::class, 'all')) {
                return [];
            }

            $items = array_values(array_filter(IotAlarmEvent::all(), function (array $event) {
                return in_array((string) ($event['status'] ?? 'open'), ['open', 'acknowledged', 'silenced'], true);
            }));

            usort($items, function ($a, $b) {
                $severityOrder = ['critical' => 3, 'warning' => 2, 'info' => 1];
                $aSeverity = $severityOrder[$a['severity'] ?? 'warning'] ?? 2;
                $bSeverity = $severityOrder[$b['severity'] ?? 'warning'] ?? 2;

                if ($aSeverity !== $bSeverity) {
                    return $bSeverity <=> $aSeverity;
                }

                $dateA = (string) ($a['last_detected_at'] ?? $a['started_at'] ?? $a['created_at'] ?? '');
                $dateB = (string) ($b['last_detected_at'] ?? $b['started_at'] ?? $b['created_at'] ?? '');

                return strcmp($dateB, $dateA);
            });

            return array_slice($items, 0, $limit);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function formatDashboardAlarmEvent(array $event): array
    {
        $status = (string) ($event['status'] ?? 'open');
        $severity = (string) ($event['severity'] ?? 'warning');
        $alarmType = (string) ($event['alarm_type'] ?? '');
        $breachType = (string) ($event['breach_type'] ?? '');

        $typeLabels = [
            'temperature_range' => 'Temperatura fuera de rango',
            'temperature_high' => 'Temperatura alta',
            'temperature_low' => 'Temperatura baja',
            'door_open_too_long' => 'Puerta abierta',
            'battery_low' => 'Batería baja',
            'rssi_low' => 'RSSI débil',
            'sensor_offline' => 'Sensor sin señal',
        ];

        $breachLabels = [
            'above_max' => 'Por encima del máximo',
            'below_min' => 'Por debajo del mínimo',
            'threshold_breach' => 'Umbral superado',
            'normal' => 'Normalizado',
            'resolved' => 'Normalizado',
        ];

        $statusLabels = [
            'open' => 'Activa',
            'acknowledged' => 'Reconocida',
            'silenced' => 'Silenciada',
            'resolved' => 'Resuelta',
        ];

        $severityLabels = [
            'critical' => 'Crítica',
            'warning' => 'Advertencia',
            'info' => 'Informativa',
        ];

        $detectedValue = $event['detected_value'] ?? null;
        $detectedLabel = $this->formatAlarmDetectedValue($detectedValue, $alarmType);
        $expectedLabel = $this->formatAlarmExpectedValue($event, $alarmType);

        return [
            'alarm_id' => $event['alarm_id'] ?? '',
            'camera' => $event['camera_name_snapshot'] ?? $event['camera_id'] ?? $event['sensor_key'] ?? $event['sensor_id'] ?? 'Cámara',
            'name' => $event['alarm_name'] ?? ($typeLabels[$alarmType] ?? 'Alarma'),
            'type_label' => $typeLabels[$alarmType] ?? ucfirst(str_replace('_', ' ', $alarmType ?: 'Alarma')),
            'breach_label' => $breachLabels[$breachType] ?? null,
            'detected_label' => $detectedLabel,
            'expected_label' => $expectedLabel,
            'status' => $status,
            'status_label' => $statusLabels[$status] ?? ucfirst($status),
            'status_class' => $status === 'open' ? 'danger' : ($status === 'acknowledged' ? 'warning' : 'offline'),
            'severity' => $severity,
            'severity_label' => $severityLabels[$severity] ?? ucfirst($severity),
            'severity_class' => $severity === 'critical' ? 'danger' : ($severity === 'info' ? 'success' : 'warning'),
            'door' => ($event['door_state'] ?? '') === 'open' ? 'Abierta' : ((($event['door_state'] ?? '') === 'closed') ? 'Cerrada' : '—'),
            'last_detected_at' => $this->formatAlarmDate($event['last_detected_at'] ?? $event['started_at'] ?? null),
            'summary' => trim(($breachLabels[$breachType] ?? $typeLabels[$alarmType] ?? 'Alarma') . ' · Detectado: ' . $detectedLabel . ' · Esperado: ' . $expectedLabel),
        ];
    }

    private function formatAlarmDetectedValue($value, string $alarmType): string
    {
        if ($value === null || $value === '') {
            return 'Sin dato';
        }

        $number = is_numeric($value) ? (float) $value : null;

        if ($number === null) {
            return (string) $value;
        }

        return match ($alarmType) {
            'battery_low' => number_format($number, 0) . '%',
            'rssi_low' => number_format($number, 0) . ' dBm',
            'door_open_too_long' => $number >= 1 ? 'Abierta' : 'Cerrada',
            'sensor_offline' => number_format($number, 0) . ' min',
            default => number_format($number, 1) . '°C',
        };
    }

    private function formatAlarmExpectedValue(array $event, string $alarmType): string
    {
        if ($alarmType === 'temperature_range' && isset($event['min_value'], $event['max_value'])) {
            return number_format((float) $event['min_value'], 1) . '°C - ' . number_format((float) $event['max_value'], 1) . '°C';
        }

        if (isset($event['threshold_value']) && $event['threshold_value'] !== '') {
            return trim(($event['condition_operator'] ?? '') . ' ' . $this->formatAlarmDetectedValue($event['threshold_value'], $alarmType));
        }

        return 'Sin umbral';
    }

    private function formatAlarmDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function index()
    {
        return view('iot.dashboard', $this->getSensorData());
    }

    public function analytics()
    {
        return view('iot.analytics', $this->getSensorData());
    }

    public function alerts()
    {
        return view('iot.alerts', $this->getSensorData());
    }

    public function settings()
    {
        return view('iot.settings');
    }
}