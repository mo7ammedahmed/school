<?php

declare(strict_types=1);

namespace App\Domain\Communication\Services;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Models\SchoolSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * School-configured SMS delivery.
 *
 * Supports Unifonic and Twilio over HTTP, plus a `log` driver used as a safe
 * default. The log driver reports `sent => false` rather than pretending an
 * unconfigured school actually delivered a message.
 */
class SmsSender
{
    public const KEY = 'sms';

    public const PROVIDERS = ['log', 'unifonic', 'twilio'];

    private const array SECRETS = ['api_key', 'auth_token'];

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function __construct(private readonly int $schoolId) {}

    public static function for(School|int $school): self
    {
        return new self($school instanceof School ? (int) $school->id : $school);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'provider' => 'log',
            'sender_id' => null,
            'api_key' => null,
            'account_sid' => null,
            'auth_token' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->values === null) {
            $setting = SchoolSetting::query()
                ->where('school_id', $this->schoolId)
                ->where('key', self::KEY)
                ->first();

            $decoded = $setting && $setting->value ? json_decode((string) $setting->value, true) : null;

            $this->values = array_merge(self::defaults(), is_array($decoded) ? $decoded : []);
        }

        return $this->values;
    }

    public function provider(): string
    {
        $provider = (string) ($this->all()['provider'] ?? 'log');

        return in_array($provider, self::PROVIDERS, true) ? $provider : 'log';
    }

    public function isConfigured(): bool
    {
        return match ($this->provider()) {
            'unifonic' => $this->reveal('api_key') !== null && $this->senderId() !== null,
            'twilio' => $this->reveal('auth_token') !== null
                && ($this->all()['account_sid'] ?? null) !== null
                && $this->senderId() !== null,
            default => false,
        };
    }

    public function senderId(): ?string
    {
        $value = $this->all()['sender_id'] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        $current = $this->all();

        foreach (self::SECRETS as $secret) {
            if (! array_key_exists($secret, $values)) {
                continue;
            }

            if ($values[$secret] === null || $values[$secret] === '') {
                $values[$secret] = $current[$secret] ?? null;

                continue;
            }

            $values[$secret] = Crypt::encryptString((string) $values[$secret]);
        }

        $merged = array_merge($current, $values);
        $merged['provider'] = in_array($merged['provider'] ?? 'log', self::PROVIDERS, true)
            ? $merged['provider']
            : 'log';

        SchoolSetting::updateOrCreate(
            ['school_id' => $this->schoolId, 'key' => self::KEY],
            ['value' => json_encode($merged), 'type' => 'json'],
        );

        $this->values = $merged;
    }

    /**
     * @return array<string, mixed>
     */
    public function masked(): array
    {
        return [
            'provider' => $this->provider(),
            'sender_id' => $this->senderId(),
            'account_sid' => $this->all()['account_sid'] ?? null,
            'has_api_key' => $this->reveal('api_key') !== null,
            'has_auth_token' => $this->reveal('auth_token') !== null,
        ];
    }

    /**
     * @return array{sent: bool, driver: string, detail: string}
     */
    public function send(?string $to, string $body): array
    {
        $to = $to === null ? null : trim($to);

        if ($to === null || $to === '') {
            return ['sent' => false, 'driver' => $this->provider(), 'detail' => 'No phone number on file.'];
        }

        if (! $this->isConfigured()) {
            // Still record the intent so an operator can see what would have gone out.
            Log::info('SMS not sent: no provider configured', ['to' => $to, 'body' => $body]);

            return ['sent' => false, 'driver' => 'log', 'detail' => 'SMS provider not configured.'];
        }

        try {
            return $this->provider() === 'twilio'
                ? $this->sendViaTwilio($to, $body)
                : $this->sendViaUnifonic($to, $body);
        } catch (Throwable $e) {
            Log::error('SMS delivery failed', ['to' => $to, 'error' => $e->getMessage()]);

            return ['sent' => false, 'driver' => $this->provider(), 'detail' => $e->getMessage()];
        }
    }

    /**
     * @return array{sent: bool, driver: string, detail: string}
     */
    private function sendViaTwilio(string $to, string $body): array
    {
        $sid = (string) $this->all()['account_sid'];
        $token = (string) $this->reveal('auth_token');

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->timeout(15)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $this->senderId(),
                'To' => $to,
                'Body' => $body,
            ]);

        return [
            'sent' => $response->successful(),
            'driver' => 'twilio',
            'detail' => $response->successful() ? 'Delivered to Twilio.' : 'Twilio returned '.$response->status(),
        ];
    }

    /**
     * @return array{sent: bool, driver: string, detail: string}
     */
    private function sendViaUnifonic(string $to, string $body): array
    {
        $response = Http::asForm()
            ->timeout(15)
            ->post('https://el.cloud.unifonic.com/rest/SMS/messages', [
                'AppSid' => (string) $this->reveal('api_key'),
                'SenderID' => $this->senderId(),
                'Recipient' => $to,
                'Body' => $body,
            ]);

        $success = $response->successful() && $response->json('success') !== false;

        return [
            'sent' => $success,
            'driver' => 'unifonic',
            'detail' => $success ? 'Delivered to Unifonic.' : 'Unifonic rejected the message.',
        ];
    }

    private function reveal(string $key): ?string
    {
        $stored = $this->all()[$key] ?? null;

        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $stored);
        } catch (DecryptException) {
            return null;
        }
    }
}
