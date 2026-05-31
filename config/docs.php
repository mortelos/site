<?php

return [
    'current_version' => env('DOCS_CURRENT_VERSION', '0'),

    'site_url' => env('DOCS_SITE_URL', 'https://mortelos.com'),

    'repository_url' => env('DOCS_REPOSITORY_URL', 'https://github.com/mortelos/docs.git'),

    'content_path' => env('DOCS_CONTENT_PATH'),

    'mirror_path' => storage_path('app/docs/repos/mortelos-docs.git'),

    'worktrees_path' => storage_path('app/docs/worktrees'),
];
