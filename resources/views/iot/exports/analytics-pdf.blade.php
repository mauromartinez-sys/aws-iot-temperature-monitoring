<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe IoT</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color:#111827; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .muted { color:#6b7280; }
        .summary { width:100%; margin: 18px 0; border-collapse: collapse; }
        .summary td { padding:10px; border:1px solid #e5e7eb; }
        .summary strong { display:block; font-size:18px; margin-top:5px; }
        table { width:100%; border-collapse: collapse; }
        th { background:#0f172a; color:white; font-size:10px; text-transform:uppercase; }
        th, td { border:1px solid #e5e7eb; padding:7px; text-align:left; }
        .success { color:#047857; font-weight:bold; }
        .warning { color:#b45309; font-weight:bold; }
        .danger { color:#b91c1c; font-weight:bold; }
    </style>
</head>
<body>
    <h1>Informe de análisis IoT</h1>
    <div class="muted">Rango: {{ $from->format('d/m/Y H:i') }} a {{ $to->format('d/m/Y H:i') }}</div>

    <table class="summary">
        <tr>
            <td>Lecturas<strong>{{ $summary['readings'] }}</strong></td>
            <td>Sensores<strong>{{ $summary['sensors'] }}</strong></td>
            <td>Temperatura promedio<strong>{{ $summary['avg_temp'] !== null ? $summary['avg_temp'].'°C' : '—' }}</strong></td>
            <td>Alertas<strong>{{ $summary['warning_count'] + $summary['critical_count'] }}</strong></td>
            <td>Puerta abierta<strong>{{ $summary['door_open_count'] }}</strong></td>
        </tr>
    </table>

    <h2>Eventos relevantes</h2>
    <table>
        <thead>
            <tr><th>Fecha</th><th>Cámara</th><th>Sensor</th><th>Temp.</th><th>Puerta</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @forelse(array_slice($alarms, 0, 30) as $row)
                <tr>
                    <td>{{ $row['timestamp_label'] }}</td>
                    <td>{{ $row['camera_id'] }}</td>
                    <td>{{ $row['sensor_id'] }}</td>
                    <td>{{ $row['temperature'] !== null ? $row['temperature'].'°C' : '—' }}</td>
                    <td>{{ $row['door_open'] ? 'Abierta' : 'Cerrada' }}</td>
                    <td class="{{ $row['status_class'] }}">{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Sin eventos críticos en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Detalle de mediciones</h2>
    <table>
        <thead>
            <tr><th>Fecha</th><th>Cámara</th><th>Sensor</th><th>Temp.</th><th>Humedad</th><th>Puerta</th><th>Batería</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @forelse(array_slice($rows, 0, 120) as $row)
                <tr>
                    <td>{{ $row['timestamp_label'] }}</td>
                    <td>{{ $row['camera_id'] }}</td>
                    <td>{{ $row['sensor_id'] }}</td>
                    <td>{{ $row['temperature'] !== null ? $row['temperature'].'°C' : '—' }}</td>
                    <td>{{ $row['humidity'] !== null ? $row['humidity'].'%' : '—' }}</td>
                    <td>{{ $row['door_open'] ? 'Abierta' : 'Cerrada' }}</td>
                    <td>{{ $row['battery'] !== null ? $row['battery'].'%' : '—' }}</td>
                    <td class="{{ $row['status_class'] }}">{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Sin mediciones para el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
