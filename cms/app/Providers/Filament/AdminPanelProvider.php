<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\View\PanelsRenderHook;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Tables\View\TablesRenderHook;
use Filament\Widgets;
use App\Filament\Widgets\PublicarSiteWidget;
use App\Filament\Widgets\RadioTabs;
use App\Filament\Widgets\VisualizacoesWidget;
use App\Filament\Pages\Auth\Login;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                PublicarSiteWidget::class,
                VisualizacoesWidget::class,
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"><style>.fi-wi-table .fi-ta-header-toolbar{flex-wrap:wrap;row-gap:.5rem;}.fi-wi-table .fi-ta-header-toolbar>div:first-child{flex-wrap:wrap;row-gap:.5rem;flex-shrink:1;}.fi-wi-table .fi-ta-header-toolbar>.ms-auto{flex-wrap:wrap;flex-shrink:1;row-gap:.5rem;min-width:min-content;}</style>',
            )
            ->renderHook(
                TablesRenderHook::TOOLBAR_REORDER_TRIGGER_AFTER,
                fn (): string => Blade::render(<<<'BLADE'
                    <x-filament::tabs.item
                        :active="false"
                        alpine-active="$wire.activeTab === 'radios'"
                        x-on:click="$wire.call('setTab', 'radios')"
                        icon="heroicon-o-radio"
                    >Rádios</x-filament::tabs.item>

                    <x-filament::tabs.item
                        :active="false"
                        alpine-active="$wire.activeTab === 'busca-externa'"
                        x-on:click="$wire.call('setTab', 'busca-externa')"
                        icon="heroicon-o-magnifying-glass"
                    >Busca Externa</x-filament::tabs.item>
                BLADE),
                scopes: RadioTabs::class,
            )
            ->renderHook(
                TablesRenderHook::TOOLBAR_SEARCH_AFTER,
                fn (): string => Blade::render(<<<'BLADE'
                    <button
                        type="button"
                        class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 inline-grid gap-1.5 px-4 py-2 text-sm rounded-lg fi-color-primary fi-btn-color-primary bg-custom-600 text-white shadow-sm hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400"
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        wire:click="mountAction('nova-radio')"
                        x-show="$wire.activeTab === 'radios'"
                    >Nova Rádio</button>
                    <button
                        type="button"
                        class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 inline-grid gap-1.5 px-4 py-2 text-sm rounded-lg fi-color-primary fi-btn-color-primary bg-custom-600 text-white shadow-sm hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400"
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        wire:click="mountAction('nova-regra')"
                        x-show="$wire.activeTab === 'busca-externa'"
                    >Nova Regra</button>
                BLADE),
                scopes: RadioTabs::class,
            );
    }
}
