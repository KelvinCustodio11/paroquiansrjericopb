<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HistoriaPagina;
use Illuminate\Database\Seeder;

class HistoriaPaginaSeeder extends Seeder
{
    public function run(): void
    {
        HistoriaPagina::updateOrCreate(['id' => 1], [
            // SEO
            'seo_titulo'    => 'História da Paróquia',
            'seo_descricao' => 'Conheça a história, missão e devoção da Paróquia Nossa Senhora dos Remédios em Jericó/PB. Tradição de fé construída com a comunidade.',

            // Page Header
            'page_titulo'      => 'Nossa Trajetória',
            'breadcrumb_atual' => 'Nossa História',

            // About Us
            'about_subtitulo' => '200 anos de fé',
            'about_titulo'    => 'Nossa <span>Trajetória</span>',
            'about_intro1'    => 'A Paróquia Nossa Senhora dos Remédios de Jericó/PB tem suas raízes na devoção popular do sertão nordestino, onde a fé sempre foi o alicerce das comunidades.',
            'about_intro2'    => 'Ao longo de dois séculos de história, a paróquia acompanhou o crescimento da cidade, formando gerações e construindo com o povo uma comunidade viva e atuante.',
            'about_imagem1'   => null,
            'about_imagem2'   => null,
            'about_topicos'   => [
                ['icone' => 'images/icon-about-1.svg', 'titulo' => 'Fé que transforma'],
                ['icone' => 'images/icon-about-2.svg', 'titulo' => 'Comunidade unida'],
                ['icone' => 'images/icon-about-3.svg', 'titulo' => 'Tradição e renovação'],
            ],

            // Missão
            'missao_subtitulo' => 'Nossa missão',
            'missao_titulo'    => 'Evangelizar e <span>Servir</span>',
            'missao_subtexto'  => 'Comprometidos com o Evangelho',
            'missao_texto'     => 'A missão da Paróquia Nossa Senhora dos Remédios é anunciar o Evangelho de Jesus Cristo, celebrar os sacramentos e promover a caridade, formando discípulos missionários comprometidos com a transformação da sociedade à luz da fé.',
            'missao_cta_href'  => 'contato.html',
            'missao_cta_texto' => 'Entre em contato',
            'missao_imagem'    => null,

            // Visão / Missão
            'vm_subtitulo' => 'Valores e vocação',
            'vm_titulo'    => 'Visão e <span>Missão</span>',
            'vm_abas'      => [
                [
                    'label'     => 'Nossa Visão',
                    'subtitulo' => 'Para onde caminhamos',
                    'titulo'    => 'Uma paróquia <span>viva e missionária</span>',
                    'subtexto'  => 'Construindo o Reino de Deus',
                    'texto'     => 'Ser uma comunidade de fé que evangeliza, acolhe e serve, tornando-se sinal do amor de Deus no coração do sertão paraibano.',
                    'imagem'    => null,
                ],
                [
                    'label'     => 'Nossa Missão',
                    'subtitulo' => 'Nosso compromisso',
                    'titulo'    => 'Anunciar e <span>testemunhar</span>',
                    'subtexto'  => 'Fe em ação',
                    'texto'     => 'Anunciar Jesus Cristo por meio dos sacramentos, da formação e da caridade, formando discípulos missionários que transformam a sociedade.',
                    'imagem'    => null,
                ],
            ],

            // Contadores
            'contador_items' => [
                ['valor' => '200', 'sufixo' => '+', 'label' => 'Anos de história',     'descricao' => 'Séculos de fé e devoção'],
                ['valor' => '12',  'sufixo' => '',  'label' => 'Comunidades',           'descricao' => 'Espalhadas pelo município'],
                ['valor' => '7',   'sufixo' => '',  'label' => 'Ministérios ativos',    'descricao' => 'Servindo a comunidade'],
                ['valor' => '52',  'sufixo' => '',  'label' => 'Missas por semana',     'descricao' => 'Em toda a paróquia'],
            ],

            // Serviços / What We Do
            'servicos_subtitulo' => 'O que fazemos',
            'servicos_titulo'    => 'Como <span>servimos</span>',
            'servicos'           => [
                ['icone' => 'images/icon-service-1.svg', 'titulo' => 'Sacramentos', 'descricao' => 'Celebramos todos os sacramentos da Igreja Católica com dedicação e fé.'],
                ['icone' => 'images/icon-service-2.svg', 'titulo' => 'Catequese',   'descricao' => 'Formação para crianças, jovens e adultos em preparação aos sacramentos.'],
                ['icone' => 'images/icon-service-3.svg', 'titulo' => 'Pastoral',    'descricao' => 'Grupos de oração, caridade e apostolado animando a vida da paróquia.'],
            ],

            // Equipe (vazio por padrão — preencher no admin)
            'equipe_subtitulo' => 'Nossa equipe',
            'equipe_titulo'    => 'Quem nos <span>serve</span>',
            'membros'          => [],

            // Pároco (vazio — dados vêm do ParocoResource)
            'paroco_subtitulo' => 'Mensagem do pároco',
            'paroco_titulo'    => 'Uma palavra <span>pastoral</span>',
            'paroco_subtexto'  => '',
            'paroco_texto'     => '',
            'paroco_imagem'    => null,
            'paroco_assinatura' => null,
            'paroco_cargo'     => 'Pároco',

            // Valores
            'valores_subtitulo' => 'Nossos valores',
            'valores_titulo'    => 'O que <span>acreditamos</span>',
            'valores_faqs'      => [
                ['pergunta' => 'Qual é a nossa fé?',         'resposta' => 'Professamos a fé apostólica e católica, centrada em Jesus Cristo, Filho de Deus, morto e ressuscitado pela nossa salvação.'],
                ['pergunta' => 'Como cuidamos do próximo?',  'resposta' => 'Por meio da Pastoral da Caridade, Cáritas e ações solidárias que atendem às necessidades dos mais vulneráveis.'],
                ['pergunta' => 'Como formamos discípulos?',  'resposta' => 'Através da catequese, grupos de oração, estudos bíblicos e encontros de formação para todas as idades.'],
            ],
            'valores_imagens' => [],
        ]);
    }
}
