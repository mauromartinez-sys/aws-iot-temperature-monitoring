<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alertas por cámara - IoT Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    
    <link rel="icon" type="image/png" href="{{ asset('images/logo-iot.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-iot.png') }}">
<style>
        :root {
            --bg: #07111f;
            --panel: rgba(15, 23, 42, 0.86);
            --panel-strong: rgba(15, 23, 42, 0.96);
            --panel-soft: rgba(30, 41, 59, 0.72);
            --border: rgba(148, 163, 184, 0.18);
            --text: #e5edf8;
            --muted: #94a3b8;
            --cyan: #22d3ee;
            --blue: #38bdf8;
            --green: #22c55e;
            --yellow: #f59e0b;
            --red: #ef4444;
            --purple: #a855f7;
            --shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
        }

        * { box-sizing: border-box; }

        html {
            scrollbar-width: thin;
            scrollbar-color: rgba(34, 211, 238, .55) rgba(15, 23, 42, .70);
        }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: rgba(15, 23, 42, .70); border-radius: 999px; }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(34,211,238,.75), rgba(168,85,247,.55));
            border-radius: 999px;
            border: 2px solid rgba(15, 23, 42, .80);
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(34, 211, 238, 0.18), transparent 32%),
                radial-gradient(circle at top right, rgba(168, 85, 247, 0.16), transparent 34%),
                linear-gradient(135deg, #020617 0%, #07111f 45%, #0f172a 100%);
            color: var(--text);
            font-family: Inter, Segoe UI, Roboto, Arial, sans-serif;
        }

        .page { width: min(1540px, calc(100% - 36px)); margin: 0 auto; padding: 28px 0 42px; }

        .topbar {
            display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 24px;
        }

        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-mark {
            width: 48px; height: 48px; border-radius: 16px;
            background: linear-gradient(135deg, var(--cyan), var(--purple));
            box-shadow: 0 0 34px rgba(34, 211, 238, 0.34);
            display: grid; place-items: center; font-weight: 900;
        }
        .brand h1 { margin: 0; font-size: 28px; letter-spacing: -0.045em; }
        .brand p { margin: 4px 0 0; color: #9fb3d1; font-size: 14px; }

        .nav { display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 10px; }
        .nav a, .logout-btn {
            text-decoration: none; color: var(--text); padding: 11px 17px; border-radius: 15px;
            border: 1px solid rgba(148, 163, 184, 0.18); background: rgba(15, 23, 42, 0.64);
            font-size: 14px; font-weight: 800; cursor:pointer;
        }
        .nav a:hover, .nav a.active, .logout-btn:hover {
            border-color: rgba(34, 211, 238, .55); background: rgba(14, 116, 144, .22);
        }
        .logout-form { margin:0; }

        .hero {
            position: relative; overflow: hidden; border-radius: 30px; padding: 30px;
            background:
                linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(15, 23, 42, 0.68)),
                radial-gradient(circle at top right, rgba(34, 211, 238, 0.25), transparent 35%);
            border: 1px solid var(--border); box-shadow: var(--shadow); margin-bottom: 18px;
        }
        .hero-inner { position: relative; display: grid; grid-template-columns: 1.1fr .9fr; gap: 24px; align-items: center; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px; color: #67e8f9;
            background: rgba(8, 145, 178, 0.14); border: 1px solid rgba(103, 232, 249, 0.22);
            padding: 7px 12px; border-radius: 999px; font-size: 13px; font-weight: 900; margin-bottom: 16px;
        }
        .pulse { width: 8px; height: 8px; background: var(--green); border-radius: 999px; box-shadow: 0 0 0 8px rgba(34, 197, 94, 0.12); }
        .hero h2 { margin: 0; font-size: clamp(34px, 4vw, 56px); line-height: .96; letter-spacing: -0.06em; }
        .hero h2 span { background: linear-gradient(90deg, #67e8f9, #a78bfa, #f0abfc); -webkit-background-clip: text; color: transparent; }
        .hero p { margin: 16px 0 0; color: #b6c6d8; font-size: 16px; line-height: 1.7; max-width: 780px; }

        .hero-stats { display:grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .hero-stat {
            padding: 18px; border-radius: 22px; background: rgba(2,6,23,.42);
            border: 1px solid var(--border); backdrop-filter: blur(12px);
        }
        .hero-stat .value { font-size: 30px; font-weight: 900; letter-spacing: -.04em; }
        .hero-stat .label { margin-top: 6px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .1em; }

        .alert-success, .errors {
            margin-bottom: 18px; padding: 14px 16px; border-radius: 18px; font-size: 14px; font-weight: 700;
        }
        .alert-success { border: 1px solid rgba(34,197,94,.25); background: rgba(34,197,94,.10); color: #bbf7d0; }
        .errors { border:1px solid rgba(239,68,68,.25); background:rgba(239,68,68,.10); color:#fecaca; }
        .errors ul { margin:8px 0 0; padding-left:18px; }

        .subnav { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
        .subnav a {
            text-decoration:none; color:#cbd5e1; padding:11px 16px; border-radius:15px;
            border:1px solid rgba(148,163,184,.18); background:rgba(15,23,42,.64); font-weight:800; font-size:14px;
        }
        .subnav a.active { color:#fff; border-color:rgba(34,211,238,.55); background:rgba(14,116,144,.22); }

        .content-grid { display:grid; grid-template-columns: minmax(0,1.45fr) minmax(380px,.55fr); gap:18px; align-items:start; }
        .panel {
            background: var(--panel); border: 1px solid var(--border); border-radius: 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,.24); overflow:hidden; backdrop-filter: blur(14px);
        }
        .panel-header { padding:22px 24px 16px; display:flex; justify-content:space-between; gap:16px; align-items:flex-start; border-bottom:1px solid rgba(148,163,184,.12); }
        .panel-header h3 { margin:0; font-size:21px; letter-spacing:-.03em; }
        .panel-header p { margin:7px 0 0; color:var(--muted); font-size:13px; line-height:1.5; }
        .panel-body { padding:18px 20px 22px; }

        .rule-list { display:grid; gap:14px; }
        .rule-card {
            border:1px solid rgba(148,163,184,.14); background:rgba(2,6,23,.38);
            border-radius:22px; padding:16px; display:grid; gap:14px;
        }
        .rule-head { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; }
        .rule-head strong { display:block; font-size:18px; }
        .rule-head small { display:block; color:var(--muted); margin-top:4px; line-height:1.45; }
        .badges { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
        .badge {
            display:inline-flex; align-items:center; justify-content:center; padding:7px 10px; border-radius:999px;
            border:1px solid rgba(148,163,184,.18); background:rgba(15,23,42,.7); color:#cbd5e1;
            font-size:11px; text-transform:uppercase; letter-spacing:.06em; font-weight:900;
        }
        .badge-green { color:#86efac; background:rgba(34,197,94,.12); border-color:rgba(34,197,94,.25); }
        .badge-yellow { color:#fde68a; background:rgba(245,158,11,.12); border-color:rgba(245,158,11,.25); }
        .badge-red { color:#fecaca; background:rgba(239,68,68,.12); border-color:rgba(239,68,68,.25); }
        .badge-purple { color:#e9d5ff; background:rgba(168,85,247,.12); border-color:rgba(168,85,247,.25); }
        .badge-blue { color:#bae6fd; background:rgba(14,165,233,.12); border-color:rgba(14,165,233,.25); }

        .rule-meta { display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; }
        .meta-box {
            border:1px solid rgba(148,163,184,.12); background:rgba(15,23,42,.48); border-radius:16px; padding:12px;
        }
        .meta-box span { display:block; color:#9fb3d1; font-size:11px; text-transform:uppercase; letter-spacing:.08em; font-weight:900; }
        .meta-box strong { display:block; margin-top:6px; font-size:15px; color:#fff; }

        .form-grid { display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; }
        .field { display:grid; gap:8px; }
        .field.full { grid-column:1 / -1; }
        label { color:#bfdbfe; font-size:12px; text-transform:uppercase; letter-spacing:.08em; font-weight:900; }
        input, select, textarea {
            width:100%; border:1px solid rgba(148,163,184,.20); background:rgba(2,6,23,.48);
            color:#fff; border-radius:16px; padding:13px 14px; outline:none; font-weight:700;
        }
        textarea { min-height: 88px; resize: vertical; }
        input:focus, select:focus, textarea:focus { border-color:rgba(34,211,238,.70); box-shadow:0 0 0 4px rgba(34,211,238,.10); }

        .switch-row { display:flex; align-items:center; justify-content:space-between; gap:14px; color:#cbd5e1; }
        .switch { display:inline-flex; align-items:center; gap:10px; color:#cbd5e1; font-size:13px; font-weight:800; }
        .switch input { width:18px; height:18px; accent-color:var(--cyan); }

        .actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
        .btn {
            border:1px solid rgba(148,163,184,.20); background:rgba(15,23,42,.70); color:#fff;
            border-radius:16px; padding:12px 16px; font-weight:900; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;
        }
        .btn-primary { border-color:rgba(34,211,238,.50); background:rgba(8,145,178,.25); }
        .btn-green { border-color:rgba(34,197,94,.28); background:rgba(34,197,94,.14); color:#bbf7d0; }
        .btn-red { border-color:rgba(239,68,68,.28); background:rgba(239,68,68,.12); color:#fecaca; }
        .btn-yellow { border-color:rgba(245,158,11,.28); background:rgba(245,158,11,.12); color:#fde68a; }

        .add-form { display:grid; gap:12px; }
        .side-note { border:1px solid rgba(34,211,238,.18); background:rgba(8,145,178,.09); color:#bae6fd; border-radius:22px; padding:16px; margin-top:14px; }
        .side-note h4 { margin:0 0 8px; }
        .side-note p { margin:0; color:#b6c6d8; line-height:1.55; font-size:13px; }

        .empty {
            border:1px dashed rgba(148,163,184,.24); border-radius:22px; padding:28px; text-align:center; color:#94a3b8;
            background:rgba(2,6,23,.28);
        }

        @media (max-width: 1180px) {
            .hero-inner, .content-grid { grid-template-columns: 1fr; }
            .hero-stats { grid-template-columns: repeat(2, 1fr); }
            .rule-meta { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 760px) {
            .topbar { align-items:flex-start; flex-direction:column; }
            .page { width:min(100% - 22px, 1540px); }
            .form-grid, .hero-stats, .rule-meta { grid-template-columns:1fr; }
        }
    
        .dynamic-help {
            margin-top: 6px;
            color: #93c5fd;
            font-size: 12px;
            line-height: 1.45;
        }
        .field.is-hidden, .form-grid.is-hidden, .dynamic-help.is-hidden { display: none !important; }
        .notice-box {
            border: 1px solid rgba(245,158,11,.24);
            background: rgba(245,158,11,.10);
            color: #fde68a;
            border-radius: 18px;
            padding: 13px 14px;
            font-size: 13px;
            line-height: 1.45;
            font-weight: 700;
        }
        .range-preview {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 11px;
            border-radius: 999px;
            color: #bae6fd;
            background: rgba(14, 165, 233, .12);
            border: 1px solid rgba(14, 165, 233, .25);
            font-size: 12px;
            font-weight: 900;
        }

    
        .confirm-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(2, 6, 23, 0.76);
            backdrop-filter: blur(10px);
        }

        .confirm-modal-backdrop.is-open {
            display: flex;
        }

        .confirm-modal {
            width: min(520px, 100%);
            border-radius: 26px;
            background:
                radial-gradient(circle at top left, rgba(239, 68, 68, 0.18), transparent 34%),
                linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(2, 6, 23, 0.98));
            border: 1px solid rgba(248, 113, 113, 0.28);
            box-shadow: 0 28px 90px rgba(0, 0, 0, 0.52);
            overflow: hidden;
        }

        .confirm-modal-header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 22px 24px 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        }

        .confirm-modal-icon {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            color: #fecaca;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(248, 113, 113, 0.30);
            font-size: 22px;
            font-weight: 950;
        }

        .confirm-modal-title {
            margin: 0;
            color: #f8fafc;
            font-size: 21px;
            letter-spacing: -0.04em;
        }

        .confirm-modal-subtitle {
            margin: 6px 0 0;
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.55;
        }

        .confirm-modal-body {
            padding: 18px 24px 8px;
        }

        .confirm-rule-name {
            padding: 13px 15px;
            border-radius: 16px;
            background: rgba(2, 6, 23, 0.46);
            border: 1px solid rgba(148, 163, 184, 0.13);
            color: #e0f2fe;
            font-size: 14px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .confirm-modal-warning {
            margin-top: 13px;
            color: #fca5a5;
            font-size: 12px;
            line-height: 1.45;
        }

        .confirm-modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 18px 24px 24px;
        }

        .confirm-modal-btn {
            min-height: 42px;
            padding: 10px 15px;
            border-radius: 15px;
            border: 1px solid rgba(148, 163, 184, 0.20);
            background: rgba(15, 23, 42, 0.72);
            color: #dbeafe;
            font-family: inherit;
            font-size: 13px;
            font-weight: 950;
            cursor: pointer;
        }

        .confirm-modal-btn:hover {
            border-color: rgba(34, 211, 238, 0.45);
            color: white;
        }

        .confirm-modal-btn.danger {
            color: #fff;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.95), rgba(127, 29, 29, 0.95));
            border-color: rgba(248, 113, 113, 0.45);
            box-shadow: 0 14px 38px rgba(127, 29, 29, 0.30);
        }

        .confirm-modal-btn.danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 44px rgba(127, 29, 29, 0.42);
        }

    

        /* Logo IoT personalizado */
        .brand-mark,
        .brand-icon {
            background: transparent !important;
            box-shadow: none !important;
            overflow: hidden;
            padding: 0;
        }
        .brand-mark img,
        .brand-icon img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            border-radius: inherit;
        }

    </style>
</head>

<body>
@php
    $navUser = auth()->user();
@endphp

<div class="page">
    <div class="topbar">
        <div class="brand">
            <div class="brand-mark"><img src="{{ asset('images/logo-iot.png') }}" alt="Logo IoT"></div>
            <div>
                <h1>Alertas por cámara</h1>
                <p>Reglas operativas por cámara, rango térmico, puerta, señal, batería y destinatarios</p>
            </div>
        </div>

        <nav class="nav">
            @if($navUser?->canViewDashboard())
                <a href="{{ route('iot.dashboard') }}">Dashboard</a>
            @endif
            @if(method_exists($navUser, 'canViewPoints') ? $navUser->canViewPoints() : $navUser?->canViewDashboard())
                <a href="{{ route('iot.points') }}">Puntos de medición</a>
            @endif
            @if(method_exists($navUser, 'canViewReports') ? $navUser->canViewReports() : $navUser?->canExportLegalReports())
                <a href="{{ route('iot.analytics') }}">Informes</a>
            @endif
            @if(method_exists($navUser, 'canUseOperationalRegister') ? $navUser->canUseOperationalRegister() : method_exists($navUser, 'canRegisterOperationalEvents') && $navUser->canRegisterOperationalEvents())
                <a href="{{ route('iot.operations') }}">Registro operativo</a>
            @endif
            @if($navUser?->canManageSensors() || $navUser?->canManageUsers() || $navUser?->canModifyThresholds())
                <a href="{{ route('iot.settings') }}" class="active">Configuración</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">Salir</button>
            </form>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="errors">
            <strong>Revisá los datos ingresados:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="hero">
        <div class="hero-inner">
            <div>
                <div class="eyebrow"><span class="pulse"></span> Motor de alarmas conectado a Lambda</div>
                <h2>Reglas configurables <span>por rango operativo</span></h2>
                <p>
                    Para cámaras frigoríficas, la regla principal es el rango permitido: si la temperatura baja del mínimo o supera el máximo, Lambda crea una alarma. Si vuelve al rango, la resuelve automáticamente.
                </p>
            </div>

            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="value">{{ $stats['total'] ?? 0 }}</div>
                    <div class="label">Reglas</div>
                </div>
                <div class="hero-stat">
                    <div class="value">{{ $stats['active'] ?? 0 }}</div>
                    <div class="label">Activas</div>
                </div>
                <div class="hero-stat">
                    <div class="value">{{ $stats['critical'] ?? 0 }}</div>
                    <div class="label">Críticas</div>
                </div>
                <div class="hero-stat">
                    <div class="value">{{ $stats['temperature'] ?? 0 }}</div>
                    <div class="label">Térmicas</div>
                </div>
            </div>
        </div>
    </section>

    <div class="subnav">
        <a href="{{ route('iot.settings') }}">Sensores / Cámaras</a>
        @if($navUser?->canManageUsers())
            <a href="{{ route('iot.settings.users') }}">Usuarios y Roles</a>
        @endif
        <a href="{{ route('iot.settings.alarms') }}" class="active">Alertas por cámara</a>
        <a href="{{ route('iot.operations') }}">Registro operativo</a>
    </div>

    <div class="content-grid">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Reglas configuradas</h3>
                    <p>Tabla DynamoDB: <strong>{{ $tableName }}</strong>. Las reglas se ejecutan en Lambda de forma independiente del sistema web.</p>
                </div>
                <span class="badge badge-blue">Lambda-ready</span>
            </div>

            <div class="panel-body">
                <div class="rule-list">
                    @forelse($rules as $rule)
                        @php
                            $severityClass = match($rule['severity'] ?? 'warning') {
                                'critical' => 'badge-red',
                                'info' => 'badge-blue',
                                default => 'badge-yellow',
                            };

                            $alarmType = $rule['alarm_type'] ?? 'temperature_range';
                            $conditionLabel = '';

                            if ($alarmType === 'temperature_range') {
                                $conditionLabel = 'Temperatura entre ' . ($rule['min_value'] ?? '-') . '°C y ' . ($rule['max_value'] ?? '-') . '°C';
                            } elseif ($alarmType === 'door_open_too_long') {
                                $conditionLabel = 'Puerta abierta durante ' . ($rule['duration_minutes'] ?? 0) . ' min';
                            } elseif ($alarmType === 'battery_low') {
                                $conditionLabel = 'Batería menor que ' . ($rule['threshold_value'] ?? '-') . '%';
                            } elseif ($alarmType === 'rssi_low') {
                                $conditionLabel = 'RSSI menor que ' . ($rule['threshold_value'] ?? '-') . ' dBm';
                            } elseif ($alarmType === 'sensor_offline') {
                                $conditionLabel = 'Sin señal por más de ' . ($rule['threshold_value'] ?? '-') . ' min';
                            } else {
                                $conditionLabel = ($metrics[$rule['metric'] ?? ''] ?? ($rule['metric'] ?? '-')) . ' ' . ($operators[$rule['condition_operator'] ?? ''] ?? '') . ' ' . ($rule['threshold_value'] ?? '-');
                            }
                        @endphp

                        <article class="rule-card">
                            <div class="rule-head">
                                <div>
                                    <strong>{{ $rule['alarm_name'] ?? 'Regla sin nombre' }}</strong>
                                    <small>
                                        {{ $rule['camera_display_name'] ?? $rule['sensor_key'] ?? 'Sensor' }}
                                        · {{ $alarmTypes[$rule['alarm_type'] ?? ''] ?? ($rule['alarm_type'] ?? 'Tipo') }}
                                        · Destinatarios: {{ $rule['recipients_label'] ?: 'Sin destinatarios' }}
                                    </small>
                                </div>
                                <div class="badges">
                                    <span class="badge {{ ($rule['is_active'] ?? false) ? 'badge-green' : 'badge-red' }}">
                                        {{ ($rule['is_active'] ?? false) ? 'Activa' : 'Inactiva' }}
                                    </span>
                                    <span class="badge {{ $severityClass }}">{{ $severities[$rule['severity'] ?? 'warning'] ?? 'Advertencia' }}</span>
                                </div>
                            </div>

                            <div class="rule-meta">
                                <div class="meta-box">
                                    <span>Condición</span>
                                    <strong>{{ $conditionLabel }}</strong>
                                </div>
                                <div class="meta-box">
                                    <span>Durante</span>
                                    <strong>{{ $rule['duration_minutes'] ?? 0 }} min</strong>
                                </div>
                                <div class="meta-box">
                                    <span>Puerta</span>
                                    <strong>{{ $doorConditions[$rule['door_condition'] ?? 'any'] ?? 'No considerar' }}</strong>
                                </div>
                                <div class="meta-box">
                                    <span>Cooldown</span>
                                    <strong>{{ $rule['cooldown_minutes'] ?? 30 }} min</strong>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('iot.settings.alarms.update', $rule['rule_id']) }}" class="alarm-form">
                                @csrf

                                <div class="form-grid">
                                    <div class="field">
                                        <label>Nombre</label>
                                        <input type="text" name="alarm_name" value="{{ old('alarm_name', $rule['alarm_name'] ?? '') }}" required>
                                    </div>

                                    <div class="field">
                                        <label>Cámara</label>
                                        <select name="sensor_key" required>
                                            @foreach($sensors as $sensor)
                                                <option value="{{ $sensor->sensor_key }}" @selected(($rule['sensor_key'] ?? '') === $sensor->sensor_key)>
                                                    {{ $sensor->display_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="field">
                                        <label>Tipo de alarma</label>
                                        <select name="alarm_type" class="alarm-type-select" required>
                                            @foreach($alarmTypes as $key => $label)
                                                <option value="{{ $key }}" @selected(($rule['alarm_type'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="field js-field-metric">
                                        <label>Métrica</label>
                                        <select name="metric" required>
                                            @foreach($metrics as $key => $label)
                                                <option value="{{ $key }}" @selected(($rule['metric'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="field js-field-range">
                                        <label>Temperatura mínima permitida</label>
                                        <input type="number" step="0.01" name="min_value" value="{{ old('min_value', $rule['min_value'] ?? '') }}">
                                    </div>

                                    <div class="field js-field-range">
                                        <label>Temperatura máxima permitida</label>
                                        <input type="number" step="0.01" name="max_value" value="{{ old('max_value', $rule['max_value'] ?? '') }}">
                                    </div>

                                    <div class="field js-field-operator">
                                        <label>Operador</label>
                                        <select name="condition_operator" required>
                                            @foreach($operators as $key => $label)
                                                <option value="{{ $key }}" @selected(($rule['condition_operator'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="field js-field-threshold">
                                        <label class="js-threshold-label">Valor límite</label>
                                        <input type="number" step="0.01" name="threshold_value" value="{{ old('threshold_value', $rule['threshold_value'] ?? '') }}">
                                    </div>

                                    <div class="field">
                                        <label class="js-duration-label">Duración mínima</label>
                                        <input type="number" min="0" name="duration_minutes" value="{{ old('duration_minutes', $rule['duration_minutes'] ?? 10) }}" required>
                                    </div>

                                    <div class="field">
                                        <label>Condición de puerta</label>
                                        <select name="door_condition" class="door-condition-select" required>
                                            @foreach($doorConditions as $key => $label)
                                                <option value="{{ $key }}" @selected(($rule['door_condition'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="field">
                                        <label>Gracia post-cierre</label>
                                        <input type="number" min="0" name="door_grace_minutes" value="{{ old('door_grace_minutes', $rule['door_grace_minutes'] ?? 0) }}" required>
                                    </div>

                                    <div class="field">
                                        <label>Severidad</label>
                                        <select name="severity" required>
                                            @foreach($severities as $key => $label)
                                                <option value="{{ $key }}" @selected(($rule['severity'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="field">
                                        <label>Cooldown</label>
                                        <input type="number" min="0" name="cooldown_minutes" value="{{ old('cooldown_minutes', $rule['cooldown_minutes'] ?? 30) }}" required>
                                    </div>

                                    <div class="field">
                                        <label>Estado</label>
                                        <div class="switch-row">
                                            <span>Evaluar regla</span>
                                            <label class="switch">
                                                <input type="checkbox" name="is_active" value="1" @checked($rule['is_active'] ?? false)>
                                                Activa
                                            </label>
                                        </div>
                                    </div>

                                    <div class="field full js-offline-warning">
                                        <div class="notice-box">
                                            Sensor sin señal requiere el verificador programado por EventBridge. Esta regla queda guardada, pero no se evalúa completamente desde el Stream de lecturas.
                                        </div>
                                    </div>

                                    <div class="field full">
                                        <label>Destinatarios</label>
                                        <textarea name="recipients" required>{{ old('recipients', implode("\n", (array) ($rule['recipients'] ?? []))) }}</textarea>
                                    </div>
                                </div>

                                <div class="actions">
                                    <button class="btn btn-primary" type="submit">Guardar cambios</button>
                                    <button class="btn btn-yellow" type="submit" form="toggle-{{ $rule['rule_id'] }}">
                                        {{ ($rule['is_active'] ?? false) ? 'Desactivar' : 'Activar' }}
                                    </button>
                                    <button class="btn btn-red" type="button" data-delete-rule-trigger data-delete-form="delete-{{ $rule['rule_id'] }}" data-rule-name="{{ $rule['alarm_name'] ?? 'Regla sin nombre' }}" >Eliminar</button>
                                </div>
                            </form>

                            <form id="toggle-{{ $rule['rule_id'] }}" method="POST" action="{{ route('iot.settings.alarms.toggle', $rule['rule_id']) }}">
                                @csrf
                            </form>

                            <form id="delete-{{ $rule['rule_id'] }}" method="POST" action="{{ route('iot.settings.alarms.destroy', $rule['rule_id']) }}">
                                @csrf
                                @method('DELETE')
                            </form>
                        </article>
                    @empty
                        <div class="empty">
                            Todavía no hay reglas configuradas. Creá la primera regla desde el panel lateral.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <aside>
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h3>Nueva regla</h3>
                        <p>Configurá una alerta operativa. Para cámaras frigoríficas, usá preferentemente “Rango de temperatura”.</p>
                    </div>
                </div>

                <div class="panel-body">
                    <form class="add-form alarm-form" method="POST" action="{{ route('iot.settings.alarms.store') }}">
                        @csrf

                        <div class="field">
                            <label>Nombre de alarma</label>
                            <input type="text" name="alarm_name" value="{{ old('alarm_name') }}" placeholder="Ej: Rango operativo Lácteos" required>
                        </div>

                        <div class="field">
                            <label>Cámara</label>
                            <select name="sensor_key" required>
                                @foreach($sensors as $sensor)
                                    <option value="{{ $sensor->sensor_key }}" @selected(old('sensor_key') === $sensor->sensor_key)>
                                        {{ $sensor->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Tipo de alarma</label>
                            <select name="alarm_type" class="alarm-type-select" required>
                                @foreach($alarmTypes as $key => $label)
                                    <option value="{{ $key }}" @selected(old('alarm_type', 'temperature_range') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="dynamic-help js-type-help"></div>
                        </div>

                        <div class="field js-field-metric">
                            <label>Métrica</label>
                            <select name="metric" required>
                                @foreach($metrics as $key => $label)
                                    <option value="{{ $key }}" @selected(old('metric', 'temperature') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-grid js-range-grid">
                            <div class="field js-field-range">
                                <label>Temperatura mínima permitida</label>
                                <input type="number" step="0.01" name="min_value" value="{{ old('min_value', 8) }}">
                            </div>

                            <div class="field js-field-range">
                                <label>Temperatura máxima permitida</label>
                                <input type="number" step="0.01" name="max_value" value="{{ old('max_value', 11) }}">
                            </div>
                        </div>

                        <div class="form-grid js-threshold-grid">
                            <div class="field js-field-operator">
                                <label>Operador</label>
                                <select name="condition_operator" required>
                                    @foreach($operators as $key => $label)
                                        <option value="{{ $key }}" @selected(old('condition_operator', '>') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field js-field-threshold">
                                <label class="js-threshold-label">Valor límite</label>
                                <input type="number" step="0.01" name="threshold_value" value="{{ old('threshold_value', 8) }}">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label class="js-duration-label">Durante minutos</label>
                                <input type="number" min="0" name="duration_minutes" value="{{ old('duration_minutes', 10) }}" required>
                            </div>

                            <div class="field">
                                <label>Cooldown min</label>
                                <input type="number" min="0" name="cooldown_minutes" value="{{ old('cooldown_minutes', 30) }}" required>
                            </div>
                        </div>

                        <div class="field">
                            <label>Condición de puerta</label>
                            <select name="door_condition" class="door-condition-select" required>
                                @foreach($doorConditions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('door_condition', 'closed') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Gracia post-cierre</label>
                            <input type="number" min="0" name="door_grace_minutes" value="{{ old('door_grace_minutes', 5) }}" required>
                        </div>

                        <div class="field">
                            <label>Severidad</label>
                            <select name="severity" required>
                                @foreach($severities as $key => $label)
                                    <option value="{{ $key }}" @selected(old('severity', 'warning') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field js-offline-warning">
                            <div class="notice-box">
                                Sensor sin señal requiere el verificador programado por EventBridge. Esta regla queda guardada, pero no se evalúa completamente desde el Stream de lecturas.
                            </div>
                        </div>

                        <div class="field">
                            <label>Destinatarios</label>
                            <textarea name="recipients" placeholder="supervisor@empresa.com&#10;calidad@empresa.com" required>{{ old('recipients') }}</textarea>
                        </div>

                        <label class="switch" style="justify-content:space-between; padding: 8px 0;">
                            Regla activa
                            <input type="checkbox" name="is_active" value="1" checked>
                        </label>

                        <button class="btn btn-green" type="submit">Crear regla en DynamoDB</button>
                    </form>
                </div>
            </section>

            <div class="side-note">
                <h4>Regla recomendada</h4>
                <p>Para cámaras frigoríficas usá “Rango de temperatura”: definís mínima y máxima permitida, y Lambda dispara por debajo o por encima del rango.</p>
            </div>

            <div class="side-note">
                <h4>Próximo paso</h4>
                <p>Después de validar la UI, conviene probar reglas controladas de batería baja, RSSI débil y puerta abierta.</p>
            </div>
        </aside>
    </div>
</div>


<div class="confirm-modal-backdrop" id="deleteRuleModal" aria-hidden="true">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteRuleModalTitle">
        <div class="confirm-modal-header">
            <div class="confirm-modal-icon">!</div>
            <div>
                <h3 class="confirm-modal-title" id="deleteRuleModalTitle">Eliminar regla</h3>
                <p class="confirm-modal-subtitle">
                    Esta acción eliminará la regla seleccionada. Lambda dejará de evaluarla y no se generarán nuevas alarmas con esta configuración.
                </p>
            </div>
        </div>

        <div class="confirm-modal-body">
            <div class="confirm-rule-name" id="deleteRuleName">Regla seleccionada</div>
            <div class="confirm-modal-warning">
                Si la regla ya generó eventos históricos, esos eventos no se eliminan; solo se elimina la configuración futura.
            </div>
        </div>

        <div class="confirm-modal-actions">
            <button type="button" class="confirm-modal-btn" id="cancelDeleteRule">Cancelar</button>
            <button type="button" class="confirm-modal-btn danger" id="confirmDeleteRule">Eliminar regla</button>
        </div>
    </div>
</div>


<script>

(function () {
    const modal = document.getElementById('deleteRuleModal');
    const ruleName = document.getElementById('deleteRuleName');
    const cancelBtn = document.getElementById('cancelDeleteRule');
    const confirmBtn = document.getElementById('confirmDeleteRule');

    if (!modal || !ruleName || !cancelBtn || !confirmBtn) {
        return;
    }

    let selectedFormId = null;

    const openModal = (formId, name) => {
        selectedFormId = formId;
        ruleName.textContent = name || 'Regla seleccionada';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Eliminar regla';
        confirmBtn.focus();
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        selectedFormId = null;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Eliminar regla';
        document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-delete-rule-trigger]').forEach(button => {
        button.addEventListener('click', () => {
            openModal(button.dataset.deleteForm, button.dataset.ruleName);
        });
    });

    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', event => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    confirmBtn.addEventListener('click', () => {
        if (!selectedFormId) {
            closeModal();
            return;
        }

        const form = document.getElementById(selectedFormId);

        if (form) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Eliminando...';
            form.submit();
        } else {
            closeModal();
        }
    });
})();


(function () {
    const defaults = {
        temperature_range: {
            metric: 'temperature',
            operator: '>',
            threshold: '',
            door: 'closed',
            thresholdLabel: 'Valor límite',
            durationLabel: 'Durante minutos',
            help: 'Definí un rango permitido. Lambda dispara si la temperatura baja del mínimo o supera el máximo.'
        },
        temperature_high: {
            metric: 'temperature',
            operator: '>',
            threshold: 11,
            door: 'closed',
            thresholdLabel: 'Temperatura máxima permitida',
            durationLabel: 'Durante minutos',
            help: 'Dispara cuando la temperatura supera el valor límite.'
        },
        temperature_low: {
            metric: 'temperature',
            operator: '<',
            threshold: 8,
            door: 'closed',
            thresholdLabel: 'Temperatura mínima permitida',
            durationLabel: 'Durante minutos',
            help: 'Dispara cuando la temperatura baja del valor límite.'
        },
        door_open_too_long: {
            metric: 'door_open',
            operator: '=',
            threshold: 1,
            door: 'open',
            thresholdLabel: 'Puerta abierta',
            durationLabel: 'Tiempo máximo abierta',
            help: 'Dispara si la puerta permanece abierta durante el tiempo indicado.'
        },
        battery_low: {
            metric: 'battery',
            operator: '<',
            threshold: 20,
            door: 'any',
            thresholdLabel: 'Batería mínima permitida (%)',
            durationLabel: 'Durante minutos',
            help: 'Dispara si la batería baja del porcentaje definido.'
        },
        rssi_low: {
            metric: 'rssi',
            operator: '<',
            threshold: -105,
            door: 'any',
            thresholdLabel: 'RSSI mínimo aceptable (dBm)',
            durationLabel: 'Durante minutos',
            help: 'Dispara si la señal RSSI cae por debajo del nivel definido.'
        },
        sensor_offline: {
            metric: 'last_seen_minutes',
            operator: '>',
            threshold: 15,
            door: 'any',
            thresholdLabel: 'Minutos sin señal',
            durationLabel: 'Verificar cada',
            help: 'Esta alarma se completa con EventBridge: revisa sensores que dejaron de reportar.'
        }
    };

    function setSelectValue(select, value) {
        if (!select || value === undefined || value === null) return;
        const option = Array.from(select.options).find(item => item.value === String(value));
        if (option) select.value = String(value);
    }

    function setInputValue(input, value) {
        if (!input || value === undefined || value === null || value === '') return;
        if (input.value === '' || input.dataset.autoManaged === '1') {
            input.value = value;
            input.dataset.autoManaged = '1';
        }
    }

    function toggle(el, show) {
        if (!el) return;
        el.classList.toggle('is-hidden', !show);
    }

    function updateForm(form, applyDefaults = false) {
        const typeSelect = form.querySelector('.alarm-type-select');
        const type = typeSelect ? typeSelect.value : 'temperature_range';
        const config = defaults[type] || defaults.temperature_range;

        const isRange = type === 'temperature_range';
        const isOffline = type === 'sensor_offline';

        form.querySelectorAll('.js-field-range').forEach(el => toggle(el, isRange));
        form.querySelectorAll('.js-range-grid').forEach(el => toggle(el, isRange));
        form.querySelectorAll('.js-field-operator').forEach(el => toggle(el, !isRange));
        form.querySelectorAll('.js-field-threshold').forEach(el => toggle(el, !isRange));
        form.querySelectorAll('.js-threshold-grid').forEach(el => toggle(el, !isRange));
        form.querySelectorAll('.js-offline-warning').forEach(el => toggle(el, isOffline));

        const metric = form.querySelector('[name="metric"]');
        const operator = form.querySelector('[name="condition_operator"]');
        const threshold = form.querySelector('[name="threshold_value"]');
        const door = form.querySelector('[name="door_condition"]');

        if (applyDefaults) {
            setSelectValue(metric, config.metric);
            setSelectValue(operator, config.operator);
            setSelectValue(door, config.door);
            setInputValue(threshold, config.threshold);
        }

        form.querySelectorAll('.js-threshold-label').forEach(label => label.textContent = config.thresholdLabel);
        form.querySelectorAll('.js-duration-label').forEach(label => label.textContent = config.durationLabel);

        const help = form.querySelector('.js-type-help');
        if (help) help.textContent = config.help;

        if (type === 'door_open_too_long') {
            setSelectValue(metric, 'door_open');
            setSelectValue(operator, '=');
            setInputValue(threshold, 1);
            setSelectValue(door, 'open');
        }
    }

    document.querySelectorAll('.alarm-form').forEach(form => {
        updateForm(form, false);

        const typeSelect = form.querySelector('.alarm-type-select');
        if (typeSelect) {
            typeSelect.addEventListener('change', () => updateForm(form, true));
        }
    });
})();
</script>
</body>
</html>
