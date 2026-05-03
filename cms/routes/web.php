<?php

use App\Http\Controllers\PageViewController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Serve arquivos do site estático pelo mesmo servidor do CMS,
 * evitando problemas de CORS quando o Filament exibe previews de imagens.
 */
Route::get('/site-media/{path}', function (string $path) {
    $disk     = Storage::disk('site_static');
    $fullPath = $disk->path($path);
    $diskRoot = realpath($disk->path(''));
    $realPath = realpath($fullPath);

    abort_unless(
        $realPath !== false && str_starts_with($realPath, $diskRoot . DIRECTORY_SEPARATOR),
        403
    );
    abort_unless(file_exists($realPath), 404);

    return response()->file($realPath);
})->where('path', '.+');

/*
 * API pública: registra visualizações de páginas do site estático.
 * CORS habilitado via config/cors.php + HandleCors middleware.
 */
Route::post('/api/page-view', [PageViewController::class, 'store'])
    ->name('api.page-view');
