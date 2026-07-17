<?php

namespace App\Http\Controllers;

use App\Models\ScheduledReport;
use App\Models\IotSensor;
use Illuminate\Http\Request;

class ScheduledReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'camera_ids' => 'required|array|min:1',
            'camera_ids.*' => 'required|string|max:120',
            'frequency' => 'required|in:weekly,biweekly,monthly',
            'recipients' => 'required|string',
        ]);

        $emails = collect(explode(',', $request->recipients))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->values()
            ->toArray();

        $activeSensorKeys = IotSensor::query()
            ->where('is_active', true)
            ->pluck('sensor_key')
            ->map(fn ($sensorKey) => (string) $sensorKey)
            ->all();

        $cameraIds = collect($request->camera_ids)
            ->map(fn ($sensorKey) => (string) $sensorKey)
            ->filter(fn ($sensorKey) => in_array($sensorKey, $activeSensorKeys, true))
            ->values()
            ->all();

        if (empty($cameraIds)) {
            return back()
                ->withErrors(['camera_ids' => 'Seleccioná al menos una cámara activa.'])
                ->withInput();
        }

        ScheduledReport::create([
            'name' => $request->name,
            'camera_ids' => $cameraIds,
            'frequency' => $request->frequency,
            'send_pdf' => $request->has('send_pdf'),
            'send_excel' => $request->has('send_excel'),
            'include_ai_summary' => $request->has('include_ai_summary'),
            'active' => true,
            'recipients' => $emails,
            'next_run_at' => now(),
        ]);

        return redirect()
            ->route('iot.analytics')
            ->with('success', 'Reporte automático guardado correctamente.');
    }

    public function destroy(ScheduledReport $report)
    {
        $report->delete();

        return redirect()
            ->route('iot.analytics')
            ->with('success', 'Reporte automático eliminado correctamente.');
    }
}
