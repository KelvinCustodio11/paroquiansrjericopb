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
                                    ->label('Linha de destaque (acima do título)')
                                    ->placeholder('Ex: Paróquia Nossa Senhora dos Remédios — Jericó/PB')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('hero_titulo')
                                    ->label('Título principal')
                                    ->placeholder('Ex: Fé, Esperança e Amor…')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('hero_descricao')
                                    ->label('Descrição / subtítulo')
                                    ->rows(3)
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
