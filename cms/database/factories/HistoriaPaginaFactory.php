<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HistoriaPagina;
use Illuminate\Database\Eloquent\Factories\Factory;

class HistoriaPaginaFactory extends Factory
{
    protected $model = HistoriaPagina::class;

    public function definition(): array
    {
        return [
            // SEO
            'seo_titulo'    => $this->faker->sentence(5),
            'seo_descricao' => $this->faker->sentence(20),

            // Page Header
            'page_titulo'      => 'Nossa Trajetória',
            'breadcrumb_atual' => 'Nossa História',

            // About
            'about_subtitulo' => 'Sobre Nós',
            'about_titulo'    => 'Conheça Nossa Paróquia',
            'about_intro1'    => $this->faker->paragraph(),
            'about_intro2'    => $this->faker->paragraph(),
            'about_imagem1'   => 'uploads/historia/imagem1.jpg',
            'about_imagem2'   => 'uploads/historia/imagem2.jpg',
            'about_topicos'   => [
                ['icone' => 'images/icon-about-1.svg', 'titulo' => 'Item de tradição centenária'],
                ['icone' => 'images/icon-about-2.svg', 'titulo' => 'Comunidade viva e acolhedora'],
                ['icone' => 'images/icon-about-3.svg', 'titulo' => 'Missão evangelizadora'],
            ],

            // Missão
            'missao_subtitulo' => 'Nossa Missão',
            'missao_titulo'    => 'Evangelizar e Servir',
            'missao_subtexto'  => 'Um propósito sagrado',
            'missao_texto'     => $this->faker->paragraph(),
            'missao_cta_href'  => '#contato',
            'missao_cta_texto' => 'Fale Conosco',
            'missao_imagem'    => 'uploads/historia/missao.jpg',

            // Visão / Missão
            'vm_subtitulo' => 'Visão e Missão',
            'vm_titulo'    => 'Nossos Valores Fundamentais',
            'vm_abas'      => [
                ['titulo' => 'Visão',  'texto' => 'Ser farol de fé na região.'],
                ['titulo' => 'Missão', 'texto' => 'Evangelizar com amor.'],
                ['titulo' => 'Valores', 'texto' => 'Fé, esperança e caridade.'],
            ],

            // Contadores
            'contador_items' => [
                ['valor' => '200', 'sufixo' => '+', 'label' => 'Anos de História',   'descricao' => 'Séculos de fé'],
                ['valor' => '5000', 'sufixo' => '+', 'label' => 'Fiéis',             'descricao' => 'Na comunidade'],
                ['valor' => '12',   'sufixo' => '',  'label' => 'Comunidades',        'descricao' => 'No município'],
            ],

            // Serviços
            'servicos_subtitulo' => 'O Que Fazemos',
            'servicos_titulo'    => 'Nossos Ministérios',
            'servicos'           => [
                ['icone' => 'images/icon-service-1.svg', 'titulo' => 'Liturgia',  'descricao' => 'Celebrações eucarísticas.'],
                ['icone' => 'images/icon-service-2.svg', 'titulo' => 'Pastoral',  'descricao' => 'Atendimento às famílias.'],
                ['icone' => 'images/icon-service-3.svg', 'titulo' => 'Catequese', 'descricao' => 'Formação cristã.'],
            ],

            // Equipe
            'equipe_subtitulo' => 'Nossa Equipe',
            'equipe_titulo'    => 'Conheça os Ministros',
            'membros'          => [
                ['nome' => 'Pe. João', 'cargo' => 'Pároco',      'imagem' => 'uploads/historia/padre.jpg'],
                ['nome' => 'Dn. Paulo', 'cargo' => 'Diácono',    'imagem' => 'uploads/historia/diacono.jpg'],
            ],

            // Pároco
            'paroco_subtitulo' => 'Mensagem',
            'paroco_titulo'    => 'Palavra do Pároco',
            'paroco_subtexto'  => 'Com fé e esperança',
            'paroco_texto'     => $this->faker->paragraph(),
            'paroco_imagem'    => 'uploads/historia/paroco.jpg',
            'paroco_assinatura' => 'uploads/historia/assinatura.png',
            'paroco_cargo'     => 'Pároco da Paróquia NSR',

            // Valores
            'valores_subtitulo' => 'Valores',
            'valores_titulo'    => 'Nossos Princípios',
            'valores_faqs'      => [
                ['pergunta' => 'O que nos move?', 'resposta' => 'A fé em Deus.'],
                ['pergunta' => 'Nossa visão?',    'resposta' => 'Igreja viva e missionária.'],
            ],
            'valores_imagens'   => [
                'uploads/historia/valor1.jpg',
                'uploads/historia/valor2.jpg',
            ],
        ];
    }

    /**
     * Retorna o estado mínimo (somente campos obrigatórios preenchidos, arrays vazios).
     */
    public function vazio(): static
    {
        return $this->state([
            'about_topicos'  => null,
            'vm_abas'        => null,
            'contador_items' => null,
            'servicos'       => null,
            'membros'        => null,
            'valores_faqs'   => null,
            'valores_imagens' => null,
        ]);
    }
}
