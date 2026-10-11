<dialog class="resource-viewer" id="resource-viewer-{{ $revision->id }}" data-resource-viewer aria-label="Lesson resource preview">
    <header class="resource-viewer-toolbar"><div><p class="overline">LESSON RESOURCE</p><h2 data-resource-title>Resource preview</h2></div><div class="resource-viewer-actions">
        <a class="button button-secondary button-small" data-resource-download data-navigation-download><x-action-icon name="download"/> Download original</a>
        <button class="button button-secondary button-small" type="button" data-resource-enlarge><x-action-icon name="expand"/> Enlarge preview</button>
        <button class="button button-quiet button-small" type="button" data-resource-close aria-label="Close preview"><x-action-icon name="close"/> Close</button>
    </div></header>
    <p class="resource-viewer-status field-hint" data-resource-status role="status"></p>
    <iframe class="resource-viewer-frame" data-resource-frame title="Lesson resource preview" referrerpolicy="no-referrer"></iframe>
    <p class="resource-viewer-footer field-hint">Preview unavailable? <a class="quiet-link" data-resource-original>Open resource page <x-action-icon name="right"/></a>. Download the original for full formatting.</p>
</dialog>
