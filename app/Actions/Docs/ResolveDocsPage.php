<?php

namespace App\Actions\Docs;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

class ResolveDocsPage
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly ResolveDocsVersion $resolveDocsVersion,
        private readonly SyncDocsRepository $syncDocsRepository,
        private readonly RenderDocsMarkdown $renderDocsMarkdown,
    ) {}

    /**
     * @return array{
     *     version: string,
     *     slug: string,
     *     title: string,
     *     description: string,
     *     canonical_path: string,
     *     canonical_url: string,
     *     front_matter: array<string, mixed>,
     *     html: string,
     *     headings: list<array{id: string, level: int, text: string}>,
     *     navigation: array<string, mixed>,
     *     previous: array<string, string>|null,
     *     next: array<string, string>|null
     * }
     */
    public function execute(string $version, ?string $slug = null): array
    {
        $version = $this->resolveDocsVersion->execute($version);
        $contentRoot = $this->syncDocsRepository->execute($version);
        $slug = $this->normalizeSlug($slug);

        $path = $this->findMarkdownFile($contentRoot, $slug);
        abort_unless($path !== null, 404);

        [$frontMatter, $markdown] = $this->parseMarkdownFile($path);
        $markdown = $this->stripDocumentTitle($markdown);
        $rendered = $this->renderDocsMarkdown->execute($markdown);
        $navigation = $this->readJsonFile($contentRoot.DIRECTORY_SEPARATOR.'navigation.json');
        $flatNavigation = $this->flattenNavigation($navigation);
        $currentIndex = collect($flatNavigation)->search(fn (array $item): bool => $item['slug'] === $slug);
        $canonicalPath = (string) ($frontMatter['canonical_path'] ?? '/docs/'.$version.'/'.$slug);

        return [
            'version' => $version,
            'slug' => $slug,
            'title' => (string) ($frontMatter['title'] ?? $slug),
            'description' => (string) ($frontMatter['description'] ?? ''),
            'canonical_path' => $canonicalPath,
            'canonical_url' => rtrim((string) config('docs.site_url', 'https://mortelos.com'), '/').$canonicalPath,
            'front_matter' => $frontMatter,
            'html' => $rendered['html'],
            'headings' => $rendered['headings'],
            'navigation' => $navigation,
            'previous' => is_int($currentIndex) ? Arr::get($flatNavigation, $currentIndex - 1) : null,
            'next' => is_int($currentIndex) ? Arr::get($flatNavigation, $currentIndex + 1) : null,
        ];
    }

    private function normalizeSlug(?string $slug): string
    {
        $slug = trim($slug ?: 'index', '/');

        abort_if($slug === '' || str_contains($slug, '..'), 404);
        abort_unless((bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/', $slug), 404);

        return $slug;
    }

    private function findMarkdownFile(string $contentRoot, string $slug): ?string
    {
        $candidates = [
            $contentRoot.DIRECTORY_SEPARATOR.$slug.'.md',
            $contentRoot.DIRECTORY_SEPARATOR.$slug.DIRECTORY_SEPARATOR.'index.md',
        ];

        foreach ($candidates as $candidate) {
            if ($this->files->isFile($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function stripDocumentTitle(string $markdown): string
    {
        return preg_replace('/\A\s*#\s+.+\R{1,2}/u', '', $markdown, 1) ?? $markdown;
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

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path): array
    {
        abort_unless($this->files->isFile($path), 404);

        $decoded = json_decode($this->files->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $navigation
     * @return list<array{section: string, title: string, slug: string}>
     */
    private function flattenNavigation(array $navigation): array
    {
        return collect($navigation['sections'] ?? [])
            ->flatMap(fn (array $section): array => collect($section['items'] ?? [])
                ->map(fn (array $item): array => [
                    'section' => (string) ($section['title'] ?? ''),
                    'title' => (string) ($item['title'] ?? ''),
                    'slug' => (string) ($item['slug'] ?? ''),
                ])
                ->all())
            ->filter(fn (array $item): bool => $item['slug'] !== '')
            ->values()
            ->all();
    }
}
