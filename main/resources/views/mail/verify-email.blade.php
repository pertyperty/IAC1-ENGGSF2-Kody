<!DOCTYPE html>
<html lang="en"><body>
    <h1>Verify your Kody email</h1>
    <p>Confirm your email address to activate your account.</p>
    @if($verificationCode)<p>Your one-time code:</p><p style="font-size:28px;font-weight:bold;letter-spacing:6px">{{ $verificationCode }}</p><p>Enter it with your email address in Kody. The code expires ten minutes after it was requested; five incorrect attempts disable it.</p>@endif
    <p><a href="{{ $verificationUrl }}">Verify email address</a></p>
    <p>This link expires after {{ config('account.verification.expires_minutes') }} minutes and can be used once.</p>
    <p>If the link doesn’t open, use this link token in the verification page’s alternate method:</p>
    <p>{{ $verificationToken }}</p>
    <p>If you didn’t create a Kody account, you can ignore this message.</p>
</body></html>
