<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CameraEvent extends Model
{
    public const TYPE_APERTURA = 'apertura';
    public const TYPE_REPOSICION = 'reposicion';
    public const TYPE_RETIRO = 'retiro';
    public const TYPE_LIMPIEZA = 'limpieza';
    public const TYPE_MANTENIMIENTO = 'mantenimiento';
    public const TYPE_AUDITORIA = 'auditoria';
    public const TYPE_CALIDAD = 'control_calidad';
    public const TYPE_FALLA = 'falla';
    public const TYPE_OTRO = 'otro';

    protected $fillable = [
        'sensor_key',
        'user_id',
        'event_type',
        'reason',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public static function eventTypes(): array
    {
        return [
            self::TYPE_APERTURA => 'Apertura operativa',
            self::TYPE_REPOSICION => 'Reposición de mercadería',
            self::TYPE_RETIRO => 'Retiro de mercadería',
            self::TYPE_LIMPIEZA => 'Limpieza',
            self::TYPE_MANTENIMIENTO => 'Mantenimiento',
            self::TYPE_AUDITORIA => 'Auditoría',
            self::TYPE_CALIDAD => 'Control de calidad',
            self::TYPE_FALLA => 'Falla / puerta trabada',
            self::TYPE_OTRO => 'Otro',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sensorName(): string
    {
        return IotSensor::displayNameFor($this->sensor_key);
    }

    public function eventTypeLabel(): string
    {
        return self::eventTypes()[$this->event_type] ?? ucfirst(str_replace('_', ' ', $this->event_type));
    }
}
