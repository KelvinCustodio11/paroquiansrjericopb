<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * Campo de seleção visual de ícone Font Awesome.
 *
 * Uso:
 *   IconPickerField::make('icone')->label('Ícone')
 *
 * Salva no banco apenas o nome da classe sem 'fa-solid ', ex: "fa-calendar-days".
 * O template HTML já adiciona 'fa-solid' antes.
 */
class IconPickerField extends Field
{
    protected string $view = 'filament.forms.components.icon-picker';

    /** Lista de ícones disponíveis: [classe => rótulo] */
    public static function icons(): array
    {
        return [
            // Tempo / Agenda
            'fa-calendar-days'      => 'Calendário',
            'fa-calendar-check'     => 'Calendário check',
            'fa-calendar-plus'      => 'Calendário +',
            'fa-clock'              => 'Relógio',
            'fa-hourglass-half'     => 'Ampulheta',

            // Localização
            'fa-location-dot'       => 'Localização',
            'fa-map-location-dot'   => 'Mapa',
            'fa-church'             => 'Igreja',
            'fa-house'              => 'Casa',

            // Pessoas
            'fa-users'              => 'Grupo',
            'fa-user'               => 'Pessoa',
            'fa-person-praying'     => 'Orando',
            'fa-children'           => 'Crianças',
            'fa-people-group'       => 'Comunidade',
            'fa-user-tie'           => 'Padre/Líder',

            // Religioso
            'fa-cross'              => 'Cruz',
            'fa-dove'               => 'Pomba',
            'fa-hands-praying'      => 'Mãos em oração',
            'fa-bible'              => 'Bíblia',
            'fa-book-open'          => 'Livro aberto',
            'fa-scroll'             => 'Pergaminho',
            'fa-star'               => 'Estrela',
            'fa-star-of-david'      => 'Estrela de Davi',
            'fa-hand-holding-heart' => 'Coração nas mãos',
            'fa-heart'              => 'Coração',
            'fa-fire'               => 'Chama',
            'fa-water'              => 'Água',
            'fa-wheat-awn'          => 'Trigo',
            'fa-wine-glass'         => 'Cálice',
            'fa-fish'               => 'Peixe',

            // Música / Mídia
            'fa-music'              => 'Música',
            'fa-guitar'             => 'Violão',
            'fa-microphone'         => 'Microfone',
            'fa-headphones'         => 'Headphone',
            'fa-video'              => 'Vídeo',
            'fa-photo-film'         => 'Foto/Vídeo',

            // Comunicação / Info
            'fa-phone'              => 'Telefone',
            'fa-envelope'           => 'E-mail',
            'fa-comments'           => 'Comentários',
            'fa-comment'            => 'Mensagem',
            'fa-bullhorn'           => 'Anúncio',
            'fa-circle-info'        => 'Informação',
            'fa-circle-question'    => 'Pergunta',

            // Ações / Status
            'fa-check'              => 'Check',
            'fa-check-circle'       => 'Check círculo',
            'fa-circle-check'       => 'Círculo OK',
            'fa-flag'               => 'Bandeira',
            'fa-trophy'             => 'Troféu',
            'fa-award'              => 'Prêmio',
            'fa-medal'              => 'Medalha',
            'fa-handshake'          => 'Aperto de mão',
            'fa-hand-holding'       => 'Mão segurando',

            // Eventos / Atividades
            'fa-graduation-cap'     => 'Formatura',
            'fa-chalkboard-teacher' => 'Professor',
            'fa-chair'              => 'Cadeira',
            'fa-door-open'          => 'Porta aberta',
            'fa-door-closed'        => 'Porta fechada',
            'fa-ticket'             => 'Ingresso',
            'fa-gift'               => 'Presente',
            'fa-cookie'             => 'Biscoito',
            'fa-bowl-food'          => 'Refeição',
            'fa-utensils'           => 'Talheres',

            // Financeiro / Solidariedade
            'fa-circle-dollar-to-slot' => 'Doação',
            'fa-hand-holding-dollar'   => 'Arrecadação',
            'fa-coins'                 => 'Moedas',
            'fa-piggy-bank'            => 'Cofre',

            // Natureza / Decoração
            'fa-sun'                => 'Sol',
            'fa-moon'               => 'Lua',
            'fa-cloud-sun'          => 'Tempo',
            'fa-seedling'           => 'Broto',
            'fa-tree'               => 'Árvore',
            'fa-leaf'               => 'Folha',
            'fa-flower'             => 'Flor',

            // Misc úteis
            'fa-bars'               => 'Menu',
            'fa-list'               => 'Lista',
            'fa-table-list'         => 'Tabela',
            'fa-clipboard-list'     => 'Prancheta',
            'fa-pen-to-square'      => 'Editar',
            'fa-magnifying-glass'   => 'Pesquisa',
            'fa-link'               => 'Link',
            'fa-share-nodes'        => 'Compartilhar',
            'fa-print'              => 'Imprimir',
            'fa-qrcode'             => 'QR Code',
        ];
    }
}
