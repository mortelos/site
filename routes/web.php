<?php

use App\Actions\Docs\BuildDocsSitemap;
use Illuminate\Support\Facades\Route;

Route::domain('docs.mortelos.nl')
    ->get('/{path?}', fn (?string $path = null) => redirect()->away(
        'https://mortelos.com/docs'.($path ? '/'.ltrim($path, '/') : ''),
        301,
    ))
    ->where('path', '.*');

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
