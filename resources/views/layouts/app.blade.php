<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>IoT Dashboard</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body { background: #f4f6f9; }

        .topbar {
            background: #1f2937;
            color: white;
            padding: 12px 20px;
        }

        .topbar a {
            color: #d1d5db;
            margin-right: 20px;
            text-decoration: none;
            font-weight: 500;
        }

        .topbar a.active {
            color: #f59e0b;
        }
    </style>
</head>

<body>

<div class="topbar">
    <a href="/iot/dashboard">Tablero de mandos</a>
    <a href="/iot/points">Puntos de medición</a>
    <a href="/iot/analytics">Análisis e informes</a>
    <a href="/iot/alerts">Alarmas</a>
</div>

<div class="container-fluid p-4">
    @yield('content')
</div>

</body>
</html>