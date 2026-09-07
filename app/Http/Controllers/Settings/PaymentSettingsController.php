<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class PaymentSettingsController extends Controller
{
    public function edit(): Response
    {
        return inertia('settings/payments/edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_gateway' => 'required|in:stripe,paypal,razorpay,custom',
            'stripe_key' => 'nullable|string|max:255',
            'stripe_secret' => 'nullable|string|max:255',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_secret' => 'nullable|string|max:255',
            'paypal_mode' => 'required|in:sandbox,live',
            'currency' => 'required|string|max:10',
            'payment_enabled' => 'required|boolean',
            'auto_approve_payments' => 'required|boolean',
        ]);

        // TODO: Save payment settings to database or config

        return redirect()->route('settings.payments.edit')->with('success', 'Payment settings updated successfully.');
    }
}
