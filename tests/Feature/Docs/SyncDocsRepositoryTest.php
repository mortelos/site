<?php

namespace Tests\Feature\Docs;

use App\Actions\Docs\SyncDocsRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class SyncDocsRepositoryTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = storage_path('framework/testing/docs-sync');

        $files = app(Filesystem::class);
        $files->deleteDirectory($this->workspace);
        $files->ensureDirectoryExists($this->workspace.'/source/mortelos/docs');
        $files->put($this->workspace.'/source/mortelos/docs/index.md', "# Index\n");

        $this->git($this->workspace.'/source', ['init', '--initial-branch=main']);
        $this->git($this->workspace.'/source', ['add', '.']);
        $this->git($this->workspace.'/source', ['commit', '-m', 'Add docs content']);

        config([
            'docs.content_path' => null,
            'docs.repository_url' => $this->workspace.'/source',
            'docs.mirror_path' => $this->workspace.'/mirror.git',
            'docs.worktrees_path' => $this->workspace.'/worktrees',
            'docs.version_branches' => ['0' => 'main'],
        ]);
    }

    public function test_version_resolves_to_its_mapped_branch(): void
    {
        $contentRoot = app(SyncDocsRepository::class)->execute('0');

        $this->assertFileExists($contentRoot.'/index.md');
    }

    public function test_version_without_mapping_reads_the_branch_of_the_same_name(): void
    {
        $this->git($this->workspace.'/source', ['branch', '1']);
        config(['docs.version_branches' => []]);

        $contentRoot = app(SyncDocsRepository::class)->execute('1');

        $this->assertFileExists($contentRoot.'/index.md');
    }

    public function test_missing_branch_is_reported_instead_of_silently_falling_back(): void
    {
        config(['docs.version_branches' => []]);

        $this->expectException(RuntimeException::class);

        app(SyncDocsRepository::class)->execute('9');
    }

    /**
     * @param  list<string>  $arguments
     */
    private function git(string $directory, array $arguments): void
    {
        Process::path($directory)
            ->run(array_merge([
                'git',
                '-c', 'user.email=tests@mortelos.nl',
                '-c', 'user.name=MortelOS Tests',
            ], $arguments))
            ->throw();
    }
}
