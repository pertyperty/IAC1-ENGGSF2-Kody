<h1>Your instructor application: {{ $decision }}</h1>
<p>Your Kody instructor application has been reviewed.</p>
@if($notes)<p>Reviewer feedback: {{ $notes }}</p>@endif
@if($decision === 'Approved')<p>Your Instructor role has been granted. Account access remains subject to your current account status.</p>@else<p>Your existing account access is unchanged by this application decision.</p>@endif
<p>Sign in to Kody to view your profile and application status.</p>
