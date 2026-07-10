<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - IoT Dashboard</title>
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
            width: min(1500px, calc(100% - 36px));
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

        .nav { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
        .nav a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.68);
            border: 1px solid var(--border);
            font-size: 14px;
            font-weight: 700;
        }
        .nav a:hover,
        .nav a.active {
            color: white;
            border-color: rgba(34, 211, 238, 0.55);
            background: rgba(14, 116, 144, 0.22);
        }


        .settings-subnav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin: 18px 0 18px;
        }
        .settings-subnav a {
            color: #dbeafe;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.74);
            border: 1px solid rgba(148, 163, 184, 0.18);
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.18);
        }
        .settings-subnav a:hover,
        .settings-subnav a.active {
            color: white;
            border-color: rgba(34, 211, 238, 0.75);
            background: linear-gradient(135deg, rgba(14, 116, 144, 0.34), rgba(15, 23, 42, 0.82));
            box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.08);
        }

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

        .hero-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .hero-stat {
            padding: 18px;
            border-radius: 22px;
            background: rgba(2, 6, 23, 0.42);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }
        .hero-stat .value { font-size: 30px; font-weight: 900; letter-spacing: -0.04em; }
        .hero-stat .label { margin-top: 6px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .1em; }

        .alert-success {
            margin-bottom: 18px;
            border: 1px solid rgba(34, 197, 94, .25);
            background: rgba(34, 197, 94, .10);
            color: #bbf7d0;
            padding: 14px 16px;
            border-radius: 18px;
            font-size: 14px;
            font-weight: 700;
        }

        .settings-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(360px, .5fr); gap: 18px; align-items: start; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.24);
            overflow: hidden;
            backdrop-filter: blur(14px);
        }
        .panel-header { padding: 22px 24px 16px; display:flex; justify-content:space-between; align-items:flex-start; gap:14px; border-bottom: 1px solid rgba(148, 163, 184, .12); }
        .panel-header h3 { margin:0; font-size:20px; letter-spacing:-.03em; }
        .panel-header p { margin:6px 0 0; color:var(--muted); font-size:13px; line-height:1.55; }
        .panel-body { padding: 18px; }

        .sensor-list { display: grid; gap: 14px; }
        .sensor-card {
            border: 1px solid rgba(148, 163, 184, .16);
            background: rgba(2, 6, 23, .32);
            border-radius: 24px;
            padding: 18px;
            position: relative;
            overflow: hidden;
        }
        .sensor-card:after {
            content: "";
            position: absolute;
            width: 130px;
            height: 130px;
            right: -64px;
            top: -64px;
            background: rgba(34, 211, 238, .10);
            border-radius: 999px;
        }
        .sensor-head { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; }
        .sensor-id { display:flex; align-items:center; gap:10px; min-width:0; }
        .sensor-dot { width: 12px; height: 12px; border-radius:999px; background:var(--green); box-shadow: 0 0 0 8px rgba(34,197,94,.10); }
        .sensor-id strong { display:block; font-size:16px; letter-spacing:-.02em; }
        .sensor-id small { color:var(--muted); font-size:12px; }
        .badge {
            display:inline-flex; align-items:center; gap:7px; border-radius:999px; padding:7px 11px;
            border:1px solid rgba(34,211,238,.22); color:#a5f3fc; background:rgba(34,211,238,.10); font-size:12px; font-weight:800;
        }
        .form-grid { position:relative; z-index:1; display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; }
        .field.full { grid-column: 1 / -1; }
        .field label { display:block; font-size:12px; color:#a8c7e8; font-weight:800; margin-bottom:7px; text-transform:uppercase; letter-spacing:.08em; }
        .field input, .field textarea, .field select {
            width:100%; min-height:44px; border:1px solid rgba(148,163,184,.18); border-radius:15px;
            color:var(--text); background:rgba(15,23,42,.72); outline:none; padding:11px 12px; font-size:14px;
        }
        .field textarea { min-height:78px; resize:vertical; }
        .field input:focus, .field textarea:focus, .field select:focus { border-color:rgba(34,211,238,.62); box-shadow:0 0 0 4px rgba(34,211,238,.08); }
        .field input[readonly] { color:#94a3b8; background:rgba(2,6,23,.42); cursor:not-allowed; }
        .switch-row { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 0 0; position:relative; z-index:1; }
        .switch-row span { color:var(--muted); font-size:13px; }
        .switch { display:flex; align-items:center; gap:8px; color:#cbd5e1; font-size:13px; font-weight:800; }
        .switch input { width:18px; height:18px; accent-color:#22d3ee; }

        .actions { margin-top:18px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; }
        .btn {
            appearance:none; border:1px solid var(--border); border-radius:16px; color:var(--text); text-decoration:none;
            padding:12px 15px; background:rgba(15,23,42,.72); font-size:14px; font-weight:900; cursor:pointer;
            transition:.18s ease; display:inline-flex; align-items:center; gap:8px;
        }
        .btn:hover { transform: translateY(-1px); border-color:rgba(34,211,238,.55); background:rgba(14,116,144,.18); }
        .btn-primary { background:linear-gradient(135deg, rgba(34,211,238,.28), rgba(168,85,247,.18)); border-color:rgba(34,211,238,.45); }
        .btn-green { background:rgba(34,197,94,.12); border-color:rgba(34,197,94,.35); color:#bbf7d0; }

        .side-card { padding: 18px; border-radius:24px; border:1px solid rgba(148,163,184,.16); background:rgba(2,6,23,.32); margin-bottom:14px; }
        .side-card h4 { margin:0 0 8px; font-size:15px; }
        .side-card p { margin:0; color:var(--muted); font-size:13px; line-height:1.6; }
        .mini-list { display:grid; gap:10px; margin-top:12px; }
        .mini-item { display:flex; justify-content:space-between; gap:12px; padding:11px 12px; border-radius:16px; background:rgba(15,23,42,.55); border:1px solid rgba(148,163,184,.12); color:#cbd5e1; font-size:13px; }
        .mini-item strong { color:var(--text); }

        .add-form { display:grid; gap:12px; }
        .danger-note { border-color:rgba(245,158,11,.22); background:rgba(245,158,11,.08); color:#fde68a; }
        .errors { margin-bottom:18px; padding:14px 16px; border-radius:18px; border:1px solid rgba(239,68,68,.25); background:rgba(239,68,68,.10); color:#fecaca; }
        .errors ul { margin:8px 0 0; padding-left:18px; }

        @media (max-width: 1180px) {
            .hero-inner, .settings-grid { grid-template-columns: 1fr; }
            .hero-stats { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 760px) {
            .topbar { align-items:flex-start; flex-direction:column; }
            .page { width: min(100% - 22px, 1500px); }
    
        .settings-subnav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin: 18px 0 18px;
        }
        .settings-subnav a {
            color: #dbeafe;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.74);
            border: 1px solid rgba(148, 163, 184, 0.18);
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.18);
        }
        .settings-subnav a:hover,
        .settings-subnav a.active {
            color: white;
            border-color: rgba(34, 211, 238, 0.75);
            background: linear-gradient(135deg, rgba(14, 116, 144, 0.34), rgba(15, 23, 42, 0.82));
            box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.08);
        }

        .hero { padding:22px; border-radius:24px; }
            .hero-stats { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .settings-grid { grid-template-columns: 1fr; }
            .panel-header { flex-direction:column; }
            .actions { justify-content:stretch; }
            .btn { justify-content:center; width:100%; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div class="brand">
            <div class="brand-mark">⚙</div>
            <div>
                <h1>Configuración</h1>
                <p>Administración funcional del sistema de monitoreo IoT</p>
            </div>
        </div>
        <nav class="nav">
            <a href="{{ route('iot.dashboard') }}">Dashboard</a>
            <a href="{{ route('iot.points') }}">Puntos de medición</a>
            <a href="{{ route('iot.analytics') }}">Análisis de informes</a>
            <a href="{{ route('iot.settings') }}" class="active">Configuración</a>
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
                <div class="eyebrow"><span class="pulse"></span> Centro de administración</div>
                <h2>Configurá tus <span>cámaras y sensores</span></h2>
                <p>
                    Mantené fijo el identificador técnico que llega desde DynamoDB, pero definí nombres visibles, ubicaciones y umbrales operativos para que el dashboard sea más claro para usuarios, auditores y clientes.
                </p>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="value">{{ $sensors->count() }}</div>
                    <div class="label">Sensores</div>
                </div>
                <div class="hero-stat">
                    <div class="value">{{ $activeSensors }}</div>
                    <div class="label">Activos</div>
                </div>
                <div class="hero-stat">
                    <div class="value">{{ $configuredNames }}</div>
                    <div class="label">Renombrados</div>
                </div>
            </div>
        </div>
    </section>


    @php
        $settingsUser = auth()->user();
        $settingsIsAdmin = $settingsUser && method_exists($settingsUser, 'isAdmin') && $settingsUser->isAdmin();

        $canShowUsers = $settingsIsAdmin || ($settingsUser && method_exists($settingsUser, 'canManageUsers') && $settingsUser->canManageUsers());
        $canShowAlarms = $settingsIsAdmin || ($settingsUser && method_exists($settingsUser, 'canManageAlarms') && $settingsUser->canManageAlarms()) || ($settingsUser && method_exists($settingsUser, 'canModifyThresholds') && $settingsUser->canModifyThresholds());
        $canShowOperations = $settingsIsAdmin || ($settingsUser && method_exists($settingsUser, 'canUseOperationalRegister') && $settingsUser->canUseOperationalRegister());
    @endphp

    <nav class="settings-subnav">
        <a href="{{ route('iot.settings') }}" class="active">Sensores / Cámaras</a>

        @if($canShowUsers)
            <a href="{{ route('iot.settings.users') }}">Usuarios y Roles</a>
        @endif

        @if($canShowAlarms && \Illuminate\Support\Facades\Route::has('iot.settings.alarms'))
            <a href="{{ route('iot.settings.alarms') }}">Alertas por cámara</a>
        @endif

        @if($canShowOperations && \Illuminate\Support\Facades\Route::has('iot.operations'))
            <a href="{{ route('iot.operations') }}">Registro operativo</a>
        @endif
    </nav>

    <div class="settings-grid">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Sensores / Cámaras</h3>
                    <p>El ID técnico no se modifica. El nombre visible se reutilizará luego en dashboard, puntos de medición, análisis y exportaciones.</p>
                </div>
                <span class="badge">Mapeo visual</span>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('iot.settings.sensors.update') }}">
                    @csrf
                    <div class="sensor-list">
                        @foreach ($sensors as $sensor)
                            <article class="sensor-card">
                                <div class="sensor-head">
                                    <div class="sensor-id">
                                        <span class="sensor-dot" style="background: {{ $sensor->is_active ? 'var(--green)' : 'var(--red)' }}; box-shadow: 0 0 0 8px {{ $sensor->is_active ? 'rgba(34,197,94,.10)' : 'rgba(239,68,68,.10)' }};"></span>
                                        <div>
                                            <strong>{{ $sensor->display_name }}</strong>
                                            <small>ID técnico: {{ $sensor->sensor_key }}</small>
                                        </div>
                                    </div>
                                    <span class="badge">{{ $sensor->is_active ? 'Activo' : 'Inactivo' }}</span>
                                </div>

                                <input type="hidden" name="sensors[{{ $loop->index }}][id]" value="{{ $sensor->id }}">

                                <div class="form-grid">
                                    <div class="field">
                                        <label>ID técnico</label>
                                        <input type="text" value="{{ $sensor->sensor_key }}" readonly>
                                    </div>
                                    <div class="field">
                                        <label>Nombre visible</label>
                                        <input type="text" name="sensors[{{ $loop->index }}][display_name]" value="{{ old('sensors.' . $loop->index . '.display_name', $sensor->display_name) }}" required>
                                    </div>
                                    <div class="field">
                                        <label>Ubicación</label>
                                        <input type="text" name="sensors[{{ $loop->index }}][location]" value="{{ old('sensors.' . $loop->index . '.location', $sensor->location) }}" placeholder="Ej: Planta Laferrere / Cámara congelados">
                                    </div>
                                    <div class="field">
                                        <label>Tipo</label>
                                        <input type="text" name="sensors[{{ $loop->index }}][camera_type]" value="{{ old('sensors.' . $loop->index . '.camera_type', $sensor->camera_type) }}" placeholder="Ej: Frío positivo / Congelados">
                                    </div>
                                    <div class="field">
                                        <label>Alarma mínima °C</label>
                                        <input type="number" step="0.1" name="sensors[{{ $loop->index }}][alarm_min]" value="{{ old('sensors.' . $loop->index . '.alarm_min', $sensor->alarm_min) }}" placeholder="Ej: -2">
                                    </div>
                                    <div class="field">
                                        <label>Alarma máxima °C</label>
                                        <input type="number" step="0.1" name="sensors[{{ $loop->index }}][alarm_max]" value="{{ old('sensors.' . $loop->index . '.alarm_max', $sensor->alarm_max) }}" placeholder="Ej: 8">
                                    </div>
                                    <div class="field">
                                        <label>Orden</label>
                                        <input type="number" min="0" max="999" name="sensors[{{ $loop->index }}][display_order]" value="{{ old('sensors.' . $loop->index . '.display_order', $sensor->display_order) }}">
                                    </div>
                                    <div class="field">
                                        <label>Estado</label>
                                        <div class="switch-row" style="padding-top: 8px;">
                                            <span>Mostrar en el sistema</span>
                                            <label class="switch">
                                                <input type="checkbox" name="active_sensors[]" value="{{ $sensor->id }}" @checked($sensor->is_active)>
                                                Activo
                                            </label>
                                        </div>
                                    </div>
                                    <div class="field full">
                                        <label>Descripción</label>
                                        <textarea name="sensors[{{ $loop->index }}][description]" placeholder="Detalle interno para identificar mejor este punto de medición">{{ old('sensors.' . $loop->index . '.description', $sensor->description) }}</textarea>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="actions">
                        <a class="btn" href="{{ route('iot.dashboard') }}">Volver al dashboard</a>
                        <button class="btn btn-primary" type="submit">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </section>

        <aside>
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h3>Nuevo sensor</h3>
                        <p>Usalo si DynamoDB empieza a enviar un nuevo ID técnico.</p>
                    </div>
                </div>
                <div class="panel-body">
                    <form class="add-form" method="POST" action="{{ route('iot.settings.sensors.store') }}">
                        @csrf
                        <div class="field">
                            <label>ID técnico</label>
                            <input type="text" name="sensor_key" placeholder="Ej: camara_4" required>
                        </div>
                        <div class="field">
                            <label>Nombre visible</label>
                            <input type="text" name="display_name" placeholder="Ej: Cámara Expedición" required>
                        </div>
                        <div class="field">
                            <label>Ubicación</label>
                            <input type="text" name="location" placeholder="Ej: Planta 1 / Sector despacho">
                        </div>
                        <div class="field">
                            <label>Tipo</label>
                            <input type="text" name="camera_type" placeholder="Ej: Frío positivo">
                        </div>
                        <button class="btn btn-green" type="submit">Agregar sensor</button>
                    </form>
                </div>
            </section>

            <div class="side-card danger-note">
                <h4>Regla importante</h4>
                <p>No cambies el ID técnico que llega desde DynamoDB. Ese valor funciona como clave para unir lecturas con nombres amigables.</p>
            </div>

            <div class="side-card">
                <h4>Mapa de uso futuro</h4>
                <p>Este módulo queda preparado para usuarios, roles, clientes, umbrales de alarma y notificaciones.</p>
                <div class="mini-list">
                    <div class="mini-item"><span>Usuarios y roles</span><strong>Disponible</strong></div>
                    <div class="mini-item"><span>Clientes / sucursales</span><strong>Próximo</strong></div>
                    <div class="mini-item"><span>Alertas por cámara</span><strong>Disponible</strong></div>
                    <div class="mini-item"><span>Registro operativo</span><strong>Disponible</strong></div>
                </div>
            </div>

            <div class="side-card">
                <h4>Cómo se verá</h4>
                <p>Donde hoy aparece <strong>camara_1</strong>, después mostraremos el nombre visible configurado, por ejemplo <strong>Cámara Congelados</strong>.</p>
            </div>
        </aside>
    </div>
</div>
</body>
</html>
