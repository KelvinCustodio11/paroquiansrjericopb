<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Configuracao;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Configuracoes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Configurações do Site';
    protected static ?string $title = 'Configurações do Site';
    protected static ?int $navigationSort = 98;
    protected static string $view = 'filament.pages.configuracoes';

    public ?array $data = [];

    public function mount(): void
    {
        $config = Configuracao::current();
        $this->form->fill($config->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('configuracoes')
                    ->tabs([
                        // ── Identidade Visual ─────────────────────────────
                        Forms\Components\Tabs\Tab::make('Identidade Visual')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Forms\Components\ColorPicker::make('cor_principal')
                                    ->label('Cor de destaque (botões e links)')
                                    ->helperText('Cor dos botões, links ativos e elementos de destaque. Ex: #acaa59')
                                    ->required(),
                                Forms\Components\ColorPicker::make('cor_fundo_escuro')
                                    ->label('Cor de fundo escuro (overlay e seções dark)')
                                    ->helperText('Usada no overlay do hero, seções escuras e header fixo. Ex: #000000')
                                    ->required(),
                                Forms\Components\ColorPicker::make('cor_fundo_claro')
                                    ->label('Cor de fundo claro (seções alternadas)')
                                    ->helperText('Fundo das seções claras alternadas (citações, boxes). Ex: #FFF4F1')
                                    ->required(),
                                Forms\Components\ColorPicker::make('cor_texto')
                                    ->label('Cor do texto principal')
                                    ->helperText('Cor padrão do corpo do texto. Ex: #525252')
                                    ->required(),
                            ])
                            ->columns(2),

                        // ── Logos ─────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Logos')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\ColorPicker::make('logo_cor')
                                    ->label('Cor dos logos')
                                    ->helperText('Substitui a cor dos ícones nos logos padrão (cabeçalho, rodapé e carregamento). Ex: #acaa59')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\FileUpload::make('logo_header_img')
                                    ->label('Logo do cabeçalho')
                                    ->helperText('Substitui o logo no menu de navegação. Deixe vazio para usar o padrão. Recomendado: max-height 55px, fundo transparente.')
                                    ->disk('site_static')
                                    ->directory('images/uploads/logos')
                                    ->visibility('public')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp'])
                                    ->maxSize(2048)
                                    ->afterStateHydrated(function (Forms\Components\FileUpload $component, $state): void {
                                        if (is_string($state) && $state !== '') {
                                            $component->state([ltrim($state, '/')]);
                                        } elseif (! is_array($state)) {
                                            $component->state([]);
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state): ?string {
                                        if (is_array($state)) {
                                            $val = reset($state);
                                            return $val !== false ? ltrim((string) $val, '/') : null;
                                        }
                                        return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                                    }),
                                Forms\Components\FileUpload::make('logo_footer_img')
                                    ->label('Logo do rodapé')
                                    ->helperText('Substitui o logo no rodapé. Deixe vazio para usar o padrão. Recomendado: max-height 80px, fundo transparente.')
                                    ->disk('site_static')
                                    ->directory('images/uploads/logos')
                                    ->visibility('public')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp'])
                                    ->maxSize(2048)
                                    ->afterStateHydrated(function (Forms\Components\FileUpload $component, $state): void {
                                        if (is_string($state) && $state !== '') {
                                            $component->state([ltrim($state, '/')]);
                                        } elseif (! is_array($state)) {
                                            $component->state([]);
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state): ?string {
                                        if (is_array($state)) {
                                            $val = reset($state);
                                            return $val !== false ? ltrim((string) $val, '/') : null;
                                        }
                                        return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                                    }),
                                Forms\Components\FileUpload::make('logo_loader_img')
                                    ->label('Logo do carregamento inicial (Preloader)')
                                    ->helperText('Substitui o ícone giratório na tela de carregamento. Deixe vazio para usar o padrão. Recomendado: 66×86px, fundo transparente.')
                                    ->disk('site_static')
                                    ->directory('images/uploads/logos')
                                    ->visibility('public')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp'])
                                    ->maxSize(1024)
                                    ->afterStateHydrated(function (Forms\Components\FileUpload $component, $state): void {
                                        if (is_string($state) && $state !== '') {
                                            $component->state([ltrim($state, '/')]);
                                        } elseif (! is_array($state)) {
                                            $component->state([]);
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state): ?string {
                                        if (is_array($state)) {
                                            $val = reset($state);
                                            return $val !== false ? ltrim((string) $val, '/') : null;
                                        }
                                        return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                                    }),
                            ])
                            ->columns(2),

                        // ── Cabeçalho ────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Cabeçalho')
                            ->icon('heroicon-o-rectangle-group')
                            ->schema([
                                Forms\Components\TextInput::make('header_cta_texto')
                                    ->label('Texto do botão de ação')
                                    ->placeholder('Ex: Ouça agora')
                                    ->required(),
                                Forms\Components\TextInput::make('header_cta_link')
                                    ->label('Link do botão de ação')
                                    ->placeholder('Ex: # ou https://...')
                                    ->required(),
                            ]),

                        // ── Hero (Página Inicial) ─────────────────────────
                        Forms\Components\Tabs\Tab::make('Hero / Banner')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('hero_imagem')
                                    ->label('Imagem de fundo do banner')
                                    ->disk('site_static')
                                    ->directory('images/uploads/hero')
                                    ->visibility('public')
                                    ->image()
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(8192)
                                    ->helperText('Recomendado: 1920×1080px, máx. 8 MB.')
                                    ->afterStateHydrated(function (Forms\Components\FileUpload $component, $state): void {
                                        if (is_string($state) && $state !== '') {
                                            $component->state([ltrim($state, '/')]);
                                        } elseif (! is_array($state)) {
                                            $component->state([]);
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state): ?string {
                                        if (is_array($state)) {
                                            $val = reset($state);
                                            return $val !== false ? ltrim((string) $val, '/') : null;
                                        }
                                        return is_string($state) && $state !== '' ? ltrim($state, '/') : null;
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('hero_tagline')
                                    ->label('Linha de destaque principal (acima do título)')
                                    ->placeholder('Ex: Paróquia Nossa Senhora dos Remédios — Jericó/PB')
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('hero_taglines')
                                    ->label('Variações da linha de destaque (rotação automática)')
                                    ->helperText('Se preenchido, o texto acima será substituído pelas variações abaixo, alternando com animação.')
                                    ->schema([
                                        Forms\Components\TextInput::make('texto')->label('Texto')->required()->maxLength(150),
                                        Forms\Components\ColorPicker::make('cor')->label('Cor (opcional)'),
                                        Forms\Components\Select::make('efeito')
                                            ->label('Efeito de transição')
                                            ->options(['fade' => 'Fade', 'slide' => 'Slide', 'typewriter' => 'Typewriter'])
                                            ->default('fade'),
                                        Forms\Components\TextInput::make('duracao')->label('Duração (ms)')->numeric()->default(3000),
                                    ])
                                    ->columns(4)
                                    ->addActionLabel('Adicionar variação')
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('hero_titulo')
                                    ->label('Título principal')
                                    ->placeholder('Ex: Fé, Esperança e Amor…')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('hero_titulos')
                                    ->label('Variações do título (rotação automática)')
                                    ->schema([
                                        Forms\Components\TextInput::make('texto')->label('Texto')->required()->maxLength(200),
                                        Forms\Components\ColorPicker::make('cor')->label('Cor (opcional)'),
                                        Forms\Components\Select::make('efeito')
                                            ->label('Efeito')
                                            ->options(['fade' => 'Fade', 'slide' => 'Slide', 'typewriter' => 'Typewriter'])
                                            ->default('fade'),
                                        Forms\Components\TextInput::make('duracao')->label('Duração (ms)')->numeric()->default(4000),
                                    ])
                                    ->columns(4)
                                    ->addActionLabel('Adicionar variação')
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('hero_descricao')
                                    ->label('Descrição / subtítulo principal')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('hero_descricoes')
                                    ->label('Variações da descrição (rotação automática)')
                                    ->schema([
                                        Forms\Components\Textarea::make('texto')->label('Texto')->required()->rows(2)->maxLength(400),
                                        Forms\Components\Select::make('efeito')
                                            ->label('Efeito')
                                            ->options(['fade' => 'Fade', 'slide' => 'Slide'])
                                            ->default('fade'),
                                        Forms\Components\TextInput::make('duracao')->label('Duração (ms)')->numeric()->default(5000),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel('Adicionar variação')
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('hero_btn1_texto')
                                        ->label('Botão 1 — Texto')
                                        ->placeholder('Ex: Horários'),
                                    Forms\Components\TextInput::make('hero_btn1_link')
                                        ->label('Botão 1 — Link')
                                        ->placeholder('Ex: agenda-liturgica.html'),
                                    Forms\Components\TextInput::make('hero_btn2_texto')
                                        ->label('Botão 2 — Texto')
                                        ->placeholder('Ex: Calendário Litúrgico'),
                                    Forms\Components\TextInput::make('hero_btn2_link')
                                        ->label('Botão 2 — Link')
                                        ->placeholder('Ex: agenda-liturgica.html'),
                                ]),
                            ]),

                        // ── Rodapé ────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Rodapé')
                            ->icon('heroicon-o-bars-3-bottom-left')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('paroquia_nome')
                                        ->label('Nome da paróquia (exibido no footer)')
                                        ->placeholder('Ex: Paróquia Nossa Senhora dos Remédios')
                                        ->required(),
                                    Forms\Components\TextInput::make('paroquia_titulo')
                                        ->label('Título/subtítulo do footer')
                                        ->placeholder('Ex: Diocese de Cajazeiras'),
                                ]),
                                Forms\Components\Textarea::make('footer_descricao')
                                    ->label('Texto de descrição')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('footer_telefone')
                                        ->label('Telefone')
                                        ->placeholder('Ex: (83) 3435-1020'),
                                    Forms\Components\TextInput::make('footer_email')
                                        ->label('E-mail')
                                        ->email()
                                        ->placeholder('Ex: contato@paroquia.com.br'),
                                ]),
                                Forms\Components\Textarea::make('footer_endereco')
                                    ->label('Endereço')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Section::make('Redes Sociais')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('footer_facebook')
                                            ->label('Facebook (URL completa)')
                                            ->placeholder('https://facebook.com/...')
                                            ->url(),
                                        Forms\Components\TextInput::make('footer_instagram')
                                            ->label('Instagram (URL completa)')
                                            ->placeholder('https://instagram.com/...')
                                            ->url(),
                                        Forms\Components\TextInput::make('footer_whatsapp')
                                            ->label('WhatsApp (URL wa.me)')
                                            ->placeholder('https://wa.me/55...')
                                            ->url(),
                                        Forms\Components\TextInput::make('footer_youtube')
                                            ->label('YouTube (URL completa)')
                                            ->placeholder('https://youtube.com/...')
                                            ->url(),
                                    ]),
                                Forms\Components\Section::make('Links Rápidos do Footer')
                                    ->description('Links exibidos na coluna "Links Rápidos" do rodapé.')
                                    ->schema([
                                        Forms\Components\Repeater::make('footer_links_rapidos')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\TextInput::make('texto')
                                                    ->label('Texto do link')
                                                    ->required()
                                                    ->maxLength(60),
                                                Forms\Components\TextInput::make('link')
                                                    ->label('URL')
                                                    ->required()
                                                    ->maxLength(200),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('Adicionar link')
                                            ->defaultItems(0),
                                    ]),
                                Forms\Components\Section::make('Sacramentos no Footer')
                                    ->description('Links exibidos na coluna "Sacramentos" do rodapé.')
                                    ->schema([
                                        Forms\Components\Repeater::make('footer_sacramentos')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\TextInput::make('nome')
                                                    ->label('Nome do sacramento')
                                                    ->required()
                                                    ->maxLength(60),
                                                Forms\Components\TextInput::make('link')
                                                    ->label('URL')
                                                    ->required()
                                                    ->maxLength(200),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('Adicionar sacramento')
                                            ->defaultItems(0),
                                    ]),
                            ]),

                        // ── Contato ───────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Contato e Localização')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\TextInput::make('contato_maps_url')
                                    ->label('URL do embed do Google Maps')
                                    ->helperText('Cole o src do iframe do Google Maps (https://www.google.com/maps/embed?pb=...)')
                                    ->maxLength(600)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('contato_horario_secretaria')
                                    ->label('Horário de atendimento da secretaria')
                                    ->placeholder('Ex: Seg a Sex: 8h às 12h e 13h às 17h')
                                    ->maxLength(200)
                                    ->columnSpanFull(),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('contato_coordenadas_lat')
                                        ->label('Latitude')
                                        ->placeholder('Ex: -6.5321'),
                                    Forms\Components\TextInput::make('contato_coordenadas_lng')
                                        ->label('Longitude')
                                        ->placeholder('Ex: -37.8475'),
                                ]),
                            ]),

                        // ── Funcionalidades ───────────────────────────────
                        Forms\Components\Tabs\Tab::make('Funcionalidades')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Forms\Components\Section::make('Player de Rádio')
                                    ->description('Personalize o painel de rádio exibido no site.')
                                    ->schema([
                                        Forms\Components\Toggle::make('radio_player_ativo')
                                            ->label('Habilitar player de rádio no site')
                                            ->helperText('Quando desligado, o botão flutuante e o player de rádio são ocultados de todas as páginas.')
                                            ->default(true),
                                        Forms\Components\TextInput::make('radio_painel_titulo')
                                            ->label('Título do painel de rádio')
                                            ->placeholder('Rádios Católicas ao Vivo')
                                            ->maxLength(80)
                                            ->helperText('Texto exibido no cabeçalho do seletor de rádios (ex: "Rádios ao Vivo", "Ouça Agora")')
                                            ->visible(fn (Forms\Get $get) => (bool) $get('radio_player_ativo')),
                                    ]),
                                Forms\Components\Section::make('Devoções Diárias')
                                    ->description('Controle o que aparece na seção de devoções diárias do site.')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('habilitar_santo_dia')
                                            ->label('Exibir Santo do Dia')
                                            ->default(true),
                                        Forms\Components\Toggle::make('habilitar_evangelho_dia')
                                            ->label('Exibir Evangelho do Dia')
                                            ->default(true),
                                        Forms\Components\Toggle::make('habilitar_terco_dia')
                                            ->label('Exibir Terço do Dia')
                                            ->default(true),
                                    ]),
                                Forms\Components\Section::make('Participação da Comunidade')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('habilitar_testemunhos')
                                            ->label('Habilitar envio de testemunhos pelos fiéis')
                                            ->helperText('Quando ativo, exibe formulário de testemunho no site. Aprovação via CMS.')
                                            ->default(false),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        Configuracao::updateOrCreate(['id' => 1], $data);

        Notification::make()
            ->title('Configurações salvas com sucesso!')
            ->body('Clique em "Publicar Site" para aplicar as alterações no site.')
            ->success()
            ->send();
    }
}
