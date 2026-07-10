<?php

namespace App\Models;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Support\Str;

class IotAlarmRule
{
    public static function tableName(): string
    {
        return env('DYNAMODB_ALARM_RULES_TABLE', 'iot_alarm_rules');
    }

    private static function client(): DynamoDbClient
    {
        return new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', env('AWS_REGION', 'us-east-1')),
            'version' => 'latest',
        ]);
    }

    private static function marshaler(): Marshaler
    {
        return new Marshaler();
    }

    public static function all(): array
    {
        try {
            $result = self::client()->scan([
                'TableName' => self::tableName(),
            ]);

            $items = [];

            foreach ($result['Items'] ?? [] as $item) {
                $items[] = self::normalize(self::marshaler()->unmarshalItem($item));
            }

            usort($items, function ($a, $b) {
                return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
            });

            return $items;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function find(string $ruleId): ?array
    {
        try {
            $result = self::client()->getItem([
                'TableName' => self::tableName(),
                'Key' => [
                    'rule_id' => ['S' => $ruleId],
                ],
            ]);

            if (empty($result['Item'])) {
                return null;
            }

            return self::normalize(self::marshaler()->unmarshalItem($result['Item']));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function create(array $data): array
    {
        $now = now()->toIso8601String();

        $item = array_merge([
            'rule_id' => 'rule_' . Str::uuid()->toString(),
            'created_at' => $now,
            'updated_at' => $now,
        ], self::sanitize($data));

        self::put($item);

        return $item;
    }

    public static function update(string $ruleId, array $data): ?array
    {
        $existing = self::find($ruleId);

        if (!$existing) {
            return null;
        }

        $item = array_merge($existing, self::sanitize($data), [
            'rule_id' => $ruleId,
            'created_at' => $existing['created_at'] ?? now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        self::put($item);

        return $item;
    }

    public static function toggle(string $ruleId): ?array
    {
        $existing = self::find($ruleId);

        if (!$existing) {
            return null;
        }

        return self::update($ruleId, [
            'is_active' => !((bool) ($existing['is_active'] ?? false)),
        ]);
    }

    public static function delete(string $ruleId): void
    {
        self::client()->deleteItem([
            'TableName' => self::tableName(),
            'Key' => [
                'rule_id' => ['S' => $ruleId],
            ],
        ]);
    }

    private static function put(array $item): void
    {
        self::client()->putItem([
            'TableName' => self::tableName(),
            'Item' => self::marshaler()->marshalItem($item),
        ]);
    }

    private static function sanitize(array $data): array
    {
        $recipients = $data['recipients'] ?? [];
        if (is_string($recipients)) {
            $recipients = preg_split('/[\n,;]+/', $recipients) ?: [];
        }

        $recipients = array_values(array_filter(array_map(
            fn ($email) => trim((string) $email),
            (array) $recipients
        )));

        $channels = $data['channels'] ?? ['email'];
        if (is_string($channels)) {
            $channels = [$channels];
        }

        $channels = array_values(array_filter(array_map(
            fn ($channel) => trim((string) $channel),
            (array) $channels
        )));

        $alarmType = (string) ($data['alarm_type'] ?? 'temperature_range');

        return [
            'sensor_key' => (string) ($data['sensor_key'] ?? ''),
            'camera_name_snapshot' => (string) ($data['camera_name_snapshot'] ?? ''),
            'alarm_name' => (string) ($data['alarm_name'] ?? ''),
            'alarm_type' => $alarmType,
            'metric' => (string) ($data['metric'] ?? self::defaultMetricForAlarmType($alarmType)),
            'condition_operator' => (string) ($data['condition_operator'] ?? self::defaultOperatorForAlarmType($alarmType)),
            'threshold_value' => isset($data['threshold_value']) && $data['threshold_value'] !== '' ? (float) $data['threshold_value'] : null,
            'min_value' => isset($data['min_value']) && $data['min_value'] !== '' ? (float) $data['min_value'] : null,
            'max_value' => isset($data['max_value']) && $data['max_value'] !== '' ? (float) $data['max_value'] : null,
            'duration_minutes' => isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : 0,
            'door_condition' => (string) ($data['door_condition'] ?? self::defaultDoorConditionForAlarmType($alarmType)),
            'door_grace_minutes' => isset($data['door_grace_minutes']) ? (int) $data['door_grace_minutes'] : 0,
            'severity' => (string) ($data['severity'] ?? 'warning'),
            'cooldown_minutes' => isset($data['cooldown_minutes']) ? (int) $data['cooldown_minutes'] : 30,
            'recipients' => $recipients,
            'channels' => !empty($channels) ? $channels : ['email'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];
    }

    public static function normalize(array $item): array
    {
        $item['threshold_value'] = isset($item['threshold_value']) ? (float) $item['threshold_value'] : null;
        $item['min_value'] = isset($item['min_value']) ? (float) $item['min_value'] : null;
        $item['max_value'] = isset($item['max_value']) ? (float) $item['max_value'] : null;
        $item['duration_minutes'] = isset($item['duration_minutes']) ? (int) $item['duration_minutes'] : 0;
        $item['door_grace_minutes'] = isset($item['door_grace_minutes']) ? (int) $item['door_grace_minutes'] : 0;
        $item['cooldown_minutes'] = isset($item['cooldown_minutes']) ? (int) $item['cooldown_minutes'] : 30;
        $item['is_active'] = (bool) ($item['is_active'] ?? false);
        $item['recipients'] = array_values((array) ($item['recipients'] ?? []));
        $item['channels'] = array_values((array) ($item['channels'] ?? ['email']));

        return $item;
    }

    public static function alarmTypes(): array
    {
        return [
            'temperature_range' => 'Rango de temperatura',
            'temperature_high' => 'Temperatura alta',
            'temperature_low' => 'Temperatura baja',
            'door_open_too_long' => 'Puerta abierta demasiado tiempo',
            'battery_low' => 'Batería baja',
            'sensor_offline' => 'Sensor sin señal',
            'rssi_low' => 'RSSI débil',
        ];
    }

    public static function metrics(): array
    {
        return [
            'temperature' => 'Temperatura',
            'humidity' => 'Humedad',
            'door_open' => 'Puerta abierta',
            'battery' => 'Batería',
            'rssi' => 'RSSI',
            'last_seen_minutes' => 'Minutos sin señal',
        ];
    }

    public static function operators(): array
    {
        return [
            '>' => 'Mayor que',
            '>=' => 'Mayor o igual que',
            '<' => 'Menor que',
            '<=' => 'Menor o igual que',
            '=' => 'Igual a',
        ];
    }

    public static function doorConditions(): array
    {
        return [
            'any' => 'No considerar puerta',
            'open' => 'Solo si la puerta está abierta',
            'closed' => 'Solo si la puerta está cerrada',
        ];
    }

    public static function severities(): array
    {
        return [
            'info' => 'Informativa',
            'warning' => 'Advertencia',
            'critical' => 'Crítica',
        ];
    }

    public static function defaultMetricForAlarmType(string $alarmType): string
    {
        return match ($alarmType) {
            'door_open_too_long' => 'door_open',
            'battery_low' => 'battery',
            'rssi_low' => 'rssi',
            'sensor_offline' => 'last_seen_minutes',
            default => 'temperature',
        };
    }

    public static function defaultOperatorForAlarmType(string $alarmType): string
    {
        return match ($alarmType) {
            'temperature_low', 'battery_low', 'rssi_low' => '<',
            'door_open_too_long' => '=',
            'sensor_offline' => '>',
            default => '>',
        };
    }

    public static function defaultThresholdForAlarmType(string $alarmType): ?float
    {
        return match ($alarmType) {
            'door_open_too_long' => 1,
            'battery_low' => 20,
            'rssi_low' => -105,
            'sensor_offline' => 15,
            default => null,
        };
    }

    public static function defaultDoorConditionForAlarmType(string $alarmType): string
    {
        return match ($alarmType) {
            'door_open_too_long' => 'open',
            'temperature_range', 'temperature_high', 'temperature_low' => 'closed',
            default => 'any',
        };
    }

}
