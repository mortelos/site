<?php

return [
    'current_version' => env('DOCS_CURRENT_VERSION', '0'),

    // Docs version => branch in the docs repository. Versions without an entry
    // read the branch of the same name. Version 0 tracks main until a v1 ships
    // and version 0 gets frozen on its own branch.
    'version_branches' => [
        '0' => 'main',
    ],

    'site_url' => env('DOCS_SITE_URL', 'https://mortelos.nl'),

    'repository_url' => env('DOCS_REPOSITORY_URL', 'https://github.com/mortelos/docs.git'),

    'content_path' => env('DOCS_CONTENT_PATH'),

    'mirror_path' => storage_path('app/docs/repos/mortelos-docs.git'),

    'worktrees_path' => storage_path('app/docs/worktrees'),
];
