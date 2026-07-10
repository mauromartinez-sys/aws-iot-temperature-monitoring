<?php

namespace App\Http\Controllers;

use App\Models\CameraEvent;
use App\Models\IotSensor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class IotOperationController extends Controller
{
    private function ensureCanRegisterEvents(): void
    {
        $user = Auth::user();

        if (!$user || !method_exists($user, 'canRegisterOperationalEvents') || !$user->canRegisterOperationalEvents()) {
            abort(403, 'No tenés permisos para registrar eventos operativos.');
        }
    }

    public function index()
    {
        $this->ensureCanRegisterEvents();

        $sensors = IotSensor::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('display_name')
            ->get();

        if ($sensors->isEmpty()) {
            $sensors = collect([
                (object) ['sensor_key' => 'sensor_demo_001', 'display_name' => 'Cámara 1', 'location' => 'Sin ubicación asignada'],
                (object) ['sensor_key' => 'sensor_demo_002', 'display_name' => 'Cámara 2', 'location' => 'Sin ubicación asignada'],
                (object) ['sensor_key' => 'sensor_demo_003', 'display_name' => 'Cámara 3', 'location' => 'Sin ubicación asignada'],
            ]);
        }

        $events = CameraEvent::query()
            ->with('user')
            ->latest()
            ->limit(40)
            ->get();

        return view('iot.operations', [
            'sensors' => $sensors,
            'events' => $events,
            'eventTypes' => CameraEvent::eventTypes(),
            'stats' => [
                'today' => CameraEvent::whereDate('created_at', now()->toDateString())->count(),
                'week' => CameraEvent::where('created_at', '>=', now()->subDays(7))->count(),
                'users' => CameraEvent::where('created_at', '>=', now()->subDays(7))->distinct('user_id')->count('user_id'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanRegisterEvents();

        $sensorKeys = IotSensor::query()->pluck('sensor_key')->toArray();
        if (empty($sensorKeys)) {
            $sensorKeys = ['sensor_demo_001', 'sensor_demo_002', 'sensor_demo_003'];
        }

        $data = $request->validate([
            'sensor_key' => ['required', 'string', Rule::in($sensorKeys)],
            'event_type' => ['required', 'string', Rule::in(array_keys(CameraEvent::eventTypes()))],
            'reason' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'sensor_key.required' => 'Seleccioná una cámara.',
            'sensor_key.in' => 'La cámara seleccionada no es válida.',
            'event_type.required' => 'Seleccioná el tipo de evento.',
            'reason.required' => 'Indicá el motivo del evento.',
        ]);

        CameraEvent::create([
            'sensor_key' => $data['sensor_key'],
            'user_id' => Auth::id(),
            'event_type' => $data['event_type'],
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'metadata' => [
                'source' => 'manual_operation_form',
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ],
        ]);

        return redirect()
            ->route('iot.operations')
            ->with('success', 'Evento operativo registrado correctamente.');
    }
}
