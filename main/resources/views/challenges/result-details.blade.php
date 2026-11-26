@if($submission->completed_at)
    @php($statistics = $summary['statistics'])
    @if($statistics['slowest_ms'] !== null || $statistics['peak_kib'] !== null)
        <section class="challenge-result-detail" aria-labelledby="performance-heading">
            <h3 id="performance-heading">Performance</h3>
            <dl class="challenge-result-stats" aria-label="Execution statistics">
                @if($statistics['slowest_ms'] !== null)
                    <div><dt>Slowest test (CPU)</dt><dd>{{ $statistics['slowest_ms'] }} ms</dd></div>
                @endif
                @if($statistics['peak_kib'] !== null)
                    <div><dt>Peak memory</dt><dd>{{ number_format($statistics['peak_kib']) }} KiB</dd></div>
                @endif
            </dl>
            <p class="field-hint">Largest recorded values across all test cases.</p>
        </section>
    @endif
    @if($summary['locations'] !== [])
        <div class="challenge-result-detail">
            <h3>Check these lines</h3>
            <ul>
                @foreach($summary['locations'] as $line => $type)
                    <li>{{ $type }}:
                        @if($canRetry)
                            <a href="{{ str_replace('#solution', '&line='.$line.'#source-code', $retryUrl) }}">Edit line {{ $line }}</a>
                        @else
                            line {{ $line }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    @foreach($summary['examples'] as $example)
        <details class="challenge-result-detail" @if($loop->first) open @endif>
            <summary>Sample {{ $example['number'] }}: Wrong output</summary>
            <dl class="challenge-output-comparison">
                <div><dt>Sample input</dt><dd><pre tabindex="0">{{ $example['input'] }}</pre></dd></div>
                <div><dt>Expected output</dt><dd><pre tabindex="0">{{ $example['expected'] }}</pre></dd></div>
                <div><dt>Your output @if($example['truncated'])(preview)@endif</dt>
                    <dd>@if($example['output'] === null)<p>Output was not captured.</p>@elseif($example['output'] === '')<p>No output.</p>@else<pre tabindex="0">{{ $example['output'] }}</pre>@endif</dd>
                </div>
            </dl>
        </details>
    @endforeach
@endif
