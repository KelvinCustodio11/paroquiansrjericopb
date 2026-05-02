<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RebuildStaticSite;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer genérico que dispara o rebuild do site estático sempre que
 * um dos modelos de conteúdo for criado, atualizado ou excluído.
 *
 * Registrado em AppServiceProvider para: Evento, Artigo, Homilia,
 * Ministerio, Paroco, HorarioMissa, Compromisso.
 */
class ContentRebuildObserver
{
    public function saved(Model $model): void
    {
        RebuildStaticSite::dispatch();
    }

    public function deleted(Model $model): void
    {
        RebuildStaticSite::dispatch();
    }
}
