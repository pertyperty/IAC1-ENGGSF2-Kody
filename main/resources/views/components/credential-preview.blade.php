@props([
    'meta',
    'inlineUrl',
    'downloadUrl',
    'downloadLabel' => 'Download private document',
    'title' => 'Supporting credential document',
    'compact' => false,
])
@php
    $available = (bool) ($meta['available'] ?? false);
    $extension = strtoupper((string) ($meta['extension'] ?? ''));
    $size = $meta['size'] ?? null;
    $sizeLabel = $size === null ? null : ($size >= 1048576 ? number_format($size / 1048576, 1).' MB' : max(1, (int) round($size / 1024)).' KB');
    $kind = ($meta['extension'] ?? '') === 'pdf' ? 'pdf' : 'image';
@endphp
<div class="credential-card {{ $compact ? 'credential-card--compact' : '' }}">
    <div class="credential-card-head">
        <div>
            <strong>{{ $title }}</strong>
            <div class="credential-meta-row">
                @if($extension !== '')<span class="resource-type">{{ $extension }}</span>@endif
                @if($sizeLabel)<span class="field-hint">{{ $sizeLabel }}</span>@endif
                <span class="status-pill" data-status="{{ $available ? 'Active' : 'Suspended' }}">{{ $available ? 'Available' : 'Unavailable' }}</span>
            </div>
        </div>
        @if($available)
            <div class="credential-card-actions">
                <a class="button button-secondary button-small" href="{{ $inlineUrl }}" target="_blank" rel="noopener noreferrer"><x-action-icon name="eye"/> Open preview</a>
                <a class="button button-dark button-small" href="{{ $downloadUrl }}" data-navigation-download>{{ $downloadLabel }} <x-action-icon name="download"/></a>
            </div>
        @endif
    </div>
    @if($available && ($meta['previewable'] ?? false))
        <details class="credential-preview-details" data-credential-preview data-credential-preview-src="{{ $inlineUrl }}" data-credential-preview-kind="{{ $kind }}" data-credential-preview-title="{{ $title }}">
            <summary>Preview private document inline</summary>
            <div class="credential-preview-host" data-credential-preview-host>
                <p class="field-hint">Loading private document preview… <a href="{{ $inlineUrl }}" target="_blank" rel="noopener noreferrer">Open in a new tab</a>.</p>
            </div>
        </details>
    @elseif(!$available)
        <p class="notice">This private supporting document is unavailable on storage. Resolve the missing document before approving or granting a qualifications badge.</p>
    @endif
</div>
