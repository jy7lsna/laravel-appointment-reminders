<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $businessName }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#18181b;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:12px;border:1px solid #e4e4e7;">
                <tr>
                    <td style="padding:24px 28px 0;font-size:15px;font-weight:700;color:#4338ca;">{{ $businessName }}</td>
                </tr>
                <tr>
                    <td style="padding:12px 28px 28px;font-size:15px;line-height:1.6;">
                        @yield('content')
                    </td>
                </tr>
            </table>
            <p style="font-size:12px;color:#71717a;margin:16px 0 0;">
                {{ $businessName }}@if($businessAddress) · {{ $businessAddress }}@endif
            </p>
        </td>
    </tr>
</table>
</body>
</html>
