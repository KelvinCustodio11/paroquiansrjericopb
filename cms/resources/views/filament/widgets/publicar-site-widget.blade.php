<x-filament-widgets::widget>
    <div class="fi-wi-stats-overview-stat relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900">
                    <x-filament::icon
                        icon="heroicon-o-rocket-launch"
                        class="h-5 w-5 text-primary-600 dark:text-primary-400"
                    />
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        Publicar Site
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Exporte os dados e atualize as páginas HTML estaticamente
                    </p>
                </div>
            </div>
            <a
                href="{{ \App\Filament\Pages\PublicarSite::getUrl() }}"
                class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 inline-grid gap-1.5 px-4 py-2 text-sm rounded-lg fi-color-primary fi-btn-color-primary bg-primary-600 text-white shadow-sm hover:bg-primary-500 focus-visible:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400"
            >
                <x-filament::icon
                    icon="heroicon-m-arrow-up-on-square"
                    class="h-4 w-4"
                />
                Ir para Publicação
            </a>
        </div>
    </div>
</x-filament-widgets::widget>
