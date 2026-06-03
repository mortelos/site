<?php

namespace App\Actions\Docs;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class BuildDocsSearchIndex
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly ResolveDocsVersion $resolveDocsVersion,
        private readonly SyncDocsRepository $syncDocsRepository,
        private readonly RenderDocsMarkdown $renderDocsMarkdown,
    ) {}

    /**
     * @return list<array{title: string, slug: string, section: string, excerpt: string}>
     */
    public function execute(string $version, string $query): array
    {
        $tokens = $this->searchTokens($query);

        if ($tokens === []) {
            return [];
        }

        return collect($this->all($version))
            ->map(fn (array $item): array => [
                ...$item,
                'score' => $this->score($item, $tokens),
            ])
            ->filter(fn (array $item): bool => $item['score'] > 0)
            ->sortByDesc('score')
            ->map(fn (array $item): array => [
                'title' => $item['title'],
                'slug' => $item['slug'],
                'section' => $item['section'],
                'excerpt' => $item['excerpt'],
            ])
            ->values()
            ->take(8)
            ->all();
    }

    /**
     * @return list<array{title: string, slug: string, section: string, excerpt: string, haystack: string, title_haystack: string, slug_haystack: string, section_haystack: string}>
     */
    public function all(string $version): array
    {
        $version = $this->resolveDocsVersion->execute($version);
        $contentRoot = $this->syncDocsRepository->execute($version);
        $navigation = $this->readJsonFile($contentRoot.DIRECTORY_SEPARATOR.'navigation.json');

        return collect($navigation['sections'] ?? [])
            ->flatMap(fn (array $section): array => collect($section['items'] ?? [])
                ->map(fn (array $item) => $this->indexItem($contentRoot, (string) ($section['title'] ?? ''), $item))
                ->filter()
                ->all())
            ->values()
            ->all();
    }

    public function write(string $version, ?string $path = null): string
    {
        $version = $this->resolveDocsVersion->execute($version);
        $path ??= storage_path('app/docs/search/'.$version.'.json');

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, json_encode($this->all($version), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{title: string, slug: string, section: string, excerpt: string, haystack: string, title_haystack: string, slug_haystack: string, section_haystack: string}|null
     */
    private function indexItem(string $contentRoot, string $section, array $item): ?array
    {
        $slug = (string) ($item['slug'] ?? '');
        $path = $contentRoot.DIRECTORY_SEPARATOR.$slug.'.md';

        if ($slug === '' || ! $this->files->isFile($path)) {
            return null;
        }

        [$frontMatter, $markdown] = $this->parseMarkdownFile($path);
        $plainText = $this->plainText($this->stripDocumentTitle($markdown));
        $title = (string) ($frontMatter['title'] ?? $item['title'] ?? $slug);
        $description = (string) ($frontMatter['description'] ?? '');
        $haystack = $this->searchableText(implode(' ', [
            $title,
            $description,
            $section,
            $slug,
            $plainText,
        ]));

        return [
            'title' => $title,
            'slug' => $slug,
            'section' => $section,
            'excerpt' => Str::limit($plainText, 150),
            'haystack' => $haystack,
            'title_haystack' => $this->searchableText($title),
            'slug_haystack' => $this->searchableText($slug),
            'section_haystack' => $this->searchableText($section),
        ];
    }

    private function plainText(string $markdown): string
    {
        $html = $this->renderDocsMarkdown->execute($markdown)['html'];
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private function stripDocumentTitle(string $markdown): string
    {
        return preg_replace('/\A\s*#\s+.+\R{1,2}/u', '', $markdown, 1) ?? $markdown;
    }

    private function searchableText(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * @return list<string>
     */
    private function searchTokens(string $query): array
    {
        return collect(explode(' ', $this->searchableText($query)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array{haystack: string, title_haystack: string, slug_haystack: string, section_haystack: string}  $item
     * @param  list<string>  $tokens
     */
    private function score(array $item, array $tokens): int
    {
        $score = 0;

        foreach ($tokens as $token) {
            if (! str_contains($item['haystack'], $token)) {
                return 0;
            }

            $score += substr_count($item['haystack'], $token);

            if (str_contains($item['title_haystack'], $token)) {
                $score += 8;
            }

            if (str_contains($item['slug_haystack'], $token)) {
                $score += 5;
            }

            if (str_contains($item['section_haystack'], $token)) {
                $score += 3;
            }
        }

        return $score;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path): array
    {
        $decoded = json_decode($this->files->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function parseMarkdownFile(string $path): array
    {
        $contents = $this->files->get($path);

        if (! preg_match('/\A---\R(?P<frontMatter>.*?)\R---\R(?P<markdown>.*)\z/s', $contents, $matches)) {
            return [[], $contents];
        }

        $frontMatter = Yaml::parse($matches['frontMatter']) ?: [];

        return [is_array($frontMatter) ? $frontMatter : [], $matches['markdown']];
    }
}
