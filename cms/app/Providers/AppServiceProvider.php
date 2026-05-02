<?php

namespace App\Providers;

use App\Models\Artigo;
use App\Models\Compromisso;
use App\Models\Evento;
use App\Models\GaleriaAlbum;
use App\Models\GaleriaFoto;
use App\Models\Homilia;
use App\Models\HorarioMissa;
use App\Models\Ministerio;
use App\Models\Paroco;
use App\Observers\ContentRebuildObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $observer = ContentRebuildObserver::class;

        Evento::observe($observer);
        Artigo::observe($observer);
        Homilia::observe($observer);
        Ministerio::observe($observer);
        Paroco::observe($observer);
        HorarioMissa::observe($observer);
        Compromisso::observe($observer);
        GaleriaAlbum::observe($observer);
        GaleriaFoto::observe($observer);
    }
}
