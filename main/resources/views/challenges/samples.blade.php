<section class="challenge-samples" aria-labelledby="challenge-samples-heading">
    <h2 id="challenge-samples-heading">Public examples</h2>
    @forelse($samples as $sample)
        <details class="challenge-sample" @if($loop->first) open @endif>
            <summary>Sample {{ $loop->iteration }}</summary>
            <dl class="challenge-output-comparison">
                <div><dt>Input</dt><dd><pre tabindex="0" aria-label="Sample input">{{ $sample->input }}</pre></dd></div>
                <div><dt>Expected output</dt><dd><pre tabindex="0" aria-label="Expected output">{{ $sample->expected_output }}</pre></dd></div>
            </dl>
        </details>
    @empty
        <p class="field-hint">No public examples. Use the input and output formats above.</p>
    @endforelse
</section>
