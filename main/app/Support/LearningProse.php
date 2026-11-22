<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\MarkdownConverter;

class LearningProse
{
    private MarkdownConverter $markdown;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 12,
            'max_delimiters_per_line' => 1000,
            'renderer' => ['soft_break' => "<br>\n"],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addRenderer(FencedCode::class, new LearningCodeBlock, 10);
        $environment->addEventListener(DocumentParsedEvent::class, $this->removeLinkTargets(...));

        $this->markdown = new MarkdownConverter($environment);
    }

    public function render(string $source): HtmlString
    {
        return new HtmlString(trim($this->markdown->convert($source)->getContent()));
    }

    private function removeLinkTargets(DocumentParsedEvent $event): void
    {
        $nodes = iterator_to_array($event->getDocument()->iterator(), false);
        foreach ($nodes as $node) {
            if ($node instanceof Link || $node instanceof Image) {
                foreach ($node->children() as $child) {
                    $node->insertBefore($child);
                }
                $node->detach();
            }
        }
    }
}
