<?php

namespace App\Support;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class LearningCodeBlock implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof FencedCode) {
            throw new \InvalidArgumentException('Expected a fenced code block.');
        }
        $language = $node->getInfoWords()[0] ?? '';
        $language = preg_match('/\A[a-z0-9][a-z0-9_+#.-]{0,23}\z/i', $language) === 1 ? $language : '';
        $label = $language === '' ? '' : '<figcaption>'.e($language).'</figcaption>';
        $code = new HtmlElement('code', $language === '' ? [] : ['class' => 'language-'.$language], e($node->getLiteral()));

        return '<figure>'.$label.'<pre>'.$code.'</pre></figure>';
    }
}
