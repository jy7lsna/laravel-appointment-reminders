@if($rescheduleUrl || $cancelUrl)
    <p style="margin:20px 0 8px;">
        @if($rescheduleUrl)
            <a href="{{ $rescheduleUrl }}" style="display:inline-block;background:#4338ca;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:600;margin:0 8px 8px 0;">Reschedule</a>
        @endif
        @if($cancelUrl)
            <a href="{{ $cancelUrl }}" style="display:inline-block;background:#ffffff;color:#b91c1c;text-decoration:none;padding:9px 17px;border-radius:8px;font-weight:600;border:1px solid #fca5a5;margin:0 0 8px;">Cancel</a>
        @endif
    </p>
    <p style="font-size:12px;color:#71717a;margin:0;">These links work until your appointment starts.</p>
@endif
