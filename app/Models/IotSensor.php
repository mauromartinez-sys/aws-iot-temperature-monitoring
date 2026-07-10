<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IotSensor extends Model
{
    protected $fillable = [
        'sensor_key',
        'display_name',
        'location',
        'camera_type',
        'description',
        'alarm_min',
        'alarm_max',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'alarm_min' => 'decimal:2',
        'alarm_max' => 'decimal:2',
        'display_order' => 'integer',
    ];

    /**
     * Devuelve el mapa tecnico => nombre visible.
     * Ej: camara_1 => Cámara Congelados Planta 1.
     */
    public static function displayNameMap(bool $onlyActive = false): array
    {
        $query = static::query();

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query
            ->orderBy('display_order')
            ->orderBy('sensor_key')
            ->pluck('display_name', 'sensor_key')
            ->toArray();
    }

    /**
     * Devuelve el nombre visible si existe; si no, conserva el ID técnico.
     */
    public static function displayNameFor(?string $sensorKey, ?array $map = null): string
    {
        if (!$sensorKey) {
            return 'Sensor sin identificar';
        }

        if ($map !== null) {
            return $map[$sensorKey] ?? $sensorKey;
        }

        return static::query()
            ->where('sensor_key', $sensorKey)
            ->value('display_name') ?: $sensorKey;
    }

    /**
     * Devuelve configuración completa indexada por sensor_key.
     */
    public static function settingsMap(bool $onlyActive = false): array
    {
        $query = static::query();

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query
            ->orderBy('display_order')
            ->orderBy('sensor_key')
            ->get()
            ->keyBy('sensor_key')
            ->map(fn (IotSensor $sensor) => [
                'sensor_key' => $sensor->sensor_key,
                'display_name' => $sensor->display_name,
                'location' => $sensor->location,
                'camera_type' => $sensor->camera_type,
                'description' => $sensor->description,
                'alarm_min' => $sensor->alarm_min !== null ? (float) $sensor->alarm_min : null,
                'alarm_max' => $sensor->alarm_max !== null ? (float) $sensor->alarm_max : null,
                'is_active' => (bool) $sensor->is_active,
                'display_order' => (int) $sensor->display_order,
            ])
            ->toArray();
    }
}
