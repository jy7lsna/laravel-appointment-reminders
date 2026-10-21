<p style="margin:20px 0;padding:2px 0 2px 14px;border-left:3px solid #d6d3d1;font-size:14px;line-height:1.7;">
    <strong>{{ $service->name }}</strong>, {{ $service->duration_minutes }} min<br>
    <span style="color:#57534e;">When:</span> {{ $when }} – {{ $endsAt }}<br>
    <span style="color:#57534e;">Name:</span> {{ $appointment->customer_name }}
</p>
