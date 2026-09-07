<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Finance;

use App\Domain\Finance\Actions\CreateInvoice;
use App\Domain\Schools\Models\School;
use App\Domain\People\Models\Student;
use App\Domain\Finance\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_invoice(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create([
            'school_id' => $school->id,
        ]);

        $action = new CreateInvoice($school);

        $result = $action->execute(
            student: $student,
            lineData: [
                'description' => 'Tuition Fee',
                'quantity' => 1,
                'unit_price' => 1000,
                'amount' => 1000,
            ],
            dueDate: '2024-09-30',
            notes: 'Test invoice'
        );

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals($student->id, $result->student_id);
        $this->assertEquals($school->id, $result->school_id);
    }
}
