<?php

namespace App\Http\Controllers;

use App\Models\IotSensor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IotSettingsController extends Controller
{
    public function index(): View
    {
        $this->ensureDefaultSensors();

        $sensors = IotSensor::query()
            ->orderBy('display_order')
            ->orderBy('sensor_key')
            ->get();

        $activeSensors = $sensors->where('is_active', true)->count();
        $inactiveSensors = $sensors->where('is_active', false)->count();
        $configuredNames = $sensors->filter(fn ($sensor) => $sensor->display_name !== $sensor->sensor_key)->count();

        return view('iot.settings.index', compact(
            'sensors',
            'activeSensors',
            'inactiveSensors',
            'configuredNames'
        ));
    }

    public function updateSensors(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sensors' => ['required', 'array'],
            'sensors.*.id' => ['required', 'integer', 'exists:iot_sensors,id'],
            'sensors.*.display_name' => ['required', 'string', 'max:120'],
            'sensors.*.location' => ['nullable', 'string', 'max:160'],
            'sensors.*.camera_type' => ['nullable', 'string', 'max:80'],
            'sensors.*.description' => ['nullable', 'string', 'max:500'],
            'sensors.*.alarm_min' => ['nullable', 'numeric', 'between:-999,999'],
            'sensors.*.alarm_max' => ['nullable', 'numeric', 'between:-999,999'],
            'sensors.*.display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $activeIds = collect($request->input('active_sensors', []))->map(fn ($id) => (int) $id)->all();

        foreach ($validated['sensors'] as $sensorData) {
            $sensor = IotSensor::findOrFail($sensorData['id']);

            $sensor->update([
                'display_name' => trim($sensorData['display_name']),
                'location' => $sensorData['location'] ?? null,
                'camera_type' => $sensorData['camera_type'] ?? null,
                'description' => $sensorData['description'] ?? null,
                'alarm_min' => $sensorData['alarm_min'] ?? null,
                'alarm_max' => $sensorData['alarm_max'] ?? null,
                'display_order' => $sensorData['display_order'] ?? 0,
                'is_active' => in_array($sensor->id, $activeIds, true),
            ]);
        }

        return redirect()
            ->route('iot.settings')
            ->with('success', 'Configuración de sensores actualizada correctamente.');
    }

    public function storeSensor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sensor_key' => ['required', 'string', 'max:100', 'unique:iot_sensors,sensor_key'],
            'display_name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'camera_type' => ['nullable', 'string', 'max:80'],
        ]);

        IotSensor::create([
            'sensor_key' => trim($validated['sensor_key']),
            'display_name' => trim($validated['display_name']),
            'location' => $validated['location'] ?? null,
            'camera_type' => $validated['camera_type'] ?? null,
            'is_active' => true,
            'display_order' => IotSensor::max('display_order') + 1,
        ]);

        return redirect()
            ->route('iot.settings')
            ->with('success', 'Sensor agregado correctamente.');
    }

    private function ensureDefaultSensors(): void
    {
        $defaults = [
            ['sensor_key' => 'camara_1', 'display_name' => 'Cámara 1', 'location' => 'Sin ubicación asignada', 'display_order' => 1],
            ['sensor_key' => 'camara_2', 'display_name' => 'Cámara 2', 'location' => 'Sin ubicación asignada', 'display_order' => 2],
            ['sensor_key' => 'camara_3', 'display_name' => 'Cámara 3', 'location' => 'Sin ubicación asignada', 'display_order' => 3],
        ];

        foreach ($defaults as $sensor) {
            IotSensor::firstOrCreate(
                ['sensor_key' => $sensor['sensor_key']],
                $sensor
            );
        }
    }
}
