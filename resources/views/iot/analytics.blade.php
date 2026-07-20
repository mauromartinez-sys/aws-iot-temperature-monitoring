<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="{{ asset('images/logo-iot.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-iot.png') }}">
<title>Análisis de Informes IoT</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg: #07111f;
            --panel: rgba(15, 23, 42, .78);
            --panel-strong: rgba(15, 23, 42, .94);
            --line: rgba(148, 163, 184, .20);
            --border: rgba(148, 163, 184, .20);
            --text: #e5eefb;
            --muted: #94a3b8;
            --blue: #38bdf8;
            --cyan: #22d3ee;
            --green: #22c55e;
            --yellow: #f59e0b;
            --red: #ef4444;
            --violet: #8b5cf6;
            --purple: #a855f7;
            --shadow: 0 24px 70px rgba(2, 6, 23, .34);
        }

        * { box-sizing: border-box; }

        *::-webkit-scrollbar { width: 10px; height: 10px; }
        *::-webkit-scrollbar-track { background: rgba(2, 6, 23, .38); border-radius: 999px; }
        *::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(34, 211, 238, .70), rgba(139, 92, 246, .68));
            border-radius: 999px;
            border: 2px solid rgba(2, 6, 23, .75);
        }
        *::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(34, 211, 238, .95), rgba(168, 85, 247, .92));
        }
        * { scrollbar-width: thin; scrollbar-color: rgba(34, 211, 238, .75) rgba(2, 6, 23, .38); }

        body {
            margin: 0;
            font-family: 'Instrument Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text);
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(34, 211, 238, .18), transparent 34%),
                radial-gradient(circle at top right, rgba(139, 92, 246, .16), transparent 28%),
                linear-gradient(135deg, #07111f 0%, #0f172a 46%, #020617 100%);
        }

        .page { width: min(1420px, calc(100% - 36px)); margin: 0 auto; padding: 28px 0 42px; }

        .topbar {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:18px;
            margin-bottom: 24px;
        }

        .brand { display:flex; align-items:center; gap:14px; }
        .brand-icon {
            width:48px;
            height:48px;
            border-radius:16px;
            background: linear-gradient(135deg, var(--cyan), var(--purple));
            display:grid;
            place-items:center;
            box-shadow: 0 0 34px rgba(34, 211, 238, .34);
        }
        .brand-icon svg { width:24px; height:24px; }
        .eyebrow { color: var(--cyan); font-size:12px; letter-spacing:.16em; text-transform:uppercase; font-weight:800; }
        h1 { margin: 4px 0 0; font-size: clamp(30px, 4vw, 48px); line-height:1; letter-spacing:-.05em; }
        .subtitle { margin: 10px 0 0; color: var(--muted); max-width: 760px; font-size:15px; }

        .nav {
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }
        .nav a {
            color:#cbd5e1;
            text-decoration:none;
            padding:10px 14px;
            border-radius:14px;
            background:rgba(15, 23, 42, .68);
            border:1px solid var(--border);
            font-size:14px;
            font-weight:700;
            transition:.18s ease;
        }
        .nav a:hover,
        .nav a.active {
            color:white;
            border-color:rgba(34, 211, 238, .55);
            background:rgba(14, 116, 144, .22);
            transform: translateY(-1px);
        }

        .btn {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:40px;
            color:#eaf6ff;
            text-decoration:none;
            border:1px solid var(--border);
            background:rgba(15, 23, 42, .68);
            padding:10px 14px;
            border-radius:14px;
            font-weight:800;
            font-size:13px;
            cursor:pointer;
            transition:.18s ease;
        }
        .btn:hover { transform: translateY(-1px); border-color: rgba(34,211,238,.62); background: rgba(14,116,144,.22); }
        .btn-primary { border-color: rgba(34,211,238,.55); background: rgba(14,116,144,.22); }
        .filters > .btn { min-height:46px; white-space:nowrap; padding-inline:18px; }
        .btn-green { border-color: rgba(34,197,94,.45); background: rgba(34,197,94,.13); }
        .btn-red { border-color: rgba(239,68,68,.45); background: rgba(239,68,68,.13); }

        .hero {
            position:relative;
            overflow:visible;
            border:1px solid var(--line);
            border-radius:30px;
            padding:26px;
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .96), rgba(15, 23, 42, .70)),
                radial-gradient(circle at 78% 20%, rgba(34,211,238,.22), transparent 30%);
            box-shadow: var(--shadow);
        }
        .hero { z-index: 50; }
        .hero:before { content:""; position:absolute; inset:-2px; background: radial-gradient(circle at 86% -10%, rgba(168,85,247,.16), transparent 30%); pointer-events:none; }
        .filters {
            position:relative;
            display:grid;
            grid-template-columns: minmax(190px, 1fr) minmax(190px, 1fr) minmax(260px, 1.15fr) minmax(260px, 1.05fr) auto;
            gap:12px;
            align-items:end;
        }
        .field label { display:block; font-size:12px; color:#b7d9f6; font-weight:800; margin-bottom:7px; }
        .field input {
            width:100%; color:var(--text); background:rgba(2,6,23,.52); border:1px solid var(--line); border-radius:16px;
            padding:12px 12px; outline:none; min-height:46px; font-weight:700;
        }
        .field input:focus { border-color: rgba(34,211,238,.65); box-shadow: 0 0 0 4px rgba(34,211,238,.08); }

        .sensor-picker {
            height: 46px;
            min-height: 46px;
            max-height: 46px;
            overflow-y:auto;
            overflow-x:hidden;
            display:flex;
            flex-direction:column;
            gap:5px;
            padding:5px;
            color:var(--text);
            background:rgba(2,6,23,.52);
            border:1px solid var(--line);
            border-radius:16px;
        }
        .sensor-option {
            min-height:34px;
            display:flex;
            align-items:center;
            gap:9px;
            padding:6px 10px;
            border-radius:12px;
            border:1px solid rgba(148,163,184,.13);
            background:rgba(15,23,42,.48);
            cursor:pointer;
            transition:.16s ease;
            user-select:none;
        }
        .sensor-option:hover { border-color:rgba(34,211,238,.40); background:rgba(14,116,144,.15); }
        .sensor-option input { width:15px; height:15px; accent-color:#22d3ee; flex:0 0 auto; }
        .sensor-option span { display:flex; align-items:center; min-width:0; }
        .sensor-option strong { font-size:13px; color:#e5eefb; line-height:1.15; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

        .quick-ranges { display:flex; gap:8px; flex-wrap:wrap; }
        .quick-ranges label { cursor:pointer; }
        .quick-ranges input { display:none; }
        .quick-ranges span {
            display:inline-flex;
            min-height:38px;
            align-items:center;
            padding:9px 12px;
            border-radius:14px;
            border:1px solid var(--line);
            color:#cbd5e1;
            background:rgba(15, 23, 42, .68);
            font-size:12px;
            font-weight:800;
            transition:.16s ease;
        }
        .quick-ranges span:hover { border-color:rgba(34,211,238,.45); background:rgba(14,116,144,.18); }
        .quick-ranges input:checked + span { color:white; border-color: rgba(34,211,238,.62); background:rgba(14,116,144,.28); box-shadow:0 0 24px rgba(34,211,238,.10); }

        .cards { display:grid; grid-template-columns: repeat(6, 1fr); gap:14px; margin:18px 0; position: relative; z-index: 1; }
        .card, .panel { border:1px solid var(--line); background:var(--panel); border-radius:24px; box-shadow: 0 18px 50px rgba(2,6,23,.25); backdrop-filter: blur(16px); }
        .card { padding:17px; min-height:112px; position:relative; overflow:hidden; }
        .card:after { content:""; position:absolute; width:88px; height:88px; right:-28px; top:-24px; border-radius:50%; background:rgba(34,211,238,.10); }
        .card .label { color: var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.12em; font-weight:800; }
        .card .value { margin-top:12px; font-size:28px; font-weight:800; letter-spacing:-.04em; }
        .card .hint { margin-top:6px; color:var(--muted); font-size:12px; }
        .grid-main { display:grid; grid-template-columns: 1.8fr .9fr; gap:18px; align-items:start; }
        .panel { padding:20px; overflow:hidden; }
        .panel-title { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; }
        .panel-title h2 { margin:0; font-size:18px; letter-spacing:-.02em; }
        .panel-title span { color: var(--muted); font-size:12px; }
        .chart-wrap { height: 390px; }
        .status-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid var(--line); }
        .success { color:#86efac; background:rgba(34,197,94,.10); border-color:rgba(34,197,94,.22); }
        .warning { color:#fde68a; background:rgba(245,158,11,.10); border-color:rgba(245,158,11,.25); }
        .danger { color:#fecaca; background:rgba(239,68,68,.11); border-color:rgba(239,68,68,.25); }
        .table-wrap { overflow:auto; border-radius:20px; border:1px solid var(--line); max-height: 520px; }
        table { width:100%; border-collapse: collapse; min-width: 980px; }
        th, td { padding: 13px 14px; border-bottom:1px solid rgba(148,163,184,.12); text-align:left; font-size:13px; }
        th { color:#cbd5e1; font-size:11px; text-transform:uppercase; letter-spacing:.12em; background:rgba(2,6,23,.72); position:sticky; top:0; z-index:1; }
        td { color:#dbeafe; }
        tr:hover td { background:rgba(34,211,238,.045); }
        .alarms { display:flex; flex-direction:column; gap:10px; max-height:390px; overflow:auto; padding-right:4px; }
        .alarm-item { border:1px solid var(--line); background:rgba(2,6,23,.35); border-radius:18px; padding:13px; }
        .alarm-item strong { display:block; margin-bottom:5px; }
        .alarm-item small { color:var(--muted); }
        .export-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .empty { padding:26px; text-align:center; color:var(--muted); }


        /* Reportes automáticos */
        .btn-report-auto {
            border-color: rgba(168, 85, 247, .50);
            background: rgba(168, 85, 247, .14);
        }
        .btn-report-auto:hover {
            border-color: rgba(34, 211, 238, .68);
            background: rgba(14, 116, 144, .24);
        }
        .report-backdrop {
            position: fixed;
            inset: 0;
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(2, 6, 23, .72);
            backdrop-filter: blur(14px);
        }
        .report-backdrop.is-open { display: flex; }
        .report-modal {
            width: min(980px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            border: 1px solid rgba(34, 211, 238, .24);
            border-radius: 28px;
            background:
                radial-gradient(circle at 85% 0%, rgba(168, 85, 247, .20), transparent 30%),
                linear-gradient(135deg, rgba(15, 23, 42, .98), rgba(2, 6, 23, .96));
            box-shadow: 0 30px 90px rgba(0, 0, 0, .52);
        }
        .report-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 24px;
            border-bottom: 1px solid rgba(148, 163, 184, .16);
        }
        .report-header h3 {
            margin: 0;
            font-size: 22px;
            letter-spacing: -.03em;
        }
        .report-header p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
        }
        .report-close {
            width: 38px;
            height: 38px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: rgba(15, 23, 42, .68);
            color: var(--text);
            font-size: 22px;
            cursor: pointer;
        }
        .report-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            padding: 22px 24px 24px;
        }
        .report-box {
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 22px;
            background: rgba(15, 23, 42, .58);
            padding: 18px;
        }
        .report-box h4 {
            margin: 0 0 14px;
            font-size: 15px;
            color: #e5eefb;
        }
        .report-field { margin-bottom: 14px; }
        .report-field label {
            display: block;
            margin-bottom: 7px;
            color: #b7d9f6;
            font-size: 12px;
            font-weight: 800;
        }
        .report-field input,
        .report-field select {
            width: 100%;
            min-height: 44px;
            color: var(--text);
            background: rgba(2, 6, 23, .55);
            border: 1px solid rgba(148, 163, 184, .20);
            border-radius: 14px;
            padding: 10px 12px;
            outline: none;
            font-weight: 700;
        }
        .report-field input:focus,
        .report-field select:focus {
            border-color: rgba(34, 211, 238, .65);
            box-shadow: 0 0 0 4px rgba(34, 211, 238, .08);
        }
        .report-checks {
            display: grid;
            gap: 10px;
            margin: 12px 0 16px;
        }
        .report-check {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #dbeafe;
            font-size: 13px;
            font-weight: 700;
        }
        .report-check input {
            width: 13px;
            height: 13px;
            flex: 0 0 13px;
            margin: 0;
            accent-color: #22d3ee;
        }
        .report-sensors {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 10px;
            max-height: 180px;
            overflow: auto;
            padding: 2px 4px 2px 0;
        }
        .report-sensor-option {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 10px;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid rgba(148, 163, 184, .14);
            border-radius: 14px;
            background: rgba(2, 6, 23, .30);
            cursor: pointer;
            transition: .18s ease;
        }
        .report-sensor-option:hover {
            border-color: rgba(34, 211, 238, .36);
            background: rgba(34, 211, 238, .07);
        }
        .report-sensor-option input {
            width: 13px !important;
            height: 13px !important;
            min-width: 13px !important;
            flex: 0 0 13px !important;
            margin: 0 !important;
            accent-color: #22d3ee;
            align-self: center !important;
        }
        .report-sensor-option span {
            display: flex !important;
            align-items: center !important;
            min-width: 0;
            line-height: 1;
        }
        .report-sensor-option span strong {
            display: inline-flex !important;
            align-items: center !important;
            font-size: 12px;
            font-weight: 900;
            color: #e5eefb;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1;
        }
        .report-list {
            display: grid;
            gap: 12px;
            max-height: 470px;
            overflow: auto;
            padding-right: 4px;
        }
        .report-item {
            border: 1px solid rgba(148, 163, 184, .16);
            border-radius: 18px;
            background: rgba(2, 6, 23, .36);
            padding: 14px;
        }
        .report-item-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }
        .report-item strong { display: block; font-size: 14px; color: #e5eefb; }
        .report-meta {
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }
        .report-badges {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .report-badge {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .18);
            color: #cbd5e1;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .report-badge.ai { color: #67e8f9; border-color: rgba(34, 211, 238, .28); background: rgba(34, 211, 238, .08); }
        .report-badge.active { color: #86efac; border-color: rgba(34, 197, 94, .28); background: rgba(34, 197, 94, .08); }
        .report-delete {
            border-color: rgba(239, 68, 68, .38);
            background: rgba(239, 68, 68, .10);
            color: #fecaca;
            min-height: 34px;
            padding: 8px 10px;
            border-radius: 12px;
        }
        .report-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 4px;
        }
        .flash-success {
            margin-top: 18px;
            border-color: rgba(34, 197, 94, .30) !important;
            background: rgba(34, 197, 94, .10) !important;
            color: #bbf7d0;
        }
        @media (max-width: 900px) {
            .report-body { grid-template-columns: 1fr; }
        }


        /* Selector visual de cámaras */
        .sensor-dropdown { position: relative; z-index: 80; }
        .sensor-dropdown-button {
            width: 100%;
            min-height: 46px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 13px;
            border: 1px solid rgba(34, 211, 238, .34);
            border-radius: 16px;
            background: rgba(2, 6, 23, .52);
            color: var(--text);
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 0 0 0 rgba(34, 211, 238, .0);
            transition: .18s ease;
        }
        .sensor-dropdown-button:hover,
        .sensor-dropdown-button.is-open {
            border-color: rgba(34, 211, 238, .70);
            background: rgba(14, 116, 144, .18);
            box-shadow: 0 0 0 4px rgba(34, 211, 238, .08);
        }
        .sensor-dropdown-main { display: flex; flex-direction: column; min-width: 0; text-align: left; }
        .sensor-dropdown-label { font-size: 14px; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sensor-dropdown-hint { margin-top: 2px; color: var(--muted); font-size: 11px; font-weight: 700; }
        .sensor-chevron { width: 10px; height: 10px; border-right: 2px solid #67e8f9; border-bottom: 2px solid #67e8f9; transform: rotate(45deg); margin-right: 4px; transition: .18s ease; }
        .sensor-dropdown-button.is-open .sensor-chevron { transform: rotate(225deg); margin-top: 6px; }

        .sensor-dropdown-panel {
            position: absolute;
            z-index: 999;
            left: 0;
            right: 0;
            top: calc(100% + 8px);
            display: none;
            padding: 12px;
            border: 1px solid rgba(34, 211, 238, .28);
            border-radius: 20px;
            background: rgba(2, 6, 23, .98);
            box-shadow: 0 24px 70px rgba(0,0,0,.45);
            backdrop-filter: blur(18px);
        }
        .sensor-dropdown-panel.is-open { display: block; }
        .sensor-search {
            width: 100%;
            min-height: 40px;
            margin-bottom: 10px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(15, 23, 42, .70);
            color: var(--text);
            outline: none;
            font-weight: 700;
        }
        .sensor-search:focus { border-color: rgba(34, 211, 238, .58); }
        .sensor-actions { display: flex; gap: 8px; margin-bottom: 10px; }
        .sensor-mini-btn {
            border: 1px solid var(--line);
            background: rgba(15,23,42,.68);
            color: #cbd5e1;
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
        }
        .sensor-mini-btn:hover { color: white; border-color: rgba(34,211,238,.52); }
        .sensor-picker {
            height: auto;
            min-height: unset;
            max-height: 240px;
            padding: 0;
            overflow-y: auto;
            border: 0;
            background: transparent;
            border-radius: 0;
        }
        .sensor-option {
            min-height: 50px;
            padding: 9px 10px;
            margin-bottom: 8px;
            display: grid;
            grid-template-columns: 18px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            border-radius: 16px;
            border: 1px solid rgba(148,163,184,.13);
            background: rgba(15,23,42,.55);
        }
        .sensor-option:last-child { margin-bottom: 0; }
        .sensor-option:hover { border-color: rgba(34,211,238,.45); background: rgba(14,116,144,.18); }
        .sensor-option input { width: 16px; height: 16px; accent-color: #22d3ee; }
        .sensor-option strong { display:block; font-size: 13px; color: #e5eefb; }
        .sensor-option small { display:block; color: var(--muted); margin-top:3px; font-size: 11px; }

        /* Tabla tipo consola AWS */
        .table-tools {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) 160px 160px 190px 140px auto;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
        }
        .table-search,
        .table-select {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(2,6,23,.48);
            color: var(--text);
            padding: 10px 12px;
            outline: none;
            font-weight: 750;
        }
        .table-search:focus,
        .table-select:focus { border-color: rgba(34,211,238,.62); box-shadow: 0 0 0 4px rgba(34,211,238,.08); }
        .table-select option { background: #020617; color: #e5eefb; }

        .table-shell {
            border-radius: 20px;
            border: 1px solid var(--line);
            overflow: hidden;
            background: rgba(2,6,23,.18);
        }
        .table-wrap {
            border-radius: 0;
            border: 0;
            max-height: none;
        }
        .table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-top: 1px solid rgba(148,163,184,.12);
            background: rgba(2,6,23,.54);
        }
        .table-count { color: var(--muted); font-size: 12px; font-weight: 800; }
        .pager { display: flex; align-items: center; gap: 8px; }
        .pager button {
            min-width: 36px;
            height: 34px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: rgba(15,23,42,.70);
            color: #cbd5e1;
            font-weight: 900;
            cursor: pointer;
        }
        .pager button:hover:not(:disabled) { border-color: rgba(34,211,238,.54); color: white; }
        .pager button:disabled { opacity: .45; cursor: not-allowed; }
        .pager .page-indicator { color: #e5eefb; font-size: 12px; font-weight: 900; min-width: 82px; text-align: center; }

        .prefs-backdrop {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(2,6,23,.74);
            backdrop-filter: blur(10px);
        }
        .prefs-backdrop.is-open { display:flex; }
        .prefs-modal {
            width: min(760px, 96vw);
            max-height: 86vh;
            overflow: hidden;
            border-radius: 24px;
            border: 1px solid rgba(148,163,184,.22);
            background: #111827;
            box-shadow: 0 30px 95px rgba(0,0,0,.55);
            display: grid;
            grid-template-rows: auto 1fr auto;
        }
        .prefs-header {
            display:flex;
            justify-content:space-between;
            gap:12px;
            align-items:center;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(148,163,184,.16);
        }
        .prefs-header h3 { margin:0; font-size: 20px; letter-spacing:-.03em; }
        .prefs-close {
            width: 38px; height: 38px;
            border-radius: 13px;
            border:1px solid var(--line);
            background: rgba(15,23,42,.74);
            color:#e5eefb;
            cursor:pointer;
            font-size:22px;
            line-height:1;
        }
        .prefs-body {
            display:grid;
            grid-template-columns: .85fr 1.15fr;
            gap: 20px;
            overflow:auto;
            padding: 20px;
        }
        .prefs-section-title { margin:0 0 10px; font-size: 13px; color:#e5eefb; font-weight:900; }
        .prefs-option {
            display:flex;
            align-items:center;
            gap:10px;
            padding: 8px 0;
            color:#cbd5e1;
            font-weight:750;
        }
        .prefs-option input { width:16px; height:16px; accent-color:#22d3ee; }
        .prefs-footer {
            display:flex;
            justify-content:flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid rgba(148,163,184,.16);
        }
        .hide-column { display: none; }


        .operation-badge {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:6px 10px;
            border-radius:999px;
            font-size:11px;
            font-weight:900;
            border:1px solid var(--line);
            white-space:nowrap;
        }
        .operation-badge.with_record {
            color:#86efac;
            background:rgba(34,197,94,.10);
            border-color:rgba(34,197,94,.28);
        }
        .operation-badge.without_record {
            color:#fecaca;
            background:rgba(239,68,68,.11);
            border-color:rgba(239,68,68,.30);
        }
        .operation-badge.not_applicable {
            color:#cbd5e1;
            background:rgba(148,163,184,.10);
            border-color:rgba(148,163,184,.18);
        }

        .operation-only-row {
            background: linear-gradient(90deg, rgba(245,158,11,.08), rgba(15,23,42,.18));
        }
        .operation-only-row td {
            border-top-color: rgba(245,158,11,.20);
            border-bottom-color: rgba(245,158,11,.08);
        }

        .operation-detail {
            margin-top:6px;
            color:var(--muted);
            font-size:11px;
            line-height:1.35;
        }


        .ops-summary {
            margin: 18px 0;
            border: 1px solid rgba(34, 211, 238, .18);
            background:
                radial-gradient(circle at top right, rgba(34, 211, 238, .12), transparent 28%),
                linear-gradient(135deg, rgba(15, 23, 42, .88), rgba(2, 6, 23, .52));
            border-radius: 26px;
            padding: 18px;
            box-shadow: 0 18px 50px rgba(2, 6, 23, .24);
        }

        .ops-summary-head {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:14px;
            margin-bottom:14px;
        }

        .ops-summary-head h2 {
            margin:0;
            font-size:19px;
            letter-spacing:-.03em;
        }

        .ops-summary-head span {
            display:block;
            margin-top:4px;
            color:var(--muted);
            font-size:12px;
        }

        .ops-summary-badge {
            display:inline-flex;
            align-items:center;
            gap:7px;
            border-radius:999px;
            border:1px solid rgba(34,211,238,.28);
            color:#67e8f9;
            background:rgba(34,211,238,.08);
            padding:7px 11px;
            font-size:11px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.08em;
            white-space:nowrap;
        }

        .ops-summary-grid {
            display:grid;
            grid-template-columns: repeat(5, 1fr);
            gap:12px;
        }

        .ops-card {
            min-height:112px;
            border:1px solid rgba(148,163,184,.16);
            border-radius:20px;
            background:rgba(2,6,23,.34);
            padding:15px;
            position:relative;
            overflow:hidden;
        }

        .ops-card:after {
            content:"";
            position:absolute;
            width:74px;
            height:74px;
            right:-26px;
            top:-28px;
            border-radius:50%;
            background:rgba(34,211,238,.10);
        }

        .ops-card.warning:after { background:rgba(245,158,11,.14); }
        .ops-card.danger:after { background:rgba(239,68,68,.14); }
        .ops-card.success:after { background:rgba(34,197,94,.13); }

        .ops-card .label {
            color:var(--muted);
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:.10em;
            font-weight:900;
            position:relative;
            z-index:1;
        }

        .ops-card .value {
            margin-top:12px;
            font-size:28px;
            line-height:1;
            font-weight:900;
            letter-spacing:-.05em;
            position:relative;
            z-index:1;
        }

        .ops-card .hint {
            margin-top:8px;
            color:var(--muted);
            font-size:12px;
            line-height:1.35;
            position:relative;
            z-index:1;
        }

        @media (max-width: 1100px) {
            .ops-summary-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 640px) {
            .ops-summary-grid { grid-template-columns: 1fr; }
            .ops-summary-head { flex-direction:column; }
        }

        @media (max-width: 1100px) {
            .table-tools { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 720px) {
            .table-tools { grid-template-columns: 1fr; }
            .prefs-body { grid-template-columns: 1fr; }
            .table-footer { flex-direction: column; align-items:flex-start; }
        }

        @media (max-width: 1100px) {
            .filters { grid-template-columns: 1fr 1fr; }
            .cards { grid-template-columns: repeat(3, 1fr); }
            .grid-main { grid-template-columns:1fr; }
        }
        @media (max-width: 720px) {
            .topbar { align-items:flex-start; flex-direction:column; }
            .filters { grid-template-columns: 1fr; }
            .cards { grid-template-columns: repeat(2, 1fr); }
            .page { width:min(100% - 22px, 1420px); }
            .panel-title { align-items:flex-start; flex-direction:column; }
        }
    
        /* Fix seguro selector cámaras: check + nombre amigable alineados */
        .sensor-dropdown-panel .sensor-option {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 12px !important;
            min-height: 46px !important;
            padding: 10px 12px !important;
            text-align: left !important;
        }

        .sensor-dropdown-panel .sensor-option input {
            width: 17px !important;
            height: 17px !important;
            min-width: 17px !important;
            flex: 0 0 17px !important;
            margin: 0 !important;
            accent-color: #22d3ee !important;
        }

        .sensor-dropdown-panel .sensor-option span {
            display: flex !important;
            align-items: center !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }

        .sensor-dropdown-panel .sensor-option strong {
            display: block !important;
            width: 100% !important;
            text-align: left !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .sensor-dropdown-panel .sensor-option small {
            display: none !important;
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
<?php
    $sensorDisplayNames = [];
    foreach (($sensors ?? []) as $id => $sensor) {
        $displayName = data_get($sensor, 'display_name') ?: data_get($sensor, 'camera_id') ?: data_get($sensor, 'sensor_id') ?: $id;
        $cameraId = data_get($sensor, 'camera_id');
        $sensorId = data_get($sensor, 'sensor_id');
        $sensorDisplayNames[$id] = $displayName;
        if ($cameraId) { $sensorDisplayNames[$cameraId] = $displayName; }
        if ($sensorId) { $sensorDisplayNames[$sensorId] = $displayName; }
    }
    $displaySensorName = function ($key, $fallback = null) use ($sensorDisplayNames) {
        return ($key && isset($sensorDisplayNames[$key])) ? $sensorDisplayNames[$key] : ($fallback ?: $key ?: 'Sensor sin identificar');
    };
    $scheduledReports = \App\Models\ScheduledReport::latest()->get();
    $navUser = auth()->user();
?>
<div class="page">
    <div class="topbar">
        <div class="brand">
            <div class="brand-icon"><img src="{{ asset('images/logo-iot.png') }}" alt="Logo IoT"></div>
            <div>
                <div class="eyebrow">IoT Analytics</div>
                <h1>Análisis de Informes</h1>
                <p class="subtitle">Filtrá eventos por fecha y hora, visualizá tendencias térmicas, auditá lecturas críticas y exportá reportes operativos.</p>
            </div>
        </div>
        <nav class="nav">
            <?php if ($navUser && $navUser->canViewDashboard()): ?>
                <a href="{{ route('iot.dashboard') }}" class="{{ request()->routeIs('iot.dashboard') ? 'active' : '' }}">Dashboard</a>
            <?php endif; ?>
            <?php if ($navUser && $navUser->canViewPoints()): ?>
                <a href="{{ route('iot.points') }}" class="{{ request()->routeIs('iot.points') ? 'active' : '' }}">Puntos de medición</a>
            <?php endif; ?>
            <?php if ($navUser && $navUser->canViewReports()): ?>
                <a href="{{ route('iot.analytics') }}" class="{{ request()->routeIs('iot.analytics') ? 'active' : '' }}">Informes</a>
            <?php endif; ?>
            <?php if ($navUser && $navUser->canUseOperationalRegister()): ?>
                <a href="{{ route('iot.operations') }}" class="{{ request()->routeIs('iot.operations') ? 'active' : '' }}">Registro operativo</a>
            <?php endif; ?>
            <?php if ($navUser && $navUser->canManageSettings()): ?>
                <a href="{{ route('iot.settings') }}" class="{{ request()->routeIs('iot.settings') || request()->routeIs('iot.settings.*') ? 'active' : '' }}">Configuración</a>
            <?php endif; ?>
            <form method="POST" action="{{ route('logout') }}" style="display:inline-flex; margin:0;">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <button type="submit" class="btn" style="cursor:pointer;">Salir</button>
            </form>
        </nav>
    </div>

    <section class="hero">
        <form method="GET" action="{{ url('/iot/analytics') }}">
            <div class="filters">
                <div class="field">
                    <label>Desde</label>
                    <input type="datetime-local" name="from" value="{{ $fromInput }}">
                </div>
                <div class="field">
                    <label>Hasta</label>
                    <input type="datetime-local" name="to" value="{{ $toInput }}">
                </div>
                <div class="field">
                    <label>Sensores</label>
                    <div class="sensor-dropdown" id="sensorDropdown">
                        <button type="button" class="sensor-dropdown-button" id="sensorDropdownButton" aria-expanded="false">
                            <span class="sensor-dropdown-main">
                                <span class="sensor-dropdown-label" id="sensorDropdownLabel">Seleccionar cámaras</span>
                                <span class="sensor-dropdown-hint">Seleccioná una o varias cámaras</span>
                            </span>
                            <span class="sensor-chevron" aria-hidden="true"></span>
                        </button>

                        <div class="sensor-dropdown-panel" id="sensorDropdownPanel">
                            <input type="search" class="sensor-search" id="sensorSearch" placeholder="Buscar cámara o sensor...">

                            <div class="sensor-actions">
                                <button type="button" class="sensor-mini-btn" id="selectAllSensors">Seleccionar todas</button>
                                <button type="button" class="sensor-mini-btn" id="clearSensors">Limpiar</button>
                            </div>

                            <div class="sensor-picker">
                                @foreach($sensors as $id => $sensor)
                                    <?php
                                        $sensorLabel = data_get($sensor, 'display_name') ?: data_get($sensor, 'camera_id') ?: data_get($sensor, 'sensor_id') ?: $id;
                                        $sensorId = data_get($sensor, 'sensor_id') ?: $id;
                                    ?>
                                    <label class="sensor-option" data-sensor-option data-search="{{ strtolower($sensorLabel.' '.$sensorId.' '.$id) }}">
                                        <input type="checkbox" name="sensors[]" value="{{ $id }}" data-sensor-checkbox data-label="{{ $sensorLabel }}" @checked(in_array($id, $selectedSensors ?? [], true))>
                                        <span>
                                            <strong>{{ $sensorLabel }}</strong>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label>Rango rápido</label>
                    <div class="quick-ranges">
                        @foreach(['last_1h' => '1h', 'last_6h' => '6h', 'last_12h' => '12h', 'last_24h' => '24h', 'last_7d' => '7d', 'custom' => 'Manual'] as $key => $label)
                            <label><input type="radio" name="range" value="{{ $key }}" @checked($range === $key)><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Aplicar filtros</button>
            </div>
        </form>
    </section>

    @if(session('success'))
        <div class="panel flash-success">
            {{ session('success') }}
        </div>
    @endif

    @if(is_object($errors) && method_exists($errors, 'any') && $errors->any())
        <div class="panel" style="margin-top:18px; border-color:rgba(239,68,68,.35);">
            <strong>No se pudo guardar el reporte</strong>
            @foreach($errors->all() as $error)
                <div style="color:#fecaca; margin-top:6px;">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(is_array($errors ?? null) && !empty($errors))
        <div class="panel" style="margin-top:18px; border-color:rgba(239,68,68,.35);">
            <strong>Advertencias de consulta</strong>
            @foreach($errors as $error)
                <div style="color:#fecaca; margin-top:6px;">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="cards">
        <div class="card"><div class="label">Lecturas</div><div class="value">{{ $summary['readings'] }}</div><div class="hint">eventos filtrados</div></div>
        <div class="card"><div class="label">Sensores</div><div class="value">{{ $summary['sensors'] }}</div><div class="hint">incluidos</div></div>
        <div class="card"><div class="label">Temp. promedio</div><div class="value">{{ $summary['avg_temp'] !== null ? $summary['avg_temp'].'°C' : '—' }}</div><div class="hint">rango seleccionado</div></div>
        <div class="card"><div class="label">Temp. máxima</div><div class="value">{{ $summary['max_temp'] !== null ? $summary['max_temp'].'°C' : '—' }}</div><div class="hint">pico térmico</div></div>
        <div class="card"><div class="label">Alertas</div><div class="value">{{ $summary['warning_count'] + $summary['critical_count'] }}</div><div class="hint">warning + crítico</div></div>
        <div class="card"><div class="label">Puerta abierta</div><div class="value">{{ $summary['door_open_count'] }}</div><div class="hint">eventos detectados</div></div>
    </section>

    <section class="ops-summary">
        <div class="ops-summary-head">
            <div>
                <h2>Resumen operativo</h2>
                <span>Cruce entre aperturas detectadas por sensores y motivos cargados por supervisores.</span>
            </div>
            <div class="ops-summary-badge">Trazabilidad del período</div>
        </div>

        <div class="ops-summary-grid">
            <div class="ops-card">
                <div class="label">Aperturas detectadas</div>
                <div class="value">{{ $summary['operational']['detected_openings'] ?? 0 }}</div>
                <div class="hint">Lecturas con puerta abierta</div>
            </div>

            <div class="ops-card success">
                <div class="label">Aperturas justificadas</div>
                <div class="value">{{ $summary['operational']['justified_openings'] ?? 0 }}</div>
                <div class="hint">
                    @if(($summary['operational']['justification_rate'] ?? null) !== null)
                        {{ $summary['operational']['justification_rate'] }}% de aperturas con registro
                    @else
                        Sin aperturas detectadas
                    @endif
                </div>
            </div>

            <div class="ops-card {{ ($summary['operational']['without_record'] ?? 0) > 0 ? 'danger' : '' }}">
                <div class="label">Aperturas sin registro</div>
                <div class="value">{{ $summary['operational']['without_record'] ?? 0 }}</div>
                <div class="hint">Puerta abierta sin motivo asociado</div>
            </div>

            <div class="ops-card {{ ($summary['operational']['records_without_reading'] ?? 0) > 0 ? 'warning' : '' }}">
                <div class="label">Registros sin lectura</div>
                <div class="value">{{ $summary['operational']['records_without_reading'] ?? 0 }}</div>
                <div class="hint">Carga manual sin medición asociada</div>
            </div>

            <div class="ops-card">
                <div class="label">Motivo más frecuente</div>
                <div class="value" style="font-size:18px; line-height:1.15;">
                    {{ $summary['operational']['most_frequent_reason'] ?? '—' }}
                </div>
                <div class="hint">
                    @if(($summary['operational']['most_frequent_reason_count'] ?? 0) > 0)
                        {{ $summary['operational']['most_frequent_reason_count'] }} registros en el período
                    @else
                        Sin registros operativos
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="grid-main">
        <div class="panel">
            <div class="panel-title">
                <div><h2>Tendencia de temperatura</h2><span>{{ $from->format('d/m/Y H:i') }} → {{ $to->format('d/m/Y H:i') }}</span></div>
                <div class="export-actions">
                    <a class="btn btn-green" href="{{ url('/iot/analytics/export/csv?' . http_build_query(request()->query())) }}">CSV</a>
                    <a class="btn btn-primary" href="{{ url('/iot/analytics/export/excel?' . http_build_query(request()->query())) }}">Excel</a>
                    <a class="btn btn-red" href="{{ url('/iot/analytics/export/pdf?' . http_build_query(request()->query())) }}">PDF</a>
                    <button type="button" class="btn btn-report-auto" id="openScheduledReports">Reportes Automáticos</button>
                </div>
            </div>
            <div class="chart-wrap"><canvas id="trendChart"></canvas></div>
        </div>

        <div class="panel">
            <div class="panel-title"><div><h2>Eventos relevantes</h2><span>Lecturas fuera de rango</span></div></div>
            <div class="alarms">
                @forelse(array_slice($alarms, 0, 14) as $alarm)
                    <div class="alarm-item">
                        <span class="status-pill {{ $alarm['status_class'] }}">{{ $alarm['status'] }}</span>
                        <strong style="margin-top:10px;">{{ $displaySensorName($alarm['camera_id'] ?? $alarm['sensor_id'] ?? null, $alarm['camera_id'] ?? null) }} · {{ $alarm['temperature'] }}°C</strong>
                        <small>{{ $alarm['timestamp_label'] }} · Puerta {{ $alarm['door_open'] ? 'abierta' : 'cerrada' }}</small>
                    </div>
                @empty
                    <div class="empty">No se detectaron eventos críticos en el rango seleccionado.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="panel" style="margin-top:18px;">
        <div class="panel-title">
            <div><h2>Tabla de mediciones</h2><span>Detalle exportable de eventos filtrados · muestra operativa limitada para carga rápida</span></div>
            <button type="button" class="btn" id="openTablePrefs">Preferencias</button>
        </div>

        <div class="table-tools">
            <input type="search" class="table-search" id="readingSearch" placeholder="Buscar por cámara, sensor, estado, puerta...">

            <select class="table-select" id="statusFilter">
                <option value="">Estado: todos</option>
                <option value="success">Normal</option>
                <option value="warning">Alerta</option>
                <option value="danger">Crítico</option>
                <option value="offline">Sin señal</option>
            </select>

            <select class="table-select" id="doorFilter">
                <option value="">Puerta: todas</option>
                <option value="abierta">Abierta</option>
                <option value="cerrada">Cerrada</option>
            </select>

            <select class="table-select" id="operationFilter">
                <option value="">Registro operativo: todos</option>
                <option value="with_record">Con registro</option>
                <option value="without_record">Sin registro</option>
                <option value="not_applicable">No aplica</option>
            </select>

            <select class="table-select" id="pageSizeSelect">
                <option value="10">10 filas</option>
                <option value="20" selected>20 filas</option>
                <option value="50">50 filas</option>
                <option value="100">100 filas</option>
            </select>

            <button type="button" class="btn btn-primary" id="clearTableFilters">Borrar filtros</button>
        </div>

        <div class="table-shell">
            <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th data-col="fecha">Fecha</th>
                        <th data-col="camara">Cámara</th>
                        <th data-col="temp">Temp.</th>
                        <th data-col="humedad">Humedad</th>
                        <th data-col="puerta">Puerta</th>
                        <th data-col="registro">Registro operativo</th>
                        <th data-col="motivo">Motivo</th>
                        <th data-col="responsable">Responsable</th>
                        <th data-col="bateria">Batería</th>
                        <th data-col="senal">Señal</th>
                        <th data-col="estado">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <?php
                            $rowCamera = $displaySensorName($row['camera_id'] ?? $row['sensor_id'] ?? null, $row['camera_id'] ?? null);
                            $rowDoor = !empty($row['is_operation_only']) ? 'Registro manual' : ($row['door_open'] ? 'Abierta' : 'Cerrada');
                            $operationStatus = $row['operation_status'] ?? 'not_applicable';
                            $operationLabel = $row['operation_status_label'] ?? 'No aplica';
                            $operationReason = $row['operation_reason'] ?? null;
                            $operationUser = $row['operation_user'] ?? null;
                            $operationNotes = $row['operation_notes'] ?? null;
                            $operationCreatedAt = $row['operation_created_at'] ?? null;
                            $isOperationOnly = !empty($row['is_operation_only']);
                            $rowSearch = strtolower(trim(
                                ($row['timestamp_label'] ?? '') . ' ' .
                                $rowCamera . ' ' .
                                ($row['sensor_id'] ?? '') . ' ' .
                                ($row['status'] ?? '') . ' ' .
                                $rowDoor . ' ' .
                                ($row['temperature'] ?? '') . ' ' .
                                ($row['humidity'] ?? '') . ' ' .
                                $operationLabel . ' ' .
                                ($operationReason ?? '') . ' ' .
                                ($operationUser ?? '') . ' ' .
                                ($operationNotes ?? '') . ' ' .
                                ($isOperationOnly ? 'sin lectura asociada registro manual' : '')
                            ));
                        ?>
                        <tr data-reading-row class="{{ !empty($row['is_operation_only']) ? 'operation-only-row' : '' }}"
                            data-search="{{ $rowSearch }}"
                            data-status="{{ $row['status_class'] }}"
                            data-door="{{ !empty($row['is_operation_only']) ? 'abierta' : strtolower($rowDoor) }}"
                            data-operation="{{ $operationStatus }}">
                            <td data-col="fecha">{{ $row['timestamp_label'] }}</td>
                            <td data-col="camara">
                                <strong>{{ $rowCamera }}</strong>
                                <div style="color:var(--muted); font-size:11px; margin-top:3px;">{{ $row['sensor_id'] }}</div>
                            </td>
                            <td data-col="temp">{{ $row['temperature'] !== null ? $row['temperature'].'°C' : '—' }}</td>
                            <td data-col="humedad">{{ $row['humidity'] !== null ? $row['humidity'].'%' : '—' }}</td>
                            <td data-col="puerta">{{ $rowDoor }}</td>
                            <td data-col="registro">
                                <span class="operation-badge {{ $operationStatus }}">{{ $operationLabel }}</span>
                                @if($operationCreatedAt)
                                    <div class="operation-detail">{{ $operationCreatedAt }}</div>
                                @endif
                            </td>
                            <td data-col="motivo">
                                @if($operationReason)
                                    <strong>{{ $operationReason }}</strong>
                                    @if($operationNotes)
                                        <div class="operation-detail">{{ $operationNotes }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td data-col="responsable">{{ $operationUser ?: '—' }}</td>
                            <td data-col="bateria">{{ $row['battery'] !== null ? $row['battery'].'%' : '—' }}</td>
                            <td data-col="senal">{{ $row['signal_rssi'] !== null ? $row['signal_rssi'].' dBm' : '—' }}</td>
                            <td data-col="estado"><span class="status-pill {{ $row['status_class'] }}">{{ $row['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="empty">No hay mediciones para el rango seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            <div class="table-footer">
                <div class="table-count" id="tableCount">Mostrando 0 de 0 lecturas</div>
                <div class="pager">
                    <button type="button" id="prevPage">‹</button>
                    <span class="page-indicator" id="pageIndicator">Página 1/1</span>
                    <button type="button" id="nextPage">›</button>
                </div>
            </div>
        </div>
    </section>


    <div class="report-backdrop" id="scheduledReportModal" aria-hidden="true">
        <div class="report-modal" role="dialog" aria-modal="true" aria-labelledby="scheduledReportTitle">
            <div class="report-header">
                <div>
                    <h3 id="scheduledReportTitle">Reportes Automáticos</h3>
                    <p>Programá envíos periódicos desde Informes y dejá preparada la opción de resumen por IA.</p>
                </div>
                <button type="button" class="report-close" id="closeScheduledReports">×</button>
            </div>

            <div class="report-body">
                <form method="POST" action="{{ route('iot.reports.store') }}" class="report-box">
                    @csrf
                    <h4>Nueva configuración</h4>

                    <div class="report-field">
                        <label>Nombre del reporte</label>
                        <input type="text" name="name" placeholder="Ej: Reporte mensual Calidad" required>
                    </div>

                    <div class="report-field">
                        <label>Cámaras incluidas</label>
                        <div class="report-sensors">
                            @foreach($reportSensors ?? [] as $sensor)
                                <label class="report-sensor-option" title="{{ $sensor->display_name }}">
                                    <input type="checkbox" name="camera_ids[]" value="{{ $sensor->sensor_key }}" @checked(in_array($sensor->sensor_key, array_map(fn($id) => $sensors[$id]['camera_key'] ?? $id, $selectedSensors ?? []), true))>
                                    <span><strong>{{ $sensor->display_name }}</strong></span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="report-field">
                        <label>Frecuencia</label>
                        <select name="frequency" required>
                            <option value="weekly">Semanal</option>
                            <option value="biweekly">Quincenal</option>
                            <option value="monthly">Mensual</option>
                        </select>
                    </div>

                    <div class="report-field">
                        <label>Destinatarios</label>
                        <input type="text" name="recipients" placeholder="calidad@empresa.com, sistemas@empresa.com" required>
                    </div>

                    <div class="report-checks">
                        <label class="report-check"><input type="checkbox" name="send_pdf" checked> Generar PDF</label>
                        <label class="report-check"><input type="checkbox" name="send_excel"> Generar Excel</label>
                        <label class="report-check"><input type="checkbox" name="include_ai_summary"> Incluir resumen por IA</label>
                    </div>

                    <div class="report-footer">
                        <button type="button" class="btn" id="cancelScheduledReports">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar reporte</button>
                    </div>
                </form>

                <div class="report-box">
                    <h4>Reportes configurados</h4>

                    <div class="report-list">
                        @forelse($scheduledReports as $report)
                            <div class="report-item">
                                <div class="report-item-top">
                                    <div>
                                        <strong>{{ $report->name }}</strong>
                                        <div class="report-meta">
                                            Frecuencia: {{ ['weekly' => 'Semanal', 'biweekly' => 'Quincenal', 'monthly' => 'Mensual'][$report->frequency] ?? $report->frequency }}<br>
                                            Cámaras: {{ collect($report->camera_ids ?? [])->map(fn($cameraId) => $reportCameraNameMap[$cameraId] ?? $cameraId)->join(', ') ?: 'Sin cámaras' }}<br>
                                            Próximo envío: {{ optional($report->next_run_at)->format('d/m/Y H:i') ?: 'Pendiente' }}<br>
                                            Destinatarios: {{ implode(', ', $report->recipients ?? []) }}
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('iot.reports.destroy', $report->id) }}" onsubmit="return confirm('¿Eliminar este reporte automático?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn report-delete">Eliminar</button>
                                    </form>
                                </div>

                                <div class="report-badges">
                                    @if($report->send_pdf)<span class="report-badge">PDF</span>@endif
                                    @if($report->send_excel)<span class="report-badge">Excel</span>@endif
                                    @if($report->include_ai_summary)<span class="report-badge ai">IA</span>@endif
                                    @if($report->active)<span class="report-badge active">Activo</span>@endif
                                </div>
                            </div>
                        @empty
                            <div class="empty">Todavía no hay reportes automáticos configurados.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="prefs-backdrop" id="tablePrefsModal" aria-hidden="true">
        <div class="prefs-modal" role="dialog" aria-modal="true" aria-labelledby="tablePrefsTitle">
            <div class="prefs-header">
                <h3 id="tablePrefsTitle">Preferencias de tabla</h3>
                <button type="button" class="prefs-close" id="closeTablePrefs">×</button>
            </div>

            <div class="prefs-body">
                <div>
                    <p class="prefs-section-title">Tamaño de página</p>
                    <label class="prefs-option"><input type="radio" name="pref_page_size" value="10"> 10 filas</label>
                    <label class="prefs-option"><input type="radio" name="pref_page_size" value="20" checked> 20 filas</label>
                    <label class="prefs-option"><input type="radio" name="pref_page_size" value="50"> 50 filas</label>
                    <label class="prefs-option"><input type="radio" name="pref_page_size" value="100"> 100 filas</label>
                </div>

                <div>
                    <p class="prefs-section-title">Seleccionar columnas visibles</p>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="fecha" checked> Fecha</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="camara" checked> Cámara</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="temp" checked> Temperatura</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="humedad" checked> Humedad</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="puerta" checked> Puerta</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="registro" checked> Registro operativo</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="motivo" checked> Motivo</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="responsable" checked> Responsable</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="bateria" checked> Batería</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="senal" checked> Señal</label>
                    <label class="prefs-option"><input type="checkbox" data-column-toggle="estado" checked> Estado</label>
                </div>
            </div>

            <div class="prefs-footer">
                <button type="button" class="btn" id="cancelTablePrefs">Cancelar</button>
                <button type="button" class="btn btn-primary" id="applyTablePrefs">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const chartPayloadRaw = @json($chart);
    const ctx = document.getElementById('trendChart');

    function normalizeMinuteLabel(label) {
        const text = String(label ?? '').trim();

        const dateTimeMatch = text.match(/(\d{1,2}:\d{2})(?::\d{2})?/);
        if (dateTimeMatch) {
            return dateTimeMatch[1];
        }

        return text;
    }

    function aggregateChartByMinute(payload) {
        const labels = Array.isArray(payload.labels) ? payload.labels : [];
        const minuteLabels = [];
        const seenLabels = new Set();

        labels.forEach(label => {
            const minute = normalizeMinuteLabel(label);
            if (!seenLabels.has(minute)) {
                seenLabels.add(minute);
                minuteLabels.push(minute);
            }
        });

        const datasets = (payload.datasets || []).map(dataset => {
            const buckets = new Map();

            (dataset.data || []).forEach((value, index) => {
                const numericValue = Number(value);
                if (!Number.isFinite(numericValue)) return;

                const minute = normalizeMinuteLabel(labels[index]);
                if (!buckets.has(minute)) {
                    buckets.set(minute, { sum: 0, count: 0 });
                }

                const bucket = buckets.get(minute);
                bucket.sum += numericValue;
                bucket.count += 1;
            });

            return {
                ...dataset,
                data: minuteLabels.map(minute => {
                    const bucket = buckets.get(minute);
                    return bucket ? Number((bucket.sum / bucket.count).toFixed(2)) : null;
                })
            };
        });

        return { labels: minuteLabels, datasets };
    }

    const chartPayload = aggregateChartByMinute(chartPayloadRaw);

    function calculateTemperatureAxis(payload) {
        const values = [];

        (payload.datasets || []).forEach(dataset => {
            (dataset.data || []).forEach(value => {
                const numericValue = Number(value);
                if (Number.isFinite(numericValue)) {
                    values.push(numericValue);
                }
            });
        });

        if (!values.length) {
            return { min: -2, max: 25 };
        }

        const minValue = Math.min(...values);
        const maxValue = Math.max(...values);
        const spread = Math.max(2, maxValue - minValue);
        const padding = Math.max(2, Math.ceil(spread * 0.15));

        let min = Math.floor(minValue - padding);
        let max = Math.ceil(maxValue + padding);

        if (min === max) {
            min -= 2;
            max += 2;
        }

        min = Math.floor(min / 5) * 5;
        max = Math.ceil(max / 5) * 5;

        if (minValue >= 0 && min < 0) {
            min = 0;
        }

        return { min, max };
    }

    const temperatureAxis = calculateTemperatureAxis(chartPayload);

    const palette = ['#22d3ee', '#8b5cf6', '#22c55e', '#f59e0b', '#ef4444'];
    chartPayload.datasets = chartPayload.datasets.map((dataset, index) => ({
        ...dataset,
        borderColor: palette[index % palette.length],
        backgroundColor: palette[index % palette.length] + '22',
        pointBackgroundColor: palette[index % palette.length],
        pointBorderColor: palette[index % palette.length],
        tension: .35,
        borderWidth: 2,
        pointRadius: 0,
        pointHoverRadius: 4,
        pointHitRadius: 12,
        fill: false
    }));

    new Chart(ctx, {
        type: 'line',
        data: chartPayload,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: '#cbd5e1', usePointStyle: true, boxWidth: 10, font: { weight: 700 } } },
                tooltip: {
                    backgroundColor: 'rgba(2,6,23,.94)',
                    borderColor: 'rgba(34,211,238,.28)',
                    borderWidth: 1,
                    titleColor: '#e5eefb',
                    bodyColor: '#cbd5e1',
                    callbacks: {
                        label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y ?? '—'}°C`
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#94a3b8', maxRotation: 0, autoSkip: true, maxTicksLimit: 6 },
                    grid: { color: 'rgba(148,163,184,.08)' }
                },
                y: {
                    min: temperatureAxis.min,
                    max: temperatureAxis.max,
                    ticks: { color: '#94a3b8', callback: value => value + '°C' },
                    grid: { color: 'rgba(148,163,184,.10)' }
                }
            },
            elements: {
                line: { cubicInterpolationMode: 'monotone' }
            }
        }
    });


    // Selector visual de cámaras
    const sensorDropdownButton = document.getElementById('sensorDropdownButton');
    const sensorDropdownPanel = document.getElementById('sensorDropdownPanel');
    const sensorDropdownLabel = document.getElementById('sensorDropdownLabel');
    const sensorCheckboxes = Array.from(document.querySelectorAll('[data-sensor-checkbox]'));
    const sensorOptions = Array.from(document.querySelectorAll('[data-sensor-option]'));
    const sensorSearch = document.getElementById('sensorSearch');

    function updateSensorDropdownLabel() {
        if (!sensorDropdownLabel) return;

        const selected = sensorCheckboxes.filter(input => input.checked);

        if (selected.length === 0) {
            sensorDropdownLabel.textContent = 'Seleccionar cámaras';
            return;
        }

        if (selected.length === 1) {
            sensorDropdownLabel.textContent = selected[0].dataset.label || '1 cámara seleccionada';
            return;
        }

        sensorDropdownLabel.textContent = `${selected.length} cámaras seleccionadas`;
    }

    sensorDropdownButton?.addEventListener('click', () => {
        sensorDropdownPanel?.classList.toggle('is-open');
        sensorDropdownButton.classList.toggle('is-open');
        sensorDropdownButton.setAttribute('aria-expanded', sensorDropdownPanel?.classList.contains('is-open') ? 'true' : 'false');
    });

    document.addEventListener('click', event => {
        const dropdown = document.getElementById('sensorDropdown');
        if (dropdown && !dropdown.contains(event.target)) {
            sensorDropdownPanel?.classList.remove('is-open');
            sensorDropdownButton?.classList.remove('is-open');
            sensorDropdownButton?.setAttribute('aria-expanded', 'false');
        }
    });

    sensorCheckboxes.forEach(input => input.addEventListener('change', updateSensorDropdownLabel));

    document.getElementById('selectAllSensors')?.addEventListener('click', () => {
        sensorCheckboxes.forEach(input => input.checked = true);
        updateSensorDropdownLabel();
    });

    document.getElementById('clearSensors')?.addEventListener('click', () => {
        sensorCheckboxes.forEach(input => input.checked = false);
        updateSensorDropdownLabel();
    });

    sensorSearch?.addEventListener('input', () => {
        const value = sensorSearch.value.trim().toLowerCase();
        sensorOptions.forEach(option => {
            option.style.display = option.dataset.search.includes(value) ? '' : 'none';
        });
    });

    updateSensorDropdownLabel();


    // Reportes automáticos: modal integrado en Informes
    const scheduledReportModal = document.getElementById('scheduledReportModal');
    const openScheduledReports = document.getElementById('openScheduledReports');
    const closeScheduledReports = document.getElementById('closeScheduledReports');
    const cancelScheduledReports = document.getElementById('cancelScheduledReports');

    function openScheduledReportModal() {
        scheduledReportModal?.classList.add('is-open');
        scheduledReportModal?.setAttribute('aria-hidden', 'false');
    }

    function closeScheduledReportModal() {
        scheduledReportModal?.classList.remove('is-open');
        scheduledReportModal?.setAttribute('aria-hidden', 'true');
    }

    openScheduledReports?.addEventListener('click', openScheduledReportModal);
    closeScheduledReports?.addEventListener('click', closeScheduledReportModal);
    cancelScheduledReports?.addEventListener('click', closeScheduledReportModal);

    scheduledReportModal?.addEventListener('click', event => {
        if (event.target === scheduledReportModal) closeScheduledReportModal();
    });

    // Tabla de mediciones: filtros automáticos, paginado y preferencias en vivo
    const tableRows = Array.from(document.querySelectorAll('[data-reading-row]'));
    const readingSearch = document.getElementById('readingSearch');
    const statusFilter = document.getElementById('statusFilter');
    const doorFilter = document.getElementById('doorFilter');
    const operationFilter = document.getElementById('operationFilter');
    const pageSizeSelect = document.getElementById('pageSizeSelect');
    const tableCount = document.getElementById('tableCount');
    const pageIndicator = document.getElementById('pageIndicator');
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');

    let currentPage = 1;
    let filteredRows = [...tableRows];

    function applyTableFilters() {
        const search = (readingSearch?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || '';
        const door = doorFilter?.value || '';
        const operation = operationFilter?.value || '';

        filteredRows = tableRows.filter(row => {
            const matchesSearch = !search || (row.dataset.search || '').includes(search);
            const matchesStatus = !status || row.dataset.status === status;
            const matchesDoor = !door || row.dataset.door === door;
            const matchesOperation = !operation || row.dataset.operation === operation;

            return matchesSearch && matchesStatus && matchesDoor && matchesOperation;
        });

        currentPage = 1;
        renderTablePage();
    }

    function renderTablePage() {
        const pageSize = Number(pageSizeSelect?.value || 20);
        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));

        currentPage = Math.min(Math.max(1, currentPage), totalPages);

        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        tableRows.forEach(row => row.style.display = 'none');
        filteredRows.slice(start, end).forEach(row => row.style.display = '');

        const visibleFrom = total === 0 ? 0 : start + 1;
        const visibleTo = Math.min(end, total);

        if (tableCount) {
            tableCount.textContent = `Mostrando ${visibleFrom}-${visibleTo} de ${total} lecturas filtradas`;
        }

        if (pageIndicator) {
            pageIndicator.textContent = `Página ${currentPage}/${totalPages}`;
        }

        if (prevPage) prevPage.disabled = currentPage <= 1;
        if (nextPage) nextPage.disabled = currentPage >= totalPages;
    }

    function syncPreferencePageSize() {
        const checked = document.querySelector(`input[name="pref_page_size"][value="${pageSizeSelect?.value || 20}"]`);
        if (checked) checked.checked = true;
    }

    function applyVisibleColumns() {
        document.querySelectorAll('[data-column-toggle]').forEach(toggle => {
            const column = toggle.dataset.columnToggle;
            document.querySelectorAll(`[data-col="${column}"]`).forEach(cell => {
                cell.classList.toggle('hide-column', !toggle.checked);
            });
        });
    }

    readingSearch?.addEventListener('input', applyTableFilters);
    statusFilter?.addEventListener('change', applyTableFilters);
    doorFilter?.addEventListener('change', applyTableFilters);

    operationFilter?.addEventListener('change', applyTableFilters);
    operationFilter?.addEventListener('input', applyTableFilters);

    pageSizeSelect?.addEventListener('change', () => {
        syncPreferencePageSize();
        currentPage = 1;
        renderTablePage();
    });

    document.querySelectorAll('input[name="pref_page_size"]').forEach(input => {
        input.addEventListener('change', () => {
            if (!input.checked || !pageSizeSelect) return;
            pageSizeSelect.value = input.value;
            currentPage = 1;
            renderTablePage();
        });
    });

    document.querySelectorAll('[data-column-toggle]').forEach(input => {
        input.addEventListener('change', applyVisibleColumns);
    });

    prevPage?.addEventListener('click', () => {
        currentPage -= 1;
        renderTablePage();
    });

    nextPage?.addEventListener('click', () => {
        currentPage += 1;
        renderTablePage();
    });

    document.getElementById('clearTableFilters')?.addEventListener('click', () => {
        if (readingSearch) readingSearch.value = '';
        if (statusFilter) statusFilter.value = '';
        if (doorFilter) doorFilter.value = '';
        if (operationFilter) operationFilter.value = '';
        applyTableFilters();
    });

    const prefsModal = document.getElementById('tablePrefsModal');
    const openTablePrefs = document.getElementById('openTablePrefs');
    const closeTablePrefs = document.getElementById('closeTablePrefs');
    const cancelTablePrefs = document.getElementById('cancelTablePrefs');
    const applyTablePrefs = document.getElementById('applyTablePrefs');

    function openPrefs() {
        prefsModal?.classList.add('is-open');
        prefsModal?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closePrefs() {
        prefsModal?.classList.remove('is-open');
        prefsModal?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    openTablePrefs?.addEventListener('click', openPrefs);
    closeTablePrefs?.addEventListener('click', closePrefs);
    cancelTablePrefs?.addEventListener('click', closePrefs);
    applyTablePrefs?.addEventListener('click', closePrefs);

    prefsModal?.addEventListener('click', event => {
        if (event.target === prefsModal) closePrefs();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && prefsModal?.classList.contains('is-open')) {
            closePrefs();
        }
    });

    syncPreferencePageSize();
    applyVisibleColumns();
    applyTableFilters();


</script>
</body>
</html>
