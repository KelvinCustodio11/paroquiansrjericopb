<x-filament-panels::page>

    <div class="space-y-6">

        {{-- Cards de totais gerais ──────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @php
                $cards = [
                    ['label' => 'Hoje',        'valor' => $totais['hoje'],   'icon' => 'heroicon-o-sun',           'color' => 'text-yellow-500'],
                    ['label' => 'Esta semana', 'valor' => $totais['semana'], 'icon' => 'heroicon-o-calendar-days', 'color' => 'text-blue-500'],
                    ['label' => 'Este mês',    'valor' => $totais['mes'],    'icon' => 'heroicon-o-calendar',      'color' => 'text-indigo-500'],
                    ['label' => 'Este ano',    'valor' => $totais['ano'],    'icon' => 'heroicon-o-chart-bar',     'color' => 'text-purple-500'],
                    ['label' => 'Total geral', 'valor' => $totais['geral'],  'icon' => 'heroicon-o-globe-alt',     'color' => 'text-emerald-500'],
                ];
            @endphp

            @foreach($cards as $card)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 flex flex-col items-center gap-2">
                <x-filament::icon :icon="$card['icon']" class="h-7 w-7 {{ $card['color'] }}" />
                <span class="text-3xl font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ number_format($card['valor'], 0, ',', '.') }}
                </span>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 text-center">{{ $card['label'] }}</span>
            </div>
            @endforeach
        </div>

        {{-- Botão atualizar ─────────────────────────────────────────────────── --}}
        <div class="flex justify-end">
            <x-filament::button
                wire:click="atualizar"
                icon="heroicon-o-arrow-path"
                color="gray"
                size="sm"
            >
                Atualizar dados
            </x-filament::button>
        </div>

        {{-- Tabela por página ───────────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-950/5 dark:border-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5 text-gray-400" />
                    Visualizações por página
                </h3>
            </div>

            @if(count($paginas) === 0)
                <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    Nenhuma visualização registrada ainda. O script <code>js/page-views.js</code>
                    precisa estar ativo no site estático.
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Página</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Hoje</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Semana</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Mês</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Ano</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                        @foreach($paginas as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $row['titulo'] ?: $row['pagina'] }}
                                </div>
                                <div class="text-xs text-gray-400 font-mono">{{ $row['pagina'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($row['hoje'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($row['semana'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($row['mes'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($row['ano'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold text-gray-900 dark:text-white">
                                {{ number_format($row['geral'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>

</x-filament-panels::page>
