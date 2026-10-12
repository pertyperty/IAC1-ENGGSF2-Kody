<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>{{ $attachment['name'] }}</title>
<style>
body { margin: 0; background: #e9edf3; color: #192d47; font: 14px/1.5 Arial, sans-serif; }
h1 { font-size: 16px; padding: 12px 16px 0; margin: 0; overflow-wrap: anywhere; }
[role=status] { padding: 8px 16px; margin: 0; font-size: 12px; }
[data-office-pages] { overflow: auto; padding: 12px; }
[data-office-pages] section:has(>img) { max-width: 1200px; margin: 0 auto 24px; }
[data-office-pages] h2 { font-size: 13px; margin: 0 0 8px; }
[data-office-pages] img { display: block; width: 100%; height: auto; background: white; }
.docx-wrapper { padding: 0 !important; background: transparent !important; }
.docx-wrapper>section.docx { box-shadow: none !important; margin-bottom: 16px !important; }
@media(max-width:700px) { .docx-wrapper>section.docx { width: 100% !important; min-width: 0 !important; padding: 24px !important; box-sizing: border-box; } }
</style></head><body data-office-type="{{ $attachment['extension'] }}"><h1>{{ $attachment['name'] }}</h1><p data-office-status role="status">Rendering your document…</p><main data-office-pages aria-busy="true"></main>
<script type="application/octet-stream" data-office-source>{{ $documentData }}</script>
<script nonce="{{ $nonce }}" src="{{ $bundleUrl }}"></script>
</body></html>
