<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            value: $wire.entangle('{{ $getStatePath() }}'),
            open: false,
            search: '',
            icons: {{ json_encode(\App\Filament\Forms\Components\IconPickerField::icons()) }},
            get filtered() {
                if (!this.search) return this.icons;
                const q = this.search.toLowerCase();
                return Object.fromEntries(
                    Object.entries(this.icons).filter(([k, v]) =>
                        k.includes(q) || v.toLowerCase().includes(q)
                    )
                );
            },
            select(icon) {
                this.value = icon;
                this.open = false;
                this.search = '';
            }
        }"
        class="relative"
    >
        {{-- Campo de texto + prévia + botão --}}
        <div class="flex items-center gap-2">

            {{-- Prévia do ícone selecionado --}}
            <div
                class="flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800"
                style="width:40px;height:40px;flex-shrink:0;"
                x-show="value"
            >
                <i class="fa-solid" :class="value" style="font-size:18px;color:var(--primary-600,#4f46e5)"></i>
            </div>

            {{-- Input de texto --}}
            <input
                type="text"
                x-model="value"
                placeholder="Ex: fa-calendar-days"
                @focus="open = true"
                class="fi-input block w-full border-none bg-transparent py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6"
                style="flex:1;border:1px solid #d1d5db;border-radius:6px;padding:8px 10px;"
            >

            {{-- Botão abrir painel --}}
            <button
                type="button"
                @click="open = !open"
                class="rounded-lg px-3 py-2 text-sm font-medium bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 transition"
                title="Escolher ícone"
                style="flex-shrink:0;white-space:nowrap;"
            >
                <i class="fa-solid fa-swatchbook me-1"></i> Escolher
            </button>
        </div>

        {{-- Painel de seleção --}}
        <div
            x-show="open"
            x-transition
            @click.outside="open = false"
            style="position:absolute;z-index:9999;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1px solid #d1d5db;border-radius:10px;padding:14px;box-shadow:0 8px 32px rgba(0,0,0,.15);max-height:380px;overflow-y:auto;"
            class="dark:bg-gray-800 dark:border-gray-600"
        >
            {{-- Busca --}}
            <input
                type="text"
                x-model="search"
                placeholder="Buscar ícone... (ex: calendário, cruz, coração)"
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-3 dark:bg-gray-700 dark:border-gray-500 dark:text-white"
                @click.stop
                autofocus
            >

            {{-- Grid de ícones --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(72px,1fr));gap:8px;">
                <template x-for="[icon, label] in Object.entries(filtered)" :key="icon">
                    <button
                        type="button"
                        @click.stop="select(icon)"
                        :title="label + ' — ' + icon"
                        :class="value === icon ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                        style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:8px 4px;border-radius:8px;border:1px solid transparent;cursor:pointer;transition:all .15s;"
                    >
                        <i class="fa-solid" :class="icon" style="font-size:20px;"></i>
                        <span x-text="label" style="font-size:10px;color:#666;text-align:center;line-height:1.2;max-width:68px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                    </button>
                </template>
                <template x-if="Object.keys(filtered).length === 0">
                    <p style="grid-column:1/-1;text-align:center;color:#aaa;font-size:13px;padding:16px;">
                        Nenhum ícone encontrado. Digite o nome direto no campo.
                    </p>
                </template>
            </div>

            <p style="font-size:11px;color:#aaa;margin-top:10px;border-top:1px solid #eee;padding-top:8px;">
                Mais ícones em <a href="https://fontawesome.com/icons" target="_blank" rel="noopener noreferrer" style="color:#4f46e5">fontawesome.com/icons</a> — copie o nome sem "fa-solid"
            </p>
        </div>

    </div>
</x-dynamic-component>
