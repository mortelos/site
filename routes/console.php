<?php

use App\Actions\Docs\BuildDocsSearchIndex;
use App\Actions\Docs\ValidateDocsContent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('docs:validate {version?}', function (ValidateDocsContent $validateDocsContent): int {
    $result = $validateDocsContent->execute($this->argument('version') ?: (string) config('docs.current_version', '0'));

    foreach ($result['errors'] as $error) {
        $this->error($error);
    }

    if ($result['errors'] !== []) {
        return 1;
    }

    $this->info("Validated {$result['pages']} docs pages for version {$result['version']}.");

    return 0;
})->purpose('Validate MortelOS docs content');

Artisan::command('docs:index {version?} {--output=}', function (BuildDocsSearchIndex $buildDocsSearchIndex): int {
    $path = $buildDocsSearchIndex->write(
        $this->argument('version') ?: (string) config('docs.current_version', '0'),
        $this->option('output') ?: null,
    );

    $this->info("Docs search index written to {$path}.");

    return 0;
})->purpose('Write the MortelOS docs search index JSON');
