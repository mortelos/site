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
    ) {}

    /**
     * @return list<array{title: string, slug: string, section: string, excerpt: string}>
     */
    public function execute(string $version, string $query): array
    {
        $query = Str::lower(trim($query));

        if ($query === '') {
            return [];
        }

        $version = $this->resolveDocsVersion->execute($version);
        $contentRoot = $this->syncDocsRepository->execute($version);
        $navigation = $this->readJsonFile($contentRoot.DIRECTORY_SEPARATOR.'navigation.json');

        return collect($navigation['sections'] ?? [])
            ->flatMap(fn (array $section): array => collect($section['items'] ?? [])
                ->map(fn (array $item) => $this->searchItem($contentRoot, (string) ($section['title'] ?? ''), $item, $query))
                ->filter()
                ->all())
            ->values()
            ->take(8)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{title: string, slug: string, section: string, excerpt: string}|null
     */
    private function searchItem(string $contentRoot, string $section, array $item, string $query): ?array
    {
        $slug = (string) ($item['slug'] ?? '');
        $path = $contentRoot.DIRECTORY_SEPARATOR.$slug.'.md';

        if ($slug === '' || ! $this->files->isFile($path)) {
            return null;
        }

        [$frontMatter, $markdown] = $this->parseMarkdownFile($path);
        $plainText = trim(preg_replace('/\s+/', ' ', strip_tags($markdown)) ?? '');
        $haystack = Str::lower(implode(' ', [
            $frontMatter['title'] ?? '',
            $frontMatter['description'] ?? '',
            $section,
            $plainText,
        ]));

        if (! str_contains($haystack, $query)) {
            return null;
        }

        return [
            'title' => (string) ($frontMatter['title'] ?? $item['title'] ?? $slug),
            'slug' => $slug,
            'section' => $section,
            'excerpt' => Str::limit($plainText, 150),
        ];
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
