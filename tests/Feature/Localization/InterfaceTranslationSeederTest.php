<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Domain\Localization\Models\InterfaceTranslation;
use Database\Seeders\InterfaceTranslationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Arabic pages start from a dictionary that ships with the app.
 *
 * The seeder writes the hand-written pairs into the same table the automatic
 * provider writes to, so a checkout with no provider key still reads Arabic.
 */
class InterfaceTranslationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_the_dictionary_from_the_hand_written_pairs(): void
    {
        $version = InterfaceTranslation::catalogVersion();

        $this->seed(InterfaceTranslationSeeder::class);

        $this->assertDatabaseCount('interface_translations', $this->pairCount());

        $this->assertDatabaseHas('interface_translations', [
            'source_hash' => InterfaceTranslation::hashSource('Academic Year'),
            'english' => 'Academic Year',
            'arabic' => 'العام الدراسي',
            'updated_by' => null,
        ]);

        $this->assertDatabaseHas('interface_translations', [
            'english' => 'First Name',
            'arabic' => 'الاسم الأول',
        ]);

        $this->assertDatabaseHas('interface_translations', [
            'english' => 'Attendance Rate',
            'arabic' => 'نسبة الحضور',
        ]);

        // The catalog moved, so browsers holding the old dictionary are refilled.
        $this->assertNotSame($version, InterfaceTranslation::catalogVersion());
    }

    public function test_it_leaves_existing_wording_alone(): void
    {
        InterfaceTranslation::query()->create([
            'source_hash' => InterfaceTranslation::hashSource('Academic Year'),
            'english' => 'Academic Year',
            'arabic' => 'عام دراسي (محرَّر يدويًا)',
        ]);

        $this->seed(InterfaceTranslationSeeder::class);

        $this->assertDatabaseHas('interface_translations', [
            'english' => 'Academic Year',
            'arabic' => 'عام دراسي (محرَّر يدويًا)',
        ]);

        $this->assertDatabaseCount('interface_translations', $this->pairCount());
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $this->seed(InterfaceTranslationSeeder::class);

        $count = InterfaceTranslation::query()->count();
        $version = InterfaceTranslation::catalogVersion();

        $this->seed(InterfaceTranslationSeeder::class);

        $this->assertSame($count, InterfaceTranslation::query()->count());
        $this->assertSame($version, InterfaceTranslation::catalogVersion());
    }

    private function pairCount(): int
    {
        $decoded = json_decode(
            (string) File::get(database_path('seeders/data/interface-translations-ar.json')),
            true,
        );

        $this->assertIsArray($decoded);

        return count($decoded);
    }
}
