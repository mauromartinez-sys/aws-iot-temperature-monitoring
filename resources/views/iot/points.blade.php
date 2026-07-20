<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Puntos de medición - IoT Dashboard</title>
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

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

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

        .brand h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: -0.03em;
        }

        .brand p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

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
            margin-bottom: 22px;
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
            padding: 20px;
            border-radius: 24px;
            background: rgba(2, 6, 23, 0.42);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }

        .hero-tile .value {
            font-size: 34px;
            font-weight: 900;
            letter-spacing: -0.04em;
            margin-bottom: 6px;
        }

        .hero-tile .label {
            color: var(--muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .selector-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }

        .selector-card {
            border: 1px solid var(--border);
            background: var(--panel);
            border-radius: 28px;
            padding: 20px;
            box-shadow: 0 18px 55px rgba(0, 0, 0, 0.22);
            cursor: pointer;
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
            color: inherit;
        }

        .selector-card:hover,
        .selector-card.active {
            transform: translateY(-4px);
            border-color: rgba(34, 211, 238, 0.45);
            box-shadow: 0 24px 65px rgba(0, 0, 0, 0.28);
        }

        .selector-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: start;
            margin-bottom: 14px;
        }

        .selector-title {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .selector-subtitle {
            color: var(--muted);
            font-size: 13px;
            margin-top: 3px;
        }

        .selector-footer {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 16px;
        }

        .mini-stat {
            padding: 10px;
            border-radius: 16px;
            background: rgba(2, 6, 23, 0.34);
            border: 1px solid rgba(148, 163, 184, 0.10);
        }

        .mini-stat .label {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 6px;
        }

        .mini-stat .value {
            font-size: 14px;
            font-weight: 800;
        }

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

        .badge.success {
            color: #86efac;
            background: rgba(34, 197, 94, 0.12);
            border-color: rgba(34, 197, 94, 0.25);
        }

        .badge.warning {
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.28);
        }

        .badge.danger {
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.28);
        }

        .badge.offline {
            color: #cbd5e1;
            background: rgba(148, 163, 184, 0.10);
        }

        .room {
            position: relative;
            width: 100%;
            aspect-ratio: 1.2 / 0.9;
            border-radius: 24px;
            overflow: hidden;
            background:
                radial-gradient(circle at 80% 25%, rgba(34, 211, 238, 0.25), transparent 25%),
                linear-gradient(160deg, #122338 0%, #0a1424 100%);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .room-frame {
            position: absolute;
            inset: 14px;
            border-radius: 20px;
            background: linear-gradient(180deg, #dce8f6, #b9ccdf);
            border: 2px solid rgba(255, 255, 255, 0.15);
            box-shadow: inset 0 0 30px rgba(255,255,255,0.14);
        }

        .room-interior {
            position: absolute;
            inset: 26px 28px 26px 30px;
            border-radius: 16px;
            background:
                linear-gradient(180deg, rgba(240, 248, 255, 0.92), rgba(207, 224, 240, 0.92));
            overflow: hidden;
        }

        .room-interior:before,
        .room-interior:after {
            content: "";
            position: absolute;
            left: 12px;
            right: 12px;
            height: 6px;
            border-radius: 8px;
            background: rgba(140, 167, 191, 0.75);
        }

        .room-interior:before { top: 34%; }
        .room-interior:after { top: 66%; }

        .crate, .crate2, .crate3 {
            position: absolute;
            width: 32%;
            height: 18%;
            border-radius: 10px;
            background: linear-gradient(180deg, #9bd2ff, #6eb6f7);
            box-shadow: inset 0 -8px 18px rgba(0,0,0,0.12);
        }

        .crate  { left: 12%; top: 14%; }
        .crate2 { right: 12%; top: 44%; background: linear-gradient(180deg, #b4f0c5, #7bd899); }
        .crate3 { left: 24%; bottom: 10%; background: linear-gradient(180deg, #f9d08a, #f0aa53); }

        .room-door {
            position: absolute;
            top: 10px;
            bottom: 10px;
            left: 10px;
            width: 42%;
            border-radius: 18px;
            background:
                linear-gradient(180deg, #eff4fa 0%, #c9d8e9 100%);
            border: 2px solid rgba(255,255,255,0.6);
            transform-origin: left center;
            transition: transform .65s cubic-bezier(.2,.7,.2,1);
            box-shadow: 14px 0 18px rgba(0,0,0,0.18);
            z-index: 4;
        }

        .room-door:before {
            content: "";
            position: absolute;
            top: 16%;
            right: 14%;
            width: 10px;
            height: 46%;
            border-radius: 999px;
            background: linear-gradient(180deg, #8296aa, #5d7389);
        }

        .room-door:after {
            content: "";
            position: absolute;
            inset: 10px;
            border-radius: 14px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .selector-card:hover .room-door,
        .selector-card.active .room-door,
        .detail-room.open .room-door,
        .detail-room:hover .room-door {
            transform: perspective(1000px) rotateY(-58deg);
        }

        .temp-pill {
            position: absolute;
            right: 12px;
            top: 12px;
            z-index: 6;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(2, 6, 23, 0.78);
            border: 1px solid rgba(148, 163, 184, 0.18);
            font-size: 12px;
            font-weight: 800;
            color: #fff;
        }

        /* FIX DEFINITIVO: 3 cards alineadas por áreas de grilla
           Fila 1: cámara seleccionada | salud general
           Fila 2: evolución de temperatura full width */
        .detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.36fr) minmax(370px, 0.74fr);
            grid-template-areas:
                "detail health"
                "chart chart";
            gap: 18px;
            align-items: stretch;
            margin-bottom: 18px;
        }

        .detail-grid > .main-column {
            grid-area: detail;
            display: flex;
            flex-direction: column;
            min-width: 0;
            height: 100%;
            align-self: stretch;
        }

        .detail-grid > .side-stack {
            grid-area: health;
            display: flex;
            flex-direction: column;
            min-width: 0;
            height: 100%;
            align-self: stretch;
        }

        .detail-grid > .main-column > .panel:first-child,
        .detail-grid > .side-stack > .health-panel {
            flex: 1 1 auto;
            height: 100%;
            min-height: 100%;
        }

.detail-grid > .main-column > .panel:first-child .alarm-history {
            margin-top: auto;
        }

        .detail-grid > .charts-grid {
            grid-area: chart;
            width: 100%;
            margin-bottom: 0;
        }

        .main-column {
            min-width: 0;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 22px;
            box-shadow: 0 18px 60px rgba(0, 0, 0, 0.22);
            min-width: 0;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .panel-title {
            margin: 0;
            font-size: 18px;
            letter-spacing: -0.03em;
        }

        .panel-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .detail-layout {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 18px;
            align-items: start;
        }

        .detail-room-wrap {
            position: relative;
        }

        .detail-room {
            max-width: 460px;
            margin: 0 auto;
        }

        .room-overlay {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 18px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            z-index: 8;
        }

        .overlay-card {
            flex: 1;
            padding: 14px;
            border-radius: 18px;
            background: rgba(2, 6, 23, 0.75);
            border: 1px solid rgba(148, 163, 184, 0.14);
            backdrop-filter: blur(10px);
        }

        .overlay-card .label {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 6px;
        }

        .overlay-card .value {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .metric-box {
            padding: 16px;
            border-radius: 18px;
            background: rgba(2, 6, 23, 0.34);
            border: 1px solid rgba(148, 163, 184, 0.10);
        }

        .metric-box .label {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 8px;
        }

        .metric-box .value {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .side-stack {
            min-width: 0;
        }

        .health-panel {
            display: flex;
            flex-direction: column;
        }

        .health-panel .factor-list {
            flex: 1;
            align-content: start;
        }

        .health-score {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
        }

        .score-circle {
            width: 112px;
            height: 112px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at center, rgba(15, 23, 42, 1) 52%, transparent 53%),
                conic-gradient(var(--health-color, #22d3ee) 0deg var(--health-deg, 0deg), rgba(255,255,255,0.08) var(--health-deg, 0deg) 360deg);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 0 24px rgba(34, 211, 238, 0.10), inset 0 0 35px rgba(255,255,255,0.05);
        }

        .score-circle span {
            font-size: 28px;
            font-weight: 900;
        }

        .progress {
            height: 12px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.12);
            overflow: hidden;
            margin-top: 12px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--health-color, #22d3ee), rgba(255,255,255,0.38));
            width: 0%;
            transition: width .35s ease, background .35s ease;
        }

        .factor-list,
        .modules-grid,
        .future-grid {
            display: grid;
            gap: 10px;
        }

        .factor-item,
        .module-item,
        .future-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 13px 14px;
            border-radius: 16px;
            background: rgba(2, 6, 23, 0.34);
            border: 1px solid rgba(148, 163, 184, 0.10);
        }

        .tag {
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .tag.good {
            color: #86efac;
            background: rgba(34, 197, 94, 0.12);
        }

        .tag.warn {
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.12);
        }

        .tag.bad {
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.12);
        }

        .tag.pending {
            color: #cbd5e1;
            background: rgba(148, 163, 184, 0.12);
        }


        .alarm-history {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid rgba(148, 163, 184, 0.12);
        }

        .alarm-history-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .alarm-history-head h4 {
            margin: 0;
            font-size: 16px;
            letter-spacing: -0.02em;
        }

        .alarm-history-head p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .alarm-list {
            display: grid;
            gap: 12px;
        }

        .alarm-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: start;
            padding: 15px;
            border-radius: 18px;
            background:
                linear-gradient(135deg, rgba(2, 6, 23, 0.52), rgba(15, 23, 42, 0.44));
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .alarm-item.open,
        .alarm-item.acknowledged,
        .alarm-item.silenced {
            border-color: rgba(245, 158, 11, 0.28);
            background:
                linear-gradient(135deg, rgba(245, 158, 11, 0.10), rgba(2, 6, 23, 0.46));
        }

        .alarm-main strong {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .alarm-summary {
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.45;
            margin-bottom: 8px;
        }

        .alarm-meta {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.55;
        }

        .alarm-badges {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        .alarm-empty {
            padding: 18px;
            border-radius: 18px;
            background: rgba(2, 6, 23, 0.28);
            border: 1px dashed rgba(148, 163, 184, 0.18);
            color: var(--muted);
            line-height: 1.55;
        }


        .alarm-filter-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .alarm-filter-btn {
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(15, 23, 42, 0.74);
            color: #cbd5e1;
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            font-family: inherit;
        }

        .alarm-filter-btn.active,
        .alarm-filter-btn:hover {
            border-color: rgba(34, 211, 238, 0.55);
            color: #fff;
            background: rgba(14, 116, 144, 0.22);
        }

        .alarm-actions {
            grid-column: 1 / -1;
            display: grid;
            gap: 8px;
            margin-top: 10px;
        }

        .alarm-action-row {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 8px;
            align-items: center;
        }

        .alarm-note-input {
            min-height: 38px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.42);
            color: var(--text);
            padding: 9px 11px;
            font-size: 12px;
            outline: none;
        }

        .alarm-action-btn {
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

        .alarm-action-btn.warn {
            border-color: rgba(245, 158, 11, 0.35);
            background: rgba(245, 158, 11, 0.12);
            color: #fde68a;
        }

        .alarm-action-btn.good {
            border-color: rgba(34, 197, 94, 0.35);
            background: rgba(34, 197, 94, 0.12);
            color: #bbf7d0;
        }

        .alarm-management-note {
            grid-column: 1 / -1;
            color: #cbd5e1;
            background: rgba(2, 6, 23, 0.28);
            border: 1px solid rgba(148, 163, 184, 0.10);
            padding: 10px 12px;
            border-radius: 14px;
            font-size: 12px;
            line-height: 1.45;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            width: 100%;
        }

        .charts-grid .panel {
            grid-column: 1 / -1;
            width: 100%;
        }

        .chart-wrap {
            height: 360px;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            margin-top: 18px;
            align-items: start;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            padding: 12px 10px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 10px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.10);
            font-size: 14px;
        }

        tr:last-child td { border-bottom: none; }

        .muted { color: var(--muted); }




        /* Override final de alineación */
        .detail-grid {
            grid-template-areas:
                "detail health"
                "chart chart";
            align-items: stretch;
        }

        .detail-grid > .main-column {
            grid-area: detail;
            display: flex;
            flex-direction: column;
            align-self: stretch;
            height: 100%;
        }

        .detail-grid > .side-stack {
            grid-area: health;
            display: flex;
            flex-direction: column;
            align-self: stretch;
            height: 100%;
        }

        .detail-grid > .main-column > .panel:first-child,
        .detail-grid > .side-stack > .health-panel {
            flex: 1 1 auto;
            height: 100%;
            min-height: 100%;
        }

        .detail-grid > .main-column > .panel:first-child {
            display: flex;
            flex-direction: column;
        }

        .detail-grid > .main-column > .panel:first-child .alarm-history {
            margin-top: auto;
        }

        .detail-grid > .charts-grid {
            grid-area: chart;
        }

        @media (max-width: 1260px) {
            .hero-grid,
            .detail-grid,
            .detail-layout,
            .charts-grid,
            .bottom-grid {
                grid-template-columns: 1fr;
            }

            .selector-grid {
                grid-template-columns: 1fr;
            }

            .detail-grid {
                grid-template-areas:
                    "detail"
                    "health"
                    "chart";
                align-items: start;
            }

            .detail-grid > .main-column,
            .detail-grid > .side-stack,
            .detail-grid > .main-column > .panel:first-child,
            .detail-grid > .side-stack > .health-panel {
                height: auto;
                min-height: 0;
            }

            .detail-grid > .main-column > .panel:first-child .alarm-history {
                margin-top: 20px;
            }


            .detail-grid > .main-column,
            .detail-grid > .side-stack,
            .detail-grid > .main-column > .panel:first-child,
            .detail-grid > .side-stack > .panel:first-child {
                height: auto;
            }

    }

        @media (max-width: 720px) {
            .page {
                width: min(100% - 20px, 1500px);
                padding-top: 14px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero-status,
            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .detail-layout {
                gap: 24px;
            }

            .chart-wrap {
                height: 280px;
            }
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
<div class="page">

    <header class="topbar">
        <div class="brand">
            <div class="brand-mark"><img src="{{ asset('images/logo-iot.png') }}" alt="Logo IoT"></div>
            <div>
                <h1>Puntos de medición</h1>
                <p>Detalle visual e interactivo por cámara frigorífica</p>
            </div>
        </div>

        <nav class="nav">
            <a href="/iot/dashboard">Dashboard</a>
            <a class="active" href="/iot/points">Puntos de medición</a>
            <a href="/iot/analytics">Análisis de informes</a>
            <a href="/iot/settings">Configuración</a>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-grid">
            <div>
                <div class="eyebrow">
                    <span class="pulse"></span>
                    Vista técnica por cámara
                </div>

                <h2>Explorá cada <span>cámara frigorífica</span></h2>

                <p class="hero-desc">
                    Seleccioná una cámara para visualizar su estado actual, evolución térmica,
                    variables ambientales y una estructura preparada para salud integral del equipo,
                    incluyendo futuras métricas de compresor, vibración y consumo eléctrico.
                </p>
            </div>

            <div class="hero-status">
                <div class="hero-tile">
                    <div class="value">{{ $summary['total_points'] }}</div>
                    <div class="label">Cámaras monitoreadas</div>
                </div>

                <div class="hero-tile">
                    <div class="value">{{ $summary['alerts'] }}</div>
                    <div class="label">Eventos detectados</div>
                </div>

                <div class="hero-tile">
                    <div class="value">{{ $summary['avg_health'] }}%</div>
                    <div class="label">Salud promedio</div>
                </div>

                <div class="hero-tile">
                    <div class="value">
                        {{ $summary['avg_temp'] !== null ? $summary['avg_temp'].'°C' : '—' }}
                    </div>
                    <div class="label">Temperatura promedio</div>
                </div>
            </div>
        </div>
    </section>

    <section class="selector-grid">
        @foreach($points as $point)
            <button type="button"
                    class="selector-card {{ $loop->first ? 'active' : '' }}"
                    data-sensor-id="{{ $point['sensor_id'] }}">
                <div class="selector-top">
                    <div>
                        <div class="selector-title">{{ $point['display_name'] ?? $point['camera_id'] ?? $point['sensor_id'] ?? 'Sensor sin identificar' }}</div>
                        <div class="selector-subtitle">{{ $point['description'] }}</div>
                    </div>
                    <span class="badge {{ $point['status_class'] }}">{{ $point['status_label'] }}</span>
                </div>

                <div class="room">
                    <div class="temp-pill">
                        {{ $point['temperature'] !== null ? number_format($point['temperature'], 1).'°C' : '—' }}
                    </div>
                    <div class="room-frame"></div>
                    <div class="room-interior">
                        <div class="crate"></div>
                        <div class="crate2"></div>
                        <div class="crate3"></div>
                    </div>
                    <div class="room-door"></div>
                </div>

                <div class="selector-footer">
                    <div class="mini-stat">
                        <div class="label">Humedad</div>
                        <div class="value">{{ $point['humidity'] !== null ? $point['humidity'].'%' : '—' }}</div>
                    </div>
                    <div class="mini-stat">
                        <div class="label">Puerta</div>
                        <div class="value">{{ $point['door_open'] ? 'Abierta' : 'Cerrada' }}</div>
                    </div>
                    <div class="mini-stat">
                        <div class="label">Salud</div>
                        <div class="value">{{ $point['health_score'] }}%</div>
                    </div>
                </div>
            </button>
        @endforeach
    </section>

    @php $firstPoint = $points[0] ?? null; @endphp

    @if($firstPoint)
        <section class="detail-grid">
            <div class="main-column">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title" id="detailTitle">{{ $firstPoint['display_name'] ?? $firstPoint['camera_id'] ?? $firstPoint['sensor_id'] ?? 'Sensor sin identificar' }}</h3>
                        <p class="panel-subtitle" id="detailSubtitle">{{ $firstPoint['location'] }} · {{ $firstPoint['description'] }}</p>
                    </div>
                    <span id="detailStatus" class="badge {{ $firstPoint['status_class'] }}">{{ $firstPoint['status_label'] }}</span>
                </div>

                <div class="detail-layout">
                    <div class="detail-room-wrap">
                        <div class="room detail-room {{ $firstPoint['door_open'] ? 'open' : '' }}" id="detailRoom">
                            <div class="temp-pill" id="detailRoomTemp">
                                {{ $firstPoint['temperature'] !== null ? number_format($firstPoint['temperature'], 1).'°C' : '—' }}
                            </div>
                            <div class="room-frame"></div>
                            <div class="room-interior">
                                <div class="crate"></div>
                                <div class="crate2"></div>
                                <div class="crate3"></div>
                            </div>
                            <div class="room-door"></div>
                        </div>

                        <div class="room-overlay">
                            <div class="overlay-card">
                                <div class="label">Temperatura actual</div>
                                <div class="value" id="overlayTemperature">
                                    {{ $firstPoint['temperature'] !== null ? number_format($firstPoint['temperature'], 1).'°C' : '—' }}
                                </div>
                            </div>
                            <div class="overlay-card">
                                <div class="label">Estado de puerta</div>
                                <div class="value" id="overlayDoor">
                                    {{ $firstPoint['door_open'] ? 'Abierta' : 'Cerrada' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="metrics-grid">
                            <div class="metric-box">
                                <div class="label">Humedad</div>
                                <div class="value" id="metricHumidity">{{ $firstPoint['humidity'] !== null ? $firstPoint['humidity'].'%' : '—' }}</div>
                            </div>
                            <div class="metric-box">
                                <div class="label">Batería</div>
                                <div class="value" id="metricBattery">{{ $firstPoint['battery'] !== null ? $firstPoint['battery'].'%' : '—' }}</div>
                            </div>
                            <div class="metric-box">
                                <div class="label">RSSI</div>
                                <div class="value" id="metricRssi">{{ $firstPoint['signal_rssi'] !== null ? $firstPoint['signal_rssi'].' dBm' : '—' }}</div>
                            </div>
                            <div class="metric-box">
                                <div class="label">SNR</div>
                                <div class="value" id="metricSnr">{{ $firstPoint['signal_snr'] !== null ? $firstPoint['signal_snr'].' dB' : '—' }}</div>
                            </div>
                            <div class="metric-box">
                                <div class="label">Última señal</div>
                                <div class="value" id="metricMinutes" title="{{ $firstPoint['last_seen_at_label'] ?? '—' }}">{{ $firstPoint['minutes_ago_label'] ?? '—' }}</div>
                            </div>
                            <div class="metric-box">
                                <div class="label">Sensor ID</div>
                                <div class="value" id="metricSensorId">{{ $firstPoint['sensor_id'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alarm-history">
                    <div class="alarm-history-head">
                        <div>
                            <h4>Historial de alarmas</h4>
                            <p>Eventos automáticos generados por Lambda para esta cámara</p>
                        </div>
                        <span class="tag pending" id="alarmEventsCount">{{ count($firstPoint['alarm_events'] ?? []) }} eventos</span>
                    </div>

                    <div class="alarm-filter-bar">
                        <button type="button" class="alarm-filter-btn active" data-alarm-filter="all">Todas</button>
                        <button type="button" class="alarm-filter-btn" data-alarm-filter="active">Activas</button>
                        <button type="button" class="alarm-filter-btn" data-alarm-filter="resolved">Resueltas</button>
                    </div>

                    <div class="alarm-list" id="alarmEventsList">
                        @php $initialAlarmEvents = $firstPoint['alarm_events'] ?? []; @endphp
                        @forelse($initialAlarmEvents as $alarm)
                            <div id="alarm-{{ $alarm['alarm_id'] ?? '' }}" class="alarm-item {{ $alarm['status'] ?? 'open' }}" data-status="{{ in_array(($alarm['status'] ?? 'open'), ['open','acknowledged','silenced'], true) ? 'active' : 'resolved' }}">
                                <div class="alarm-main">
                                    <strong>{{ $alarm['alarm_name'] ?? $alarm['alarm_type_label'] ?? 'Alarma' }}</strong>
                                    <div class="alarm-summary">{{ $alarm['summary'] ?? 'Evento de alarma' }}</div>
                                    <div class="alarm-meta">
                                        Inicio: {{ $alarm['started_at'] ?? '—' }}
                                        · Última detección: {{ $alarm['last_detected_at'] ?? '—' }}
                                        @if(!empty($alarm['resolved_at']))
                                            · Resuelta: {{ $alarm['resolved_at'] }}
                                        @endif
                                        · Puerta: {{ ($alarm['door_state'] ?? '') === 'open' ? 'Abierta' : (($alarm['door_state'] ?? '') === 'closed' ? 'Cerrada' : '—') }}
                                    </div>
                                </div>
                                <div class="alarm-badges">
                                    <span class="tag {{ $alarm['status_class'] ?? 'pending' }}">{{ $alarm['status_label'] ?? 'Estado' }}</span>
                                    <span class="tag {{ $alarm['severity_class'] ?? 'warn' }}">{{ $alarm['severity_label'] ?? 'Severidad' }}</span>
                                </div>

                                @if(!empty($alarm['last_comment']))
                                    <div class="alarm-management-note">
                                        Último comentario: {{ $alarm['last_comment'] }}
                                        @if(!empty($alarm['last_comment_by']))
                                            · {{ $alarm['last_comment_by'] }}
                                        @endif
                                    </div>
                                @endif

                                @if(auth()->user()?->canAcknowledgeAlarms())
                                    <div class="alarm-actions">
                                        <form method="POST" action="{{ route('iot.alarms.comment', $alarm['alarm_id']) }}" class="alarm-action-row">
                                            @csrf
                                            <input class="alarm-note-input" type="text" name="note" maxlength="500" placeholder="Agregar comentario operativo">
                                            <button class="alarm-action-btn" type="submit">Comentar</button>
                                            @if(in_array(($alarm['status'] ?? 'open'), ['open'], true))
                                                <button class="alarm-action-btn good" type="submit" formaction="{{ route('iot.alarms.acknowledge', $alarm['alarm_id']) }}">Reconocer</button>
                                                <button class="alarm-action-btn warn" type="submit" formaction="{{ route('iot.alarms.silence', $alarm['alarm_id']) }}">Silenciar</button>
                                            @endif
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="alarm-empty">
                                Todavía no hay alarmas automáticas registradas para esta cámara.
                                Cuando Lambda detecte temperatura fuera de rango, batería baja, RSSI débil u otra condición,
                                el evento aparecerá en este historial.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>


                



            </div>

            <div class="side-stack">
                <div class="panel health-panel">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Salud general de la cámara</h3>
                            <p class="panel-subtitle">Preparado para crecer con nuevas variables</p>
                        </div>
                    </div>

                    <div class="health-score">
                        <div class="score-circle" id="healthCircle" style="--health-color: {{ $firstPoint['health_score'] >= 90 ? '#22c55e' : ($firstPoint['health_score'] >= 75 ? '#22d3ee' : ($firstPoint['health_score'] >= 60 ? '#f59e0b' : '#ef4444')) }}; --health-deg: {{ min(100, max(0, (int) $firstPoint['health_score'])) * 3.6 }}deg;">
                            <span id="healthScore">{{ $firstPoint['health_score'] }}%</span>
                        </div>
                        <div>
                            <div class="badge {{ $firstPoint['health_class'] }}" id="healthLabel">{{ $firstPoint['health_label'] }}</div>
                            <div class="muted" style="margin-top:10px;" id="healthMessage">
                                {{ $firstPoint['status_message'] }}
                            </div>
                            <div class="progress">
                                <div class="progress-bar" id="healthBar" style="width: {{ $firstPoint['health_score'] }}%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="factor-list" id="factorList">
                        @foreach($firstPoint['health_factors'] as $factor)
                            @continue(($factor['label'] ?? '') === 'Humedad')
                            <div class="factor-item">
                                <div>
                                    <strong>{{ $factor['label'] }}</strong>
                                    <div class="muted">{{ $factor['value'] }}</div>
                                </div>
                                <span class="tag {{ $factor['status'] }}">
                                    {{ $factor['status'] === 'good' ? 'OK' : ($factor['status'] === 'warn' ? 'Atención' : 'Crítico') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        
            <section class="charts-grid">
                <div class="panel">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Evolución de temperatura</h3>
                            <p class="panel-subtitle">Histórico reciente del punto seleccionado · vista ampliada</p>
                        </div>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="tempChart"></canvas>
                    </div>
                </div>
            </section>

        </section>

        <section class="bottom-grid">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title">Últimas lecturas</h3>
                        <p class="panel-subtitle">Eventos recientes del punto de medición seleccionado</p>
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Temperatura</th>
                                <th>Humedad</th>
                                <th>Puerta</th>
                                <th>Batería</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            @foreach($firstPoint['history_rows'] as $row)
                                <tr>
                                    <td>{{ $row['timestamp'] }}</td>
                                    <td>{{ $row['temperature'] }}</td>
                                    <td>{{ $row['humidity'] }}</td>
                                    <td>{{ $row['door'] }}</td>
                                    <td>{{ $row['battery'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
</div>

<script>
    const points = @json($points ?? []);
    const sensorNameMap = @json($sensorNameMap ?? []);
    const displaySensorName = (point) => {
        if (point?.display_name) return point.display_name;
        const key = point?.camera_id || point?.sensor_id || '';
        return sensorNameMap?.[key] || key || 'Sensor sin identificar';
    };
    const healthColor = (score) => {
        const value = Number(score || 0);
        if (value >= 90) return '#22c55e';
        if (value >= 75) return '#22d3ee';
        if (value >= 60) return '#f59e0b';
        return '#ef4444';
    };
    const pointsMap = Object.fromEntries(points.map(point => [point.sensor_id, point]));
    let selectedId = points.length ? points[0].sensor_id : null;

    const statusClasses = ['success', 'warning', 'danger', 'offline'];

    function formatElapsedMinutes(minutes) {
        if (minutes === null || minutes === undefined || minutes === '') return '—';

        const value = Number(minutes);
        if (Number.isNaN(value)) return '—';

        if (value < 1) {
            return '1 min';
        }

        if (value < 60) {
            return `${Math.max(1, Math.floor(value))} min`;
        }

        if (value < 1440) {
            return `${Math.max(1, Math.floor(value / 60))} h`;
        }

        const days = Math.max(1, Math.floor(value / 1440));
        return `${days} ${days === 1 ? 'día' : 'días'}`;
    }

    function setBadgeClasses(element, newClass) {
        statusClasses.forEach(cls => element.classList.remove(cls));
        element.classList.add(newClass);
    }



    function preserveAlarmScrollOnSubmit() {
        document.addEventListener('submit', (event) => {
            if (event.target.closest('.alarm-action-row')) {
                sessionStorage.setItem('iotAlarmScrollY', String(window.scrollY));
            }
        });

        const targetAlarm = @json(session('scroll_to_alarm'));
        const savedScrollY = sessionStorage.getItem('iotAlarmScrollY');

        if (targetAlarm) {
            setTimeout(() => {
                const element = document.getElementById(`alarm-${targetAlarm}`);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (savedScrollY !== null) {
                    window.scrollTo({ top: Number(savedScrollY), behavior: 'smooth' });
                }
                sessionStorage.removeItem('iotAlarmScrollY');
            }, 250);
        } else if (savedScrollY !== null) {
            setTimeout(() => {
                window.scrollTo({ top: Number(savedScrollY), behavior: 'smooth' });
                sessionStorage.removeItem('iotAlarmScrollY');
            }, 150);
        }
    }


    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function renderAlarmEvents(events) {
        if (!events || !events.length) {
            return `
                <div class="alarm-empty">
                    Todavía no hay alarmas automáticas registradas para esta cámara.
                    Cuando Lambda detecte temperatura fuera de rango, batería baja, RSSI débil u otra condición,
                    el evento aparecerá en este historial.
                </div>
            `;
        }

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        const canManageAlarms = {{ auth()->user()?->canAcknowledgeAlarms() ? 'true' : 'false' }};

        return events.map(alarm => {
            const status = alarm.status || 'open';
            const filterStatus = ['open', 'acknowledged', 'silenced'].includes(status) ? 'active' : 'resolved';
            const alarmId = encodeURIComponent(alarm.alarm_id || '');

            const managementNote = alarm.last_comment ? `
                <div class="alarm-management-note">
                    Último comentario: ${escapeHtml(alarm.last_comment)}
                    ${alarm.last_comment_by ? `· ${escapeHtml(alarm.last_comment_by)}` : ''}
                </div>
            ` : '';

            const actions = canManageAlarms && alarm.alarm_id ? `
                <div class="alarm-actions">
                    <form method="POST" action="/iot/alarms/${alarmId}/comment" class="alarm-action-row">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input class="alarm-note-input" type="text" name="note" maxlength="500" placeholder="Agregar comentario operativo">
                        <button class="alarm-action-btn" type="submit">Comentar</button>
                        ${status === 'open' ? `
                            <button class="alarm-action-btn good" type="submit" formaction="/iot/alarms/${alarmId}/acknowledge">Reconocer</button>
                            <button class="alarm-action-btn warn" type="submit" formaction="/iot/alarms/${alarmId}/silence">Silenciar</button>
                        ` : ''}
                    </form>
                </div>
            ` : '';

            return `
                <div id="alarm-${escapeHtml(alarm.alarm_id || '')}" class="alarm-item ${escapeHtml(status)}" data-status="${filterStatus}">
                    <div class="alarm-main">
                        <strong>${escapeHtml(alarm.alarm_name || alarm.alarm_type_label || 'Alarma')}</strong>
                        <div class="alarm-summary">${escapeHtml(alarm.summary || 'Evento de alarma')}</div>
                        <div class="alarm-meta">
                            Inicio: ${escapeHtml(alarm.started_at || '—')}
                            · Última detección: ${escapeHtml(alarm.last_detected_at || '—')}
                            ${alarm.resolved_at ? `· Resuelta: ${escapeHtml(alarm.resolved_at)}` : ''}
                            · Puerta: ${alarm.door_state === 'open' ? 'Abierta' : (alarm.door_state === 'closed' ? 'Cerrada' : '—')}
                        </div>
                    </div>
                    <div class="alarm-badges">
                        <span class="tag ${escapeHtml(alarm.status_class || 'pending')}">${escapeHtml(alarm.status_label || 'Estado')}</span>
                        <span class="tag ${escapeHtml(alarm.severity_class || 'warn')}">${escapeHtml(alarm.severity_label || 'Severidad')}</span>
                    </div>
                    ${managementNote}
                    ${actions}
                </div>
            `;
        }).join('');
    }

    function applyAlarmFilter(filter) {
        document.querySelectorAll('.alarm-filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.alarmFilter === filter);
        });

        document.querySelectorAll('#alarmEventsList .alarm-item').forEach(item => {
            item.style.display = filter === 'all' || item.dataset.status === filter ? '' : 'none';
        });
    }


    function factorTagText(status) {
        if (status === 'good') return 'OK';
        if (status === 'warn') return 'Atención';
        if (status === 'bad') return 'Crítico';
        return 'Estado';
    }

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
            x: {
                ticks: { color: '#94a3b8' },
                grid: { color: 'rgba(148, 163, 184, 0.08)' }
            },
            y: {
                ticks: { color: '#94a3b8' },
                grid: { color: 'rgba(148, 163, 184, 0.08)' }
            }
        }
    };

    const tempCtx = document.getElementById('tempChart').getContext('2d');

    const tempChart = new Chart(tempCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Temperatura',
                data: [],
                borderColor: '#22d3ee',
                backgroundColor: 'rgba(34, 211, 238, 0.15)',
                tension: 0.35,
                borderWidth: 3,
                pointRadius: 2,
                pointHoverRadius: 5,
                fill: true
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: {
                    ...commonOptions.scales.y,
                    ticks: {
                        color: '#94a3b8',
                        callback: value => value + '°C',
                    }
                }
            }
        }
    });


    function renderPoint(sensorId) {
        const point = pointsMap[sensorId];
        if (!point) return;

        selectedId = sensorId;

        document.addEventListener('click', (event) => {
        const btn = event.target.closest('.alarm-filter-btn');
        if (btn) {
            applyAlarmFilter(btn.dataset.alarmFilter || 'all');
        }
    });

    document.querySelectorAll('.selector-card').forEach(card => {
            card.classList.toggle('active', card.dataset.sensorId === sensorId);
        });

        document.getElementById('detailTitle').textContent = displaySensorName(point);
        document.getElementById('detailSubtitle').textContent = `${point.location} · ${point.description}`;

        const detailStatus = document.getElementById('detailStatus');
        detailStatus.textContent = point.status_label;
        setBadgeClasses(detailStatus, point.status_class);

        const room = document.getElementById('detailRoom');
        room.classList.toggle('open', !!point.door_open);

        document.getElementById('detailRoomTemp').textContent = point.temperature !== null ? `${Number(point.temperature).toFixed(1)}°C` : '—';
        document.getElementById('overlayTemperature').textContent = point.temperature !== null ? `${Number(point.temperature).toFixed(1)}°C` : '—';
        document.getElementById('overlayDoor').textContent = point.door_open ? 'Abierta' : 'Cerrada';

        document.getElementById('metricHumidity').textContent = point.humidity !== null ? `${point.humidity}%` : '—';
        document.getElementById('metricBattery').textContent = point.battery !== null ? `${point.battery}%` : '—';
        document.getElementById('metricRssi').textContent = point.signal_rssi !== null ? `${point.signal_rssi} dBm` : '—';
        document.getElementById('metricSnr').textContent = point.signal_snr !== null ? `${point.signal_snr} dB` : '—';
        const metricMinutes = document.getElementById('metricMinutes');
        metricMinutes.textContent = point.minutes_ago_label || formatElapsedMinutes(point.minutes_ago);
        metricMinutes.title = point.last_seen_at_label || '—';
        document.getElementById('metricSensorId').textContent = point.sensor_id;

        document.getElementById('healthScore').textContent = `${point.health_score}%`;
        const currentHealthColor = healthColor(point.health_score);
        const currentHealthDeg = Math.max(0, Math.min(100, Number(point.health_score || 0))) * 3.6;
        const healthCircle = document.getElementById('healthCircle');
        if (healthCircle) {
            healthCircle.style.setProperty('--health-color', currentHealthColor);
            healthCircle.style.setProperty('--health-deg', `${currentHealthDeg}deg`);
        }

        const healthLabel = document.getElementById('healthLabel');
        healthLabel.textContent = point.health_label;
        setBadgeClasses(healthLabel, point.health_class);

        document.getElementById('healthMessage').textContent = point.status_message;
        const healthBar = document.getElementById('healthBar');
        healthBar.style.width = `${point.health_score}%`;
        healthBar.style.setProperty('--health-color', currentHealthColor);

        document.getElementById('factorList').innerHTML = (point.health_factors || []).map(factor => `
            <div class="factor-item">
                <div>
                    <strong>${factor.label}</strong>
                    <div class="muted">${factor.value}</div>
                </div>
                <span class="tag ${factor.status}">${factorTagText(factor.status)}</span>
            </div>
        `).join('') + `
        `;

        const modulesGrid = document.getElementById('modulesGrid');
        if (modulesGrid) {
            modulesGrid.innerHTML = (point.modules || []).map(module => `
                <div class="module-item">
                    <div>
                        <strong>${module.name}</strong>
                        <div class="muted">${module.value}</div>
                    </div>
                    <span class="tag ${module.state === 'active' ? 'good' : 'pending'}">
                        ${module.state === 'active' ? 'Activo' : 'Roadmap'}
                    </span>
                </div>
            `).join('');
        }

        const alarmEventsList = document.getElementById('alarmEventsList');
        if (alarmEventsList) {
            alarmEventsList.innerHTML = renderAlarmEvents(point.alarm_events || []);
        }

        const alarmEventsCount = document.getElementById('alarmEventsCount');
        if (alarmEventsCount) {
            const count = (point.alarm_events || []).length;
            alarmEventsCount.textContent = `${count} ${count === 1 ? 'evento' : 'eventos'}`;
        }

        applyAlarmFilter(document.querySelector('.alarm-filter-btn.active')?.dataset.alarmFilter || 'all');

        document.getElementById('historyTableBody').innerHTML = (point.history_rows || []).map(row => `
            <tr>
                <td>${row.timestamp}</td>
                <td>${row.temperature}</td>
                <td>${row.humidity}</td>
                <td>${row.door}</td>
                <td>${row.battery}</td>
            </tr>
        `).join('');

        tempChart.data.labels = point.history.labels || [];
        tempChart.data.datasets[0].data = point.history.temperatures || [];
        tempChart.update();



}

    document.querySelectorAll('.selector-card').forEach(card => {
        card.addEventListener('click', () => renderPoint(card.dataset.sensorId));
    });

    if (selectedId) {
        renderPoint(selectedId);
    }

    preserveAlarmScrollOnSubmit();
</script>

</body>
</html>