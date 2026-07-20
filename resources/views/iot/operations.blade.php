<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro operativo - IoT Dashboard</title>
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
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 0% 0%, rgba(34, 211, 238, .14), transparent 35%),
                radial-gradient(circle at 100% 0%, rgba(168, 85, 247, .16), transparent 34%),
                linear-gradient(135deg, #06101d, #0b1024 55%, #11172f);
            color: var(--text);
        }

        .page { width: min(1480px, calc(100% - 48px)); margin: 0 auto; padding: 34px 0 48px; }

        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 26px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-mark {
            width: 54px; height: 54px; border-radius: 18px; display: grid; place-items: center;
            background: linear-gradient(135deg, var(--cyan), var(--purple)); color: #fff; font-weight: 900;
            box-shadow: 0 18px 40px rgba(34, 211, 238, .15);
        }
        .brand h1 { margin: 0; font-size: 28px; letter-spacing: -0.04em; }
        .brand p { margin: 3px 0 0; color: #9fb3d1; font-size: 14px; }

        .nav { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; align-items: center; }
        .nav a, .nav button {
            color: #dbeafe; text-decoration: none; border: 1px solid var(--border); background: rgba(15,23,42,.55);
            padding: 11px 16px; border-radius: 14px; font-weight: 800; font-size: 13px; cursor: pointer;
        }
        .nav a:hover, .nav a.active, .nav button:hover { border-color: rgba(34,211,238,.85); box-shadow: inset 0 0 0 1px rgba(34,211,238,.18); }

        .hero {
            border: 1px solid var(--border); background: linear-gradient(135deg, rgba(15,23,42,.92), rgba(8,47,73,.58));
            border-radius: 28px; padding: 34px; margin-bottom: 20px; overflow: hidden; position: relative;
        }
        .hero::after { content: ""; position: absolute; inset: -80px -40px auto auto; width: 320px; height: 320px; border-radius: 50%; background: rgba(34,211,238,.16); filter: blur(6px); }
        .hero-inner { position: relative; z-index: 1; display: grid; grid-template-columns: 1.35fr .65fr; gap: 26px; align-items: center; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid rgba(34,211,238,.28); color: #67e8f9; background: rgba(34,211,238,.09); border-radius: 999px; font-size: 12px; font-weight: 900; }
        .pulse { width: 9px; height: 9px; border-radius: 50%; background: var(--green); box-shadow: 0 0 0 8px rgba(34,197,94,.12); }
        .hero h2 { margin: 18px 0 12px; font-size: clamp(36px, 4vw, 58px); line-height: .96; letter-spacing: -0.07em; }
        .hero h2 span { background: linear-gradient(90deg, #67e8f9, #a78bfa, #f0abfc); -webkit-background-clip: text; color: transparent; }
        .hero p { margin: 0; max-width: 860px; color: #c4d3ea; line-height: 1.65; }
        .hero-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .hero-stat { border: 1px solid var(--border); background: rgba(2,6,23,.35); border-radius: 20px; padding: 18px; }
        .hero-stat .value { font-size: 32px; font-weight: 950; }
        .hero-stat .label { color: var(--muted); text-transform: uppercase; letter-spacing: .08em; font-size: 11px; }

        .grid { display: grid; grid-template-columns: 1fr 1.05fr; gap: 20px; align-items: start; }
        .panel { border: 1px solid var(--border); background: var(--panel); border-radius: 26px; overflow: hidden; box-shadow: 0 24px 80px rgba(0,0,0,.18); }
        .panel-header { padding: 22px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; gap: 16px; align-items: center; }
        .panel-header h3 { margin: 0; font-size: 21px; letter-spacing: -0.03em; }
        .panel-header p { margin: 4px 0 0; color: var(--muted); font-size: 13px; line-height: 1.45; }
        .panel-body { padding: 22px 24px; }

        .form-grid { display: grid; gap: 16px; }
        .field label { display: block; margin-bottom: 8px; color: #bcd2ef; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }
        input, select, textarea {
            width: 100%; border-radius: 16px; border: 1px solid rgba(148,163,184,.20); background: rgba(2,6,23,.35);
            color: var(--text); padding: 14px 15px; outline: none; font-weight: 750;
        }
        textarea { min-height: 126px; resize: vertical; line-height: 1.5; }
        input:focus, select:focus, textarea:focus { border-color: rgba(34,211,238,.8); box-shadow: 0 0 0 4px rgba(34,211,238,.10); }
        option { background: #0b1220; color: #e5edf8; }

        .btn { border: 1px solid rgba(34,211,238,.55); background: rgba(34,211,238,.12); color: #e0faff; padding: 13px 18px; border-radius: 16px; font-weight: 950; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn:hover { border-color: rgba(34,211,238,.95); transform: translateY(-1px); }
        .btn-primary { background: linear-gradient(135deg, rgba(34,211,238,.28), rgba(168,85,247,.22)); }
        .actions { display: flex; gap: 12px; align-items: center; justify-content: flex-end; margin-top: 18px; }

        .alert-success { margin: 0 0 20px; border: 1px solid rgba(34,197,94,.30); background: rgba(34,197,94,.10); color: #bbf7d0; padding: 14px 16px; border-radius: 18px; font-weight: 800; }
        .errors { margin: 0 0 20px; border: 1px solid rgba(239,68,68,.30); background: rgba(239,68,68,.10); color: #fecaca; padding: 14px 16px; border-radius: 18px; }

        .event-list { display: grid; gap: 12px; max-height: 640px; overflow: auto; padding-right: 6px; }
        .event-card { border: 1px solid rgba(148,163,184,.14); background: rgba(2,6,23,.30); border-radius: 18px; padding: 15px; display: grid; gap: 8px; }
        .event-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .event-title { font-weight: 950; color: #f8fafc; }
        .event-meta { color: var(--muted); font-size: 12px; line-height: 1.45; }
        .event-notes { color: #cbd5e1; font-size: 13px; line-height: 1.45; }
        .badge { display: inline-flex; align-items: center; justify-content: center; padding: 7px 10px; border-radius: 999px; font-size: 11px; font-weight: 950; border: 1px solid rgba(34,211,238,.26); color: #a5f3fc; background: rgba(34,211,238,.09); white-space: nowrap; }
        .muted { color: var(--muted); }

        .quick-reasons { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 8px; }
        .reason-chip { border: 1px solid rgba(148,163,184,.16); background: rgba(2,6,23,.25); color: #cbd5e1; border-radius: 14px; padding: 10px 12px; font-size: 12px; font-weight: 800; }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: rgba(15,23,42,.50); border-radius: 999px; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(180deg, rgba(34,211,238,.75), rgba(168,85,247,.65)); border-radius: 999px; border: 2px solid rgba(15,23,42,.8); }

        @media (max-width: 980px) { .hero-inner, .grid { grid-template-columns: 1fr; } .hero-stats { grid-template-columns: 1fr; } .topbar { align-items: flex-start; flex-direction: column; } }
    
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
<div class="page">
    <div class="topbar">
        <div class="brand">
            <div class="brand-mark"><img src="{{ asset('images/logo-iot.png') }}" alt="Logo IoT"></div>
            <div>
                <h1>Registro operativo</h1>
                <p>Carga manual de motivos de apertura, limpieza, reposición y mantenimiento</p>
            </div>
        </div>
        <nav class="nav">
            @php($navUser = auth()->user())
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
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="hero">
        <div class="hero-inner">
            <div>
                <div class="eyebrow"><span class="pulse"></span> Trazabilidad manual</div>
                <h2>Registrá el <span>motivo operativo</span></h2>
                <p>
                    Cada apertura o intervención queda vinculada a una cámara, un usuario y una fecha. Más adelante estos registros se podrán cruzar con eventos automáticos de puerta, temperatura e informes legales.
                </p>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><div class="value">{{ $stats['today'] }}</div><div class="label">Hoy</div></div>
                <div class="hero-stat"><div class="value">{{ $stats['week'] }}</div><div class="label">Últimos 7 días</div></div>
                <div class="hero-stat"><div class="value">{{ $stats['users'] }}</div><div class="label">Usuarios activos</div></div>
            </div>
        </div>
    </section>

    <div class="grid">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Nuevo evento operativo</h3>
                    <p>Completá cámara, tipo y motivo. El usuario y la fecha se guardan automáticamente.</p>
                </div>
                <span class="badge">Carga rápida</span>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('iot.operations.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="field">
                            <label>Cámara</label>
                            <select name="sensor_key" required>
                                <option value="">Seleccionar cámara</option>
                                @foreach($sensors as $sensor)
                                    <option value="{{ $sensor->sensor_key }}" @selected(old('sensor_key') === $sensor->sensor_key)>
                                        {{ $sensor->display_name }}{{ !empty($sensor->location) ? ' · ' . $sensor->location : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Tipo de evento</label>
                            <select name="event_type" required>
                                @foreach($eventTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('event_type', 'reposicion') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Motivo</label>
                            <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Ej: Reposición de mercadería por recepción de proveedor" required>
                            <div class="quick-reasons">
                                <div class="reason-chip">Reposición de mercadería</div>
                                <div class="reason-chip">Retiro de mercadería</div>
                                <div class="reason-chip">Limpieza programada</div>
                                <div class="reason-chip">Control de calidad</div>
                            </div>
                        </div>

                        <div class="field">
                            <label>Observación</label>
                            <textarea name="notes" placeholder="Detalle opcional: quién solicitó la apertura, condición encontrada, mercadería ingresada o cualquier dato útil para auditoría.">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="actions">
                        <a class="btn" href="{{ route('iot.points') }}">Ver puntos de medición</a>
                        <button class="btn btn-primary" type="submit">Guardar evento operativo</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Eventos recientes</h3>
                    <p>Últimas cargas manuales registradas por usuarios del sistema.</p>
                </div>
                <span class="badge">Trazabilidad</span>
            </div>
            <div class="panel-body">
                <div class="event-list">
                    @forelse($events as $event)
                        <article class="event-card">
                            <div class="event-top">
                                <div class="event-title">{{ $event->sensorName() }}</div>
                                <span class="badge">{{ $event->created_at->format('d/m H:i') }}</span>
                            </div>
                            <div class="event-meta">
                                {{ $event->eventTypeLabel() }} · {{ $event->reason }} · {{ optional($event->user)->name ?? 'Usuario no disponible' }}
                            </div>
                            @if($event->notes)
                                <div class="event-notes">{{ $event->notes }}</div>
                            @endif
                        </article>
                    @empty
                        <div class="event-card">
                            <div class="event-title">Sin eventos registrados</div>
                            <div class="event-meta">Cuando el equipo cargue motivos de apertura o intervención, aparecerán en esta lista.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</div>
</body>
</html>
