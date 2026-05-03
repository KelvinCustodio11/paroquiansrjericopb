<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PageView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PageViewController extends Controller
{
    /**
     * Registra uma visualização de página proveniente do site estático.
     *
     * POST /api/page-view
     * Body JSON: { "pagina": "home", "titulo": "Página Inicial" }
     *
     * Deduplica por ip_hash + pagina dentro de uma janela de 4 horas,
     * evitando contagens infladas por recarregamentos.
     */
    public function store(Request $request): JsonResponse
    {
        // Rate limit: 60 requisições por IP a cada minuto
        $limiterKey = 'page-view:' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, maxAttempts: 60)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }
        RateLimiter::hit($limiterKey, decaySeconds: 60);

        $validated = $request->validate([
            'pagina' => ['required', 'string', 'max:150', 'regex:/^[a-zA-Z0-9_\-:\/\.]+$/'],
            'titulo' => ['nullable', 'string', 'max:200'],
        ]);

        $ipHash = hash('sha256', $request->ip() . config('app.key'));
        $pagina = $validated['pagina'];

        // Deduplicação: mesma sessão (ip_hash + pagina) nas últimas 4 horas
        $jaContou = PageView::where('ip_hash', $ipHash)
            ->where('pagina', $pagina)
            ->where('viewed_at', '>=', now()->subHours(4))
            ->exists();

        if (! $jaContou) {
            PageView::create([
                'pagina'    => $pagina,
                'titulo'    => $validated['titulo'] ?? null,
                'ip_hash'   => $ipHash,
                'viewed_at' => now(),
            ]);
        }

        return response()->json(['ok' => true], 200);
    }
}
