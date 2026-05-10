<x-filament-widgets::widget>

    <div class="space-y-4">

        {{-- Cabeçalho com controles ─────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-gray-400" />
                    Visualizações do Site
                </h3>

                <div class="flex flex-wrap items-center gap-2">

                    {{-- Top N --}}
                    <select
                        wire:model.live="topN"
                        class="text-xs rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    >
                        <option value="0">Todas as páginas</option>
                        <option value="5">Top 5</option>
                        <option value="10">Top 10</option>
                        <option value="20">Top 20</option>
                    </select>

                    {{-- Toggle visitas / visitantes --}}
                    <button
                        wire:click="toggleUnicos"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors
                            {{ $somentUnicos
                                ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 dark:border-primary-600'
                                : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <x-filament::icon
                            icon="{{ $somentUnicos ? 'heroicon-o-user' : 'heroicon-o-users' }}"
                            class="h-4 w-4"
                        />
                        {{ $somentUnicos ? 'Visitantes únicos' : 'Total de visitas' }}
                    </button>

                    {{-- Atualizar --}}
                    <x-filament::button
                        wire:click="atualizar"
                        icon="heroicon-o-arrow-path"
                        color="gray"
                        size="sm"
                    >
                        Atualizar
                    </x-filament::button>

                </div>
            </div>
        </div>

        {{-- Cards de totais ─────────────────────────────────────────────────── --}}
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

        {{-- Gráfico — tendência últimos 30 dias ─────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white mb-4 flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-4 w-4 text-gray-400" />
                Tendência — últimos 30 dias
                <span class="ml-auto text-xs font-normal text-gray-400">
                    {{ $somentUnicos ? 'visitantes únicos por dia' : 'visitas por dia' }}
                </span>
            </h3>
            <div
                wire:ignore
                x-data="visuChart(@js(array_keys($chartData)), @js(array_values($chartData)))"
                x-on:chart-updated.window="rebuild($event.detail.data)"
                style="position:relative; height:160px;"
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Período personalizado ────────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex items-center gap-2 flex-shrink-0">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4 text-gray-400" />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        Período personalizado
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                    <input
                        type="date"
                        wire:model.live="periodoInicio"
                        max="{{ now()->format('Y-m-d') }}"
                        class="text-xs rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    >
                    <span class="text-xs text-gray-400">até</span>
                    <input
                        type="date"
                        wire:model.live="periodoFim"
                        max="{{ now()->format('Y-m-d') }}"
                        class="text-xs rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    >
                    <div class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 px-4 py-1.5">
                        <span class="text-xl font-bold tabular-nums text-gray-900 dark:text-white">
                            {{ number_format($totalPeriodo, 0, ',', '.') }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $somentUnicos ? 'visitantes' : 'visitas' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabela por página (ordenável) ───────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-950/5 dark:border-white/10 flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-table-cells" class="h-4 w-4 text-gray-400" />
                    Por página
                </h3>
                <span class="text-xs text-gray-400">
                    {{ count($paginas) }} de {{ count($paginasFull) }} páginas
                    &middot; {{ $somentUnicos ? 'visitantes únicos' : 'total de visitas' }}
                </span>
            </div>

            @if(count($paginasFull) === 0)
                <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Nenhuma visualização registrada ainda.
                </div>
            @else
            <div class="overflow-x-auto" wire:loading.class="opacity-60">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            @php
                                $colunas = [
                                    'titulo' => ['label' => 'Página', 'align' => 'left'],
                                    'hoje'   => ['label' => 'Hoje',   'align' => 'right'],
                                    'semana' => ['label' => 'Semana', 'align' => 'right'],
                                    'mes'    => ['label' => 'Mês',    'align' => 'right'],
                                    'ano'    => ['label' => 'Ano',    'align' => 'right'],
                                    'geral'  => ['label' => 'Total',  'align' => 'right'],
                                ];
                            @endphp

                            @foreach($colunas as $col => $meta)
                            <th
                                wire:click="ordenar('{{ $col }}')"
                                class="px-4 py-3 text-{{ $meta['align'] }} font-medium text-gray-600 dark:text-gray-300 cursor-pointer select-none whitespace-nowrap hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                <span class="inline-flex items-center {{ $meta['align'] === 'right' ? 'justify-end' : '' }} gap-1 w-full">
                                    {{ $meta['label'] }}
                                    @if($ordenarPor === $col)
                                        <x-filament::icon
                                            icon="{{ $ordenarDirecao === 'asc' ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down' }}"
                                            class="h-3 w-3 text-primary-500 flex-shrink-0"
                                        />
                                    @else
                                        <x-filament::icon
                                            icon="heroicon-m-chevron-up-down"
                                            class="h-3 w-3 text-gray-300 dark:text-gray-600 flex-shrink-0"
                                        />
                                    @endif
                                </span>
                            </th>
                            @endforeach
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

    {{-- Alpine.js: gráfico de tendência (Chart.js) ──────────────────────────── --}}
    <script>
        function visuChart(labels, values) {
            return {
                chart: null,

                init() {
                    const self = this;
                    const boot = function () { self.build(labels, values); };

                    if (window.Chart) { boot(); return; }

                    // Evita carregar Chart.js duas vezes
                    if (window._chartJsLoading) {
                        window._chartJsLoading.then(boot);
                        return;
                    }

                    window._chartJsLoading = new Promise(function (resolve) {
                        const s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js'\;
                        s.onload = function () { resolve(); boot(); };
                        document.head.appendChild(s);
                    });
                },

                rebuild(data) {
                    this.build(Object.keys(data), Object.values(data));
                },

                build(lbls, vals) {
                    if (this.chart) { this.chart.destroy(); this.chart = null; }

                    const fmt  = d => { const [, m, day] = String(d).split('-'); return `${day}/${m}`; };
                    const dark = document.documentElement.classList.contains('dark');
                    const grid = dark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
                    const tick = dark ? '#9ca3af' : '#6b7280';

                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'line',
                        data: {
                            labels: lbls.map(fmt),
                            datasets: [{
                                data: vals,
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245,158,11,0.08)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                borderWidth: 2,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { title: ctx => lbls[ctx[0].dataIndex] } },
                            },
                            scales: {
                                x: { ticks: { color: tick, maxRotation: 0 }, grid: { color: grid } },
                                y: { beginAtZero: true, ticks: { precision: 0, color: tick }, grid: { color: grid } },
                            },
                        },
                    });
                },
            };
        }
    </script>

</x-filament-widgets::widget>
