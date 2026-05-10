<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriaPagina extends Model
{
    use HasFactory;

    protected $table = 'historia_pagina';

    protected $fillable = [
        // SEO
        'seo_titulo',
        'seo_descricao',
        // Page Header
        'page_titulo',
        'breadcrumb_atual',
        // About
        'about_subtitulo',
        'about_titulo',
        'about_intro1',
        'about_intro2',
        'about_imagem1',
        'about_imagem2',
        'about_topicos',
        // Missão
        'missao_subtitulo',
        'missao_titulo',
        'missao_subtexto',
        'missao_texto',
        'missao_cta_href',
        'missao_cta_texto',
        'missao_imagem',
        // Visão / Missão
        'vm_subtitulo',
        'vm_titulo',
        'vm_abas',
        // Contadores
        'contador_items',
        // Serviços
        'servicos_subtitulo',
        'servicos_titulo',
        'servicos',
        // Equipe
        'equipe_subtitulo',
        'equipe_titulo',
        'membros',
        // Pároco
        'paroco_subtitulo',
        'paroco_titulo',
        'paroco_subtexto',
        'paroco_texto',
        'paroco_imagem',
        'paroco_assinatura',
        'paroco_cargo',
        // Valores
        'valores_subtitulo',
        'valores_titulo',
        'valores_faqs',
        'valores_imagens',
    ];

    protected $casts = [
        'about_topicos'   => 'array',
        'vm_abas'         => 'array',
        'contador_items'  => 'array',
        'servicos'        => 'array',
        'membros'         => 'array',
        'valores_faqs'    => 'array',
        'valores_imagens' => 'array',
    ];

    /**
     * Retorna a única instância existente (ou cria uma nova em branco).
     */
    public static function current(): static
    {
        return static::firstOrCreate(['id' => 1]);
    }

    /**
     * Gera o array para data/historia.json, com todas as variáveis esperadas
     * pelo template templates/historia.html (Mustache).
     */
    public function toJsonExport(): array
    {
        // Abas: adiciona campos ativo/tab_id/aba_id para o Mustache
        $vmAbas = [];
        foreach (($this->vm_abas ?? []) as $i => $aba) {
            $idx = $i + 1;
            $vmAbas[] = array_merge($aba, [
                'ativo'  => $i === 0,
                'tab_id' => "vm-tab-{$idx}",
                'aba_id' => "vm-pane-{$idx}",
            ]);
        }

        // Topicos: garante delay progressivo
        $topicos = [];
        foreach (($this->about_topicos ?? []) as $i => $t) {
            $topicos[] = array_merge($t, [
                'atraso' => $i > 0 ? (0.25 * $i) . 's' : null,
            ]);
        }

        // Serviços: delay progressivo
        $servicos = [];
        foreach (($this->servicos ?? []) as $i => $s) {
            $servicos[] = array_merge($s, [
                'atraso' => $i > 0 ? (0.25 * $i) . 's' : null,
            ]);
        }

        // Membros equipe: delay progressivo
        $membros = [];
        foreach (($this->membros ?? []) as $i => $m) {
            $membros[] = array_merge($m, [
                'atraso' => $i > 0 ? (0.25 * $i) . 's' : null,
            ]);
        }

        // FAQs: primeiro item aberto por padrão
        $valores_faqs = [];
        foreach (($this->valores_faqs ?? []) as $i => $f) {
            $valores_faqs[] = array_merge($f, [
                'aberto'     => $i === 0,
                'heading_id' => 'heading' . ($i + 1),
                'faq_id'     => 'faq' . ($i + 1),
            ]);
        }

        // Imagens do slider de valores
        $valores_imagens = array_map(
            fn ($img) => is_string($img) ? ['imagem' => $img] : $img,
            $this->valores_imagens ?? []
        );

        return [
            // SEO
            'meta_titulo'    => $this->seo_titulo    ?? 'História da Paróquia',
            'meta_descricao' => $this->seo_descricao ?? '',

            // Page Header
            'page_titulo'      => $this->page_titulo      ?? 'Nossa Trajetória',
            'breadcrumb_atual' => $this->breadcrumb_atual ?? 'Nossa História',

            // About
            'about_subtitulo' => $this->about_subtitulo ?? '',
            'about_titulo'    => $this->about_titulo    ?? '',
            'about_intro1'    => $this->about_intro1    ?? '',
            'about_intro2'    => $this->about_intro2    ?? '',
            'about_imagem1'   => $this->about_imagem1 ? '/storage/' . ltrim((string) $this->about_imagem1, '/') : '',
            'about_imagem2'   => $this->about_imagem2 ? '/storage/' . ltrim((string) $this->about_imagem2, '/') : '',
            'topicos'         => $topicos ?: null,

            // Missão
            'missao_subtitulo' => $this->missao_subtitulo ?? '',
            'missao_titulo'    => $this->missao_titulo    ?? '',
            'missao_subtexto'  => $this->missao_subtexto  ?? '',
            'missao_texto'     => $this->missao_texto     ?? '',
            'missao_cta_href'  => $this->missao_cta_href  ?? '#',
            'missao_cta_texto' => $this->missao_cta_texto ?? 'Saiba mais',
            'missao_imagem'    => $this->missao_imagem ? '/storage/' . ltrim((string) $this->missao_imagem, '/') : '',

            // Visão / Missão
            'vm_subtitulo' => $this->vm_subtitulo ?? '',
            'vm_titulo'    => $this->vm_titulo    ?? '',
            'vm_abas'      => $vmAbas ?: null,

            // Contadores
            'contador_items' => $this->contador_items ?: null,

            // Serviços
            'servicos_subtitulo' => $this->servicos_subtitulo ?? '',
            'servicos_titulo'    => $this->servicos_titulo    ?? '',
            'servicos'           => $servicos ?: null,

            // Equipe
            'equipe_subtitulo' => $this->equipe_subtitulo ?? '',
            'equipe_titulo'    => $this->equipe_titulo    ?? '',
            'membros'          => $membros ?: null,

            // Pároco
            'paroco_subtitulo' => $this->paroco_subtitulo ?? '',
            'paroco_titulo'    => $this->paroco_titulo    ?? '',
            'paroco_subtexto'  => $this->paroco_subtexto  ?? '',
            'paroco_texto'     => $this->paroco_texto     ?? '',
            'paroco_imagem'    => $this->paroco_imagem    ? '/storage/' . ltrim((string) $this->paroco_imagem, '/') : '',
            'paroco_assinatura' => $this->paroco_assinatura ? '/storage/' . ltrim((string) $this->paroco_assinatura, '/') : '',
            'paroco_cargo'     => $this->paroco_cargo     ?? '',

            // Valores
            'valores_subtitulo' => $this->valores_subtitulo ?? '',
            'valores_titulo'    => $this->valores_titulo    ?? '',
            'valores_faqs'      => $valores_faqs      ?: null,
            'valores_imagens'   => $valores_imagens   ?: null,
        ];
    }
}
