@extends('layouts.app')

@section('content')

<h2 class="mb-4">Puntos de medición</h2>

<div class="card">
    <div class="card-body">

        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Cámara</th>
                    <th>Sensor</th>
                    <th>Temperatura</th>
                    <th>Última medición</th>
                </tr>
            </thead>

            <tbody>
                @foreach($points as $p)
                    <tr>
                        <td>
                            <span class="badge bg-{{ $p['class'] }}">
                                {{ $p['status'] }}
                            </span>
                        </td>
                        <td>{{ $p['camera'] }}</td>
                        <td>{{ $p['sensor'] }}</td>
                        <td><strong>{{ $p['temperature'] }}°C</strong></td>
                        <td>{{ $p['timestamp'] }}</td>
                    </tr>
                @endforeach
            </tbody>

        </table>

    </div>
</div>

@endsection