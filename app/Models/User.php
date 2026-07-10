<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLE_OPERARIO = 'operario';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_CALIDAD = 'calidad';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_REGISTRO_OPERATIVO = 'registro_operativo';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public static function roles(): array
    {
        return [
            self::ROLE_OPERARIO => 'Operario',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_CALIDAD => 'Calidad',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_REGISTRO_OPERATIVO => 'Registro operativo',
        ];
    }

    public function roleLabel(): string
    {
        return self::roles()[$this->role] ?? ucfirst((string) $this->role);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isOperario(): bool
    {
        return $this->role === self::ROLE_OPERARIO;
    }

    public function isCalidad(): bool
    {
        return $this->role === self::ROLE_CALIDAD;
    }

    public function isRegistroOperativo(): bool
    {
        return $this->role === self::ROLE_REGISTRO_OPERATIVO;
    }

    public function homeRouteName(): string
    {
        return $this->isRegistroOperativo() ? 'iot.operations' : 'iot.dashboard';
    }

    public function canViewDashboard(): bool
    {
        return in_array($this->role, [
            self::ROLE_OPERARIO,
            self::ROLE_SUPERVISOR,
            self::ROLE_CALIDAD,
            self::ROLE_ADMIN,
        ], true);
    }

    public function canViewPoints(): bool
    {
        return in_array($this->role, [
            self::ROLE_OPERARIO,
            self::ROLE_SUPERVISOR,
            self::ROLE_CALIDAD,
            self::ROLE_ADMIN,
        ], true);
    }

    public function canReceiveAlerts(): bool
    {
        return in_array($this->role, [
            self::ROLE_OPERARIO,
            self::ROLE_SUPERVISOR,
            self::ROLE_ADMIN,
        ], true);
    }

    public function canAcknowledgeAlarms(): bool
    {
        return in_array($this->role, [
            self::ROLE_OPERARIO,
            self::ROLE_SUPERVISOR,
            self::ROLE_ADMIN,
        ], true);
    }

    public function canModifyThresholds(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPERVISOR,
            self::ROLE_ADMIN,
        ], true);
    }

    public function canManageAlarms(): bool
    {
        return $this->canModifyThresholds();
    }

    public function canManageSensors(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canManageSettings(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canExportLegalReports(): bool
    {
        return in_array($this->role, [
            self::ROLE_CALIDAD,
            self::ROLE_ADMIN,
        ], true);
    }

    public function canViewReports(): bool
    {
        return $this->canExportLegalReports();
    }

    public function canManageUsers(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canRegisterOperationalEvents(): bool
    {
        return in_array($this->role, [
            self::ROLE_OPERARIO,
            self::ROLE_SUPERVISOR,
            self::ROLE_ADMIN,
            self::ROLE_REGISTRO_OPERATIVO,
        ], true);
    }

    public function canUseOperationalRegister(): bool
    {
        return $this->canRegisterOperationalEvents();
    }

    public function canViewOperationalEvents(): bool
    {
        return in_array($this->role, [
            self::ROLE_OPERARIO,
            self::ROLE_SUPERVISOR,
            self::ROLE_CALIDAD,
            self::ROLE_ADMIN,
            self::ROLE_REGISTRO_OPERATIVO,
        ], true);
    }

    public function canManageOperationalEvents(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPERVISOR,
            self::ROLE_ADMIN,
        ], true);
    }

    public function hasPermission(string $permission): bool
    {
        return match ($permission) {
            'view_dashboard' => $this->canViewDashboard(),
            'view_points' => $this->canViewPoints(),
            'view_reports' => $this->canViewReports(),
            'export_reports' => $this->canExportLegalReports(),
            'use_operations' => $this->canUseOperationalRegister(),
            'register_operations' => $this->canRegisterOperationalEvents(),
            'view_operations' => $this->canViewOperationalEvents(),
            'manage_settings' => $this->canManageSettings(),
            'manage_sensors' => $this->canManageSensors(),
            'manage_users' => $this->canManageUsers(),
            'manage_alarms' => $this->canManageAlarms(),
            'modify_thresholds' => $this->canModifyThresholds(),
            default => false,
        };
    }
}
