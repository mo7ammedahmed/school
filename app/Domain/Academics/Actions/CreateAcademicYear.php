<?php

declare(strict_types=1);

namespace App\Domain\Academics\Actions;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\TenantContext;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateAcademicYear
{
    public function __construct(private readonly School $school) {}

    public function execute(array $data): AcademicYear
    {
        // The action was constructed with the school it works on, so it pins
        // that school while it runs: the demotion of the previous current year
        // and the insert are then both inside the same tenant, whether the
        // caller is a request, an onboarding step or a test. Without the pin,
        // the global tenant scope would let the update match nothing.
        return app(TenantContext::class)->runFor($this->school->id, function () use ($data): AcademicYear {
            if (empty($data['name_en']) || empty($data['start_date']) || empty($data['end_date'])) {
                throw new Exception('Academic year name, start date, and end date are required.');
            } if ($data['start_date'] >= $data['end_date']) {
                throw new Exception('Start date must be before end date.');
            } if (! empty($data['is_current'])) {
                AcademicYear::where('school_id', $this->school->id)->where('is_current', true)->update(['is_current' => false]);
            } $data['school_id'] = $this->school->id;

            try {
                return DB::transaction(fn () => AcademicYear::create($data));
            } catch (UniqueConstraintViolationException $e) {
                throw new Exception('An academic year with this name already exists.', $e->getCode(), previous: $e);
            }
        });
    }
}
