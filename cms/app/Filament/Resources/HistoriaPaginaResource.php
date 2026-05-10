<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\HistoriaPaginaResource\Pages;
use App\Models\HistoriaPagina;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HistoriaPaginaResource extends Resource
{
    protected static ?string $model = HistoriaPagina::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'História da Paróquia';
    protected static ?string $navigationGroup = 'Conteúdo';
    protected static ?string $modelLabel = 'História da Paróquia';
    protected static ?string $pluralModelLabel = 'História da Paróquia';
    protected static ?int $navigationSort = 8;

    /**
     * Formulário dividido em tabs por seção da página.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('historia_tabs')
                ->tabs([

                    // ── SEO ──────────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('SEO')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Forms\Components\TextInput::make('seo_titulo')
                                ->label('Título da aba / meta title')
                                ->helperText('Aparece na aba do navegador e nos resultados de busca (50-60 caracteres).')
                                ->maxLength(80),
                            Forms\Components\Textarea::make('seo_descricao')
                                ->label('Meta descrição')
                                ->helperText('Descrição exibida nos resultados de busca (140-160 caracteres).')
                                ->rows(3)
                                ->maxLength(320),
                        ]),

                    // ── Page Header ──────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Cabeçalho')
                        ->icon('heroicon-o-window')
                        ->schema([
                            Forms\Components\TextInput::make('page_titulo')
                                ->label('Título da página (h1)')
                                ->helperText('Título exibido no banner de cabeçalho da página.')
                                ->maxLength(120),
                            Forms\Components\TextInput::make('breadcrumb_atual')
                                ->label('Texto do breadcrumb')
                                ->helperText('Ex: Nossa História')
                                ->maxLength(80),
                        ]),

                    // ── About Us ─────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Apresentação')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Section::make('Textos')->columns(2)->schema([
                                Forms\Components\TextInput::make('about_subtitulo')
                                    ->label('Subtítulo (tag h3 — texto pequeno acima)')
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('about_titulo')
                                    ->label('Título principal (h2 — pode usar <span> para destaque)')
                                    ->maxLength(200),
                                Forms\Components\Textarea::make('about_intro1')
                                    ->label('Parágrafo 1')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('about_intro2')
                                    ->label('Parágrafo 2 (HTML permitido)')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                            Forms\Components\Section::make('Imagens')->columns(2)->schema([
                                Forms\Components\FileUpload::make('about_imagem1')
                                    ->label('Imagem principal (540×400 px)')
                                    ->helperText('Imagem maior, à esquerda.')
                                    ->disk('public')
                                    ->directory('uploads/historia')
                                    ->visibility('public')
                                    ->image()
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(4096),
                                Forms\Components\FileUpload::make('about_imagem2')
                                    ->label('Imagem secundária (300×300 px)')
                                    ->helperText('Imagem menor, sobreposta.')
                                    ->disk('public')
                                    ->directory('uploads/historia')
                                    ->visibility('public')
                                    ->image()
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(2048),
                            ]),
                            Forms\Components\Section::make('Tópicos em destaque')->schema([
                                Forms\Components\Repeater::make('about_topicos')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\TextInput::make('icone')
                                            ->label('Ícone (caminho da imagem, ex: images/icon-about-1.svg)')
                                            ->maxLength(300),
                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Texto do tópico')
                                            ->required()
                                            ->maxLength(120),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Adicionar tópico')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->reorderableWithButtons(),
                            ]),
                        ]),

                    // ── Missão ───────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Missão')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            Forms\Components\Section::make('Textos')->columns(2)->schema([
                                Forms\Components\TextInput::make('missao_subtitulo')
                                    ->label('Subtítulo')
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('missao_titulo')
                                    ->label('Título (pode usar <span> para destaque)')
                                    ->maxLength(200),
                                Forms\Components\TextInput::make('missao_subtexto')
                                    ->label('Sub-texto (h3 secundário)')
                                    ->maxLength(200)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('missao_texto')
                                    ->label('Texto da missão')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                            Forms\Components\Section::make('Botão de ação')->columns(2)->schema([
                                Forms\Components\TextInput::make('missao_cta_href')
                                    ->label('URL do botão')
                                    ->helperText('Ex: contato.html ou #')
                                    ->maxLength(300),
                                Forms\Components\TextInput::make('missao_cta_texto')
                                    ->label('Texto do botão')
                                    ->maxLength(80),
                            ]),
                            Forms\Components\Section::make('Imagem')->schema([
                                Forms\Components\FileUpload::make('missao_imagem')
                                    ->label('Imagem da seção Missão (600×480 px)')
                                    ->disk('public')
                                    ->directory('uploads/historia')
                                    ->visibility('public')
                                    ->image()
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(4096),
                            ]),
                        ]),

                    // ── Visão / Missão (abas) ─────────────────────────────
                    Forms\Components\Tabs\Tab::make('Visão / Missão')
                        ->icon('heroicon-o-square-3-stack-3d')
                        ->schema([
                            Forms\Components\Section::make('Cabeçalho da seção')->columns(2)->schema([
                                Forms\Components\TextInput::make('vm_subtitulo')
                                    ->label('Subtítulo')
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('vm_titulo')
                                    ->label('Título (pode usar <span>)')
                                    ->maxLength(200),
                            ]),
                            Forms\Components\Section::make('Abas')->schema([
                                Forms\Components\Repeater::make('vm_abas')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Rótulo da aba')
                                            ->required()
                                            ->maxLength(60),
                                        Forms\Components\TextInput::make('subtitulo')
                                            ->label('Subtítulo')
                                            ->maxLength(120),
                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Título (pode usar <span>)')
                                            ->maxLength(200),
                                        Forms\Components\TextInput::make('subtexto')
                                            ->label('Sub-texto (h3)')
                                            ->maxLength(200),
                                        Forms\Components\Textarea::make('texto')
                                            ->label('Texto')
                                            ->rows(3),
                                        Forms\Components\FileUpload::make('imagem')
                                            ->label('Imagem (600×480 px)')
                                            ->disk('public')
                                            ->directory('uploads/historia')
                                            ->visibility('public')
                                            ->image()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(4096),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Adicionar aba')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->reorderableWithButtons(),
                            ]),
                        ]),

                    // ── Contadores ───────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Contadores')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            Forms\Components\Repeater::make('contador_items')
                                ->label('Itens do contador (recomendado: 4 itens)')
                                ->schema([
                                    Forms\Components\TextInput::make('valor')
                                        ->label('Número')
                                        ->required()
                                        ->maxLength(20),
                                    Forms\Components\TextInput::make('sufixo')
                                        ->label('Sufixo (ex: +, %, anos)')
                                        ->maxLength(20),
                                    Forms\Components\TextInput::make('label')
                                        ->label('Rótulo')
                                        ->required()
                                        ->maxLength(80),
                                    Forms\Components\TextInput::make('descricao')
                                        ->label('Descrição secundária')
                                        ->maxLength(120),
                                ])
                                ->columns(4)
                                ->addActionLabel('Adicionar contador')
                                ->defaultItems(0)
                                ->collapsible()
                                ->reorderableWithButtons(),
                        ]),

                    // ── Serviços (What We Do) ─────────────────────────────
                    Forms\Components\Tabs\Tab::make('Serviços')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->schema([
                            Forms\Components\Section::make('Cabeçalho da seção')->columns(2)->schema([
                                Forms\Components\TextInput::make('servicos_subtitulo')
                                    ->label('Subtítulo')
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('servicos_titulo')
                                    ->label('Título (pode usar <span>)')
                                    ->maxLength(200),
                            ]),
                            Forms\Components\Section::make('Itens')->schema([
                                Forms\Components\Repeater::make('servicos')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\TextInput::make('icone')
                                            ->label('Ícone (caminho, ex: images/icon-service-1.svg)')
                                            ->maxLength(300),
                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Título')
                                            ->required()
                                            ->maxLength(120),
                                        Forms\Components\Textarea::make('descricao')
                                            ->label('Descrição')
                                            ->rows(2),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel('Adicionar serviço')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->reorderableWithButtons(),
                            ]),
                        ]),

                    // ── Equipe ───────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Equipe')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Forms\Components\Section::make('Cabeçalho da seção')->columns(2)->schema([
                                Forms\Components\TextInput::make('equipe_subtitulo')
                                    ->label('Subtítulo')
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('equipe_titulo')
                                    ->label('Título (pode usar <span>)')
                                    ->maxLength(200),
                            ]),
                            Forms\Components\Section::make('Membros')->schema([
                                Forms\Components\Repeater::make('membros')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\FileUpload::make('imagem')
                                            ->label('Foto (350×350 px)')
                                            ->disk('public')
                                            ->directory('uploads/historia/equipe')
                                            ->visibility('public')
                                            ->image()
                                            ->imageEditor()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(2048),
                                        Forms\Components\TextInput::make('nome')
                                            ->label('Nome')
                                            ->required()
                                            ->maxLength(120),
                                        Forms\Components\TextInput::make('cargo')
                                            ->label('Cargo / função')
                                            ->maxLength(120),
                                        Forms\Components\TextInput::make('facebook')
                                            ->label('URL Facebook')
                                            ->url()
                                            ->maxLength(300),
                                        Forms\Components\TextInput::make('instagram')
                                            ->label('URL Instagram')
                                            ->url()
                                            ->maxLength(300),
                                        Forms\Components\TextInput::make('whatsapp')
                                            ->label('Link WhatsApp (https://wa.me/...)')
                                            ->url()
                                            ->maxLength(300),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel('Adicionar membro')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->reorderableWithButtons(),
                            ]),
                        ]),

                    // ── Pároco ───────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Pároco')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Forms\Components\Section::make('Textos')->columns(2)->schema([
                                Forms\Components\TextInput::make('paroco_subtitulo')
                                    ->label('Subtítulo')
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('paroco_titulo')
                                    ->label('Título (pode usar <span>)')
                                    ->maxLength(200),
                                Forms\Components\TextInput::make('paroco_subtexto')
                                    ->label('Sub-texto (h3)')
                                    ->maxLength(200)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('paroco_texto')
                                    ->label('Texto da mensagem')
                                    ->rows(4)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('paroco_cargo')
                                    ->label('Cargo (ex: Pároco, Administrador Paroquial)')
                                    ->maxLength(120)
                                    ->columnSpanFull(),
                            ]),
                            Forms\Components\Section::make('Imagens')->columns(2)->schema([
                                Forms\Components\FileUpload::make('paroco_imagem')
                                    ->label('Foto do pároco (600×500 px)')
                                    ->disk('public')
                                    ->directory('uploads/historia')
                                    ->visibility('public')
                                    ->image()
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(4096),
                                Forms\Components\FileUpload::make('paroco_assinatura')
                                    ->label('Imagem da assinatura (120×60 px, fundo transparente)')
                                    ->disk('public')
                                    ->directory('uploads/historia')
                                    ->visibility('public')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/webp'])
                                    ->maxSize(512),
                            ]),
                        ]),

                    // ── Valores ──────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Valores')
                        ->icon('heroicon-o-star')
                        ->schema([
                            Forms\Components\Section::make('Cabeçalho da seção')->columns(2)->schema([
                                Forms\Components\TextInput::make('valores_subtitulo')
                                    ->label('Subtítulo')
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('valores_titulo')
                                    ->label('Título (pode usar <span>)')
                                    ->maxLength(200),
                            ]),
                            Forms\Components\Section::make('FAQ / Perguntas')->schema([
                                Forms\Components\Repeater::make('valores_faqs')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\TextInput::make('pergunta')
                                            ->label('Pergunta')
                                            ->required()
                                            ->maxLength(200),
                                        Forms\Components\Textarea::make('resposta')
                                            ->label('Resposta')
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->columns(1)
                                    ->addActionLabel('Adicionar pergunta/resposta')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->reorderableWithButtons(),
                            ]),
                            Forms\Components\Section::make('Imagens do slider')->schema([
                                Forms\Components\Repeater::make('valores_imagens')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\FileUpload::make('imagem')
                                            ->label('Imagem (600×480 px)')
                                            ->disk('public')
                                            ->directory('uploads/historia/valores')
                                            ->visibility('public')
                                            ->image()
                                            ->imageEditor()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(4096)
                                            ->required(),
                                    ])
                                    ->columns(1)
                                    ->addActionLabel('Adicionar imagem')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->reorderableWithButtons(),
                            ]),
                        ]),

                ])->columnSpanFull(),
        ]);
    }

    /**
     * Sem tabela — este resource tem apenas edição direta.
     */
    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\EditHistoriaPagina::route('/'),
            'edit'  => Pages\EditHistoriaPagina::route('/edit'),
        ];
    }

    /**
     * Redireciona a rota de listagem diretamente para a edição do singleton.
     */
    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl('edit', $parameters, $isAbsolute, $panel, $tenant);
    }
}
