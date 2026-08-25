<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\IconPickerField;
use App\Filament\Resources\EventoResource\Pages;
use App\Models\Evento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventoResource extends Resource
{
    protected static ?string $model = Evento::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Eventos';

    protected static ?string $modelLabel = 'Evento';

    protected static ?string $pluralModelLabel = 'Eventos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']),
                        Forms\Components\TextInput::make('subtitulo')
                            ->label('Subtítulo')
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Data e Local')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('data_inicio')
                            ->label('Data de Início')
                            ->required()
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('data_fim')
                            ->label('Data de Término')
                            ->displayFormat('d/m/Y'),
                        Forms\Components\TimePicker::make('hora_inicio')
                            ->label('Horário')
                            ->seconds(false),
                        Forms\Components\TextInput::make('local')
                            ->label('Local')
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Classificação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('categoria')
                            ->required()
                            ->options([
                                'liturgico'  => 'Litúrgico',
                                'pastoral'   => 'Pastoral',
                                'social'     => 'Social',
                                'formativo'  => 'Formativo',
                                'festivo'    => 'Festivo',
                                'outro'      => 'Outro',
                            ]),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->default('agendado')
                            ->options([
                                'agendado'      => 'Agendado',
                                'em-andamento'  => 'Em andamento',
                                'encerrado'     => 'Encerrado',
                                'cancelado'     => 'Cancelado',
                            ]),
                    ]),

                Forms\Components\Section::make('Conteúdo')
                    ->schema([
                        Forms\Components\Textarea::make('resumo')
                            ->label('Resumo')
                            ->rows(3)
                            ->maxLength(320)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('conteudo')
                            ->label('Conteúdo completo')
                            ->toolbarButtons(['bold','italic','bulletList','orderedList','link','h2','h3','blockquote'])
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('imagem_capa')
                            ->label('Imagem de Capa')
                            ->disk('public')
                            ->directory('uploads/events')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('628')
                            ->imageResizeMode('cover')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(3072)
                            ->required()
                            ->hint('Ideal: 1200 × 628 px (16:9) | JPG, PNG ou WebP | Máx. 3 MB')
                            ->afterStateHydrated(function (Forms\Components\FileUpload $component, $state): void {
                                // FileUpload trabalha internamente com array; string vinda do banco precisa ser envolvida
                                if (is_string($state) && $state !== '') {
                                    $component->state([ltrim(preg_replace('#^storage/#', '', $state), '/')]);
                                } elseif (! is_array($state)) {
                                    $component->state([]);
                                }
                            })
                            ->dehydrateStateUsing(function ($state): ?string {
                                // Salva como string simples no banco
                                if (is_array($state)) {
                                    $val = reset($state);
                                    return $val !== false ? 'storage/' . ltrim((string) $val, '/') : null;
                                }
                                return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Publicação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('publicado')->label('Publicado'),
                        Forms\Components\Toggle::make('destaque')->label('Destaque na home'),
                    ]),

                Forms\Components\Section::make('Barra de Estatísticas (opcional)')
                    ->description('Exibe 3 números/valores em destaque abaixo da imagem de capa. Deixe vazio para usar a data/horário/categoria automaticamente.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('stats_bar')
                            ->label('Itens da barra')
                            ->schema([
                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor / Número')
                                    ->required()
                                    ->placeholder('Ex: 31, 19h, Maio'),
                                Forms\Components\TextInput::make('legenda')
                                    ->label('Legenda')
                                    ->required()
                                    ->placeholder('Ex: Dias De Celebração'),
                            ])
                            ->columns(2)
                            ->maxItems(3)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Tópicos em Destaque (opcional)')
                    ->description('Lista de itens exibidos com ícone de check-verde, em 2 colunas, após o texto principal.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('topicos_destaque')
                            ->label('Tópicos')
                            ->simple(
                                Forms\Components\TextInput::make('topico')
                                    ->placeholder('Ex: Terço e Ladainha de Nossa Senhora')
                                    ->required()
                            )
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('texto_pos_topicos')
                            ->label('Parágrafo de conclusão (após os tópicos)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Galeria de Fotos (opcional)')
                    ->description('Exibida abaixo dos tópicos. Deixe vazio para ocultar a seção.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('galeria_titulo')
                            ->label('Título (parte inicial)')
                            ->placeholder('Ex: Momentos'),
                        Forms\Components\TextInput::make('galeria_titulo_destaque')
                            ->label('Título (parte em destaque/cor)')
                            ->placeholder('Ex: do Mês Mariano'),
                        Forms\Components\TextInput::make('galeria_subtitulo')
                            ->label('Subtítulo (acima do título, em letras pequenas)')
                            ->placeholder('Ex: galeria de fotos')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('galeria_imagens')
                            ->label('Imagens')
                            ->schema([
                                Forms\Components\FileUpload::make('url')
                                    ->label('Imagem')
                                    ->disk('public')
                                    ->directory('uploads/events')
                                    ->visibility('public')
                                    ->image()
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(3072)
                                    ->required()
                                    ->afterStateHydrated(function (Forms\Components\FileUpload $component, $state): void {
                                        if (is_string($state) && $state !== '') {
                                            $component->state([ltrim(preg_replace('#^storage/#', '', $state), '/')]);
                                        } elseif (! is_array($state)) {
                                            $component->state([]);
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state): ?string {
                                        if (is_array($state)) {
                                            $val = reset($state);
                                            return $val !== false ? 'storage/' . ltrim((string) $val, '/') : null;
                                        }
                                        return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                                    }),
                                Forms\Components\TextInput::make('alt')
                                    ->label('Texto alternativo (alt)')
                                    ->required()
                                    ->placeholder('Ex: Abertura do Mês Mariano'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Programação / Agenda (opcional)')
                    ->description('Accordion com horários e atividades. Deixe vazio para ocultar.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('programacao_titulo')
                            ->label('Título da seção (parte inicial)')
                            ->placeholder('Ex: Datas e'),
                        Forms\Components\TextInput::make('programacao_titulo_destaque')
                            ->label('Título (parte em destaque/cor)')
                            ->placeholder('Ex: Celebrações Especiais'),
                        Forms\Components\TextInput::make('programacao_subtitulo')
                            ->label('Subtítulo (acima do título)')
                            ->placeholder('Ex: programação completa')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('programacao')
                            ->label('Itens do accordion')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('slug')
                                        ->label('Slug (ID único)')
                                        ->required()
                                        ->placeholder('Ex: abertura'),
                                    Forms\Components\TextInput::make('hora')
                                        ->label('Horário')
                                        ->placeholder('Ex: 9h00'),
                                    IconPickerField::make('icone')
                                        ->label('Ícone'),
                                Forms\Components\Placeholder::make('icone_hint_prog')
                                        ->label('')
                                        ->content(''),
                                ]),
                                Forms\Components\TextInput::make('titulo')
                                    ->label('Título do item')
                                    ->required()
                                    ->columnSpanFull()
                                    ->placeholder('Ex: Abertura e Acolhida'),
                                Forms\Components\Textarea::make('descricao')
                                    ->label('Descrição')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('local_display')
                                        ->label('Local (exibição)'),
                                    Forms\Components\TextInput::make('horario_display')
                                        ->label('Horário (exibição)'),
                                    Forms\Components\TextInput::make('publico_display')
                                        ->label('Público (exibição)'),
                                ]),
                                Forms\Components\Toggle::make('aberto')
                                    ->label('Aberto por padrão')
                                    ->hint('Marque apenas 1 item'),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Sidebar — Informações do Evento (opcional)')
                    ->description('Substitui os itens automáticos da sidebar. Deixe vazio para usar data/horário/local automaticamente.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('sidebar_descricao')
                            ->label('Texto introdutório da sidebar')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Ex: Tudo o que você precisa saber para participar do evento.'),
                        Forms\Components\Repeater::make('sidebar_items')
                            ->label('Itens de informação')
                            ->schema([
                                IconPickerField::make('icone')
                                    ->label('Ícone')
                                    ->required(),
                                Forms\Components\TextInput::make('titulo')
                                    ->label('Rótulo')
                                    ->placeholder('Ex: Período')
                                    ->required(),
                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor')
                                    ->placeholder('Ex: 1º a 31 de Maio de 2025')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Sidebar — Marcos / Timeline (opcional)')
                    ->description('Barras de progresso na sidebar que mostram datas ou etapas do evento.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('sidebar_milestones')
                            ->label('Marcos')
                            ->schema([
                                Forms\Components\TextInput::make('titulo')
                                    ->label('Título (em negrito/cor)')
                                    ->required()
                                    ->placeholder('Ex: Abertura'),
                                Forms\Components\TextInput::make('complemento')
                                    ->label('Complemento (após o título)')
                                    ->placeholder('Ex: — 1º de Maio'),
                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor (direita)')
                                    ->required()
                                    ->placeholder('Ex: 19h'),
                                Forms\Components\TextInput::make('progresso')
                                    ->label('Progresso (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(100)
                                    ->suffix('%'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('categoria')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'liturgico' => 'info',
                        'festivo'   => 'warning',
                        'social'    => 'success',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'agendado'     => 'info',
                        'em-andamento' => 'warning',
                        'encerrado'    => 'gray',
                        'cancelado'    => 'danger',
                        default        => 'gray',
                    }),
                Tables\Columns\IconColumn::make('publicado')->boolean(),
                Tables\Columns\IconColumn::make('destaque')->boolean(),
            ])
            ->defaultSort('data_inicio', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('categoria')
                    ->options([
                        'liturgico' => 'Litúrgico', 'pastoral' => 'Pastoral',
                        'social' => 'Social', 'formativo' => 'Formativo',
                        'festivo' => 'Festivo', 'outro' => 'Outro',
                    ]),
                Tables\Filters\TernaryFilter::make('publicado'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Visualizar')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Evento $record): string => rtrim((string) config('app.url'), '/') . '/eventos/' . $record->slug . '.html')
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventos::route('/'),
            'create' => Pages\CreateEvento::route('/create'),
            'edit' => Pages\EditEvento::route('/{record}/edit'),
        ];
    }
}
