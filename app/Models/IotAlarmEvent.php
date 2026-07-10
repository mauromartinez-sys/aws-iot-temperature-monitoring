<?php

namespace App\Models;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class IotAlarmEvent
{
    public static function tableName(): string
    {
        return env('DYNAMODB_ALARM_EVENTS_TABLE', env('ALARM_EVENTS_TABLE', 'iot_alarm_events'));
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
            $items = [];
            $params = [
                'TableName' => self::tableName(),
            ];

            do {
                $result = self::client()->scan($params);

                foreach ($result['Items'] ?? [] as $item) {
                    $items[] = self::normalize(self::marshaler()->unmarshalItem($item));
                }

                $params['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;
            } while (!empty($params['ExclusiveStartKey']));

            usort($items, function ($a, $b) {
                $dateA = (string) ($a['last_detected_at'] ?? $a['started_at'] ?? $a['created_at'] ?? '');
                $dateB = (string) ($b['last_detected_at'] ?? $b['started_at'] ?? $b['created_at'] ?? '');

                return strcmp($dateB, $dateA);
            });

            return $items;
        } catch (\Throwable $e) {
            return [];
        }
    }


    public static function active(int $limit = 12): array
    {
        $items = array_values(array_filter(self::all(), function (array $event) {
            return self::isActiveStatus((string) ($event['status'] ?? 'open'));
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
    }

    public static function isActiveStatus(string $status): bool
    {
        $status = strtolower(trim($status));

        if ($status === '') {
            return true;
        }

        return in_array($status, ['open', 'active', 'acknowledged', 'silenced', 'pending'], true)
            || !in_array($status, ['resolved', 'closed', 'deleted', 'dismissed'], true);
    }


    public static function forSensorKeys(array $keys, int $limit = 12): array
    {
        $keys = array_values(array_unique(array_filter(array_map(
            fn ($key) => trim((string) $key),
            $keys
        ))));

        if (empty($keys)) {
            return [];
        }

        $items = array_values(array_filter(self::all(), function (array $event) use ($keys) {
            return in_array((string) ($event['sensor_key'] ?? ''), $keys, true)
                || in_array((string) ($event['camera_id'] ?? ''), $keys, true)
                || in_array((string) ($event['sensor_id'] ?? ''), $keys, true);
        }));

        return array_slice($items, 0, $limit);
    }

    public static function normalize(array $item): array
    {
        foreach ([
            'detected_value',
            'threshold_value',
            'min_value',
            'max_value',
            'cooldown_minutes',
            'notification_count',
        ] as $numericField) {
            if (isset($item[$numericField]) && $item[$numericField] !== '') {
                $item[$numericField] = is_numeric($item[$numericField])
                    ? (float) $item[$numericField]
                    : $item[$numericField];
            }
        }

        return $item;
    }
}
