<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>IoT Dashboard - Monitoreo de Cámaras</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/png" href="{{ asset('images/logo-iot.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-iot.png') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg: #07111f;
            --panel: rgba(15, 23, 42, 0.86);
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

        .nav { display: flex; gap: 10px; flex-wrap: wrap; }
        .nav a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.68);
            border: 1px solid var(--border);
            font-size: 14px;
        }
        .nav a:hover,
        .nav a.active {
            color: white;
            border-color: rgba(34, 211, 238, 0.55);
            background: rgba(14, 116, 144, 0.22);
        }


        .ops-command {
            position: relative;
            overflow: hidden;
            margin-bottom: 16px;
            padding: 22px;
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, rgba(34, 211, 238, 0.18), transparent 36%),
                linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(2, 6, 23, 0.92));
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
        }
        .ops-command.alerting {
            border-color: rgba(239, 68, 68, 0.36);
            background:
                radial-gradient(circle at top right, rgba(239, 68, 68, 0.20), transparent 38%),
                linear-gradient(135deg, rgba(127, 29, 29, 0.34), rgba(2, 6, 23, 0.94));
        }
        .ops-title {
            margin: 0;
            font-size: clamp(28px, 3vw, 46px);
            line-height: .98;
            letter-spacing: -0.06em;
        }
        .ops-title span {
            background: linear-gradient(135deg, #67e8f9, #a78bfa);
            -webkit-background-clip: text;
            color: transparent;
        }
        .ops-subtitle {
            margin: 10px 0 0;
            color: #cbd5e1;
            line-height: 1.55;
            max-width: 820px;
        }
        .ops-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #86efac;
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.28);
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 12px;
        }
        .ops-command.alerting .ops-badge {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.14);
            border-color: rgba(248, 113, 113, 0.34);
        }
        .ops-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--green);
            box-shadow: 0 0 0 7px rgba(34, 197, 94, 0.12);
        }
        .ops-command.alerting .ops-dot {
            background: var(--red);
            box-shadow: 0 0 0 7px rgba(239, 68, 68, 0.14);
        }
        .alarm-red-button {
            min-width: 220px;
            min-height: 64px;
            border: 1px solid rgba(248, 113, 113, 0.42);
            border-radius: 22px;
            color: white;
            cursor: pointer;
            padding: 12px 18px;
            font-family: inherit;
            font-weight: 950;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.96), rgba(127, 29, 29, 0.96));
            box-shadow: 0 18px 50px rgba(127, 29, 29, 0.34);
            display: grid;
            gap: 3px;
            justify-items: start;
        }
        .alarm-red-button .number {
            font-size: 28px;
            line-height: 1;
            letter-spacing: -0.05em;
        }
        .alarm-red-button .text {
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #fee2e2;
        }
        .alarm-red-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 24px 62px rgba(127, 29, 29, 0.48);
        }
        .alarm-green-card {
            min-width: 220px;
            min-height: 64px;
            border-radius: 22px;
            border: 1px solid rgba(34, 197, 94, 0.28);
            background: rgba(34, 197, 94, 0.10);
            display: grid;
            align-content: center;
            padding: 12px 18px;
            color: #bbf7d0;
            font-weight: 950;
        }
        .alarm-green-card small {
            color: #86efac;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: 11px;
            margin-top: 3px;
        }

        .quick-health-grid {
            display: grid;
            grid-template-columns: 1.15fr repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .health-card {
            position: relative;
            overflow: hidden;
            min-height: 118px;
            padding: 18px 20px;
            border-radius: 24px;
            background: var(--panel);
            border: 1px solid var(--border);
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.20);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .health-card:after {
            content: "";
            position: absolute;
            inset: -55px -42px auto auto;
            width: 120px;
            height: 120px;
            border-radius: 999px;
            background: rgba(34, 211, 238, 0.14);
        }
        .health-card.danger:after { background: rgba(239, 68, 68, 0.16); }
        .health-card.warning:after { background: rgba(245, 158, 11, 0.16); }
        .health-card > * { position: relative; }
        .health-card .label {
            color: #a8c7e8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .health-card .value {
            font-size: 32px;
            font-weight: 950;
            letter-spacing: -0.05em;
            line-height: 1;
            margin-top: 10px;
        }
        .health-card .hint { color: #9fb0c5; font-size: 12px; line-height: 1.4; }
        .health-card.main-health {
            background:
                radial-gradient(circle at top right, rgba(34, 211, 238, 0.16), transparent 38%),
                linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.66));
        }
        .health-card.main-health.danger {
            border-color: rgba(239, 68, 68, 0.34);
            background:
                radial-gradient(circle at top right, rgba(239, 68, 68, 0.20), transparent 38%),
                linear-gradient(135deg, rgba(127, 29, 29, 0.32), rgba(15, 23, 42, 0.90));
        }
        .hero.compact-hero {
            padding: 22px;
        }
        .hero.compact-hero h2 {
            font-size: clamp(28px, 3vw, 44px);
        }

        .alarm-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(2, 6, 23, 0.78);
            backdrop-filter: blur(10px);
        }
        .alarm-modal-backdrop.is-open { display: flex; }
        .alarm-modal {
            width: min(880px, 100%);
            max-height: min(86vh, 820px);
            overflow: hidden;
            border-radius: 28px;
            background:
                radial-gradient(circle at top left, rgba(239, 68, 68, 0.18), transparent 34%),
                linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(2, 6, 23, 0.98));
            border: 1px solid rgba(248, 113, 113, 0.28);
            box-shadow: 0 30px 95px rgba(0, 0, 0, 0.58);
            display: flex;
            flex-direction: column;
        }
        .alarm-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 24px 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        }
        .alarm-modal-title {
            margin: 0;
            font-size: 24px;
            letter-spacing: -0.04em;
        }
        .alarm-modal-subtitle {
            margin: 6px 0 0;
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.5;
        }
        .alarm-modal-close {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            border: 1px solid rgba(148, 163, 184, 0.20);
            background: rgba(15, 23, 42, 0.72);
            color: #dbeafe;
            cursor: pointer;
            font-size: 22px;
            line-height: 1;
        }
        .alarm-modal-body {
            overflow: auto;
            padding: 18px 24px 24px;
        }
        .modal-alarm-list { display: grid; gap: 12px; }
        .modal-alarm-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            padding: 16px;
            border-radius: 20px;
            background: rgba(2, 6, 23, 0.44);
            border: 1px solid rgba(248, 113, 113, 0.18);
        }
        .modal-alarm-item strong {
            display: block;
            font-size: 15px;
            margin-bottom: 6px;
        }
        .modal-alarm-summary {
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.45;
        }
        .modal-alarm-meta {
            margin-top: 8px;
            color: var(--muted);
            font-size: 12px;
        }
        .modal-alarm-badges {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        .modal-alarm-actions {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 8px;
            margin-top: 6px;
        }
        .modal-note-input {
            min-height: 38px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.42);
            color: var(--text);
            padding: 9px 11px;
            font-size: 12px;
            outline: none;
        }
        .modal-action-btn {
            min-height: 38px;
            border: 1px solid rgba(34, 211, 238, 0.35);
            border-radius: 14px;
            background: rgba(8, 145, 178, 0.12);
            color: #a5f3fc;
            padding: 8px 10px;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            font-family: inherit;
            white-space: nowrap;
        }
        .modal-action-btn.good {
            border-color: rgba(34, 197, 94, 0.35);
            background: rgba(34, 197, 94, 0.12);
            color: #bbf7d0;
        }
        .modal-action-btn.warn {
            border-color: rgba(245, 158, 11, 0.35);
            background: rgba(245, 158, 11, 0.12);
            color: #fde68a;
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
        .hero-grid {
            position: relative;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 28px;
            align-items: center;
        }
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
            font-weight: 700;
            margin-bottom: 16px;
        }
        .pulse {
            width: 8px;
            height: 8px;
            background: var(--green);
            border-radius: 999px;
            box-shadow: 0 0 0 8px rgba(34, 197, 94, 0.12);
        }
        .hero h2 {
            margin: 0;
            font-size: clamp(34px, 4vw, 58px);
            line-height: 0.95;
            letter-spacing: -0.06em;
        }
        .hero h2 span {
            background: linear-gradient(90deg, #67e8f9, #a78bfa, #f0abfc);
            -webkit-background-clip: text;
            color: transparent;
        }
        .hero-desc {
            max-width: 720px;
            margin: 18px 0 0;
            color: #b6c6d8;
            font-size: 16px;
            line-height: 1.7;
        }
        .hero-status {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }
        .hero-tile {
            padding: 18px;
            border-radius: 22px;
            background: rgba(2, 6, 23, 0.42);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }
        .hero-tile .value {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.04em;
            margin-bottom: 6px;
        }
        .hero-tile .label {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .kpis {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }
        .kpi {
            min-height: 120px;
            padding: 18px 20px;
            border-radius: 24px;
            background: var(--panel);
            border: 1px solid var(--border);
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.20);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .kpi .label {
            color: #a8c7e8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .kpi .value {
            font-size: 30px;
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1;
            margin: 10px 0 0;
        }
        .kpi .hint { color: #9fb0c5; font-size: 13px; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            align-items: stretch;
            margin-bottom: 18px;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 22px;
            box-shadow: 0 18px 60px rgba(0, 0, 0, 0.22);
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
            margin-bottom: 14px;
        }
        .panel-title { margin: 0; font-size: 18px; letter-spacing: -0.03em; }
        .panel-subtitle { margin: 6px 0 0; color: var(--muted); font-size: 13px; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid var(--border);
            white-space: nowrap;
        }
        .badge.success { color: #86efac; background: rgba(34, 197, 94, 0.12); border-color: rgba(34, 197, 94, 0.25); }
        .badge.warning { color: #fbbf24; background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.28); }
        .badge.danger { color: #fca5a5; background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.28); }
        .badge.offline { color: #cbd5e1; background: rgba(148, 163, 184, 0.10); }

        .trend-panel {
            min-height: 620px;
            display: flex;
            flex-direction: column;
        }
        .trend-chart-wrap {
            height: 500px;
            min-height: 500px;
            flex: 1;
        }
        .side-stack {
            display: grid;
            grid-template-rows: 1fr 1fr;
            gap: 18px;
            height: 100%;
        }
        .side-panel {
            min-height: 286px;
            display: flex;
            flex-direction: column;
        }
        .side-chart-wrap {
            height: 210px;
            min-height: 210px;
            flex: 1;
        }

        .sensor-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 18px;
        }
        .sensor-card {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            padding: 22px;
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.62));
            border: 1px solid var(--border);
            box-shadow: 0 18px 55px rgba(0, 0, 0, 0.22);
        }
        .sensor-card:before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(34, 211, 238, 0.15), transparent 42%);
            pointer-events: none;
        }
        .sensor-card > * { position: relative; }
        .sensor-head {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 18px;
        }
        .sensor-title { font-size: 17px; font-weight: 800; }
        .sensor-location { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .temp-now { font-size: 48px; font-weight: 950; letter-spacing: -0.06em; margin-bottom: 18px; }
        .sensor-metrics { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .mini-metric { padding: 12px; border-radius: 18px; background: rgba(2, 6, 23, 0.34); border: 1px solid rgba(148, 163, 184, 0.13); }
        .mini-metric .m-label { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
        .mini-metric .m-value { font-weight: 850; font-size: 15px; }

        .lower-grid {
            display: grid;
            grid-template-columns: 1fr 0.75fr 1.25fr;
            gap: 18px;
            margin-bottom: 18px;
        }
        .alert-list { display: grid; gap: 12px; }
        .alert-item { padding: 14px; border-radius: 18px; background: rgba(2, 6, 23, 0.34); border: 1px solid rgba(148, 163, 184, 0.13); }
        .alert-item strong { display: block; margin-bottom: 6px; }
        .alert-item p { margin: 5px 0 0; color: var(--muted); font-size: 13px; }
        .door-chart-wrap { height: 260px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 13px 10px; text-align: left; border-bottom: 1px solid rgba(148, 163, 184, 0.13); font-size: 13px; }
        th { color: #a8c7e8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; }
        .muted { color: var(--muted); font-size: 12px; margin-top: 3px; }

        .text-green { color: #86efac; }
        .text-yellow { color: #fbbf24; }
        .text-red { color: #fca5a5; }
        .text-cyan { color: #67e8f9; }

        @media (max-width: 1280px) {
            .kpis { grid-template-columns: repeat(3, 1fr); }
            .dashboard-grid, .hero-grid, .lower-grid, .ops-command, .quick-health-grid { grid-template-columns: 1fr; }
            .trend-panel { min-height: auto; }
            .trend-chart-wrap { height: 340px; min-height: 340px; }
            .side-stack { grid-template-columns: 1fr 1fr; grid-template-rows: auto; }
            .sensor-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 820px) {
            .page { width: min(100% - 20px, 1500px); padding-top: 14px; }
            .topbar { align-items: flex-start; flex-direction: column; }
            .hero { padding: 22px; border-radius: 24px; }
            .hero-status, .kpis, .side-stack, .sensor-grid, .modal-alarm-actions { grid-template-columns: 1fr; }
            .trend-chart-wrap { height: 300px; min-height: 300px; }
            .side-chart-wrap { height: 220px; min-height: 220px; }
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
    $sensorNameMap = $sensorNameMap ?? \App\Models\IotSensor::displayNameMap();
@endphp
<div class="page">

    <header class="topbar">
        <div class="brand">
            <div class="brand-mark"><img src="{{ asset('images/logo-iot.png') }}" alt="Logo IoT"></div>
            <div>
                <h1>Monitoreo Inteligente de Cámaras</h1>
                <p>Temperatura, humedad, puertas, batería y señal en tiempo real</p>
            </div>
        </div>

        <nav class="nav">
            <a class="{{ request()->is('iot/dashboard') ? 'active' : '' }}" href="/iot/dashboard">Dashboard</a>
            <a class="{{ request()->is('iot/points*') ? 'active' : '' }}" href="/iot/points">Puntos de medición</a>
            <a class="{{ request()->is('iot/analytics*') ? 'active' : '' }}" href="/iot/analytics">Informes</a>
            <a class="{{ request()->is('iot/settings*') ? 'active' : '' }}" href="/iot/settings">Configuración</a>
        </nav>
    </header>

    @php
        $activeAlarmEvents = $activeAlarmEvents ?? [];
        $activeAlarmCount = count($activeAlarmEvents);
        $hasActiveAlarms = $activeAlarmCount > 0;
        $healthLabel = $hasActiveAlarms ? 'Atención requerida' : 'Sistema operativo normal';
        $healthHint = $hasActiveAlarms
            ? 'Hay alarmas activas que requieren revisión operativa.'
            : 'Sin alarmas activas generadas por Lambda.';
    @endphp

    <section class="ops-command {{ $hasActiveAlarms ? 'alerting' : '' }}">
        <div>
            <div class="ops-badge">
                <span class="ops-dot"></span>
                {{ $healthLabel }}
            </div>
            <h2 class="ops-title">Visión general <span>operativa</span></h2>
            <p class="ops-subtitle">
                {{ $healthHint }} Vista ejecutiva para revisar temperatura, puertas, sensores online,
                batería y alarmas activas desde el primer vistazo.
            </p>
        </div>

        @if($hasActiveAlarms)
            <button type="button" class="alarm-red-button" id="openActiveAlarmsModal">
                <span class="number">{{ $activeAlarmCount }}</span>
                <span class="text">Ver alarmas activas</span>
            </button>
        @else
            <div class="alarm-green-card">
                0 alarmas activas
                <small>Operación normal</small>
            </div>
        @endif
    </section>

    <section class="quick-health-grid">
        <div class="health-card main-health {{ $hasActiveAlarms ? 'danger' : '' }}">
            <div class="label">Salud general</div>
            <div class="value {{ $hasActiveAlarms ? 'text-red' : 'text-green' }}">{{ $hasActiveAlarms ? 'Revisar' : 'OK' }}</div>
            <div class="hint">{{ $hasActiveAlarms ? 'Prioridad: atender alarmas activas' : 'Sin alarmas activas reportadas' }}</div>
        </div>

        <div class="health-card">
            <div class="label">Temp. promedio</div>
            <div class="value text-cyan">{{ $summary['avg_temp'] !== null ? $summary['avg_temp'].'°C' : '—' }}</div>
            <div class="hint">Promedio de cámaras activas</div>
        </div>

        <div class="health-card {{ ($summary['max_temp'] ?? 0) >= 8 ? 'warning' : '' }}">
            <div class="label">Temp. máxima</div>
            <div class="value {{ ($summary['max_temp'] ?? 0) >= 8 ? 'text-yellow' : 'text-green' }}">{{ $summary['max_temp'] !== null ? $summary['max_temp'].'°C' : '—' }}</div>
            <div class="hint">Última lectura más alta</div>
        </div>

        <div class="health-card {{ $summary['open_doors'] > 0 ? 'warning' : '' }}">
            <div class="label">Puertas abiertas</div>
            <div class="value {{ $summary['open_doors'] > 0 ? 'text-yellow' : 'text-green' }}">{{ $summary['open_doors'] }}</div>
            <div class="hint">Estado actual de accesos</div>
        </div>

        <div class="health-card">
            <div class="label">Sensores online</div>
            <div class="value text-green">{{ $summary['online_sensors'] }}/{{ $summary['total_sensors'] }}</div>
            <div class="hint">Disponibilidad actual</div>
        </div>

        <div class="health-card {{ $summary['active_alerts'] > 0 ? 'danger' : '' }}">
            <div class="label">Alarmas activas</div>
            <div class="value {{ $summary['active_alerts'] > 0 ? 'text-red' : 'text-green' }}">{{ $summary['active_alerts'] }}</div>
            <div class="hint">{{ $summary['critical_alarms'] ?? 0 }} críticas · {{ $summary['acknowledged_alarms'] ?? 0 }} reconocidas</div>
        </div>
    </section>


    <section class="sensor-grid">
        @foreach($readings as $reading)
            <article class="sensor-card">
                <div class="sensor-head">
                    <div>
                        <div class="sensor-title">
                            {{ $reading['display_name'] ?? $reading['camera_id'] ?? $reading['sensor_id'] }}
                        </div>
                        <div class="sensor-location">{{ $reading['location'] ?? $reading['site_id'] ?? 'Planta Demo' }}</div>
                    </div>
                    <span class="badge {{ $reading['status_class'] ?? 'success' }}">{{ $reading['status'] ?? 'Online' }}</span>
                </div>

                <div class="temp-now {{ ($reading['temperature'] ?? 0) >= 8 ? 'text-yellow' : 'text-cyan' }}">
                    {{ $reading['temperature'] !== null ? number_format($reading['temperature'], 1).'°C' : '—' }}
                </div>

                <div class="sensor-metrics">
                    <div class="mini-metric">
                        <div class="m-label">Humedad</div>
                        <div class="m-value">{{ $reading['humidity'] !== null ? $reading['humidity'].'%' : '—' }}</div>
                    </div>
                    <div class="mini-metric">
                        <div class="m-label">Puerta</div>
                        <div class="m-value {{ !empty($reading['door_open']) ? 'text-yellow' : 'text-green' }}">{{ !empty($reading['door_open']) ? 'Abierta' : 'Cerrada' }}</div>
                    </div>
                    <div class="mini-metric">
                        <div class="m-label">Batería</div>
                        <div class="m-value">{{ $reading['battery'] !== null ? number_format((float) $reading['battery'], 0).'%' : '—' }}</div>
                    </div>
                    <div class="mini-metric">
                        <div class="m-label">RSSI</div>
                        <div class="m-value">{{ $reading['signal_rssi'] !== null ? $reading['signal_rssi'].' dBm' : '—' }}</div>
                    </div>
                    <div class="mini-metric">
                        <div class="m-label">SNR</div>
                        <div class="m-value">{{ $reading['signal_snr'] !== null ? $reading['signal_snr'].' dB' : '—' }}</div>
                    </div>
                    <div class="mini-metric">
                        <div class="m-label">Última señal</div>
                        <div class="m-value">
                            <span title="{{ $reading['last_seen_at_label'] ?? '—' }}">{{ $reading['minutes_ago_label'] ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </section>
    <section class="dashboard-grid">
        <div class="panel trend-panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Tendencia de temperatura</h3>
                </div>
                <span class="badge success">● Datos en vivo</span>
            </div>
            <div class="trend-chart-wrap">
                <canvas id="temperatureChart"></canvas>
            </div>
        </div>
    </section>

    <section class="lower-grid">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Eventos destacados</h3>
                    <p class="panel-subtitle">Condiciones que requieren atención</p>
                </div>
            </div>
            <div class="alert-list">
                @forelse($alerts as $alert)
                    <div class="alert-item">
                        <strong>{{ $alert['type'] }} · {{ $alert['display_name'] ?? $sensorNameMap[$alert['camera_id'] ?? ''] ?? $alert['camera_id'] }}</strong>
                        <p>{{ $alert['message'] }}</p>
                        <p>Temp: {{ $alert['temperature'] !== null ? $alert['temperature'].'°C' : '—' }} · {{ $alert['timestamp'] ?? 'Sin fecha' }}</p>
                    </div>
                @empty
                    <div class="alert-item">
                        <strong class="text-green">Sin eventos críticos</strong>
                        <p>Todas las cámaras se encuentran dentro de los parámetros esperados.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Estado de puertas</h3>
                    <p class="panel-subtitle">Lectura actual por cámara</p>
                </div>
            </div>
            <div class="door-chart-wrap">
                <canvas id="doorChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h3 class="panel-title">Últimas lecturas</h3>
                    <p class="panel-subtitle">Resumen operativo consolidado</p>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                    <tr>
                        <th>Cámara</th>
                        <th>Temperatura</th>
                        <th>Humedad</th>
                        <th>Puerta</th>
                        <th>Estado</th>
                        <th>Hora</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($readings as $reading)
                        <tr>
                            <td>
                                <strong>{{ $reading['display_name'] ?? $sensorNameMap[$reading['camera_id'] ?? $reading['sensor_id'] ?? ''] ?? $reading['camera_id'] ?? $reading['sensor_id'] }}</strong>
                                <div class="muted">{{ $reading['sensor_id'] }}</div>
                            </td>
                            <td>{{ $reading['temperature'] !== null ? number_format($reading['temperature'], 1).'°C' : '—' }}</td>
                            <td>{{ $reading['humidity'] !== null ? $reading['humidity'].'%' : '—' }}</td>
                            <td>{{ !empty($reading['door_open']) ? 'Abierta' : 'Cerrada' }}</td>
                            <td><span class="badge {{ $reading['status_class'] ?? 'success' }}">{{ $reading['status'] ?? 'Online' }}</span></td>
                            <td class="muted">{{ isset($reading['timestamp']) && $reading['timestamp'] ? substr($reading['timestamp'], 11, 8) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="alarm-modal-backdrop" id="activeAlarmsModal" aria-hidden="true">
        <div class="alarm-modal" role="dialog" aria-modal="true" aria-labelledby="activeAlarmsTitle">
            <div class="alarm-modal-header">
                <div>
                    <h3 class="alarm-modal-title" id="activeAlarmsTitle">Alarmas activas del sistema</h3>
                    <p class="alarm-modal-subtitle">
                        Eventos generados por Lambda pendientes de gestión. Desde acá podés comentar,
                        reconocer o silenciar una alarma activa.
                    </p>
                </div>
                <button type="button" class="alarm-modal-close" id="closeActiveAlarmsModal">×</button>
            </div>

            <div class="alarm-modal-body">
                <div class="modal-alarm-list">
                    @forelse($activeAlarmEvents as $alarm)
                        <div class="modal-alarm-item" id="alarm-{{ $alarm['alarm_id'] ?? '' }}">
                            <div>
                                <strong>{{ $alarm['camera'] }} · {{ $alarm['name'] }}</strong>
                                <div class="modal-alarm-summary">{{ $alarm['summary'] }}</div>
                                <div class="modal-alarm-meta">
                                    Puerta: {{ $alarm['door'] }}
                                    · Última detección: {{ $alarm['last_detected_at'] ?? '—' }}
                                </div>
                            </div>

                            <div class="modal-alarm-badges">
                                <span class="badge {{ $alarm['status_class'] }}">{{ $alarm['status_label'] }}</span>
                                <span class="badge {{ $alarm['severity_class'] }}">{{ $alarm['severity_label'] }}</span>
                            </div>

                            @if(auth()->user()?->canAcknowledgeAlarms())
                                <form method="POST" action="{{ route('iot.alarms.comment', $alarm['alarm_id']) }}" class="modal-alarm-actions">
                                    @csrf
                                    <input class="modal-note-input" type="text" name="note" maxlength="500" placeholder="Comentario / acción tomada">
                                    <button class="modal-action-btn" type="submit">Comentar</button>
                                    @if(($alarm['status'] ?? 'open') === 'open')
                                        <button class="modal-action-btn good" type="submit" formaction="{{ route('iot.alarms.acknowledge', $alarm['alarm_id']) }}">Reconocer</button>
                                        <button class="modal-action-btn warn" type="submit" formaction="{{ route('iot.alarms.silence', $alarm['alarm_id']) }}">Silenciar</button>
                                    @endif
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="modal-alarm-item">
                            <div>
                                <strong class="text-green">Sin alarmas activas</strong>
                                <div class="modal-alarm-summary">No hay eventos pendientes de atención en este momento.</div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

<script>

    const activeAlarmsModal = document.getElementById('activeAlarmsModal');
    const openActiveAlarmsModal = document.getElementById('openActiveAlarmsModal');
    const closeActiveAlarmsModal = document.getElementById('closeActiveAlarmsModal');

    function showActiveAlarmsModal() {
        if (!activeAlarmsModal) return;
        activeAlarmsModal.classList.add('is-open');
        activeAlarmsModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function hideActiveAlarmsModal() {
        if (!activeAlarmsModal) return;
        activeAlarmsModal.classList.remove('is-open');
        activeAlarmsModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    openActiveAlarmsModal?.addEventListener('click', showActiveAlarmsModal);
    closeActiveAlarmsModal?.addEventListener('click', hideActiveAlarmsModal);
    activeAlarmsModal?.addEventListener('click', event => {
        if (event.target === activeAlarmsModal) {
            hideActiveAlarmsModal();
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && activeAlarmsModal?.classList.contains('is-open')) {
            hideActiveAlarmsModal();
        }
    });


    const sensorNameMap = @json($sensorNameMap);
    const displaySensorName = (key) => sensorNameMap[key] || key || 'Sensor sin identificar';
    const chartData = @json($chartData);
    const readings = @json($readings);
    const sensorIds = Object.keys(chartData);
    const palette = ['#22d3ee', '#a855f7', '#f59e0b'];
    const labels = sensorIds.length > 0 ? chartData[sensorIds[0]].labels : [];

    const tempDatasets = sensorIds.map((sensorId, index) => ({
        label: chartData[sensorId].name ?? displaySensorName(sensorId),
        data: chartData[sensorId].temperatures,
        borderColor: palette[index] ?? '#38bdf8',
        backgroundColor: (palette[index] ?? '#38bdf8') + '22',
        tension: 0.38,
        borderWidth: 3,
        pointRadius: 2,
        pointHoverRadius: 6,
        fill: false,
    }));

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#cbd5e1',
                    usePointStyle: true,
                    boxWidth: 8,
                    boxHeight: 8,
                }
            },
            tooltip: {
                backgroundColor: 'rgba(2, 6, 23, 0.92)',
                titleColor: '#e5edf8',
                bodyColor: '#cbd5e1',
                borderColor: 'rgba(148, 163, 184, 0.25)',
                borderWidth: 1,
            }
        },
        scales: {
            x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.08)' } },
            y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.08)' } }
        }
    };

    new Chart(document.getElementById('temperatureChart'), {
        type: 'line',
        data: { labels, datasets: tempDatasets },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: {
                    ...commonOptions.scales.y,
                    suggestedMin: -2,
                    suggestedMax: 18,
                    ticks: { color: '#94a3b8', callback: value => value + '°C' }
                }
            }
        }
    });

    new Chart(document.getElementById('doorChart'), {
        type: 'bar',
        data: {
            labels: readings.map(r => r.camera_id ?? displaySensorName(r.camera_key ?? r.sensor_id)),
            datasets: [{
                label: 'Puerta abierta',
                data: readings.map(r => r.door_open ? 1 : 0),
                backgroundColor: readings.map(r => r.door_open ? 'rgba(245, 158, 11, 0.8)' : 'rgba(34, 197, 94, 0.72)'),
                borderRadius: 12,
            }]
        },
        options: {
            ...commonOptions,
            plugins: { ...commonOptions.plugins, legend: { display: false } },
            scales: {
                x: commonOptions.scales.x,
                y: {
                    min: 0,
                    max: 1,
                    ticks: { color: '#94a3b8', stepSize: 1, callback: value => value === 1 ? 'Abierta' : 'Cerrada' },
                    grid: { color: 'rgba(148, 163, 184, 0.08)' }
                }
            }
        }
    });
</script>
</body>
</html>
