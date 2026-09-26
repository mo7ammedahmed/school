<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c1a16; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 22px 0 6px; }
        p { margin: 0 0 4px; }
        .muted { color: #74705f; }
        .header { border-bottom: 2px solid #1c1a16; padding-bottom: 10px; margin-bottom: 16px; }
        .row { width: 100%; }
        .row td { vertical-align: top; }
        .right { text-align: right; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border: 1px solid #ddd8c9; padding: 6px 8px; text-align: left; }
        table.items th { background: #f2efe8; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
        table.totals { width: 100%; margin-top: 10px; }
        table.totals td { padding: 3px 0; }
        table.totals td.label { text-align: right; color: #74705f; padding-right: 12px; }
        table.totals td.value { text-align: right; width: 120px; }
        .total-row td { border-top: 1px solid #1c1a16; font-size: 13px; font-weight: bold; padding-top: 6px; }
        .badge { display: inline-block; padding: 3px 8px; border: 1px solid #1c1a16; font-size: 10px; text-transform: uppercase; }
        .pay-box { margin-top: 18px; border: 1px solid #ddd8c9; background: #f8f6f1; padding: 10px 12px; }
        .pay-box .url { font-size: 9px; word-break: break-all; color: #2b2a26; }
    </style>
</head>
<body>
    <div class="header">
        <table class="row">
            <tr>
                <td>
                    <h1>{{ $schoolName }}</h1>
                    @if ($school?->address)<p class="muted">{{ $school->address }}</p>@endif
                    @if ($school?->phone)<p class="muted">{{ $school->phone }}</p>@endif
                    @if ($school?->email)<p class="muted">{{ $school->email }}</p>@endif
                </td>
                <td class="right">
                    <p class="badge">{{ $invoice->status }}</p>
                    <p><strong>{{ $invoice->invoice_number }}</strong></p>
                    <p class="muted">Issued {{ optional($invoice->issue_date)->toFormattedDateString() ?? $generatedAt->toFormattedDateString() }}</p>
                    <p class="muted">Due {{ optional($invoice->due_date)->toFormattedDateString() }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="row">
        <tr>
            <td>
                <h2>Billed to</h2>
                <p><strong>{{ $studentName }}</strong></p>
                @if ($student?->student_number)<p class="muted">Student no. {{ $student->student_number }}</p>@endif
                @if ($guardians->isNotEmpty())
                    <p class="muted">Guardian: {{ trim($guardians->first()->first_name.' '.$guardians->first()->last_name) }}</p>
                @endif
            </td>
            <td class="right">
                <h2>{{ $invoice->isPaid() ? 'Amount settled' : 'Amount due' }}</h2>
                <p style="font-size: 20px; font-weight: bold;">
                    {{ number_format((float) $invoice->balance_due, 2).' '.$invoice->currency }}
                </p>
            </td>
        </tr>
    </table>

    <h2>Details</h2>
    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th style="width: 60px;">Qty</th>
                <th style="width: 90px;">Unit price</th>
                <th style="width: 90px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td>{{ rtrim(rtrim(number_format((float) $line->quantity, 2), '0'), '.') }}</td>
                    <td>{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td>{{ number_format((float) $line->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td>{{ $invoice->notes ?: 'School fees' }}</td>
                    <td>1</td>
                    <td>{{ number_format((float) $invoice->subtotal, 2) }}</td>
                    <td>{{ number_format((float) $invoice->subtotal, 2) }}</td>
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
            <td class="label">Paid</td>
            <td class="value">{{ number_format((float) $invoice->amount_paid, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td class="label">Balance due</td>
            <td class="value">{{ number_format((float) $invoice->balance_due, 2) }} {{ $invoice->currency }}</td>
        </tr>
    </table>

    @if (! $invoice->isPaid())
        <div class="pay-box">
            <p><strong>Pay online</strong></p>
            <p class="muted">Open this secure link, or forward it to whoever handles the payment:</p>
            <p class="url">{{ $payUrl }}</p>
        </div>
    @endif

    @if ($invoice->notes)
        <h2>Notes</h2>
        <p class="muted">{{ $invoice->notes }}</p>
    @endif

    <p class="muted" style="margin-top: 24px;">Generated {{ $generatedAt->toDayDateTimeString() }}.</p>
</body>
</html>
