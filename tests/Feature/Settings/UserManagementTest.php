<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Settings → Users reads role, status and last login off the school
 * membership rather than the user row. The screen used to be handed a bare
 * user, so the table threw before it could render and the role/status selects
 * were discarded on save.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_users_screen_receives_the_role_and_status_the_table_renders(): void
    {
        $school = School::factory()->create();
        $this->actingAsSettingsAdmin($school);

        $member = $this->makeMember($school, 'teacher', true);

        $this->get('/settings/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/users/index')
                ->where('users.data', function ($data) use ($member): bool {
                    $row = collect($data)->firstWhere('id', $member->id);

                    return is_array($row)
                        && $row['role'] === 'teacher'
                        && $row['is_active'] === true
                        && array_key_exists('last_login_at', $row);
                }));
    }

    public function test_creating_a_user_stores_the_selected_role_and_status(): void
    {
        $school = School::factory()->create();
        $this->actingAsSettingsAdmin($school);

        $role = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $this->post('/settings/users', [
            'name' => 'New Teacher',
            'email' => 'new.teacher@example.com',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
            'roles' => [$role->id],
            'is_active' => '0',
        ])->assertRedirect();

        $user = User::where('email', 'new.teacher@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('teacher'));

        $membership = UserMembership::where('user_id', $user->id)->where('school_id', $school->id)->firstOrFail();
        $this->assertSame('teacher', $membership->role);
        $this->assertFalse((bool) $membership->is_active, 'the status select must reach the membership row');
    }

    public function test_editing_a_user_changes_the_role_and_status(): void
    {
        $school = School::factory()->create();
        $this->actingAsSettingsAdmin($school);

        $teacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $member = $this->makeMember($school, 'teacher', true);

        $this->put("/settings/users/{$member->id}", [
            'name' => $member->name,
            'email' => $member->email,
            'roles' => [$admin->id],
            'is_active' => '0',
        ])->assertRedirect();

        $membership = UserMembership::where('user_id', $member->id)->where('school_id', $school->id)->firstOrFail();
        $this->assertSame('admin', $membership->role);
        $this->assertFalse((bool) $membership->is_active);
        $this->assertTrue($member->fresh()->hasRole('admin'));
        $this->assertFalse($member->fresh()->hasRole('teacher'));

        // Keep the compiler honest about the unused role in this scenario.
        $this->assertNotNull($teacher);
    }

    public function test_a_user_cannot_delete_themselves(): void
    {
        $school = School::factory()->create();
        $admin = $this->actingAsSettingsAdmin($school);

        $this->delete("/settings/users/{$admin->id}")->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    private function makeMember(School $school, string $role, bool $active): User
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'role' => $role,
            'is_active' => $active,
        ]);

        return $user;
    }

    private function actingAsSettingsAdmin(School $school): User
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        foreach (['manage-settings', 'manage-schools', 'manage-users'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }
}
