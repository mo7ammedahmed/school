<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\School;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'email_verified_at',
    'theme',
    'locale',
    'timezone',
    'email_notifications',
    'push_notifications',
    'sms_notifications',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    public function memberships()
    {
        return $this->hasMany(UserMembership::class);
    }

    /**
     * Schools the user belongs to, resolved through active memberships.
     *
     * Membership rows carry the role and active flag, so school context always
     * resolves through this relationship rather than a direct pivot.
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'user_memberships')
            ->wherePivot('is_active', true);
    }

    protected function currentMembership(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->memberships()->where('is_active', true)->latest()->first(),
        );
    }

    public function getCurrentMembershipAttribute()
    {
        return $this->memberships()->where('is_active', true)->latest()->first();
    }

    protected function currentSchool(): Attribute
    {
        return Attribute::make(get: fn () => $this->currentMembership?->school);
    }
}
