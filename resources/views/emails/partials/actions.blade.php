@if($rescheduleUrl || $cancelUrl)
    @if($rescheduleUrl)
        <p style="margin:24px 0 16px;">
            <a href="{{ $rescheduleUrl }}" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:15px;font-weight:600;">Reschedule</a>
        </p>
    @endif
    @if($cancelUrl)
        <p style="margin:0 0 8px;font-size:14px;color:#57534e;">Can't make it at all? <a href="{{ $cancelUrl }}" style="color:#0f766e;">Cancel this appointment</a>.</p>
    @endif
    <p style="font-size:12px;color:#78716c;margin:0;">These links work until your appointment starts.</p>
@endif
