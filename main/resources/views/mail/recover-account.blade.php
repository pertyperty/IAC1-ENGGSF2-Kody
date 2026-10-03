<h1>Recover your Kody account</h1>
<p>Follow this link to choose a new password. It expires in {{ config('account.recovery.expires_minutes') }} minutes.</p>
<p><a href="{{ $recoveryUrl }}">Recover account</a></p>
<p>If the link does not open, paste this code on the recovery page:</p>
<p>{{ $recoveryToken }}</p>
<p>If you did not request recovery, you can ignore this email. Your password has not changed.</p>
