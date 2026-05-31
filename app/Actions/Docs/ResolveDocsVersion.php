<?php

namespace App\Actions\Docs;

class ResolveDocsVersion
{
    public function execute(string $version): string
    {
        $currentVersion = config('docs.current_version', '0');

        abort_unless($version === $currentVersion, 404);

        return $version;
    }
}
