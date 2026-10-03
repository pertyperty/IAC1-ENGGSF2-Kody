<!DOCTYPE html>
<html lang="en"><body>
    <h1>Verify your Kody email</h1>
    <p>Confirm your email address to activate your account.</p>
    <p><a href="{{ $verificationUrl }}">Verify email address</a></p>
    <p>This link expires after {{ config('account.verification.expires_minutes') }} minutes and can be used once.</p>
    <p>If the link doesn’t open, copy this verification code into Kody’s verification page:</p>
    <p>{{ $verificationToken }}</p>
    <p>If you didn’t create a Kody account, you can ignore this message.</p>
</body></html>
