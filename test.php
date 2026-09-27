<?php

use App\Domain\Schools\Models\School;
use App\Http\Controllers\Settings\AppearanceSettingsController;
use App\Models\User;

$user = User::first();
$school = School::first();
$user->update(['role' => 'super_admin']);
auth()->login($user);
session(['school_id' => $school->id]);
$controller = new AppearanceSettingsController;

return $controller->index();
