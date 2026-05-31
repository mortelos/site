<?php

namespace App\Actions\Docs;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ValidateDocsContent
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly ResolveDocsVersion $resolveDocsVersion,
        private readonly SyncDocsRepository $syncDocsRepository,
        private readonly RenderDocsMarkdown $renderDocsMarkdown,
    ) {}

    /**
     * @return array{version: string, content_root: string, pages: int, errors: list<string>}
     */
    public function execute(string $version): array
    {
        $version = $this->resolveDocsVersion->execute($version);
        $contentRoot = $this->syncDocsRepository->execute($version);
        $errors = [];

        $navigation = $this->readJsonFile($contentRoot.DIRECTORY_SEPARATOR.'navigation.json', 'navigation.json', $errors);
        $this->readJsonFile($contentRoot.DIRECTORY_SEPARATOR.'versions.json', 'versions.json', $errors);

        $navigationItems = $this->navigationItems($navigation);
        $knownSlugs = $this->knownMarkdownSlugs($contentRoot);
        $seenSlugs = [];

        foreach ($navigationItems as $item) {
            $slug = $item['slug'];

            if ($slug === '') {
                $errors[] = 'Navigation item is missing a slug.';

                continue;
            }

            if (isset($seenSlugs[$slug])) {
                $errors[] = "Navigation contains duplicate slug [{$slug}].";
            }

            $seenSlugs[$slug] = true;

            if (($item['title'] ?? '') === '') {
                $errors[] = "Navigation item [{$slug}] is missing a title.";
            }

            $path = $this->findMarkdownFile($contentRoot, $slug);

            if ($path === null) {
                $errors[] = "Navigation target [{$slug}] does not have a Markdown file.";

                continue;
            }

            $this->validateMarkdownFile($path, $slug, $version, $knownSlugs, $errors);
        }

        return [
            'version' => $version,
            'content_root' => $contentRoot,
            'pages' => count($navigationItems),
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path, string $label, array &$errors): array
    {
        if (! $this->files->isFile($path)) {
            $errors[] = "{$label} is missing.";

            return [];
        }

        $decoded = json_decode($this->files->get($path), true);

        if (! is_array($decoded)) {
            $errors[] = "{$label} is not valid JSON.";

            return [];
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $navigation
     * @return list<array{title: string, slug: string}>
     */
    private function navigationItems(array $navigation): array
    {
        return collect($navigation['sections'] ?? [])
            ->flatMap(fn (array $section): array => collect($section['items'] ?? [])
                ->map(fn (array $item): array => [
                    'title' => (string) ($item['title'] ?? ''),
                    'slug' => (string) ($item['slug'] ?? ''),
                ])
                ->all())
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function knownMarkdownSlugs(string $contentRoot): array
    {
        return collect($this->files->allFiles($contentRoot))
            ->filter(fn ($file): bool => $file->getExtension() === 'md')
            ->map(function ($file) use ($contentRoot): string {
                $relativePath = Str::of($file->getPathname())
                    ->after($contentRoot.DIRECTORY_SEPARATOR)
                    ->replace('\\', '/')
                    ->toString();

                return Str::of($relativePath)
                    ->beforeLast('.md')
                    ->replaceEnd('/index', '')
                    ->toString();
            })
            ->values()
            ->all();
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

    /**
     * @param  list<string>  $knownSlugs
     * @param  list<string>  $errors
     */
    private function validateMarkdownFile(string $path, string $slug, string $version, array $knownSlugs, array &$errors): void
    {
        [$frontMatter, $markdown, $hasFrontMatter] = $this->parseMarkdownFile($path, $errors);

        if (! $hasFrontMatter) {
            $errors[] = "Page [{$slug}] is missing front matter.";

            return;
        }

        foreach (['title', 'slug', 'version', 'description', 'order'] as $requiredKey) {
            if (! array_key_exists($requiredKey, $frontMatter) || $frontMatter[$requiredKey] === '') {
                $errors[] = "Page [{$slug}] is missing front matter key [{$requiredKey}].";
            }
        }

        if (($frontMatter['slug'] ?? $slug) !== $slug) {
            $errors[] = "Page [{$slug}] front matter slug does not match its navigation slug.";
        }

        if ((string) ($frontMatter['version'] ?? $version) !== $version) {
            $errors[] = "Page [{$slug}] front matter version does not match [{$version}].";
        }

        if (isset($frontMatter['order']) && ! is_numeric($frontMatter['order'])) {
            $errors[] = "Page [{$slug}] front matter order must be numeric.";
        }

        $this->validateHeadingIds($slug, $markdown, $errors);
        $this->validateInternalLinks($slug, $markdown, $version, $knownSlugs, $errors);
    }

    /**
     * @param  list<string>  $errors
     * @return array{0: array<string, mixed>, 1: string, 2: bool}
     */
    private function parseMarkdownFile(string $path, array &$errors): array
    {
        $contents = $this->files->get($path);

        if (! preg_match('/\A---\R(?P<frontMatter>.*?)\R---\R(?P<markdown>.*)\z/s', $contents, $matches)) {
            return [[], $contents, false];
        }

        try {
            $frontMatter = Yaml::parse($matches['frontMatter']) ?: [];
        } catch (ParseException $exception) {
            $errors[] = basename($path).' has invalid YAML front matter: '.$exception->getMessage();

            return [[], $matches['markdown'], true];
        }

        return [is_array($frontMatter) ? $frontMatter : [], $matches['markdown'], true];
    }

    /**
     * @param  list<string>  $errors
     */
    private function validateHeadingIds(string $slug, string $markdown, array &$errors): void
    {
        $ids = collect($this->renderDocsMarkdown->execute($markdown)['headings'])
            ->pluck('id')
            ->filter()
            ->all();

        foreach (array_count_values($ids) as $id => $count) {
            if ($count > 1) {
                $errors[] = "Page [{$slug}] contains duplicate heading id [{$id}].";
            }
        }
    }

    /**
     * @param  list<string>  $knownSlugs
     * @param  list<string>  $errors
     */
    private function validateInternalLinks(string $sourceSlug, string $markdown, string $version, array $knownSlugs, array &$errors): void
    {
        preg_match_all('/\[[^\]]+\]\((?P<link>[^)\s]+)(?:\s+"[^"]*")?\)/', $markdown, $matches);

        foreach ($matches['link'] ?? [] as $link) {
            $targetSlug = $this->internalLinkTarget($link, $version);

            if ($targetSlug === null) {
                continue;
            }

            if (! in_array($targetSlug, $knownSlugs, true)) {
                $errors[] = "Page [{$sourceSlug}] links to missing docs page [{$targetSlug}].";
            }
        }
    }

    private function internalLinkTarget(string $link, string $version): ?string
    {
        $link = trim($link, '<>');

        if ($link === '' || str_starts_with($link, '#') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $link)) {
            return null;
        }

        $path = parse_url($link, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $docsPrefix = '/docs/'.$version;

        if ($path === $docsPrefix || $path === $docsPrefix.'/') {
            return 'index';
        }

        if (str_starts_with($path, $docsPrefix.'/')) {
            $path = Str::after($path, $docsPrefix.'/');
        }

        if (str_starts_with($path, './')) {
            $path = Str::after($path, './');
        }

        $path = trim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return Str::of($path)->beforeLast('.md')->toString();
    }
}
