<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Domain\Academics\Models\GradingCategory;
use App\Domain\Academics\Models\GradingScale;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Every Settings screen must render the page that actually exists on disk and
 * its form must persist, otherwise the screen silently 500s or saves nothing.
 */
class SettingsPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function adminPages(): array
    {
        return [
            'school' => ['/settings/school', 'settings/school/edit'],
            'academic' => ['/settings/academic', 'settings/academic'],
            'attendance' => ['/settings/attendance', 'settings/attendance'],
            'grading' => ['/settings/grading', 'settings/grading'],
            'notifications' => ['/settings/notifications-config', 'settings/notifications-config'],
            'email' => ['/settings/email', 'settings/email'],
            'sms' => ['/settings/sms', 'settings/sms'],
            'security' => ['/settings/security', 'settings/security'],
            'localization' => ['/settings/localization', 'settings/localization'],
            'appearance' => ['/settings/appearance', 'settings/appearance'],
            'navigation' => ['/settings/navigation', 'settings/navigation'],
            'translations' => ['/settings/translations', 'settings/translations'],
            'payments' => ['/settings/payments', 'settings/payments'],
            'payment logs' => ['/settings/payments/logs', 'settings/payments-logs'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function personalPages(): array
    {
        return [
            'profile' => ['/settings/profile', 'settings/profile'],
            'password' => ['/settings/password', 'settings/password'],
            'preferences' => ['/settings/preferences', 'settings/preferences'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admin_settings_pages_load(string $url, string $component): void
    {
        $school = School::factory()->create();
        $this->actingAsSettingsAdmin($school);

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component($component));
    }

    /**
     * Only one school editor exists now. The historic Settings landing URL the
     * sidebar and breadcrumbs used must forward instead of rendering a second
     * copy of the form.
     */
    public function test_the_general_settings_url_forwards_to_the_school_editor(): void
    {
        $school = School::factory()->create();
        $this->actingAsSettingsAdmin($school);

        $this->get('/settings/general')->assertRedirect(route('settings.school'));
    }

    /**
     * The palettes are edited on the Appearance screen now; the old URL keeps
     * working by redirecting.
     */
    public function test_the_theme_screen_redirects_to_appearance(): void
    {
        $school = School::factory()->create();
        $this->actingAsSettingsAdmin($school);

        $this->get('/settings/theme')->assertRedirect(route('settings.appearance'));
    }

    #[DataProvider('personalPages')]
    public function test_personal_settings_pages_load(string $url, string $component): void
    {
        $school = School::factory()->create();
        $this->actingAsSettingsAdmin($school);

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component($component));
    }

    public function test_academic_settings_save(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/academic', [
            'grading_system' => 'letter',
            'pass_mark' => 60,
            'max_score' => 120,
        ])->assertRedirect(route('settings.academic'));

        $this->assertSame('letter', $this->stored($school->id, 'academic')['grading_system']);
        $this->assertSame(60, $this->stored($school->id, 'academic')['pass_mark']);
    }

    public function test_attendance_settings_save_and_split_the_excused_list(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/attendance', [
            'late_threshold_minutes' => 20,
            'excused_types' => 'sick, family, travel',
        ])->assertRedirect(route('settings.attendance'));

        $stored = $this->stored($school->id, 'attendance');
        $this->assertSame(20, $stored['late_threshold_minutes']);
        $this->assertSame(['sick', 'family', 'travel'], $stored['excused_types']);
    }

    public function test_email_settings_save_and_encrypt_the_password(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/email', [
            'mail_driver' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'School',
            'mail_password' => 'super-secret',
        ])->assertRedirect(route('settings.email'));

        $raw = (string) SchoolSetting::where('school_id', $school->id)->where('key', 'email')->value('value');
        $this->assertStringNotContainsString('super-secret', $raw, 'the password must be encrypted at rest');

        $this->assertSame('smtp.example.com', $this->stored($school->id, 'email')['mail_host']);
    }

    public function test_localization_settings_save(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/localization', [
            'default_locale' => 'ar',
            'default_timezone' => 'Asia/Riyadh',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'currency' => 'SAR',
            'currency_symbol' => 'SAR',
            'number_format' => '1,234.56',
            'week_start' => 0,
        ])->assertRedirect(route('settings.localization'));

        $this->assertSame('ar', $this->stored($school->id, 'localization')['default_locale']);
    }

    public function test_notification_settings_save_unchecked_as_false(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/notifications-config', [
            'email_enrollment' => '1',
            'email_attendance' => '1',
            'email_exam' => '1',
            'email_announcement' => '1',
            // email_assignment intentionally absent (unchecked).
        ])->assertRedirect(route('settings.notifications-config'));

        $stored = $this->stored($school->id, 'notifications');
        $this->assertTrue($stored['email_enrollment']);
        $this->assertFalse($stored['email_assignment']);
    }

    public function test_security_settings_save(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/security', [
            'password_min_length' => 12,
            'password_expiry_days' => 30,
            'password_require_uppercase' => '1',
            'password_require_numbers' => '1',
            'session_timeout' => 45,
            'max_login_attempts' => 7,
            'allowed_ips' => '10.0.0.0/8',
        ])->assertRedirect(route('settings.security'));

        $stored = $this->stored($school->id, 'security');
        $this->assertSame(12, $stored['password_min_length']);
        $this->assertFalse($stored['password_require_symbols'], 'an unchecked box must not be treated as enabled');
        $this->assertSame('10.0.0.0/8', $stored['allowed_ips']);
    }

    public function test_grading_settings_save_rounding_and_extracurricular(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/grading', [
            'rounding_method' => 'floor',
            'include_extracurricular' => '0',
        ])->assertRedirect(route('settings.grading'));

        $this->assertSame(
            'floor',
            SchoolSetting::where('school_id', $school->id)->where('key', 'grading_rounding_method')->value('value'),
        );
        $this->assertSame(
            'false',
            SchoolSetting::where('school_id', $school->id)->where('key', 'grading_include_extracurricular')->value('value'),
        );
    }

    public function test_grading_scales_can_be_created_edited_promoted_and_deleted(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/grading/scales', [
            'name' => 'Secondary',
            'description' => 'Percentage bands',
            'is_default' => '1',
            'scale' => [
                ['grade' => 'A', 'min' => 90, 'max' => 100],
                ['grade' => 'B', 'min' => 80, 'max' => 89],
            ],
        ])->assertRedirect(route('settings.grading'));

        $scale = GradingScale::where('school_id', $school->id)->firstOrFail();
        $this->assertSame('Secondary', $scale->name);
        $this->assertTrue((bool) $scale->is_default);
        $this->assertCount(2, json_decode((string) $scale->scale, true));

        $this->post('/settings/grading/scales', [
            'name' => 'Primary',
            'scale' => [['grade' => 'P', 'min' => 50, 'max' => 100]],
        ])->assertRedirect(route('settings.grading'));

        $other = GradingScale::where('school_id', $school->id)->where('name', 'Primary')->firstOrFail();

        // Promoting the second scale must stand the first one down.
        $this->put("/settings/grading/scales/{$other->id}", [
            'name' => 'Primary',
            'is_default' => '1',
            'scale' => [['grade' => 'P', 'min' => 50, 'max' => 100]],
        ])->assertRedirect(route('settings.grading'));

        $this->assertTrue((bool) $other->fresh()->is_default);
        $this->assertFalse((bool) $scale->fresh()->is_default);

        // Scales are soft deleted, so the row stays for audit while the screen
        // stops listing it.
        $this->delete("/settings/grading/scales/{$scale->id}")->assertRedirect(route('settings.grading'));
        $this->assertSoftDeleted('grading_scales', ['id' => $scale->id]);

        $this->get('/settings/grading')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('gradingScales', 1));

        // The remaining scale is the only one left, so it must stay selectable.
        $this->assertTrue((bool) $other->fresh()->is_default);
    }

    public function test_grading_categories_can_be_created_edited_and_deleted(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/grading/categories', [
            'name' => 'Coursework',
            'code' => 'CW',
            'weight' => 40,
        ])->assertRedirect(route('settings.grading'));

        $category = GradingCategory::where('school_id', $school->id)->firstOrFail();
        $this->assertSame(40.0, (float) $category->weight);

        $this->put("/settings/grading/categories/{$category->id}", [
            'name' => 'Coursework',
            'code' => 'CW',
            'weight' => 25,
        ])->assertRedirect(route('settings.grading'));

        $this->assertSame(25.0, (float) $category->fresh()->weight);

        $this->delete("/settings/grading/categories/{$category->id}")->assertRedirect(route('settings.grading'));
        $this->assertSoftDeleted('grading_categories', ['id' => $category->id]);
    }

    public function test_sms_settings_save_and_encrypt_the_token(): void
    {
        $this->actingAsSettingsAdmin($school = School::factory()->create());

        $this->post('/settings/sms', [
            'provider' => 'twilio',
            'sender_id' => 'SchoolOS',
            'account_sid' => 'AC123',
            'auth_token' => 'super-secret-token',
        ])->assertRedirect();

        $raw = (string) SchoolSetting::where('school_id', $school->id)->where('key', 'sms')->value('value');
        $this->assertStringNotContainsString('super-secret-token', $raw, 'the auth token must be encrypted at rest');

        $this->get('/settings/sms')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/sms')
                ->where('settings.provider', 'twilio')
                ->where('settings.has_auth_token', true)
                ->where('configured', true));
    }

    public function test_school_settings_save_the_bilingual_name(): void
    {
        $school = School::factory()->create(['name_en' => 'Old Name']);
        $this->actingAsSettingsAdmin($school);

        $this->post('/settings/school', [
            'name_en' => 'New Name',
            'name_ar' => 'اسم جديد',
            'email' => 'school@example.com',
            'phone' => '+966500000000',
            'address' => '1 Street',
        ])->assertRedirect(route('settings.school'));

        $school->refresh();
        $this->assertSame('New Name', $school->name_en);
        $this->assertSame('اسم جديد', $school->name_ar);
    }

    public function test_preferences_save_to_the_user(): void
    {
        $user = $this->actingAsSettingsAdmin(School::factory()->create());

        $this->post('/settings/preferences', [
            'locale' => 'ar',
            'timezone' => 'Asia/Riyadh',
            'theme' => 'dark',
            'email_notifications' => '1',
        ])->assertRedirect(route('settings.preferences'));

        $user->refresh();
        $this->assertSame('ar', $user->locale);
        $this->assertSame('dark', $user->theme);
        $this->assertTrue($user->email_notifications);
        $this->assertFalse($user->sms_notifications);
    }

    public function test_profile_saves_to_the_user(): void
    {
        $user = $this->actingAsSettingsAdmin(School::factory()->create());

        $this->post('/settings/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '+966511111111',
        ])->assertRedirect(route('settings.profile'));

        $this->assertSame('Updated Name', $user->refresh()->name);
    }

    public function test_password_change_updates_the_hash(): void
    {
        $user = $this->actingAsSettingsAdmin(School::factory()->create());

        $this->post('/settings/password', [
            'current_password' => 'password',
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
        ])->assertRedirect(route('settings.password'));

        $this->assertTrue(Hash::check('a-new-secure-password', $user->refresh()->password));
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(int $schoolId, string $key): array
    {
        $raw = (string) SchoolSetting::where('school_id', $schoolId)->where('key', $key)->value('value');

        return json_decode($raw, true) ?? [];
    }

    private function actingAsSettingsAdmin(School $school): User
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);
        $user->givePermissionTo('manage-settings');

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }
}
