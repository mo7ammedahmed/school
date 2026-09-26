<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f3ee;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#2b2a26;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e2ded2;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:24px 28px;border-bottom:1px solid #e2ded2;">
                <p style="margin:0;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;color:#8a8574;">{{ $schoolName }}</p>
                <h1 style="margin:8px 0 0;font-size:20px;line-height:1.3;">{{ $heading }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:24px 28px;">
                <p style="margin:0 0 12px;">Dear parent or guardian of <strong>{{ $studentName }}</strong>,</p>

                @if ($kind === 'receipt')
                    <p style="margin:0 0 12px;">
                        We have received your payment of <strong>{{ $paidAmount }}</strong> for invoice
                        <strong>{{ $invoice->invoice_number }}</strong>.
                    </p>
                @elseif ($kind === 'reminder')
                    <p style="margin:0 0 12px;">
                        Our records show invoice <strong>{{ $invoice->invoice_number }}</strong> is still outstanding.
                    </p>
                @else
                    <p style="margin:0 0 12px;">
                        Invoice <strong>{{ $invoice->invoice_number }}</strong> is now available.
                    </p>
                @endif

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;border:1px solid #e2ded2;border-radius:10px;">
                    <tr>
                        <td style="padding:10px 14px;color:#8a8574;font-size:13px;">Invoice</td>
                        <td style="padding:10px 14px;text-align:right;font-weight:600;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 14px;color:#8a8574;font-size:13px;">Due date</td>
                        <td style="padding:10px 14px;text-align:right;font-weight:600;">{{ $dueDate ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 14px;color:#8a8574;font-size:13px;">Total</td>
                        <td style="padding:10px 14px;text-align:right;font-weight:600;">{{ $total }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 14px;color:#8a8574;font-size:13px;">Balance due</td>
                        <td style="padding:10px 14px;text-align:right;font-weight:700;font-size:16px;">{{ $amountDue }}</td>
                    </tr>
                </table>

                @if ($kind !== 'receipt' && (float) $invoice->balance_due > 0)
                    <p style="margin:20px 0 8px;">
                        <a href="{{ $payUrl }}"
                           style="display:inline-block;background:#0f6b4f;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600;">
                            Pay {{ $amountDue }} securely
                        </a>
                    </p>
                    <p style="margin:8px 0 0;font-size:12px;color:#8a8574;word-break:break-all;">
                        Or paste this link into your browser:<br>{{ $payUrl }}
                    </p>
                    <p style="margin:8px 0 0;font-size:12px;color:#8a8574;">
                        This link is personal to this invoice and expires in 60 days.
                    </p>
                @endif

                <p style="margin:20px 0 0;font-size:13px;color:#8a8574;">
                    The full invoice is attached as a PDF.
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:18px 28px;border-top:1px solid #e2ded2;font-size:12px;color:#8a8574;">
                {{ $schoolName }} &middot; Sent automatically by the school management system.
            </td>
        </tr>
    </table>
</body>
</html>
