@can('viewAny', \App\Models\User::class)
<section class="staff-workspace" aria-labelledby="staff-workspace-heading">
    <div class="section-heading"><div><p class="overline">YOUR STAFF WORKSPACE</p><h2 id="staff-workspace-heading">Help good learning happen.</h2></div><p>Review, support and manage your community.</p></div>
    <div class="workspace-grid">
        <a href="{{ route('account-governance.index') }}"><span class="workspace-icon" aria-hidden="true">◎</span><strong>Community accounts</strong><span>Find accounts and review their history.</span></a>
        <a href="{{ route('content-moderation.index') }}"><span class="workspace-icon" aria-hidden="true">◇</span><strong>Content moderation</strong><span>Manage learner availability and staff withdrawals.</span></a>
        @can('viewAny', \App\Models\LearningModule::class)<a href="{{ route('module-reviews.index') }}"><span class="workspace-icon" aria-hidden="true">✦</span><strong>Module reviews</strong><span>Review new adventures and updated lessons.</span></a>@endcan
        @can('viewAny', \App\Models\LearningCourse::class)<a href="{{ route('course-reviews.index') }}"><span class="workspace-icon" aria-hidden="true">≡</span><strong>Course reviews</strong><span>Check learning paths and their pinned lessons.</span></a>@endcan
        @can('viewAny', \App\Models\CodingChallenge::class)<a href="{{ route('challenge-reviews.index') }}"><span class="workspace-icon" aria-hidden="true">{ }</span><strong>Challenge reviews</strong><span>Review coding quests and their test cases.</span></a>@endcan
        @can('viewAny', \App\Models\InstructorApplication::class)<a href="{{ route('instructor-reviews.index') }}"><span class="workspace-icon" aria-hidden="true">↗</span><strong>Instructor applications</strong><span>Review teaching credentials and decisions.</span></a>@endcan
        @can('viewAny', \App\Models\ContributorApplication::class)<a href="{{ route('contributor-reviews.index') }}"><span class="workspace-icon" aria-hidden="true">＋</span><strong>Contributor applications</strong><span>Review eligible community contributors.</span></a>@endcan
        @can('manage', \App\Models\WeeklyEvent::class)<a href="{{ route('weekly-studio.index') }}"><span class="workspace-icon" aria-hidden="true">▦</span><strong>Weekly calendar</strong><span>Prepare future weeks and publish results.</span></a>@endcan
        @can('viewAny', \App\Models\GamePreset::class)<a href="{{ route('game-presets.index') }}"><span class="workspace-icon" aria-hidden="true">⌘</span><strong>Game presets</strong><span>Shape reusable games and quiz templates.</span></a>@endcan
        @can('viewAny', \App\Models\FaqEntry::class)<a href="{{ route('faq-management.index') }}"><span class="workspace-icon" aria-hidden="true">?</span><strong>Help workshop</strong><span>Keep answers clear and easy to discover.</span></a>@endcan
        <a href="{{ route('creator-erasure.index') }}"><span class="workspace-icon" aria-hidden="true">◈</span><strong>Privacy inventories</strong><span>Review retained creator content and erasure requests.</span></a>
        @can('viewReports', \App\Models\User::class)
        <a href="{{ route('system-reports') }}"><span class="workspace-icon" aria-hidden="true">▤</span><strong>System reports</strong><span>Explore platform activity and accounting summaries.</span></a>
        <a href="{{ route('finance.index') }}"><span class="workspace-icon" aria-hidden="true">⇄</span><strong>Finance workspace</strong><span>Review reconciliation and financial histories.</span></a>
        @endcan
    </div>
</section>
@endcan
