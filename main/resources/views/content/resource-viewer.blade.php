<dialog class="resource-viewer" id="resource-viewer-{{ $revision->id }}" data-resource-viewer aria-label="Lesson resource preview">
    <header class="resource-viewer-toolbar"><div><p class="overline">LESSON RESOURCE</p><h2 data-resource-title>Resource preview</h2></div><div class="resource-viewer-actions">
        <a class="button button-secondary button-small" data-resource-download>Download original ↓</a>
        <button class="button button-secondary button-small" type="button" data-resource-enlarge>⛶ Enlarge preview</button>
        <button class="button button-quiet button-small" type="button" data-resource-close aria-label="Close preview">× Close</button>
    </div></header>
    <p class="resource-viewer-status field-hint" data-resource-status role="status"></p>
    <iframe class="resource-viewer-frame" data-resource-frame title="Lesson resource preview" referrerpolicy="no-referrer"></iframe>
    <p class="resource-viewer-footer field-hint">Preview unavailable? <a class="quiet-link" data-resource-original>Open resource page ↗</a>. Download the original for full formatting.</p>
</dialog>
