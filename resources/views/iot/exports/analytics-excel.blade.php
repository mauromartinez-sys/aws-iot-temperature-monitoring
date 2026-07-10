<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Camara</th>
            <th>Sensor</th>
            <th>Cliente</th>
            <th>Sitio</th>
            <th>Temperatura</th>
            <th>Humedad</th>
            <th>Puerta</th>
            <th>Bateria</th>
            <th>RSSI</th>
            <th>SNR</th>
            <th>Origen</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['timestamp_label'] }}</td>
                <td>{{ $row['camera_id'] }}</td>
                <td>{{ $row['sensor_id'] }}</td>
                <td>{{ $row['client_id'] }}</td>
                <td>{{ $row['site_id'] }}</td>
                <td>{{ $row['temperature'] }}</td>
                <td>{{ $row['humidity'] }}</td>
                <td>{{ $row['door_open'] ? 'Abierta' : 'Cerrada' }}</td>
                <td>{{ $row['battery'] }}</td>
                <td>{{ $row['signal_rssi'] }}</td>
                <td>{{ $row['signal_snr'] }}</td>
                <td>{{ $row['source'] }}</td>
                <td>{{ $row['status'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
