<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domain\Finance\Models\GatewayTransaction;
use App\Domain\Finance\Models\WebhookEvent;
use App\Domain\Finance\Services\GatewaySettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Lets a school administrator choose and configure the payment gateway used for
 * guardian payment links.
 */
class PaymentSettingsController extends Controller
{
    public function index(): Response
    {
        $schoolId = $this->schoolId();
        $settings = GatewaySettings::for($schoolId);

        return inertia('settings/payments', [
            'settings' => $settings->masked(),
            'gateways' => [
                ['value' => 'offline', 'label' => 'Offline / bank transfer', 'online' => false],
                ['value' => 'moyasar', 'label' => 'Moyasar', 'online' => true],
                ['value' => 'hyperpay', 'label' => 'HyperPay', 'online' => true],
                ['value' => 'stripe', 'label' => 'Stripe', 'online' => true],
            ],
            'channels' => [
                ['value' => 'email', 'label' => 'Email (invoice PDF + payment link)'],
                ['value' => 'sms', 'label' => 'SMS (short link)'],
                ['value' => 'inapp', 'label' => 'In-app notification'],
            ],
            'webhookUrl' => route('webhooks.payments.handle', ['gateway' => $settings->gateway()]),
            'activity' => $settings->activity(),
            'onlineCheckout' => $settings->supportsOnlineCheckout(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gateway' => 'required|in:'.implode(',', GatewaySettings::GATEWAYS),
            'mode' => 'required|in:'.implode(',', GatewaySettings::MODES),
            'enabled' => 'required|boolean',
            'currency' => 'required|string|max:10',
            'auto_send' => 'required|boolean',
            'channels' => 'present|array',
            'channels.*' => 'in:'.implode(',', GatewaySettings::CHANNELS),
            'public_key' => 'nullable|string|max:500',
            'secret_key' => 'nullable|string|max:500',
            'webhook_secret' => 'nullable|string|max:500',
            'clear_secret_key' => 'nullable|boolean',
            'clear_webhook_secret' => 'nullable|boolean',
            'instructions' => 'nullable|string|max:2000',
        ]);

        GatewaySettings::for($this->schoolId())->save($validated);

        return back()->with('success', 'Payment settings saved.');
    }

    public function logs(): Response
    {
        $schoolId = $this->schoolId();

        $transactions = GatewayTransaction::query()
            ->where('school_id', $schoolId)
            ->latest()
            ->limit(50)
            ->get(['id', 'gateway', 'gateway_transaction_id', 'status', 'amount', 'created_at']);

        $events = WebhookEvent::query()
            ->where('school_id', $schoolId)
            ->latest()
            ->limit(50)
            ->get(['id', 'gateway', 'event_id', 'event_type', 'status', 'error_message', 'created_at']);

        return inertia('settings/payments-logs', [
            'transactions' => $transactions,
            'events' => $events,
        ]);
    }
}
