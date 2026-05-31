<?php

use App\Actions\Docs\BuildDocsSitemap;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/docs');
Route::redirect('/docs', '/docs/0');

Route::get('/sitemap.xml', fn (BuildDocsSitemap $buildDocsSitemap) => response(
    $buildDocsSitemap->execute((string) config('docs.current_version', '0')),
    200,
    ['Content-Type' => 'application/xml'],
))->name('sitemap');

Route::livewire('/docs/{version}/{slug?}', 'pages::docs.show')
    ->where([
        'version' => '[A-Za-z0-9._-]+',
        'slug' => '.*',
    ])
    ->name('docs.show');
