<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configurações globais do site — tabela de linha única (ID=1).
 * Use Configuracao::current() para obter ou criar o registro padrão.
 */
class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = [
        'cor_principal',
        'cor_fundo_escuro',
        'cor_fundo_claro',
        'cor_texto',
        'paroquia_nome',
        'paroquia_titulo',
        'logo_cor',
        'logo_header_img',
        'logo_footer_img',
        'logo_loader_img',
        'header_cta_texto',
        'header_cta_link',
        'hero_imagem',
        'hero_tagline',
        'hero_taglines',
        'hero_titulo',
        'hero_titulos',
        'hero_descricao',
        'hero_descricoes',
        'hero_btn1_texto',
        'hero_btn1_link',
        'hero_btn2_texto',
        'hero_btn2_link',
        'footer_descricao',
        'footer_telefone',
        'footer_email',
        'footer_endereco',
        'footer_facebook',
        'footer_instagram',
        'footer_whatsapp',
        'footer_youtube',
        'footer_links_rapidos',
        'footer_sacramentos',
        'contato_maps_url',
        'contato_horario_secretaria',
        'contato_coordenadas_lat',
        'contato_coordenadas_lng',
        'habilitar_santo_dia',
        'habilitar_evangelho_dia',
        'habilitar_terco_dia',
        'habilitar_testemunhos',
        'radio_painel_titulo',
    ];

    protected $casts = [
        'hero_titulos'        => 'array',
        'hero_taglines'       => 'array',
        'hero_descricoes'     => 'array',
        'footer_links_rapidos'=> 'array',
        'footer_sacramentos'  => 'array',
        'habilitar_santo_dia'     => 'boolean',
        'habilitar_evangelho_dia' => 'boolean',
        'habilitar_terco_dia'     => 'boolean',
        'habilitar_testemunhos'   => 'boolean',
    ];

    /** Retorna o único registro de configuração, criando-o se necessário. */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], static::defaults());
    }

    /** Valores padrão alinhados com o site atual. */
    public static function defaults(): array
    {
        return [
            'cor_principal'    => '#acaa59',
            'cor_fundo_escuro' => '#000000',
            'cor_fundo_claro'  => '#FFF4F1',
            'cor_texto'        => '#525252',
            'paroquia_nome'    => 'Paróquia Nossa Senhora dos Remédios',
            'paroquia_titulo'  => null,
            'logo_cor'         => '#acaa59',
            'logo_header_img'  => null,
            'logo_footer_img'  => null,
            'logo_loader_img'  => null,
            'header_cta_texto' => 'Ouça agora',
            'header_cta_link'  => '#',
            'hero_tagline'     => 'Paróquia Nossa Senhora dos Remédios — Jericó/PB',
            'hero_taglines'    => null,
            'hero_titulo'      => 'Fé, Esperança e Amor no coração do Sertão Paraibano!',
            'hero_titulos'     => null,
            'hero_descricao'   => 'Uma comunidade de fé com mais de 66 anos de história, erguida em torno da devoção à Nossa Senhora dos Remédios, padroeira de Jericó, no sertão da Paraíba.',
            'hero_descricoes'  => null,
            'hero_btn1_texto'  => 'Horários',
            'hero_btn1_link'   => 'agenda-liturgica.html',
            'hero_btn2_texto'  => 'Calendário Litúrgico',
            'hero_btn2_link'   => 'agenda-liturgica.html',
            'footer_descricao' => 'A Paróquia Nossa Senhora dos Remédios é uma comunidade de fé comprometida com o amor a Deus e ao próximo.',
            'footer_telefone'  => '(83) 3435-1020',
            'footer_email'     => 'paroquiaremediosjerico@gmail.com',
            'footer_endereco'  => 'Rua da Matriz, s/n - Centro, Jericó/PB, CEP 58830-000',
            'footer_facebook'  => 'https://www.facebook.com/people/Paróquia-Nossa-Senhora-dos-Remédios/100095364065282/',
            'footer_instagram' => 'https://www.instagram.com/pascomremedios.jerico',
            'footer_whatsapp'  => 'https://wa.me/558334351020',
            'footer_links_rapidos' => null,
            'footer_sacramentos'   => null,
            'contato_maps_url'           => null,
            'contato_horario_secretaria' => null,
            'contato_coordenadas_lat'    => null,
            'contato_coordenadas_lng'    => null,
            'habilitar_santo_dia'     => true,
            'habilitar_evangelho_dia' => true,
            'habilitar_terco_dia'     => true,
            'habilitar_testemunhos'   => false,
        ];
    }

    public function toJsonExport(): array
    {
        $data = $this->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);
        return $data;
    }
}
