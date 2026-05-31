<?php

namespace App\Actions\Docs;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;

class SyncDocsRepository
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    public function execute(string $version): string
    {
        $configuredPath = config('docs.content_path');

        if (is_string($configuredPath) && $configuredPath !== '' && $this->files->isDirectory($configuredPath)) {
            return rtrim($configuredPath, DIRECTORY_SEPARATOR);
        }

        $repositoryUrl = config('docs.repository_url');
        $mirrorPath = config('docs.mirror_path');
        $worktreesPath = config('docs.worktrees_path');

        if (! is_string($repositoryUrl) || $repositoryUrl === '') {
            throw new RuntimeException('Docs repository URL is not configured.');
        }

        if (! is_string($mirrorPath) || $mirrorPath === '' || ! is_string($worktreesPath) || $worktreesPath === '') {
            throw new RuntimeException('Docs repository cache paths are not configured.');
        }

        $this->files->ensureDirectoryExists(dirname($mirrorPath));
        $this->files->ensureDirectoryExists($worktreesPath);

        if (! $this->files->isDirectory($mirrorPath)) {
            $this->run(['git', 'clone', '--mirror', $repositoryUrl, $mirrorPath]);
        } else {
            $this->run(['git', '--git-dir='.$mirrorPath, 'remote', 'update', '--prune']);
        }

        $commit = trim($this->run(['git', '--git-dir='.$mirrorPath, 'rev-parse', 'refs/heads/'.$version]));
        $worktreePath = $worktreesPath.DIRECTORY_SEPARATOR.$commit;

        if (! $this->files->isDirectory($worktreePath)) {
            $this->run(['git', 'clone', '--shared', '--no-checkout', $mirrorPath, $worktreePath]);
            $this->run(['git', 'checkout', '--detach', $commit], $worktreePath);
        }

        $contentRoot = $worktreePath.DIRECTORY_SEPARATOR.'mortelos'.DIRECTORY_SEPARATOR.'docs';

        if (! $this->files->isDirectory($contentRoot)) {
            throw new RuntimeException('Docs content root was not found in the synced repository.');
        }

        return $contentRoot;
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command, ?string $workingDirectory = null): string
    {
        $process = new Process($command, $workingDirectory);
        $process->setTimeout(90);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return $process->getOutput();
    }
}
