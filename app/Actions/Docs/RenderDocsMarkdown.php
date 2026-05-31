<?php

namespace App\Actions\Docs;

use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

class RenderDocsMarkdown
{
    /**
     * @return array{html: string, headings: list<array{id: string, level: int, text: string}>}
     */
    public function execute(string $markdown): array
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'apply_id_to_heading' => true,
                'fragment_prefix' => '',
                'id_prefix' => '',
                'insert' => 'none',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);

        return [
            'html' => (new MarkdownConverter($environment))->convert($markdown)->getContent(),
            'headings' => $this->headings($markdown),
        ];
    }

    /**
     * @return list<array{id: string, level: int, text: string}>
     */
    private function headings(string $markdown): array
    {
        preg_match_all('/^(#{2,3})\s+(.+)$/m', $markdown, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(fn (array $match): array => [
                'id' => Str::slug($match[2]),
                'level' => strlen($match[1]),
                'text' => trim($match[2]),
            ])
            ->values()
            ->all();
    }
}
