<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New website message</title>
</head>
<body style="margin:0;padding:24px;background:#f4f3ee;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#2b2a26;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e2ded2;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:24px 28px;border-bottom:1px solid #e2ded2;">
                <p style="margin:0;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;color:#8a8574;">{{ $schoolName }}</p>
                <h1 style="margin:8px 0 0;font-size:20px;line-height:1.3;">New message from the website</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:24px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border:1px solid #e2ded2;border-radius:10px;">
                    <tr>
                        <td style="padding:10px 14px;color:#8a8574;font-size:13px;">From</td>
                        <td style="padding:10px 14px;text-align:right;font-weight:600;">{{ $senderName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 14px;color:#8a8574;font-size:13px;">Email</td>
                        <td style="padding:10px 14px;text-align:right;font-weight:600;word-break:break-all;">{{ $senderEmail }}</td>
                    </tr>
                </table>

                <p style="margin:0 0 8px;font-size:13px;color:#8a8574;">Message</p>
                <p style="margin:0;white-space:pre-wrap;line-height:1.6;">{{ $messageBody }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:18px 28px;border-top:1px solid #e2ded2;font-size:12px;color:#8a8574;">
                Reply to this email to answer {{ $senderName }} directly.
            </td>
        </tr>
    </table>
</body>
</html>
