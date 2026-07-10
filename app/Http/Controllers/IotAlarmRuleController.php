<?php

namespace App\Http\Controllers;

use App\Models\IotAlarmRule;
use App\Models\IotSensor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class IotAlarmRuleController extends Controller
{
    private function ensureCanManageAlarms(): void
    {
        $user = Auth::user();

        $allowed = $user && (
            (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
            (method_exists($user, 'canModifyThresholds') && $user->canModifyThresholds())
        );

        if (!$allowed) {
            abort(403, 'No tenés permisos para administrar alertas.');
        }
    }

    public function index(): View
    {
        $this->ensureCanManageAlarms();

        $sensors = IotSensor::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('display_name')
            ->get();

        if ($sensors->isEmpty()) {
            $sensors = collect([
                (object) ['sensor_key' => 'camara_1', 'display_name' => 'Cámara 1', 'location' => 'Sin ubicación asignada'],
                (object) ['sensor_key' => 'camara_2', 'display_name' => 'Cámara 2', 'location' => 'Sin ubicación asignada'],
                (object) ['sensor_key' => 'camara_3', 'display_name' => 'Cámara 3', 'location' => 'Sin ubicación asignada'],
            ]);
        }

        $sensorMap = $sensors->keyBy('sensor_key');

        $rules = collect(IotAlarmRule::all())->map(function (array $rule) use ($sensorMap) {
            $sensorKey = $rule['sensor_key'] ?? null;
            $sensor = $sensorKey ? $sensorMap->get($sensorKey) : null;

            $rule['camera_display_name'] = $sensor?->display_name
                ?? ($rule['camera_name_snapshot'] ?? $sensorKey ?? 'Sensor sin identificar');

            $rule['recipients_label'] = implode(', ', (array) ($rule['recipients'] ?? []));
            $rule['channels_label'] = implode(', ', (array) ($rule['channels'] ?? []));
            return $rule;
        })->values();

        $stats = [
            'total' => $rules->count(),
            'active' => $rules->where('is_active', true)->count(),
            'critical' => $rules->where('severity', 'critical')->count(),
            'temperature' => $rules->whereIn('alarm_type', ['temperature_range', 'temperature_high', 'temperature_low'])->count(),
        ];

        return view('iot.settings.alarms', [
            'rules' => $rules,
            'sensors' => $sensors,
            'stats' => $stats,
            'alarmTypes' => IotAlarmRule::alarmTypes(),
            'metrics' => IotAlarmRule::metrics(),
            'operators' => IotAlarmRule::operators(),
            'doorConditions' => IotAlarmRule::doorConditions(),
            'severities' => IotAlarmRule::severities(),
            'tableName' => IotAlarmRule::tableName(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageAlarms();

        $data = $this->validatedData($request);

        $sensor = IotSensor::query()->where('sensor_key', $data['sensor_key'])->first();

        IotAlarmRule::create(array_merge($data, [
            'camera_name_snapshot' => $sensor?->display_name ?? $data['sensor_key'],
            'channels' => ['email'],
            'is_active' => $request->boolean('is_active'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));

        return redirect()
            ->route('iot.settings.alarms')
            ->with('success', 'Regla de alerta creada correctamente en DynamoDB.');
    }

    public function update(Request $request, string $ruleId): RedirectResponse
    {
        $this->ensureCanManageAlarms();

        $data = $this->validatedData($request);

        $sensor = IotSensor::query()->where('sensor_key', $data['sensor_key'])->first();

        IotAlarmRule::update($ruleId, array_merge($data, [
            'camera_name_snapshot' => $sensor?->display_name ?? $data['sensor_key'],
            'channels' => ['email'],
            'is_active' => $request->boolean('is_active'),
            'updated_by' => Auth::id(),
        ]));

        return redirect()
            ->route('iot.settings.alarms')
            ->with('success', 'Regla de alerta actualizada correctamente.');
    }

    public function toggle(string $ruleId): RedirectResponse
    {
        $this->ensureCanManageAlarms();

        IotAlarmRule::toggle($ruleId);

        return redirect()
            ->route('iot.settings.alarms')
            ->with('success', 'Estado de la regla actualizado correctamente.');
    }

    public function destroy(string $ruleId): RedirectResponse
    {
        $this->ensureCanManageAlarms();

        IotAlarmRule::delete($ruleId);

        return redirect()
            ->route('iot.settings.alarms')
            ->with('success', 'Regla de alerta eliminada correctamente.');
    }

    private function validatedData(Request $request): array
    {
        $this->normalizeAlarmRequest($request);

        $sensorKeys = IotSensor::query()->where('is_active', true)->pluck('sensor_key')->toArray();

        if (empty($sensorKeys)) {
            $sensorKeys = ['camara_1', 'camara_2', 'camara_3'];
        }

        $data = $request->validate([
            'sensor_key' => ['required', 'string', Rule::in($sensorKeys)],
            'alarm_name' => ['required', 'string', 'max:140'],
            'alarm_type' => ['required', 'string', Rule::in(array_keys(IotAlarmRule::alarmTypes()))],
            'metric' => ['required', 'string', Rule::in(array_keys(IotAlarmRule::metrics()))],
            'condition_operator' => ['required', 'string', Rule::in(array_keys(IotAlarmRule::operators()))],
            'threshold_value' => ['nullable', 'numeric'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'door_condition' => ['required', 'string', Rule::in(array_keys(IotAlarmRule::doorConditions()))],
            'door_grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'severity' => ['required', 'string', Rule::in(array_keys(IotAlarmRule::severities()))],
            'cooldown_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'recipients' => ['required', 'string', 'max:1000'],
        ], [
            'sensor_key.required' => 'Seleccioná una cámara.',
            'alarm_name.required' => 'Ingresá un nombre para la alarma.',
            'threshold_value.numeric' => 'El valor límite debe ser numérico.',
            'min_value.numeric' => 'La temperatura mínima debe ser numérica.',
            'max_value.numeric' => 'La temperatura máxima debe ser numérica.',
            'recipients.required' => 'Indicá al menos un destinatario.',
        ]);

        if (($data['alarm_type'] ?? null) === 'temperature_range') {
            $rangeData = $request->validate([
                'min_value' => ['required', 'numeric'],
                'max_value' => ['required', 'numeric', 'gt:min_value'],
            ], [
                'min_value.required' => 'Indicá la temperatura mínima permitida.',
                'max_value.required' => 'Indicá la temperatura máxima permitida.',
                'max_value.gt' => 'La temperatura máxima debe ser mayor que la mínima.',
            ]);

            $data['min_value'] = (float) $rangeData['min_value'];
            $data['max_value'] = (float) $rangeData['max_value'];
            $data['threshold_value'] = (float) $rangeData['max_value'];
        }

        return $data;
    }

    private function normalizeAlarmRequest(Request $request): void
    {
        $alarmType = (string) $request->input('alarm_type', 'temperature_range');

        $defaults = [
            'metric' => IotAlarmRule::defaultMetricForAlarmType($alarmType),
            'condition_operator' => IotAlarmRule::defaultOperatorForAlarmType($alarmType),
            'door_condition' => $request->input('door_condition') ?: IotAlarmRule::defaultDoorConditionForAlarmType($alarmType),
        ];

        if ($request->input('threshold_value') === null || $request->input('threshold_value') === '') {
            $defaultThreshold = IotAlarmRule::defaultThresholdForAlarmType($alarmType);
            if ($defaultThreshold !== null) {
                $defaults['threshold_value'] = $defaultThreshold;
            }
        }

        if ($alarmType === 'temperature_range') {
            $defaults['metric'] = 'temperature';
            $defaults['condition_operator'] = '>';
            if ($request->input('max_value') !== null && $request->input('max_value') !== '') {
                $defaults['threshold_value'] = $request->input('max_value');
            }
        }

        if ($alarmType === 'door_open_too_long') {
            $defaults['metric'] = 'door_open';
            $defaults['condition_operator'] = '=';
            $defaults['threshold_value'] = 1;
            $defaults['door_condition'] = 'open';
        }

        if ($alarmType === 'temperature_high') {
            $defaults['metric'] = 'temperature';
            $defaults['condition_operator'] = '>';
        }

        if ($alarmType === 'temperature_low') {
            $defaults['metric'] = 'temperature';
            $defaults['condition_operator'] = '<';
        }

        if ($alarmType === 'battery_low') {
            $defaults['metric'] = 'battery';
            $defaults['condition_operator'] = '<';
        }

        if ($alarmType === 'rssi_low') {
            $defaults['metric'] = 'rssi';
            $defaults['condition_operator'] = '<';
        }

        if ($alarmType === 'sensor_offline') {
            $defaults['metric'] = 'last_seen_minutes';
            $defaults['condition_operator'] = '>';
        }

        $request->merge($defaults);
    }

}
