<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Academics;

use App\Domain\Academics\Actions\CreateAcademicYear;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Schools\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAcademicYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_academic_year(): void
    {
        $school = School::factory()->create();
        $action = new CreateAcademicYear($school);

        $result = $action->execute([
            'name_ar' => '٢٠٢٤-٢٠٢٥',
            'name_en' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
            'is_current' => true,
        ]);

        $this->assertInstanceOf(AcademicYear::class, $result);
        $this->assertEquals('2024-2025', $result->name);
        $this->assertEquals($school->id, $result->school_id);
        $this->assertTrue($result->is_current);
    }

    public function test_prevents_duplicate_current_year(): void
    {
        $school = School::factory()->create();
        AcademicYear::factory()->create([
            'school_id' => $school->id,
            'is_current' => true,
        ]);

        $action = new CreateAcademicYear($school);

        $result = $action->execute([
            'name_ar' => '٢٠٢٥-٢٠٢٦',
            'name_en' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        $this->assertInstanceOf(AcademicYear::class, $result);
        $this->assertTrue($result->is_current);
        $this->assertFalse(AcademicYear::where('id', 1)->first()->is_current);
    }
}
