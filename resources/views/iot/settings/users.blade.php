<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios y Roles - IoT Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

        .page {
            width: min(1540px, calc(100% - 36px));
            margin: 0 auto;
            padding: 28px 0 42px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
        }

        .brand { display: flex; align-items: center; gap: 14px; }

        .brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--cyan), var(--purple));
            box-shadow: 0 0 34px rgba(34, 211, 238, 0.34);
            display: grid;
            place-items: center;
            font-weight: 900;
            color: white;
        }

        .brand h1 { margin: 0; font-size: 24px; letter-spacing: -0.03em; }
        .brand p { margin: 4px 0 0; color: var(--muted); font-size: 14px; }

        .nav { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; align-items: center; }
        .nav a, .nav button {
            color: #cbd5e1;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.68);
            border: 1px solid var(--border);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }
        .nav a:hover,
        .nav a.active,
        .nav button:hover {
            color: white;
            border-color: rgba(34, 211, 238, 0.55);
            background: rgba(14, 116, 144, 0.22);
        }

        .alert-success,
        .errors {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 18px;
            font-size: 14px;
            font-weight: 700;
        }
        .alert-success {
            border: 1px solid rgba(34, 197, 94, .25);
            background: rgba(34, 197, 94, .10);
            color: #bbf7d0;
        }
        .errors {
            border: 1px solid rgba(239, 68, 68, .25);
            background: rgba(239, 68, 68, .10);
            color: #fecaca;
        }
        .errors ul { margin: 8px 0 0; padding-left: 18px; }

        .hero {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 30px;
            background:
                linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(15, 23, 42, 0.68)),
                radial-gradient(circle at top right, rgba(34, 211, 238, 0.25), transparent 35%);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            margin-bottom: 18px;
        }
        .hero:before {
            content: "";
            position: absolute;
            inset: -120px -160px auto auto;
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, rgba(34, 211, 238, 0.22), transparent 62%);
            filter: blur(8px);
        }
        .hero-inner { position: relative; display: grid; grid-template-columns: 1.2fr .8fr; gap: 24px; align-items: center; }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #67e8f9;
            background: rgba(8, 145, 178, 0.14);
            border: 1px solid rgba(103, 232, 249, 0.22);
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 16px;
        }
        .pulse {
            width: 8px;
            height: 8px;
            background: var(--green);
            border-radius: 999px;
            box-shadow: 0 0 0 8px rgba(34, 197, 94, 0.12);
        }
        .hero h2 { margin: 0; font-size: clamp(34px, 4vw, 56px); line-height: .96; letter-spacing: -0.06em; }
        .hero h2 span { background: linear-gradient(90deg, #67e8f9, #a78bfa, #f0abfc); -webkit-background-clip: text; color: transparent; }
        .hero p { margin: 16px 0 0; color: #b6c6d8; font-size: 16px; line-height: 1.7; max-width: 760px; }

        .hero-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .hero-stat {
            padding: 18px;
            border-radius: 22px;
            background: rgba(2, 6, 23, 0.42);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }
        .hero-stat .value { font-size: 30px; font-weight: 900; letter-spacing: -0.04em; }
        .hero-stat .label { margin-top: 6px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .1em; }

        .subnav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .subnav a {
            text-decoration: none;
            color: #cbd5e1;
            padding: 11px 15px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .18);
            background: rgba(15, 23, 42, .68);
            font-weight: 900;
            font-size: 14px;
        }
        .subnav a.active,
        .subnav a:hover {
            color: white;
            border-color: rgba(34, 211, 238, .55);
            background: rgba(14, 116, 144, .22);
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(390px, .45fr);
            gap: 18px;
            align-items: start;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.24);
            overflow: hidden;
            backdrop-filter: blur(14px);
        }
        .panel-header {
            padding: 22px 24px 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
        }
        .panel-header h3 { margin: 0; font-size: 20px; letter-spacing: -.03em; }
        .panel-header p { margin: 6px 0 0; color: var(--muted); font-size: 13px; line-height: 1.55; }
        .panel-body { padding: 18px; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 7px 11px;
            border: 1px solid rgba(34, 211, 238, .22);
            color: #a5f3fc;
            background: rgba(34, 211, 238, .10);
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }
        .badge-green { border-color: rgba(34,197,94,.28); background: rgba(34,197,94,.10); color: #bbf7d0; }
        .badge-red { border-color: rgba(239,68,68,.28); background: rgba(239,68,68,.10); color: #fecaca; }
        .badge-purple { border-color: rgba(168,85,247,.28); background: rgba(168,85,247,.10); color: #e9d5ff; }
        .badge-yellow { border-color: rgba(245,158,11,.28); background: rgba(245,158,11,.10); color: #fde68a; }

        .users-list { display: grid; gap: 14px; }
        .user-card {
            border: 1px solid rgba(148, 163, 184, .16);
            background: rgba(2, 6, 23, .32);
            border-radius: 24px;
            padding: 18px;
            position: relative;
            overflow: hidden;
        }
        .user-card:after {
            content: "";
            position: absolute;
            width: 130px;
            height: 130px;
            right: -64px;
            top: -64px;
            background: rgba(34, 211, 238, .10);
            border-radius: 999px;
        }
        .user-head {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }
        .user-id { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .avatar {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-weight: 950;
            color: white;
            background: linear-gradient(135deg, rgba(34,211,238,.85), rgba(168,85,247,.75));
            box-shadow: 0 0 28px rgba(34,211,238,.22);
            flex: 0 0 auto;
        }
        .user-id strong { display: block; font-size: 17px; letter-spacing: -.02em; }
        .user-id small { display: block; color: var(--muted); font-size: 12px; margin-top: 3px; }
        .user-badges { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }

        .form-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .field.full { grid-column: 1 / -1; }
        .field label {
            display: block;
            font-size: 12px;
            color: #a8c7e8;
            font-weight: 800;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .field input,
        .field select {
            width: 100%;
            min-height: 44px;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 15px;
            color: var(--text);
            background: rgba(15, 23, 42, .72);
            outline: none;
            padding: 11px 12px;
            font-size: 14px;
        }
        .field input:focus,
        .field select:focus {
            border-color: rgba(34,211,238,.62);
            box-shadow: 0 0 0 4px rgba(34,211,238,.08);
        }
        .switch-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 8px; }
        .switch-row span { color: var(--muted); font-size: 13px; }
        .switch { display: flex; align-items: center; gap: 8px; color: #cbd5e1; font-size: 13px; font-weight: 800; }
        .switch input { width: 18px; height: 18px; accent-color: #22d3ee; }

        .actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        .btn {
            appearance: none;
            border: 1px solid var(--border);
            border-radius: 16px;
            color: var(--text);
            text-decoration: none;
            padding: 12px 15px;
            background: rgba(15,23,42,.72);
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            transition: .18s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
        .btn:hover { transform: translateY(-1px); border-color: rgba(34,211,238,.55); background: rgba(14,116,144,.18); }
        .btn-primary { background: linear-gradient(135deg, rgba(34,211,238,.28), rgba(168,85,247,.18)); border-color: rgba(34,211,238,.45); }
        .btn-green { background: rgba(34,197,94,.12); border-color: rgba(34,197,94,.35); color: #bbf7d0; }
        .btn-red { background: rgba(239,68,68,.10); border-color: rgba(239,68,68,.28); color: #fecaca; }
        .btn-yellow { background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.28); color: #fde68a; }

        .side-card {
            padding: 18px;
            border-radius: 24px;
            border: 1px solid rgba(148,163,184,.16);
            background: rgba(2,6,23,.32);
            margin-bottom: 14px;
        }
        .side-card h4 { margin: 0 0 8px; font-size: 15px; }
        .side-card p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.6; }
        .add-form { display: grid; gap: 12px; }

        .matrix-wrap {
            overflow-x: auto;
            border: 1px solid rgba(148, 163, 184, .14);
            border-radius: 22px;
            background: rgba(2, 6, 23, .28);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }
        th, td {
            padding: 13px 14px;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            text-align: center;
            font-size: 13px;
        }
        th:first-child, td:first-child { text-align: left; }
        th { color: #a8c7e8; text-transform: uppercase; letter-spacing: .08em; font-size: 11px; background: rgba(15, 23, 42, .72); }
        td { color: #dbeafe; }
        tr:last-child td { border-bottom: 0; }
        .check { color: #86efac; font-weight: 950; }
        .cross { color: #fca5a5; font-weight: 950; }

        @media (max-width: 1180px) {
            .hero-inner, .content-grid { grid-template-columns: 1fr; }
            .hero-stats { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 760px) {
            .page { width: min(100% - 22px, 1500px); }
            .topbar { align-items: flex-start; flex-direction: column; }
            .hero { padding: 22px; border-radius: 24px; }
            .hero-stats { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .panel-header { flex-direction: column; }
            .actions { justify-content: stretch; }
            .btn { width: 100%; }
            .user-head { flex-direction: column; align-items: flex-start; }
            .user-badges { justify-content: flex-start; }
        }
    
        .nav button.logout-btn {
            color: #cbd5e1;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.68);
            border: 1px solid var(--border, rgba(148, 163, 184, .22));
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }
        .nav button.logout-btn:hover {
            color: white;
            border-color: rgba(34, 211, 238, 0.55);
            background: rgba(14, 116, 144, 0.22);
        }
        .logout-form { margin: 0; }

    </style>
</head>
<body>

@php
    $users = $users ?? collect();
    $roles = $roles ?? \App\Models\User::roles();
    $stats = $stats ?? [
        'total' => $users->count(),
        'active' => $users->where('is_active', true)->count(),
        'admins' => $users->where('role', 'admin')->count(),
        'inactive' => $users->where('is_active', false)->count(),
    ];

    $permissionMatrix = $permissionMatrix ?? [
        'Ver Dashboard en vivo' => ['operario' => true, 'supervisor' => true, 'calidad' => true, 'admin' => true, 'registro_operativo' => false],
        'Puntos de medición' => ['operario' => true, 'supervisor' => true, 'calidad' => true, 'admin' => true, 'registro_operativo' => false],
        'Informes / Exportación legal' => ['operario' => false, 'supervisor' => false, 'calidad' => true, 'admin' => true, 'registro_operativo' => false],
        'Registro operativo' => ['operario' => true, 'supervisor' => true, 'calidad' => false, 'admin' => true, 'registro_operativo' => true],
        'Recibir Alertas' => ['operario' => true, 'supervisor' => true, 'calidad' => false, 'admin' => true, 'registro_operativo' => false],
        'Silenciar / Anotar Alarma' => ['operario' => true, 'supervisor' => true, 'calidad' => false, 'admin' => true, 'registro_operativo' => false],
        'Modificar Umbrales' => ['operario' => false, 'supervisor' => true, 'calidad' => false, 'admin' => true, 'registro_operativo' => false],
        'Calibrar / Alta de Sensores' => ['operario' => false, 'supervisor' => false, 'calidad' => false, 'admin' => true, 'registro_operativo' => false],
        'Gestión de Usuarios' => ['operario' => false, 'supervisor' => false, 'calidad' => false, 'admin' => true, 'registro_operativo' => false],
    ];

    $navUser = auth()->user();
    $roleOrder = ['operario', 'supervisor', 'calidad', 'admin', 'registro_operativo'];
@endphp

<div class="page">
    <div class="topbar">
        <div class="brand">
            <div class="brand-mark">👥</div>
            <div>
                <h1>Usuarios y Roles</h1>
                <p>Administración de accesos, permisos y usuarios operativos</p>
            </div>
        </div>

        <nav class="nav">
            @if($navUser?->canViewDashboard())
                <a href="{{ route('iot.dashboard') }}" class="{{ request()->routeIs('iot.dashboard') ? 'active' : '' }}">Dashboard</a>
            @endif
            @if($navUser?->canViewPoints())
                <a href="{{ route('iot.points') }}" class="{{ request()->routeIs('iot.points') ? 'active' : '' }}">Puntos de medición</a>
            @endif
            @if($navUser?->canViewReports())
                <a href="{{ route('iot.analytics') }}" class="{{ request()->routeIs('iot.analytics') ? 'active' : '' }}">Informes</a>
            @endif
            @if($navUser?->canUseOperationalRegister())
                <a href="{{ route('iot.operations') }}" class="{{ request()->routeIs('iot.operations') ? 'active' : '' }}">Registro operativo</a>
            @endif
            @if($navUser?->canManageSettings())
                <a href="{{ route('iot.settings') }}" class="{{ request()->routeIs('iot.settings') || request()->routeIs('iot.settings.*') ? 'active' : '' }}">Configuración</a>
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
                <?php foreach ($errors->all() as $error): ?>
                    <li>{{ $error }}</li>
                <?php endforeach; ?>
            </ul>
        </div>
    @endif

    <section class="hero">
        <div class="hero-inner">
            <div>
                <div class="eyebrow"><span class="pulse"></span> Control de accesos</div>
                <h2>Gestioná <span>usuarios y permisos</span></h2>
                <p>
                    Definí quién puede ver el dashboard, recibir alertas, modificar umbrales, exportar reportes legales o administrar usuarios. Esta base deja preparado el sistema para operar sin depender de Sistemas.
                </p>
            </div>

            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="value">{{ $stats['total'] ?? 0 }}</div>
                    <div class="label">Usuarios</div>
                </div>
                <div class="hero-stat">
                    <div class="value">{{ $stats['active'] ?? 0 }}</div>
                    <div class="label">Activos</div>
                </div>
                <div class="hero-stat">
                    <div class="value">{{ $stats['admins'] ?? 0 }}</div>
                    <div class="label">Admins</div>
                </div>
                <div class="hero-stat">
                    <div class="value">{{ $stats['inactive'] ?? 0 }}</div>
                    <div class="label">Inactivos</div>
                </div>
            </div>
        </div>
    </section>

    <div class="subnav">
        <a href="{{ route('iot.settings') }}">Sensores / Cámaras</a>
        <a href="{{ route('iot.settings.users') }}" class="active">Usuarios y Roles</a>
    </div>

    <div class="content-grid">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Usuarios del sistema</h3>
                    <p>Editá datos, rol, estado y contraseña. El usuario inactivo no podrá ingresar aunque tenga credenciales correctas.</p>
                </div>
                <span class="badge">RBAC operativo</span>
            </div>

            <div class="panel-body">
                <div class="users-list">
                    <?php foreach ($users as $user): ?>
                        @php
                            $initials = collect(explode(' ', trim((string) $user->name)))
                                ->filter()
                                ->take(2)
                                ->map(fn($p) => mb_substr($p, 0, 1))
                                ->implode('');

                            $roleClass = match($user->role) {
                                'admin' => 'badge-purple',
                                'supervisor' => 'badge-yellow',
                                'calidad' => 'badge-green',
                                'registro_operativo' => 'badge-blue',
                                default => '',
                            };
                        @endphp

                        <article class="user-card">
                            <div class="user-head">
                                <div class="user-id">
                                    <div class="avatar">{{ strtoupper($initials ?: 'U') }}</div>
                                    <div>
                                        <strong>{{ $user->name }}</strong>
                                        <small>
                                            {{ $user->email }} · Último acceso:
                                            {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Sin registro' }}
                                        </small>
                                    </div>
                                </div>

                                <div class="user-badges">
                                    <span class="badge {{ $roleClass }}">{{ method_exists($user, 'roleLabel') ? $user->roleLabel() : ($roles[$user->role] ?? ucfirst($user->role)) }}</span>
                                    <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-red' }}">
                                        {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('iot.settings.users.update', $user) }}">
                                @csrf

                                <div class="form-grid">
                                    <div class="field">
                                        <label>Nombre</label>
                                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                                    </div>

                                    <div class="field">
                                        <label>Email</label>
                                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    </div>

                                    <div class="field">
                                        <label>Rol</label>
                                        <select name="role" required>
                                            <?php foreach ($roles as $roleKey => $roleLabel): ?>
                                                <option value="{{ $roleKey }}" @selected(old('role', $user->role) === $roleKey)>
                                                    {{ $roleLabel }}
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field">
                                        <label>Estado</label>
                                        <div class="switch-row">
                                            <span>Permitir acceso</span>
                                            <label class="switch">
                                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                                                Activo
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="actions">
                                    <button class="btn btn-primary" type="submit">Guardar usuario</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('iot.settings.users.password', $user) }}" style="margin-top: 14px;">
                                @csrf

                                <div class="form-grid">
                                    <div class="field">
                                        <label>Nueva contraseña</label>
                                        <input type="password" name="password" placeholder="Mínimo 8 caracteres">
                                    </div>

                                    <div class="field">
                                        <label>Confirmar contraseña</label>
                                        <input type="password" name="password_confirmation" placeholder="Repetir contraseña">
                                    </div>
                                </div>

                                <div class="actions">
                                    <button class="btn" type="submit">Actualizar contraseña</button>
                                </div>
                            </form>

                            @if (auth()->id() !== $user->id)
                                <form method="POST" action="{{ route('iot.settings.users.toggle', $user) }}" style="margin-top: 10px;">
                                    @csrf

                                    <div class="actions" style="margin-top:0;">
                                        <button class="btn {{ $user->is_active ? 'btn-red' : 'btn-green' }}" type="submit">
                                            {{ $user->is_active ? 'Desactivar usuario' : 'Activar usuario' }}
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </article>
                    <?php endforeach; ?>

                    @if($users->isEmpty())
                        <article class="user-card">
                            <div class="user-head">
                                <div class="user-id">
                                    <div class="avatar">!</div>
                                    <div>
                                        <strong>No hay usuarios cargados</strong>
                                        <small>Creá el primer usuario desde el panel lateral.</small>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endif
                </div>
            </div>
        </section>

        <aside>
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h3>Nuevo usuario</h3>
                        <p>Creá accesos para operarios, supervisores, calidad, administradores o carga operativa.</p>
                    </div>
                </div>

                <div class="panel-body">
                    <form class="add-form" method="POST" action="{{ route('iot.settings.users.store') }}">
                        @csrf

                        <div class="field">
                            <label>Nombre</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Juan Pérez" required>
                        </div>

                        <div class="field">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="usuario@empresa.com" required>
                        </div>

                        <div class="field">
                            <label>Rol</label>
                            <select name="role" required>
                                <?php foreach ($roles as $roleKey => $roleLabel): ?>
                                    <option value="{{ $roleKey }}" @selected(old('role', 'operario') === $roleKey)>
                                        {{ $roleLabel }}
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label>Contraseña</label>
                            <input type="password" name="password" placeholder="Mínimo 8 caracteres" required>
                        </div>

                        <div class="field">
                            <label>Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" placeholder="Repetir contraseña" required>
                        </div>

                        <label class="switch" style="justify-content:space-between; padding: 10px 0;">
                            Usuario activo
                            <input type="checkbox" name="is_active" value="1" checked>
                        </label>

                        <button class="btn btn-green" type="submit">Crear usuario</button>
                    </form>
                </div>
            </section>

            <div class="side-card">
                <h4>Permisos por rol</h4>
                <p>La lógica actual coincide con la matriz definida para operación, calidad y administración.</p>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h3>Matriz de permisos</h3>
                        <p>Referencia rápida para validar capacidades por rol.</p>
                    </div>
                </div>

                <div class="panel-body">
                    <div class="matrix-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Funcionalidad</th>
                                    <th>Operario</th>
                                    <th>Supervisor</th>
                                    <th>Calidad</th>
                                    <th>Admin</th>
                                    <th>Registro operativo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permissionMatrix as $feature => $permissions): ?>
                                    <tr>
                                        <td>{{ $feature }}</td>
                                        <?php foreach ($roleOrder as $role): ?>
                                            <td class="{{ ($permissions[$role] ?? false) ? 'check' : 'cross' }}">
                                                {{ ($permissions[$role] ?? false) ? '✅' : '❌' }}
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
</body>
</html>
