@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">📄 Reportes Automáticos</h2>
            <small class="text-muted">
                Programación de reportes periódicos y análisis automático.
            </small>
        </div>

        <a href="{{ route('iot.reports.create') }}" class="btn btn-primary">
            + Nuevo Reporte
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white">
            Reportes Configurados
        </div>

        <div class="card-body p-0">

            @if($reports->count() === 0)

                <div class="text-center p-5">
                    <h5>No existen reportes configurados</h5>
                    <p class="text-muted">
                        Creá tu primer reporte automático.
                    </p>
                </div>

            @else

                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Frecuencia</th>
                            <th>Próxima ejecución</th>
                            <th>IA</th>
                            <th>Estado</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                    @foreach($reports as $report)

                        <tr>

                            <td>
                                <strong>{{ $report->name }}</strong>
                            </td>

                            <td>
                                {{ strtoupper($report->frequency) }}
                            </td>

                            <td>
                                {{ optional($report->next_run_at)->format('d/m/Y H:i') }}
                            </td>

                            <td>
                                @if($report->include_ai_summary)
                                    <span class="badge bg-info">
                                        IA Activada
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        Desactivada
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if($report->active)
                                    <span class="badge bg-success">
                                        Activo
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <td>

                                <form
                                    action="{{ route('iot.reports.destroy', $report->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('¿Eliminar reporte?')">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="btn btn-sm btn-outline-danger">
                                        Eliminar
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            @endif

        </div>
    </div>

</div>

@endsection