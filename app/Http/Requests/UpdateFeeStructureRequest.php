<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Editing a fee structure accepts exactly what creating one accepts — the same
 * amount and the same bilingual description — so the rules live in one place
 * rather than in two lists that can drift apart.
 */
class UpdateFeeStructureRequest extends StoreFeeStructureRequest {}
