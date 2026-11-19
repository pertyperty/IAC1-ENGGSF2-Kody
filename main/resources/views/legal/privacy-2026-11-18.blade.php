@extends('layouts.learning')
@section('title', 'Privacy notice — Kody')
@section('content')
<article class="page-width legal-page">
    <h1>Privacy notice</h1><p class="field-hint">Updated 18 November 2026</p>
    <p>The Kody project team uses your information to run your account, deliver learning activities and keep the platform safe.</p>
    <h2>Information we use</h2>
    <p>We keep your username, email, password hash, terms acceptance, account security records, learning progress, submitted solutions and reactions. Names, photos and public creator details are optional. Teaching and contributor applications include the private documents you submit. Existing financial activity has its own transaction history.</p>
    <h2>What others can see</h2>
    <p>Your username, published content, chosen public creator details and eligible leaderboard results may be public. Your email, private names and qualification documents are not public profile fields. Authorized staff access information needed for account support, review, moderation and financial duties.</p>
    <h2>Your browser and providers</h2>
    <p>Session cookies keep you signed in. Local browser settings remember your theme, guides and guest practice. Guest practice does not prove saved account progress. Embedded media may contact its provider. Additional sign-in, email, code execution and payment integrations remain paused; their information sharing will be explained before activation.</p>
    <h2>Your choices</h2>
    <p>Use account settings to update your details, remove a photo or request deletion. For access, correction, privacy concerns or other rights requests, email <a href="mailto:kody.support@gmail.com">kody.support@gmail.com</a>. We may verify your identity before sharing or changing private records. Do not email passwords or API keys.</p>
    <h2>Protection and retention</h2>
    <p>Access controls, password hashing and private file access protect account information. We keep data needed to operate your account and handle security, disputes and retained learning or transaction records. Account deletion removes or anonymizes eligible information; some audit, shared-content and financial history remains. Backups are handled separately. We do not promise instant erasure or perfect security.</p>
    <h2>Contact</h2>
    <p>Kody project team, University of Baguio, General Luna Road, Baguio City 2600, Philippines. This is the project's correspondence address and does not imply university endorsement.</p>
    <p><a href="{{ route('legal.terms') }}">Terms and conditions</a></p>
</article>
@endsection
