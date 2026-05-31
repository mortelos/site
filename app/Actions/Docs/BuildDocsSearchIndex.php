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

        return collect($this->all($version))
            ->filter(fn (array $item): bool => str_contains(Str::lower($item['haystack']), $query))
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
     * @return list<array{title: string, slug: string, section: string, excerpt: string, haystack: string}>
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
     * @return array{title: string, slug: string, section: string, excerpt: string, haystack: string}|null
     */
    private function indexItem(string $contentRoot, string $section, array $item): ?array
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

        return [
            'title' => (string) ($frontMatter['title'] ?? $item['title'] ?? $slug),
            'slug' => $slug,
            'section' => $section,
            'excerpt' => Str::limit($plainText, 150),
            'haystack' => $haystack,
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
