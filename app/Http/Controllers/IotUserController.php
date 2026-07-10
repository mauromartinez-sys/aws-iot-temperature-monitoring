<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class IotUserController extends Controller
{
    private function ensureCanManageUsers(): void
    {
        $user = Auth::user();

        if (!$user || !method_exists($user, 'canManageUsers') || !$user->canManageUsers()) {
            abort(403, 'No tenés permisos para gestionar usuarios.');
        }
    }

    public function index()
    {
        $this->ensureCanManageUsers();

        $users = User::query()
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'supervisor' THEN 2 WHEN 'calidad' THEN 3 WHEN 'operario' THEN 4 WHEN 'registro_operativo' THEN 5 ELSE 6 END")
            ->orderBy('name')
            ->get();

        $roles = User::roles();

        $stats = [
            'total' => $users->count(),
            'active' => $users->where('is_active', true)->count(),
            'admins' => $users->where('role', User::ROLE_ADMIN)->count(),
            'inactive' => $users->where('is_active', false)->count(),
        ];

        $permissionMatrix = $this->permissionMatrix();

        return view('iot.settings.users', compact('users', 'roles', 'stats', 'permissionMatrix'));
    }

    public function store(Request $request)
    {
        $this->ensureCanManageUsers();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable'],
        ], [
            'name.required' => 'Ingresá el nombre del usuario.',
            'email.required' => 'Ingresá el email del usuario.',
            'email.email' => 'El email no tiene un formato válido.',
            'email.unique' => 'Ya existe un usuario con ese email.',
            'role.required' => 'Seleccioná un rol.',
            'password.required' => 'Ingresá una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('iot.settings.users')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        $this->ensureCanManageUsers();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'is_active' => ['nullable'],
        ], [
            'name.required' => 'Ingresá el nombre del usuario.',
            'email.required' => 'Ingresá el email del usuario.',
            'email.email' => 'El email no tiene un formato válido.',
            'email.unique' => 'Ya existe otro usuario con ese email.',
            'role.required' => 'Seleccioná un rol.',
        ]);

        $isCurrentUser = Auth::id() === $user->id;

        if ($isCurrentUser && $validated['role'] !== User::ROLE_ADMIN) {
            return back()->withErrors([
                'role' => 'No podés quitarte tu propio rol de Admin.',
            ]);
        }

        $newActiveState = $request->boolean('is_active');

        if ($isCurrentUser && !$newActiveState) {
            return back()->withErrors([
                'is_active' => 'No podés desactivar tu propio usuario.',
            ]);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $newActiveState,
        ]);

        return redirect()
            ->route('iot.settings.users')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggle(User $user)
    {
        $this->ensureCanManageUsers();

        if (Auth::id() === $user->id) {
            return back()->withErrors([
                'is_active' => 'No podés activar/desactivar tu propio usuario desde esta acción.',
            ]);
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return redirect()
            ->route('iot.settings.users')
            ->with('success', $user->is_active ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    public function updatePassword(Request $request, User $user)
    {
        $this->ensureCanManageUsers();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Ingresá una nueva contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('iot.settings.users')
            ->with('success', 'Contraseña actualizada correctamente.');
    }

    private function permissionMatrix(): array
    {
        return [
            'Ver Dashboard en vivo' => [
                User::ROLE_OPERARIO => true,
                User::ROLE_SUPERVISOR => true,
                User::ROLE_CALIDAD => true,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => false,
            ],
            'Puntos de medición' => [
                User::ROLE_OPERARIO => true,
                User::ROLE_SUPERVISOR => true,
                User::ROLE_CALIDAD => true,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => false,
            ],
            'Informes / Exportación legal' => [
                User::ROLE_OPERARIO => false,
                User::ROLE_SUPERVISOR => false,
                User::ROLE_CALIDAD => true,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => false,
            ],
            'Registro operativo' => [
                User::ROLE_OPERARIO => true,
                User::ROLE_SUPERVISOR => true,
                User::ROLE_CALIDAD => false,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => true,
            ],
            'Recibir Alertas' => [
                User::ROLE_OPERARIO => true,
                User::ROLE_SUPERVISOR => true,
                User::ROLE_CALIDAD => false,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => false,
            ],
            'Silenciar / Anotar Alarma' => [
                User::ROLE_OPERARIO => true,
                User::ROLE_SUPERVISOR => true,
                User::ROLE_CALIDAD => false,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => false,
            ],
            'Modificar Umbrales' => [
                User::ROLE_OPERARIO => false,
                User::ROLE_SUPERVISOR => true,
                User::ROLE_CALIDAD => false,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => false,
            ],
            'Calibrar / Alta de Sensores' => [
                User::ROLE_OPERARIO => false,
                User::ROLE_SUPERVISOR => false,
                User::ROLE_CALIDAD => false,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => false,
            ],
            'Gestión de Usuarios' => [
                User::ROLE_OPERARIO => false,
                User::ROLE_SUPERVISOR => false,
                User::ROLE_CALIDAD => false,
                User::ROLE_ADMIN => true,
                User::ROLE_REGISTRO_OPERATIVO => false,
            ],
        ];
    }
}
