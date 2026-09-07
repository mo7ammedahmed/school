<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Domain\Identity\Models\UserMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $users = User::whereHas('memberships', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->with('roles')->latest()->paginate(15);

        return inertia('settings/users/index', ['users' => $users]);
    }

    public function create(): Response
    {
        $roles = Role::where('guard_name', 'web')->get();

        return inertia('settings/users/create', ['roles' => $roles]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        UserMembership::create([
            'user_id' => $user->id,
            'school_id' => session('school_id'),
            'role' => $user->getRoleNames()->first() ?? 'student',
            'is_active' => true,
        ]);

        return redirect()->route('settings.users.show', $user)->with('success', 'User created successfully.');
    }

    public function show(User $user): Response
    {
        $this->ensureUserBelongsToCurrentSchool($user);

        $user->load('roles');

        return inertia('settings/users/show', ['user' => $user]);
    }

    public function edit(User $user): Response
    {
        $this->ensureUserBelongsToCurrentSchool($user);

        $user->load('roles');
        $roles = Role::where('guard_name', 'web')->get();

        return inertia('settings/users/edit', ['user' => $user, 'roles' => $roles]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureUserBelongsToCurrentSchool($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('settings.users.show', $user)->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureUserBelongsToCurrentSchool($user);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot delete yourself.']);
        }

        $user->delete();

        return redirect()->route('settings.users.index')->with('success', 'User deleted successfully.');
    }

    private function ensureUserBelongsToCurrentSchool(User $user): void
    {
        abort_unless(
            $user->memberships()
                ->where('school_id', session('school_id'))
                ->where('is_active', true)
                ->exists(),
            404,
        );
    }
}
