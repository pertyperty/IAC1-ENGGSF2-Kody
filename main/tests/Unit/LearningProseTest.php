<?php

use App\Support\LearningProse;

test('learning prose renders headings lists emphasis and labeled code', function () {
    $source = <<<'MARKDOWN'
## Compare values

Start with **two** values and an *ordered* plan.

1. Read $left.
2. Compare with $right using `code`.

~~~php
if ($left < $right) {
    echo "smaller";
}
~~~
MARKDOWN;

    $html = (new LearningProse)->render($source)->toHtml();

    expect($html)->toContain('<h2>Compare values</h2>', '<strong>two</strong>', '<em>ordered</em>',
        '<ol>', '<li>Read $left.</li>', '<code>code</code>', '<figcaption>php</figcaption>',
        '<pre><code class="language-php">', '$left &lt; $right', "\n    echo &quot;smaller&quot;;\n");
});

test('learning prose preserves plain text paragraphs and line breaks', function () {
    $html = (new LearningProse)->render("Read two numbers.\r\nPrint their sum.\r\n\r\nUse 1 < 2 & 3 > 2.")->toHtml();

    expect($html)->toBe("<p>Read two numbers.<br>\nPrint their sum.</p>\n<p>Use 1 &lt; 2 &amp; 3 &gt; 2.</p>");
});

test('learning prose escapes raw tags and never renders author-provided URLs or embeds', function () {
    $source = <<<'MARKDOWN'
<script>alert(1)</script>

Use <svg onload=alert(2)> inside prose.

[normal](https://example.test/track) [script](javascript:alert%281%29) [data](data:text/html;base64,YQ==)
![external image](https://example.test/image.png)
<https://example.test/track>

~~~php&quot;onload=alert(3)
<iframe src="https://example.test"></iframe>
~~~
MARKDOWN;

    $html = (new LearningProse)->render($source)->toHtml();

    expect($html)->toContain('&lt;script&gt;', '&lt;svg', 'normal', 'script', 'data', 'external image', '&lt;iframe')
        ->not->toContain('<script', '<svg', '<iframe', '<a ', '<img', '<iframe src=', '<figcaption>', 'class="language-');
});
