<?php

namespace App\Actions\Docs;

use Illuminate\Filesystem\Filesystem;
use XMLWriter;

class BuildDocsSitemap
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly ResolveDocsVersion $resolveDocsVersion,
        private readonly SyncDocsRepository $syncDocsRepository,
    ) {}

    public function execute(string $version): string
    {
        $version = $this->resolveDocsVersion->execute($version);
        $contentRoot = $this->syncDocsRepository->execute($version);
        $navigation = $this->readJsonFile($contentRoot.DIRECTORY_SEPARATOR.'navigation.json');
        $siteUrl = rtrim((string) config('docs.site_url', 'https://mortelos.nl'), '/');

        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($this->slugs($navigation) as $slug) {
            $writer->startElement('url');
            $writer->writeElement('loc', $siteUrl.'/docs/'.$version.'/'.$slug);
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
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
     * @param  array<string, mixed>  $navigation
     * @return list<string>
     */
    private function slugs(array $navigation): array
    {
        return collect($navigation['sections'] ?? [])
            ->flatMap(fn (array $section): array => collect($section['items'] ?? [])
                ->pluck('slug')
                ->filter()
                ->all())
            ->unique()
            ->values()
            ->all();
    }
}
