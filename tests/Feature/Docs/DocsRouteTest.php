<?php

namespace Tests\Feature\Docs;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class DocsRouteTest extends TestCase
{
    private string $docsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->docsPath = storage_path('framework/testing/docs/mortelos/docs');

        $files = app(Filesystem::class);
        $files->deleteDirectory(storage_path('framework/testing/docs'));
        $files->ensureDirectoryExists($this->docsPath);

        $files->put($this->docsPath.'/versions.json', json_encode([
            'current' => '0',
            'versions' => [
                ['name' => '0', 'label' => '0', 'branch' => '0', 'status' => 'current'],
            ],
        ], JSON_PRETTY_PRINT));

        $files->put($this->docsPath.'/navigation.json', json_encode([
            'version' => '0',
            'sections' => [
                [
                    'title' => 'Getting started',
                    'items' => [
                        ['title' => 'Overview', 'slug' => 'index'],
                        ['title' => 'Installation', 'slug' => 'installation'],
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT));

        $files->put($this->docsPath.'/index.md', <<<'MD'
---
title: "MortelOS Documentation"
slug: "index"
version: "0"
description: "Start building governed Laravel portals with MortelOS."
order: 0
canonical_path: "/docs/0/index"
---

# MortelOS Documentation

## Build with MortelOS

Use a capability-first build flow.
MD);

        $files->put($this->docsPath.'/installation.md', <<<'MD'
---
title: "Installation"
slug: "installation"
version: "0"
description: "Install a MortelOS Starter host app."
order: 10
canonical_path: "/docs/0/installation"
---

# Installation

## Requirements

Install the starter app.
MD);

        config([
            'docs.current_version' => '0',
            'docs.content_path' => $this->docsPath,
            'docs.site_url' => 'https://mortelos.com',
        ]);
    }

    public function test_docs_route_renders_markdown_from_configured_content_path(): void
    {
        $this->get('/docs/0/index')
            ->assertOk()
            ->assertSee('MortelOS Documentation')
            ->assertSee('Build with MortelOS')
            ->assertSee('Getting started');
    }

    public function test_docs_version_index_defaults_to_index_slug(): void
    {
        $this->get('/docs/0')
            ->assertOk()
            ->assertSee('MortelOS Documentation');
    }

    public function test_unknown_docs_slug_returns_not_found(): void
    {
        $this->get('/docs/0/not-found')
            ->assertNotFound();
    }

    public function test_unknown_docs_version_returns_not_found(): void
    {
        $this->get('/docs/1/index')
            ->assertNotFound();
    }

    public function test_docs_search_finds_seeded_content(): void
    {
        $this->get('/docs/0/index?q=starter')
            ->assertOk()
            ->assertSee('Installation');
    }

    public function test_docs_page_includes_seo_metadata(): void
    {
        $this->get('/docs/0/index')
            ->assertOk()
            ->assertSee('<title>MortelOS Documentation - MortelOS Docs</title>', false)
            ->assertSee('<link rel="canonical" href="https://mortelos.com/docs/0/index">', false)
            ->assertSee('<meta property="og:url" content="https://mortelos.com/docs/0/index">', false);
    }

    public function test_sitemap_lists_docs_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('https://mortelos.com/docs/0/index')
            ->assertSee('https://mortelos.com/docs/0/installation');
    }

    public function test_docs_subdomain_redirects_to_canonical_docs_path(): void
    {
        $this->get('http://docs.mortelos.nl/0/installation')
            ->assertRedirect('https://mortelos.com/docs/0/installation')
            ->assertStatus(301);
    }

    public function test_docs_validation_command_passes_for_seeded_content(): void
    {
        $this->artisan('docs:validate 0')
            ->expectsOutput('Validated 2 docs pages for version 0.')
            ->assertExitCode(0);
    }

    public function test_docs_index_command_writes_search_index_json(): void
    {
        $outputPath = storage_path('framework/testing/docs-index.json');

        $this->artisan('docs:index 0 --output='.$outputPath)
            ->expectsOutput('Docs search index written to '.$outputPath.'.')
            ->assertExitCode(0);

        $this->assertFileExists($outputPath);

        $index = json_decode(file_get_contents($outputPath), true);

        $this->assertIsArray($index);
        $this->assertSame('MortelOS Documentation', $index[0]['title']);
        $this->assertSame('index', $index[0]['slug']);
    }
}
