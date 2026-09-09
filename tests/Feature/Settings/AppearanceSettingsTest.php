<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_appearance_changes_are_saved_and_audited(): void
    {
        Permission::create(['name' => 'manage-settings', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('manage-settings');
        $school = School::factory()->create();
        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['school_id' => $school->id])
            ->post('/settings/appearance', [
                'theme' => 'system',
                'primary_color' => '#0a5c42',
                'secondary_color' => '#f2efe8',
                'accent_color' => '#efecdf',
            ]);

        $response->assertRedirect('/settings/appearance');
        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'primary_color' => '#0a5c42',
            'secondary_color' => '#f2efe8',
            'accent_color' => '#efecdf',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $school->id,
            'description' => 'appearance_updated',
        ]);
    }
}
