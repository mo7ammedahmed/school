<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice {{ $invoice->invoice_number }} &middot; {{ $school?->name ?? config('app.name') }}</title>
    <style>
        :root {
            --bg: #f4f3ee;
            --card: #ffffff;
            --text: #1c1a16;
            --muted: #74705f;
            --line: #e2ded2;
            --accent: {{ $school?->accent_color ?: '#0f6b4f' }};
            --accent-fg: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 32px 16px 64px;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
        }
        .wrap { max-width: 680px; margin: 0 auto; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .brand img { height: 40px; width: auto; border-radius: 8px; }
        .brand .mark {
            height: 40px; width: 40px; border-radius: 10px; background: var(--accent); color: var(--accent-fg);
            display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;
        }
        .brand h1 { font-size: 17px; margin: 0; }
        .brand p { margin: 0; font-size: 12px; color: var(--muted); }
        .card {
            background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 26px 26px 24px;
            box-shadow: 0 1px 2px rgba(28, 26, 22, 0.04);
        }
        .card + .card { margin-top: 16px; }
        .head { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; justify-content: space-between; }
        .head h2 { margin: 0 0 4px; font-size: 20px; }
        .head .meta { margin: 0; color: var(--muted); font-size: 13px; }
        .amount { text-align: right; }
        .amount .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }
        .amount .value { font-size: 28px; font-weight: 700; margin-top: 2px; }
        .badge {
            display: inline-block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em;
            padding: 4px 10px; border-radius: 999px; border: 1px solid var(--line); color: var(--muted);
        }
        .badge.paid { background: #e8f5ee; border-color: #b9dfc9; color: #0f6b4f; }
        .flash { border-radius: 10px; padding: 12px 14px; font-size: 14px; margin-bottom: 16px; }
        .flash.success { background: #e8f5ee; border: 1px solid #b9dfc9; color: #0f6b4f; }
        .flash.error { background: #fdecec; border: 1px solid #f3c2c2; color: #8c2b2b; }
        .flash.info { background: #eef2fb; border: 1px solid #c9d6f0; color: #2b3f75; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: 14px; }
        table.lines th, table.lines td { text-align: left; padding: 9px 0; border-bottom: 1px solid var(--line); }
        table.lines th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); }
        table.lines td.num, table.lines th.num { text-align: right; }
        table.totals { width: 100%; margin-top: 14px; font-size: 14px; }
        table.totals td { padding: 4px 0; }
        table.totals td.label { text-align: right; color: var(--muted); padding-right: 16px; }
        table.totals td.value { text-align: right; width: 140px; font-variant-numeric: tabular-nums; }
        table.totals tr.grand td { border-top: 1px solid var(--line); padding-top: 10px; font-weight: 700; font-size: 16px; }
        .actions { margin-top: 22px; display: flex; flex-wrap: wrap; gap: 10px; }
        .btn {
            appearance: none; border: 1px solid transparent; border-radius: 10px; padding: 12px 20px;
            font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block;
        }
        .btn-primary { background: var(--accent); color: var(--accent-fg); }
        .btn-outline { background: transparent; border-color: var(--line); color: var(--text); }
        .btn[disabled] { opacity: 0.55; cursor: not-allowed; }
        label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px; }
        input[type="text"] {
            width: 100%; padding: 11px 12px; border: 1px solid var(--line); border-radius: 10px;
            font-size: 14px; background: #fff; color: var(--text);
        }
        .hint { font-size: 13px; color: var(--muted); margin: 0 0 16px; white-space: pre-line; }
        .foot { text-align: center; font-size: 12px; color: var(--muted); margin-top: 24px; }
        .foot a { color: var(--muted); }
        @media (max-width: 520px) {
            .amount { text-align: left; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        @if ($school?->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($school->logo_path) }}" alt="{{ $school->name }}">
        @else
            <span class="mark">{{ mb_substr($school?->name ?? 'S', 0, 1) }}</span>
        @endif
        <div>
            <h1>{{ $school?->name ?? config('app.name') }}</h1>
            <p>{{ $school?->email }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="head">
            <div>
                <h2>Invoice {{ $invoice->invoice_number }}</h2>
                <p class="meta">
                    For {{ trim(($student?->first_name ?? '').' '.($student?->last_name ?? '')) ?: 'your child' }}
                    @if ($invoice->due_date) &middot; due {{ $invoice->due_date->toFormattedDateString() }} @endif
                </p>
                <p style="margin-top:8px;">
                    @if ($paid)
                        <span class="badge paid">Paid in full</span>
                    @else
                        <span class="badge">{{ $invoice->status }}</span>
                    @endif
                </p>
            </div>
            <div class="amount">
                <div class="label">{{ $paid ? 'Amount settled' : 'Amount due' }}</div>
                <div class="value">{{ number_format((float) $invoice->balance_due, 2) }} {{ $invoice->currency }}</div>
            </div>
        </div>

        <table class="lines">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lines as $line)
                    <tr>
                        <td>{{ $line->description }}</td>
                        <td class="num">{{ number_format((float) $line->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>{{ $invoice->notes ?: 'School fees' }}</td>
                        <td class="num">{{ number_format((float) $invoice->subtotal, 2) }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Tax</td>
                <td class="value">{{ number_format((float) $invoice->tax_amount, 2) }}</td>
            </tr>
            @if ((float) $invoice->discount_amount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value">-{{ number_format((float) $invoice->discount_amount, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Total</td>
                <td class="value">{{ number_format((float) $invoice->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Already paid</td>
                <td class="value">{{ number_format((float) $invoice->amount_paid, 2) }}</td>
            </tr>
            <tr class="grand">
                <td class="label">Balance due</td>
                <td class="value">{{ number_format((float) $invoice->balance_due, 2) }} {{ $invoice->currency }}</td>
            </tr>
        </table>

        @if ($paid)
            <div class="actions">
                <span class="badge paid">Thank you — this invoice is settled.</span>
            </div>
        @else
            <div class="actions">
                @if ($onlineAvailable)
                    <form method="POST" action="{{ URL::temporarySignedRoute('public.invoices.checkout', now()->addDays(60), ['invoice' => $invoice->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            Pay {{ number_format((float) $invoice->balance_due, 2) }} {{ $invoice->currency }} now
                        </button>
                    </form>
                @endif
                <a class="btn btn-outline" href="{{ URL::temporarySignedRoute('public.invoices.pdf', now()->addDays(60), ['invoice' => $invoice->id]) }}">
                    Download PDF
                </a>
            </div>

            @if (! $onlineAvailable)
                <p class="hint" style="margin-top:18px;">
                    {{ $settings->instructions() ?: 'Online card payment is not enabled for this invoice. Please transfer the balance to the school account and confirm below.' }}
                </p>
            @endif
        @endif
    </div>

    @if (! $paid)
        <div class="card">
            <h2 style="margin:0 0 6px;font-size:16px;">Paid by bank transfer?</h2>
            <p class="hint">Let the school know so the finance team can confirm your payment.</p>
            <form method="POST" action="{{ URL::temporarySignedRoute('public.invoices.offline', now()->addDays(60), ['invoice' => $invoice->id]) }}">
                @csrf
                <label for="reference">Transfer reference (optional)</label>
                <input id="reference" name="reference" type="text" maxlength="255" placeholder="e.g. bank reference number">
                <div class="actions">
                    <button type="submit" class="btn btn-primary">I have transferred the money</button>
                </div>
            </form>
        </div>
    @endif

    <p class="foot">
        {{ $school?->name ?? config('app.name') }}
        @if ($school?->phone) &middot; {{ $school->phone }} @endif
        <br>This link is unique to invoice {{ $invoice->invoice_number }} and expires 60 days after it was sent.
    </p>
</div>
</body>
</html>
