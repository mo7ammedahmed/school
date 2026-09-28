<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\Models\UserMembership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');

        // Role, active flag and last login live on the membership, not on the
        // user row, so the screen has to be handed them explicitly — reading
        // `user.role` in the table used to throw before it could render.
        $users = User::whereHas('memberships', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })
            ->with(['roles', 'memberships' => fn ($q) => $q->where('school_id', $schoolId)])
            ->latest()
            ->paginate(15)
            ->through(fn (User $user): array => $this->payload($user));

        return inertia('settings/users/index', ['users' => $users]);
    }

    public function create(): Response
    {
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

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
            'is_active' => 'nullable|boolean',
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
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('settings.users.show', $user)->with('success', 'User created successfully.');
    }

    public function show(User $user): Response
    {
        $this->ensureUserBelongsToCurrentSchool($user);

        $user->load('roles');

        return inertia('settings/users/show', ['user' => $this->payload($user)]);
    }

    public function edit(User $user): Response
    {
        $this->ensureUserBelongsToCurrentSchool($user);

        $user->load('roles');
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return inertia('settings/users/edit', [
            'user' => $this->payload($user) + [
                'role_ids' => $user->roles->pluck('id')->all(),
            ],
            'roles' => $roles,
        ]);
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
            'is_active' => 'nullable|boolean',
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

        // The role and the active flag belong to this school's membership.
        $membership = $user->memberships()->where('school_id', session('school_id'))->first();

        if ($membership) {
            $membership->update([
                'role' => $user->getRoleNames()->first() ?? $membership->role,
                'is_active' => $request->boolean('is_active', true),
            ]);
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

    /**
     * Shape one user for the settings screens.
     *
     * @return array{id: int, name: string, email: string, role: string, is_active: bool, last_login_at: ?string}
     */
    private function payload(User $user): array
    {
        $membership = $user->relationLoaded('memberships')
            ? $user->memberships->first()
            : $user->memberships()->where('school_id', session('school_id'))->first();

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'role' => $membership?->role ?? $user->getRoleNames()->first() ?? 'member',
            'is_active' => (bool) ($membership?->is_active ?? false),
            'last_login_at' => $membership?->last_login_at?->toDateTimeString(),
        ];
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
