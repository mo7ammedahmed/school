<?php

declare(strict_types=1);

namespace App\Domain\Finance\Services;

use App\Domain\Communication\Models\Notification;
use App\Domain\Communication\Services\SmsSender;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\People\Models\Guardian;
use App\Mail\InvoiceMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;
use Throwable;

/**
 * Hands an invoice (or a reminder / receipt) to the people who actually pay it:
 * the student's guardians, falling back to the student's own contact details.
 *
 * Every attempt is recorded on the invoice so staff can see what went out and
 * what did not, instead of guessing.
 */
class InvoiceDeliveryService
{
    /**
     * @return array{channels: array<string, bool>, recipients: int, detail: string, errors: list<string>}
     */
    public function deliver(
        Invoice $invoice,
        string $kind = InvoiceMail::KIND_ISSUED,
        ?Payment $payment = null,
    ): array {
        $invoice->loadMissing(['school', 'student']);

        $settings = GatewaySettings::for((int) $invoice->school_id);
        $guardians = $invoice->guardians();
        $student = $invoice->student;

        $emails = $this->emails($guardians, $student);
        $phones = $this->phones($guardians, $student);
        $userIds = $this->userIds($guardians, $student);

        $result = [
            'channels' => ['email' => false, 'sms' => false, 'inapp' => false],
            'recipients' => $emails->count() + $phones->count() + $userIds->count(),
            'errors' => [],
            'detail' => '',
        ];

        if ($settings->wantsChannel('email') && $emails->isNotEmpty()) {
            $result['channels']['email'] = $this->sendEmails($invoice, $kind, $payment, $emails, $result['errors']);
        }

        if ($settings->wantsChannel('sms') && $phones->isNotEmpty()) {
            $result['channels']['sms'] = $this->sendSms($invoice, $kind, $phones, $result['errors']);
        }

        if ($settings->wantsChannel('inapp') && $userIds->isNotEmpty()) {
            $result['channels']['inapp'] = $this->notifyInApp($invoice, $kind, $userIds);
        }

        if ($result['recipients'] === 0) {
            $result['errors'][] = 'No guardian or student email/phone is on file.';
        }

        $this->record($invoice, $kind, $result['channels']);

        $result['detail'] = $this->summarise($kind, $result['channels'], $result['recipients']);

        return $result;
    }

    /**
     * @param  Collection<int, string>  $emails
     * @param  list<string>  $errors
     */
    private function sendEmails(
        Invoice $invoice,
        string $kind,
        ?Payment $payment,
        Collection $emails,
        array &$errors,
    ): bool {
        $sent = false;

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new InvoiceMail($invoice, $kind, $payment));
                $sent = true;
            } catch (Throwable $e) {
                Log::error('Invoice email failed', [
                    'invoice_id' => $invoice->id,
                    'to' => $email,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = 'Email to '.$email.' failed.';
            }
        }

        return $sent;
    }

    /**
     * @param  Collection<int, string>  $phones
     * @param  list<string>  $errors
     */
    private function sendSms(Invoice $invoice, string $kind, Collection $phones, array &$errors): bool
    {
        $sender = SmsSender::for((int) $invoice->school_id);
        $body = $this->smsBody($invoice, $kind);
        $sent = false;

        foreach ($phones as $phone) {
            $response = $sender->send($phone, $body);

            if ($response['sent']) {
                $sent = true;
            } else {
                $errors[] = 'SMS to '.$phone.': '.$response['detail'];
            }
        }

        return $sent;
    }

    /**
     * @param  Collection<int, int<0, max>>  $userIds
     */
    private function notifyInApp(Invoice $invoice, string $kind, Collection $userIds): bool
    {
        $title = match ($kind) {
            InvoiceMail::KIND_RECEIPT => 'Payment received',
            InvoiceMail::KIND_REMINDER => 'Invoice reminder',
            default => 'New invoice',
        };

        $body = match ($kind) {
            InvoiceMail::KIND_RECEIPT => 'We received your payment for invoice '.$invoice->invoice_number.'.',
            InvoiceMail::KIND_REMINDER => 'Invoice '.$invoice->invoice_number.' is still outstanding.',
            default => 'Invoice '.$invoice->invoice_number.' for '.$this->money($invoice, (float) $invoice->balance_due).' is ready.',
        };

        foreach ($userIds as $userId) {
            Notification::create([
                'school_id' => $invoice->school_id,
                'user_id' => $userId,
                'type' => 'invoice_'.$kind,
                'title' => $title,
                'body' => $body,
                'action_url' => InvoiceLinks::payPath($invoice),
            ]);
        }

        return $userIds->isNotEmpty();
    }

    /**
     * Record the attempt. The timestamp only advances when something actually
     * left the building, so the UI never claims an invoice was "sent" when the
     * student had no guardian to send it to.
     *
     * @param  array<string, bool>  $channels
     */
    private function record(Invoice $invoice, string $kind, array $channels): void
    {
        $attributes = ['delivery_channels' => $channels];

        if ($channels !== [] && in_array(true, $channels, true)) {
            if ($kind === InvoiceMail::KIND_REMINDER) {
                $attributes['reminder_sent_at'] = now();
            } else {
                $attributes['sent_at'] = now();
            }
        }

        $invoice->forceFill($attributes)->save();
    }

    /**
     * One address per guardian — their own if we have it, otherwise the address
     * on their portal account — then the student as a last resort. A guardian
     * with both must not receive the same invoice twice.
     *
     * @param  Collection<int, Guardian>  $guardians
     * @return Collection<int, string>
     */
    private function emails(Collection $guardians, mixed $student): Collection
    {
        $emails = $guardians
            ->map(fn (Guardian $guardian) => $guardian->email ?: $guardian->user?->email)
            ->push($student?->email);

        return $this->clean($emails);
    }

    /**
     * @param  Collection<int, Guardian>  $guardians
     * @return Collection<int, string>
     */
    private function phones(Collection $guardians, mixed $student): Collection
    {
        $phones = $guardians
            ->map(fn (Guardian $guardian) => $guardian->phone)
            ->push($student?->phone);

        return $this->clean($phones);
    }

    /**
     * @param  Collection<int, Guardian>  $guardians
     * @return Collection<int, int<0, max>>
     */
    private function userIds(Collection $guardians, mixed $student): Collection
    {
        return $guardians
            ->map(fn (Guardian $guardian) => $guardian->user_id)
            ->push($student?->user_id)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return Collection<int, string>
     */
    private function clean(Collection $values): Collection
    {
        return $values
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->unique()
            ->values();
    }

    private function smsBody(Invoice $invoice, string $kind): string
    {
        $school = $invoice->school?->name ?? config('app.name');
        $amount = $this->money($invoice, (float) $invoice->balance_due);
        $url = InvoiceLinks::payUrl($invoice);

        return match ($kind) {
            InvoiceMail::KIND_RECEIPT => "{$school}: payment received for invoice {$invoice->invoice_number}. Thank you.",
            InvoiceMail::KIND_REMINDER => "{$school}: invoice {$invoice->invoice_number} ({$amount}) is still due. Pay: {$url}",
            default => "{$school}: invoice {$invoice->invoice_number} for {$amount} is ready. Pay: {$url}",
        };
    }

    private function money(Invoice $invoice, float $amount): string
    {
        return Number::currency($amount, (string) ($invoice->currency ?: 'SAR'), app()->getLocale());
    }

    /**
     * @param  array<string, bool>  $channels
     */
    private function summarise(string $kind, array $channels, int $recipients): string
    {
        $used = array_keys(array_filter($channels));

        if ($used === []) {
            return 'Nothing was delivered.';
        }

        return sprintf(
            '%s delivered to %d contact(s) via %s.',
            ucfirst($kind),
            $recipients,
            implode(', ', $used),
        );
    }
}
