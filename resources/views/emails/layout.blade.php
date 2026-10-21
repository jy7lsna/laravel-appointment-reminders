<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $businessName }}</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1c1917;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#ffffff;">
    <tr>
        <td align="center" style="padding:32px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;">
                <tr>
                    <td style="padding:0 0 20px;font-size:14px;font-weight:600;color:#1c1917;border-bottom:1px solid #e7e5e4;">{{ $businessName }}</td>
                </tr>
                <tr>
                    <td style="padding:24px 0;font-size:15px;line-height:1.6;color:#1c1917;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 0 0;border-top:1px solid #e7e5e4;font-size:12px;line-height:1.5;color:#78716c;">
                        {{ $businessName }}@if($businessAddress) · {{ $businessAddress }}@endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
