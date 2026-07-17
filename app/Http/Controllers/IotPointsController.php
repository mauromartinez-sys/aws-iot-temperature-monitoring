<?php

namespace App\Http\Controllers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Carbon\Carbon;
use App\Models\IotSensor;
use App\Models\CameraEvent;
use App\Models\IotAlarmEvent;

class IotPointsController extends Controller
{


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
            $appTimezone = config('app.timezone', 'America/Argentina/Buenos_Aires');
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



    private function getSensors(): array
    {
        // Fuente única de verdad: puntos de medición visibles desde Configuración.
        // El panel ya no usa sensor_demo_001/002/003 fijos.
        return IotSensor::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('sensor_key')
            ->get()
            ->map(function (IotSensor $sensor) {
                $description = $sensor->description
                    ?: ($sensor->camera_type ?: 'Cámara frigorífica monitoreada');

                return [
                    'sensor_id' => $sensor->sensor_key,
                    'fallback_key' => $sensor->sensor_key,
                    'settings_key' => $sensor->sensor_key,
                    'camera_id' => $sensor->display_name ?: $sensor->sensor_key,
                    'display_name' => $sensor->display_name ?: $sensor->sensor_key,
                    'location' => $sensor->location ?: 'Sin ubicación asignada',
                    'description' => $description,
                    'camera_type' => $sensor->camera_type,
                    'alarm_min' => $sensor->alarm_min !== null ? (float) $sensor->alarm_min : null,
                    'alarm_max' => $sensor->alarm_max !== null ? (float) $sensor->alarm_max : null,
                    'is_active' => (bool) $sensor->is_active,
                    'display_order' => (int) $sensor->display_order,
                ];
            })
            ->values()
            ->all();
    }

    private function getOperationalEvents(string $sensorId, ?string $settingsKey = null, ?string $fallbackKey = null): array
    {
        try {
            $keys = array_values(array_unique(array_filter([$sensorId, $settingsKey, $fallbackKey])));
            $typeLabels = CameraEvent::eventTypes();

            return CameraEvent::query()
                ->with('user')
                ->whereIn('sensor_key', $keys)
                ->latest()
                ->limit(8)
                ->get()
                ->map(function (CameraEvent $event) use ($typeLabels) {
                    return [
                        'type' => $typeLabels[$event->event_type] ?? ucfirst(str_replace('_', ' ', $event->event_type)),
                        'reason' => $event->reason,
                        'notes' => $event->notes,
                        'user' => optional($event->user)->name ?: 'Usuario no identificado',
                        'timestamp' => optional($event->created_at)->format('d/m H:i'),
                    ];
                })
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getAlarmEvents(string $sensorId, ?string $settingsKey = null, ?string $fallbackKey = null): array
    {
        try {
            $keys = array_values(array_unique(array_filter([$sensorId, $settingsKey, $fallbackKey])));

            return array_map(function (array $event) {
                return $this->formatAlarmEvent($event);
            }, IotAlarmEvent::forSensorKeys($keys, 10));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function formatAlarmEvent(array $event): array
    {
        $status = (string) ($event['status'] ?? 'open');
        $severity = (string) ($event['severity'] ?? 'warning');
        $alarmType = (string) ($event['alarm_type'] ?? '');
        $breachType = (string) ($event['breach_type'] ?? '');

        $statusLabels = [
            'open' => 'Activa',
            'acknowledged' => 'Reconocida',
            'silenced' => 'Silenciada',
            'resolved' => 'Resuelta',
        ];

        $statusClasses = [
            'open' => 'bad',
            'acknowledged' => 'warn',
            'silenced' => 'pending',
            'resolved' => 'good',
        ];

        $severityLabels = [
            'info' => 'Informativa',
            'warning' => 'Advertencia',
            'critical' => 'Crítica',
        ];

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
            'within_range' => 'Dentro del rango',
            'threshold_breach' => 'Umbral superado',
            'normal' => 'Normalizado',
        ];

        $detectedValue = $event['detected_value'] ?? null;
        $detectedLabel = $detectedValue !== null && $detectedValue !== ''
            ? $this->formatAlarmValue((float) $detectedValue, $alarmType)
            : 'Sin dato';

        $expectedLabel = $this->formatExpectedRange($event, $alarmType);
        $startedAt = $this->formatAlarmDate($event['started_at'] ?? $event['created_at'] ?? null);
        $resolvedAt = $this->formatAlarmDate($event['resolved_at'] ?? null);
        $lastDetectedAt = $this->formatAlarmDate($event['last_detected_at'] ?? null);

        return [
            'alarm_id' => $event['alarm_id'] ?? '',
            'rule_id' => $event['rule_id'] ?? '',
            'alarm_name' => $event['alarm_name'] ?? ($typeLabels[$alarmType] ?? 'Alarma'),
            'alarm_type' => $alarmType,
            'alarm_type_label' => $typeLabels[$alarmType] ?? ucfirst(str_replace('_', ' ', $alarmType ?: 'Alarma')),
            'breach_type' => $breachType,
            'breach_label' => $breachLabels[$breachType] ?? null,
            'detected_value' => $detectedValue,
            'detected_label' => $detectedLabel,
            'expected_label' => $expectedLabel,
            'door_state' => $event['door_state'] ?? '—',
            'severity' => $severity,
            'severity_label' => $severityLabels[$severity] ?? ucfirst($severity),
            'severity_class' => $severity === 'critical' ? 'bad' : ($severity === 'info' ? 'good' : 'warn'),
            'status' => $status,
            'status_label' => $statusLabels[$status] ?? ucfirst($status),
            'status_class' => $statusClasses[$status] ?? 'pending',
            'started_at' => $startedAt,
            'resolved_at' => $resolvedAt,
            'last_detected_at' => $lastDetectedAt,
            'acknowledged_by' => $event['acknowledged_by'] ?? null,
            'acknowledgement_note' => $event['acknowledgement_note'] ?? null,
            'silenced_by' => $event['silenced_by'] ?? null,
            'silence_note' => $event['silence_note'] ?? null,
            'last_comment' => $event['last_comment'] ?? null,
            'last_comment_by' => $event['last_comment_by'] ?? null,
            'last_comment_at' => $this->formatAlarmDate($event['last_comment_at'] ?? null),
            'summary' => $this->buildAlarmSummary($event, $typeLabels[$alarmType] ?? 'Alarma', $breachLabels[$breachType] ?? null, $detectedLabel, $expectedLabel),
        ];
    }

    private function formatAlarmDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m H:i:s');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function formatAlarmValue(float $value, string $alarmType): string
    {
        return match ($alarmType) {
            'battery_low' => number_format($value, 0) . '%',
            'rssi_low' => number_format($value, 0) . ' dBm',
            'door_open_too_long' => $value >= 1 ? 'Abierta' : 'Cerrada',
            'sensor_offline' => number_format($value, 0) . ' min',
            default => number_format($value, 1) . '°C',
        };
    }

    private function formatExpectedRange(array $event, string $alarmType): string
    {
        if ($alarmType === 'temperature_range') {
            $min = $event['min_value'] ?? null;
            $max = $event['max_value'] ?? null;

            if ($min !== null && $max !== null) {
                return number_format((float) $min, 1) . '°C - ' . number_format((float) $max, 1) . '°C';
            }
        }

        $threshold = $event['threshold_value'] ?? null;
        $operator = $event['condition_operator'] ?? null;

        if ($threshold === null || $threshold === '') {
            return 'Sin umbral';
        }

        $label = $this->formatAlarmValue((float) $threshold, $alarmType);

        return trim(($operator ? $operator . ' ' : '') . $label);
    }

    private function buildAlarmSummary(array $event, string $typeLabel, ?string $breachLabel, string $detectedLabel, string $expectedLabel): string
    {
        $parts = [$typeLabel];

        if ($breachLabel) {
            $parts[] = $breachLabel;
        }

        $parts[] = 'Detectado: ' . $detectedLabel;

        if ($expectedLabel !== 'Sin umbral') {
            $parts[] = 'Esperado: ' . $expectedLabel;
        }

        return implode(' · ', $parts);
    }

    private function getDynamoClient(): DynamoDbClient
    {
        return new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);
    }

    private function getReadingStatus(array $latest): array
    {
        $temperature = $latest['temperature'] ?? null;
        $battery = $latest['battery'] ?? null;
        $doorOpen = !empty($latest['door_open']);
        $minutesAgo = $latest['minutes_ago'] ?? null;

        $status = [
            'label' => 'NORMAL',
            'class' => 'success',
            'message' => 'Operación dentro de parámetros normales',
        ];

        if ($minutesAgo !== null && $minutesAgo > 30) {
            return [
                'label' => 'SIN SEÑAL',
                'class' => 'offline',
                'message' => 'El sensor no reporta hace más de 30 minutos',
            ];
        }

        if ($temperature !== null && $temperature >= 12) {
            $status = [
                'label' => 'CRÍTICO',
                'class' => 'danger',
                'message' => 'Temperatura crítica detectada',
            ];
        } elseif ($temperature !== null && $temperature >= 8) {
            $status = [
                'label' => 'ALERTA',
                'class' => 'warning',
                'message' => 'Temperatura por encima del rango recomendado',
            ];
        }

        if ($doorOpen) {
            $status = [
                'label' => 'PUERTA ABIERTA',
                'class' => 'warning',
                'message' => 'Apertura de puerta detectada',
            ];
        }

        if ($battery !== null && $battery < 20) {
            $status = [
                'label' => 'BATERÍA BAJA',
                'class' => 'warning',
                'message' => 'Nivel de batería bajo',
            ];
        }

        return $status;
    }

    private function getHealthProfile(array $latest): array
    {
        $score = 100;

        $temperature = $latest['temperature'] ?? null;
        $humidity = $latest['humidity'] ?? null;
        $battery = $latest['battery'] ?? null;
        $doorOpen = !empty($latest['door_open']);
        $rssi = $latest['signal_rssi'] ?? null;
        $minutesAgo = $latest['minutes_ago'] ?? null;

        $factors = [];

        // Temperatura
        $tempStatus = 'good';
        $tempText = $temperature !== null ? number_format($temperature, 1) . '°C' : 'Sin dato';

        if ($temperature !== null && $temperature >= 12) {
            $score -= 30;
            $tempStatus = 'bad';
        } elseif ($temperature !== null && $temperature >= 8) {
            $score -= 18;
            $tempStatus = 'warn';
        }

        $factors[] = [
            'label' => 'Temperatura',
            'value' => $tempText,
            'status' => $tempStatus,
        ];

        // Humedad
        $humidityStatus = 'good';
        $humidityText = $humidity !== null ? $humidity . '%' : 'Sin dato';

        if ($humidity !== null && ($humidity > 85 || $humidity < 45)) {
            $score -= 8;
            $humidityStatus = 'warn';
        }

        $factors[] = [
            'label' => 'Humedad',
            'value' => $humidityText,
            'status' => $humidityStatus,
        ];

        // Puerta
        $doorStatus = $doorOpen ? 'warn' : 'good';
        if ($doorOpen) {
            $score -= 10;
        }

        $factors[] = [
            'label' => 'Puerta',
            'value' => $doorOpen ? 'Abierta' : 'Cerrada',
            'status' => $doorStatus,
        ];

        // Batería
        $batteryStatus = 'good';
        $batteryText = $battery !== null ? $battery . '%' : 'Sin dato';

        if ($battery !== null && $battery < 30) {
            $score -= 15;
            $batteryStatus = 'bad';
        } elseif ($battery !== null && $battery < 50) {
            $score -= 8;
            $batteryStatus = 'warn';
        }

        $factors[] = [
            'label' => 'Batería',
            'value' => $batteryText,
            'status' => $batteryStatus,
        ];

        // Señal
        $signalStatus = 'good';
        $signalText = $rssi !== null ? $rssi . ' dBm' : 'Sin dato';

        if ($rssi !== null && $rssi <= -110) {
            $score -= 10;
            $signalStatus = 'bad';
        } elseif ($rssi !== null && $rssi <= -100) {
            $score -= 5;
            $signalStatus = 'warn';
        }

        $factors[] = [
            'label' => 'Señal',
            'value' => $signalText,
            'status' => $signalStatus,
        ];

        // Última comunicación
        $commStatus = 'good';
        $commText = $latest['minutes_ago_label'] ?? ($minutesAgo !== null ? $minutesAgo . ' min' : 'Sin dato');

        if ($minutesAgo !== null && $minutesAgo > 30) {
            $score -= 30;
            $commStatus = 'bad';
        } elseif ($minutesAgo !== null && $minutesAgo > 10) {
            $score -= 10;
            $commStatus = 'warn';
        }

        $factors[] = [
            'label' => 'Última señal',
            'value' => $commText,
            'status' => $commStatus,
        ];

        $score = max(0, min(100, $score));

        $label = 'Excelente';
        $class = 'success';

        if ($score < 50) {
            $label = 'Crítica';
            $class = 'danger';
        } elseif ($score < 70) {
            $label = 'En riesgo';
            $class = 'warning';
        } elseif ($score < 85) {
            $label = 'Atención';
            $class = 'warning';
        }

        return [
            'score' => $score,
            'label' => $label,
            'class' => $class,
            'factors' => $factors,
        ];
    }

    public function index()
    {
        $client = $this->getDynamoClient();
        $marshaler = new Marshaler();

        $points = [];
        $alertsCount = 0;

        foreach ($this->getSensors() as $sensor) {
            try {
                $result = $client->query([
                    'TableName' => env('DYNAMODB_IOT_TABLE'),
                    'KeyConditionExpression' => 'sensor_id = :sensor_id',
                    'ExpressionAttributeValues' => [
                        ':sensor_id' => ['S' => $sensor['sensor_id']],
                    ],
                    'ScanIndexForward' => false,
                    'Limit' => 40,
                ]);
            } catch (\Exception $e) {
                $result = ['Items' => []];
            }

            $items = [];
            foreach ($result['Items'] ?? [] as $item) {
                $items[] = $marshaler->unmarshalItem($item);
            }

            if (empty($items)) {
                $points[] = [
                    'sensor_id' => $sensor['sensor_id'],
                    'camera_id' => $sensor['camera_id'],
                    'display_name' => $sensor['display_name'] ?? $sensor['camera_id'],
                    'location' => $sensor['location'],
                    'description' => $sensor['description'],
                    'temperature' => null,
                    'humidity' => null,
                    'battery' => null,
                    'door_open' => false,
                    'signal_rssi' => null,
                    'signal_snr' => null,
                    'timestamp' => null,
                    'minutes_ago' => null,
                    'minutes_ago_label' => '—',
                    'last_seen_at_label' => '—',
                    'status_label' => 'SIN DATOS',
                    'status_class' => 'offline',
                    'status_message' => 'No se encontraron lecturas para este punto',
                    'health_score' => 0,
                    'health_label' => 'Sin información',
                    'health_class' => 'offline',
                    'health_factors' => [],
                    'history' => [
                        'labels' => [],
                        'temperatures' => [],
                        'humidity' => [],
                    ],
                    'history_rows' => [],
                    'modules' => [
                        ['name' => 'Temperatura', 'value' => '—', 'state' => 'active'],
                        ['name' => 'Puerta', 'value' => '—', 'state' => 'active'],
                        ['name' => 'Batería', 'value' => '—', 'state' => 'active'],
                        ['name' => 'Señal', 'value' => '—', 'state' => 'active'],
                        ['name' => 'Compresor', 'value' => 'Próximamente', 'state' => 'pending'],
                        ['name' => 'Vibración', 'value' => 'Próximamente', 'state' => 'pending'],
                        ['name' => 'Consumo eléctrico', 'value' => 'Próximamente', 'state' => 'pending'],
                        ['name' => 'Presión / aceite', 'value' => 'Próximamente', 'state' => 'pending'],
                    ],
                    'operational_events' => $this->getOperationalEvents($sensor['sensor_id'], $sensor['settings_key'] ?? null, $sensor['fallback_key'] ?? null),
                    'alarm_events' => $this->getAlarmEvents($sensor['sensor_id'], $sensor['settings_key'] ?? null, $sensor['fallback_key'] ?? null),
                ];
                continue;
            }

            $latest = $items[0];

            $temperature = isset($latest['temperature']) ? (float) $latest['temperature'] : null;
            $humidity = isset($latest['humidity']) ? (int) $latest['humidity'] : null;
            $battery = isset($latest['battery']) ? (int) $latest['battery'] : null;
            $doorOpen = !empty($latest['door_open']);
            $rssi = isset($latest['signal_rssi']) ? (int) $latest['signal_rssi'] : null;
            $snr = isset($latest['signal_snr']) ? (float) $latest['signal_snr'] : null;
            $timestamp = $latest['timestamp'] ?? null;

            $lastActivity = $this->formatLastActivity($timestamp);
            $minutesAgo = $lastActivity['minutes'];
            $minutesAgoLabel = $lastActivity['label'];
            $lastSeenAtLabel = $lastActivity['datetime_label'];

            $latestNormalized = [
                'temperature' => $temperature,
                'humidity' => $humidity,
                'battery' => $battery,
                'door_open' => $doorOpen,
                'signal_rssi' => $rssi,
                'signal_snr' => $snr,
                'timestamp' => $timestamp,
                'minutes_ago' => $minutesAgo,
                'minutes_ago_label' => $minutesAgoLabel,
                'last_seen_at_label' => $lastSeenAtLabel,
            ];

            $status = $this->getReadingStatus($latestNormalized);
            $health = $this->getHealthProfile($latestNormalized);

            if ($status['class'] !== 'success') {
                $alertsCount++;
            }

            $historyAsc = array_reverse($items);

            $historyLabels = [];
            $historyTemperatures = [];
            $historyHumidity = [];

            foreach ($historyAsc as $entry) {
                $historyLabels[] = isset($entry['timestamp'])
                    ? Carbon::parse($entry['timestamp'])->format('H:i')
                    : '';
                $historyTemperatures[] = isset($entry['temperature']) ? (float) $entry['temperature'] : null;
                $historyHumidity[] = isset($entry['humidity']) ? (int) $entry['humidity'] : null;
            }

            $historyRows = [];
            foreach (array_slice($items, 0, 10) as $entry) {
                $historyRows[] = [
                    'timestamp' => isset($entry['timestamp']) ? Carbon::parse($entry['timestamp'])->format('d/m H:i:s') : '—',
                    'temperature' => isset($entry['temperature']) ? number_format((float) $entry['temperature'], 1) . '°C' : '—',
                    'humidity' => isset($entry['humidity']) ? (int) $entry['humidity'] . '%' : '—',
                    'door' => !empty($entry['door_open']) ? 'Abierta' : 'Cerrada',
                    'battery' => isset($entry['battery']) ? (int) $entry['battery'] . '%' : '—',
                ];
            }

            $points[] = [
                'sensor_id' => $sensor['sensor_id'],
                'camera_id' => $sensor['camera_id'],
                'display_name' => $sensor['display_name'] ?? $sensor['camera_id'],
                'location' => $sensor['location'],
                'description' => $sensor['description'],
                'temperature' => $temperature,
                'humidity' => $humidity,
                'battery' => $battery,
                'door_open' => $doorOpen,
                'signal_rssi' => $rssi,
                'signal_snr' => $snr,
                'timestamp' => $timestamp,
                'minutes_ago' => $minutesAgo,
                'minutes_ago_label' => $minutesAgoLabel,
                'last_seen_at_label' => $lastSeenAtLabel,
                'status_label' => $status['label'],
                'status_class' => $status['class'],
                'status_message' => $status['message'],
                'health_score' => $health['score'],
                'health_label' => $health['label'],
                'health_class' => $health['class'],
                'health_factors' => $health['factors'],
                'history' => [
                    'labels' => $historyLabels,
                    'temperatures' => $historyTemperatures,
                    'humidity' => $historyHumidity,
                ],
                'history_rows' => $historyRows,
                'modules' => [
                    ['name' => 'Temperatura', 'value' => $temperature !== null ? number_format($temperature, 1) . '°C' : '—', 'state' => 'active'],
                    ['name' => 'Puerta', 'value' => $doorOpen ? 'Abierta' : 'Cerrada', 'state' => 'active'],
                    ['name' => 'Batería', 'value' => $battery !== null ? $battery . '%' : '—', 'state' => 'active'],
                    ['name' => 'Señal', 'value' => $rssi !== null ? $rssi . ' dBm' : '—', 'state' => 'active'],
                    ['name' => 'Compresor', 'value' => 'Próximamente', 'state' => 'pending'],
                    ['name' => 'Vibración', 'value' => 'Próximamente', 'state' => 'pending'],
                    ['name' => 'Consumo eléctrico', 'value' => 'Próximamente', 'state' => 'pending'],
                    ['name' => 'Presión / aceite', 'value' => 'Próximamente', 'state' => 'pending'],
                ],
                'operational_events' => $this->getOperationalEvents($sensor['sensor_id'], $sensor['settings_key'] ?? null, $sensor['fallback_key'] ?? null),
                    'alarm_events' => $this->getAlarmEvents($sensor['sensor_id'], $sensor['settings_key'] ?? null, $sensor['fallback_key'] ?? null),
            ];
        }

        $avgHealth = count($points) > 0
            ? round(array_sum(array_map(fn($p) => $p['health_score'], $points)) / count($points))
            : 0;

        $avgTempValues = array_filter(array_map(fn($p) => $p['temperature'], $points), fn($v) => $v !== null);
        $avgTemp = count($avgTempValues) > 0
            ? round(array_sum($avgTempValues) / count($avgTempValues), 1)
            : null;

        return view('iot.points', [
            'points' => $points,
            'summary' => [
                'total_points' => count($points),
                'alerts' => $alertsCount,
                'avg_health' => $avgHealth,
                'avg_temp' => $avgTemp,
                'future_modules' => 4,
            ],
        ]);
    }
}