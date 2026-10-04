<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>{{ $receiptTitle }}</title></head><body>
<h1>{{ $receiptTitle }}</h1><p>Your verified Kody transaction has been recorded.</p>
@if(isset($details['kodebits']))<p>KodeBits: {{ $details['kodebits'] }} KB</p>@endif
@if(isset($details['amount_minor']))<p>Amount: PHP {{ \App\Support\ExactMoney::decimal($details['amount_minor']) }}</p>@endif
@if(isset($details['fee_minor']))<p>Fee: PHP {{ \App\Support\ExactMoney::decimal($details['fee_minor']) }}</p>@endif
<p>Receipt reference: {{ $receiptId }}</p><p>Open your Kody wallet or earnings page for the complete private history.</p>
<p>This transaction confirmation is not a tax invoice. Never reply with your password or payment credentials.</p>
</body></html>
