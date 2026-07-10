<?php

namespace App\Http\Controllers;

use App\Models\IotAlarmEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IotAlarmEventController extends Controller
{
    public function acknowledge(Request $request, string $alarmId): RedirectResponse
    {
        $this->authorizeAlarmManagement();

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $ok = IotAlarmEvent::acknowledge(
            $alarmId,
            auth()->user()?->name ?? 'Usuario',
            $data['note'] ?? null
        );

        return $this->redirectBackToAlarm($alarmId, $ok ? 'success' : 'error', $ok
            ? 'Alarma reconocida correctamente.'
            : 'No se pudo reconocer la alarma.');
    }

    public function silence(Request $request, string $alarmId): RedirectResponse
    {
        $this->authorizeAlarmManagement();

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $ok = IotAlarmEvent::silence(
            $alarmId,
            auth()->user()?->name ?? 'Usuario',
            $data['note'] ?? null
        );

        return $this->redirectBackToAlarm($alarmId, $ok ? 'success' : 'error', $ok
            ? 'Alarma silenciada correctamente.'
            : 'No se pudo silenciar la alarma.');
    }

    public function comment(Request $request, string $alarmId): RedirectResponse
    {
        $this->authorizeAlarmManagement();

        $data = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $ok = IotAlarmEvent::addComment(
            $alarmId,
            auth()->user()?->name ?? 'Usuario',
            $data['note']
        );

        return $this->redirectBackToAlarm($alarmId, $ok ? 'success' : 'error', $ok
            ? 'Comentario agregado correctamente.'
            : 'No se pudo agregar el comentario.');
    }


    private function redirectBackToAlarm(string $alarmId, string $type, string $message): RedirectResponse
    {
        $previous = preg_replace('/#.*$/', '', url()->previous());

        return redirect()
            ->to($previous . '#alarm-' . $alarmId)
            ->with($type, $message)
            ->with('scroll_to_alarm', $alarmId);
    }

    private function authorizeAlarmManagement(): void
    {
        $user = auth()->user();

        if (!$user || !method_exists($user, 'canAcknowledgeAlarms') || !$user->canAcknowledgeAlarms()) {
            abort(403, 'No tenés permisos para gestionar alarmas.');
        }
    }
}
