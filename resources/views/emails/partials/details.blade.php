<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:16px 0;background:#f8fafc;border:1px solid #e4e4e7;border-radius:8px;">
    <tr><td style="padding:14px 16px;font-size:14px;line-height:1.7;">
        <strong>{{ $service->name }}</strong> ({{ $service->duration_minutes }} min)<br>
        <span style="color:#52525b;">When:</span> {{ $when }} – {{ $endsAt }}<br>
        <span style="color:#52525b;">Name:</span> {{ $appointment->customer_name }}
    </td></tr>
</table>
