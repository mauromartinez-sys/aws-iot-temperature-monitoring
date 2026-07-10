<?php

namespace App\Models;

use Carbon\Carbon;

class IotReading
{
    public static function normalize(array $item, array $sensorFallback = [], array $nameMap = []): array
    {
        $temperature = isset($item['temperature']) ? (float) $item['temperature'] : null;
        $humidity = isset($item['humidity']) ? (int) $item['humidity'] : null;
        $battery = isset($item['battery']) ? (int) $item['battery'] : null;
        $doorOpen = self::toBool($item['door_open'] ?? false);
        $timestamp = $item['timestamp'] ?? null;

        $cameraKey = $item['camera_id']
            ?? ($sensorFallback['camera_key'] ?? null)
            ?? ($sensorFallback['camera_id'] ?? null)
            ?? ($item['sensor_id'] ?? ($sensorFallback['sensor_id'] ?? null));

        $displayName = IotSensor::displayNameFor($cameraKey, $nameMap);
        $status = self::status($temperature, $doorOpen, $battery, $sensorFallback);

        return [
            'sensor_id' => $item['sensor_id'] ?? ($sensorFallback['sensor_id'] ?? '—'),
            'camera_key' => $cameraKey,
            'camera_id' => $displayName,
            'client_id' => $item['client_id'] ?? '—',
            'site_id' => $item['site_id'] ?? ($sensorFallback['location'] ?? '—'),
            'source' => $item['source'] ?? '—',
            'temperature' => $temperature,
            'humidity' => $humidity,
            'battery' => $battery,
            'door_open' => $doorOpen,
            'signal_rssi' => isset($item['signal_rssi']) && $item['signal_rssi'] !== '' ? (int) $item['signal_rssi'] : null,
            'signal_snr' => isset($item['signal_snr']) && $item['signal_snr'] !== '' ? (float) $item['signal_snr'] : null,
            'timestamp' => $timestamp,
            'timestamp_label' => $timestamp ? Carbon::parse($timestamp)->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : '—',
            'time_label' => $timestamp ? Carbon::parse($timestamp)->timezone(config('app.timezone'))->format('H:i') : '—',
            'status' => $status['label'],
            'status_class' => $status['class'],
        ];
    }

    public static function status(?float $temperature, bool $doorOpen, ?int $battery = null, array $sensorConfig = []): array
    {
        $alarmMin = isset($sensorConfig['alarm_min']) && $sensorConfig['alarm_min'] !== null ? (float) $sensorConfig['alarm_min'] : null;
        $alarmMax = isset($sensorConfig['alarm_max']) && $sensorConfig['alarm_max'] !== null ? (float) $sensorConfig['alarm_max'] : null;

        if ($temperature !== null && $alarmMax !== null && $temperature >= $alarmMax) {
            return ['label' => 'CRÍTICO', 'class' => 'danger'];
        }

        if ($temperature !== null && $alarmMin !== null && $temperature <= $alarmMin) {
            return ['label' => 'ALERTA BAJA', 'class' => 'warning'];
        }

        if ($temperature !== null && $alarmMax === null && $temperature >= 12) {
            return ['label' => 'CRÍTICO', 'class' => 'danger'];
        }

        if ($temperature !== null && $alarmMax === null && $temperature >= 8) {
            return ['label' => 'ALERTA', 'class' => 'warning'];
        }

        if ($doorOpen) {
            return ['label' => 'PUERTA ABIERTA', 'class' => 'warning'];
        }

        if ($battery !== null && $battery < 20) {
            return ['label' => 'BATERÍA BAJA', 'class' => 'warning'];
        }

        return ['label' => 'NORMAL', 'class' => 'success'];
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'abierta'], true);
        }

        return (bool) $value;
    }
}
