<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Identity\Models\UserMembership;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function memberships()
    {
        return $this->hasMany(UserMembership::class);
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
        return Attribute::make(get: fn() => $this->currentMembership?->school);
    }
}
