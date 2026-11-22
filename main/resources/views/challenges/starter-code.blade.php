@if(filled($revision->starter_code ?? null))
    <details class="challenge-starter [contain:inline-size]"><summary>Starter code</summary>
        <pre class="max-w-full overflow-x-auto whitespace-pre"><code>{{ $revision->starter_code }}</code></pre>
    </details>
@endif
