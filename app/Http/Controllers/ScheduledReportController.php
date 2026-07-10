<?php

namespace App\Http\Controllers;

use App\Models\ScheduledReport;
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

        ScheduledReport::create([
            'name' => $request->name,
            'camera_ids' => array_values($request->camera_ids),
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
