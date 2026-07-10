@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-0">
            ➕ Nuevo Reporte Automático
        </h2>

        <small class="text-muted">
            Configuración de generación y envío periódico.
        </small>
    </div>

    <form method="POST" action="{{ route('iot.reports.store') }}">

        @csrf

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label">
                        Nombre del Reporte
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        required>
                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Cámaras incluidas
                    </label>

                    <select
                        class="form-select"
                        name="camera_ids[]"
                        multiple
                        required>

                        @foreach($sensors as $sensor)

                            <option value="{{ $sensor->id }}">
                                {{ $sensor->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Frecuencia
                    </label>

                    <select
                        class="form-select"
                        name="frequency">

                        <option value="weekly">
                            Semanal
                        </option>

                        <option value="biweekly">
                            Quincenal
                        </option>

                        <option value="monthly">
                            Mensual
                        </option>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Destinatarios
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="recipients"
                        placeholder="mail1@empresa.com, mail2@empresa.com">

                </div>

                <hr>

                <div class="form-check mb-2">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        checked
                        name="send_pdf">

                    <label class="form-check-label">
                        Generar PDF
                    </label>

                </div>

                <div class="form-check mb-2">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="send_excel">

                    <label class="form-check-label">
                        Generar Excel
                    </label>

                </div>

                <div class="form-check mb-4">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="include_ai_summary">

                    <label class="form-check-label">
                        Incluir resumen generado por IA
                    </label>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary">

                    Guardar Reporte

                </button>

                <a
                    href="{{ route('iot.reports') }}"
                    class="btn btn-secondary">

                    Cancelar

                </a>

            </div>

        </div>

    </form>

</div>

@endsection