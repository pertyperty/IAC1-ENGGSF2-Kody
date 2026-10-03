<p>A staff action was recorded for your Kody account: {{ $action }} at {{ \Carbon\CarbonImmutable::parse($occurredAt)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila.</p>
<p>This reports the action at that time. Later actions may have changed your current account status.</p>
@if($action === 'Suspended')<p>Suspension ends current sessions and restricts account access. Contact your Kody support team if you wish to appeal.</p>@else<p>You may sign in again if your account remains Active. Previous sessions are not restored.</p>@endif
