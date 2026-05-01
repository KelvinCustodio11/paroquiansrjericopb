<x-filament-panels::page>

    <div class="space-y-6">

        {{-- Descrição --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 mt-1">
                    <x-filament::icon
                        icon="heroicon-o-information-circle"
                        class="h-6 w-6 text-primary-500"
                    />
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Como funciona
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        O botão <strong>"Exportar e Publicar Agora"</strong> executa dois passos:
                    </p>
                    <ol class="mt-2 list-decimal list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li><strong>Exportar</strong> — lê todos os registros do banco (eventos, artigos, homilias, horários, ministérios, pároco) e salva em <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 rounded px-1">data/*.json</code>.</li>
                        <li><strong>Build</strong> — roda <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 rounded px-1">node scripts/build-content.js</code>, que injeta o conteúdo nas páginas <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 rounded px-1">index.html</code>, <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 rounded px-1">eventos.html</code> e gera as páginas de detalhe.</li>
                    </ol>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Após publicar, faça o deploy dos arquivos HTML para o servidor de hospedagem.
                    </p>
                </div>
            </div>
        </div>

        {{-- Resultado da última execução --}}
        @if ($status)
            <div class="fi-section rounded-xl shadow-sm ring-1 p-6 {{ $status === 'success' ? 'bg-success-50 ring-success-200 dark:bg-success-950 dark:ring-success-800' : 'bg-danger-50 ring-danger-200 dark:bg-danger-950 dark:ring-danger-800' }}">
                <div class="flex items-center gap-2 mb-3">
                    <x-filament::icon
                        icon="{{ $status === 'success' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle' }}"
                        class="h-5 w-5 {{ $status === 'success' ? 'text-success-500' : 'text-danger-500' }}"
                    />
                    <h3 class="text-sm font-semibold {{ $status === 'success' ? 'text-success-700 dark:text-success-300' : 'text-danger-700 dark:text-danger-300' }}">
                        {{ $status === 'success' ? 'Build concluído com sucesso' : 'Erro durante o build' }}
                    </h3>
                </div>
                @if ($output)
                    <pre class="mt-2 text-xs font-mono bg-gray-950 text-green-300 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap">{{ $output }}</pre>
                @endif
            </div>
        @else
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Nenhuma publicação executada nesta sessão. Use o botão acima para publicar.
            </div>
        @endif

    </div>

</x-filament-panels::page>
