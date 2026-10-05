<?php

declare(strict_types=1);

namespace App\Domain\Learning\Services;

use App\Domain\Learning\Models\LiveSession;
use App\Domain\Schools\Support\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Throwable;

/** Media credentials are limited to one user, lesson, action and expiry. */
final class LiveMediaAccess
{
    public function issue(User $user, LiveSession $session, string $action): string
    {
        return Crypt::encryptString(json_encode([
            'user' => $user->id,
            'session' => $session->id,
            'action' => $action,
            'expires' => now()->addHours(2)->timestamp,
            'credential' => hash('sha256', $user->getAuthPassword()),
        ], JSON_THROW_ON_ERROR));
    }

    public function accepts(string $token, string $path, string $action): bool
    {
        try {
            $claims = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        if (! is_array($claims) || ! in_array($action, ['read', 'publish'], true)
            || ($claims['action'] ?? null) !== $action
            || ! is_int($claims['expires'] ?? null) || $claims['expires'] <= now()->timestamp
            || ! is_int($claims['user'] ?? null) || ! is_int($claims['session'] ?? null)
            || ! is_string($claims['credential'] ?? null)) {
            return false;
        }

        $user = User::find($claims['user']);
        $session = LiveSession::withoutSchoolScope()->find($claims['session']);

        return $user !== null && $session !== null
            && hash_equals(hash('sha256', $user->getAuthPassword()), $claims['credential'])
            && hash_equals((string) $session->stream_key, $path)
            && $this->allows($user, $session, $action);
    }

    public function allows(User $user, LiveSession $session, string $action): bool
    {
        if (! in_array($action, ['read', 'publish'], true) || ! $session->isLive()) {
            return false;
        }

        if (! $user->hasRole('super_admin') && ! $user->memberships()
            ->where('school_id', $session->school_id)->where('is_active', true)->exists()) {
            return false;
        }

        return app(TenantContext::class)->runFor((int) $session->school_id,
            fn (): bool => Gate::forUser($user)->allows($action === 'publish' ? 'update' : 'view', $session));
    }
}
