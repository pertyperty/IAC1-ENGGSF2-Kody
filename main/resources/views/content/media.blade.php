@if(count($revision->attachments ?? []))
<section class="lesson-resources"><h3>Lesson resources</h3><div class="resource-grid">@foreach($revision->attachments as $index => $asset)
    @php($resourceUrl = route('module-media.show', [$revision->module_id, $revision->id, $index] + (isset($course, $slot) ? ['course' => $course->id, 'slot' => $slot->id] : [])))
    <article class="resource-card"><span class="resource-type">{{ strtoupper($asset['extension']) }}</span><div><b>{{ $asset['name'] }}</b><span>{{ number_format($asset['size'] / 1024) }} KB</span></div><div class="resource-actions"><a class="button button-secondary button-small" href="{{ $resourceUrl }}">View</a><a class="button button-quiet button-small" href="{{ $resourceUrl }}{{ str_contains($resourceUrl, '?') ? '&' : '?' }}download=1">Download ↓</a></div></article>
@endforeach</div></section>
@endif
@if($revision->video_url)
    @php($embedUrl = app(\App\Services\Content\VideoEmbed::class)->url($revision->video_url))
    @if($embedUrl)<div class="lesson-video"><iframe src="{{ $embedUrl }}" title="{{ $revision->title }} lesson video" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen; picture-in-picture" allowfullscreen sandbox="allow-scripts allow-same-origin allow-presentation"></iframe></div>
    @else<p><a class="button button-secondary" href="{{ $revision->video_url }}" target="_blank" rel="noopener noreferrer">Open lesson video ↗</a></p>@endif
@endif
