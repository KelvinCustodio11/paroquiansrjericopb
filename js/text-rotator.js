/**
 * text-rotator.js — Rotação de textos no hero
 * Lê arrays de texto de um atributo data-* ou de window.heroData
 * e alterna os textos com efeito de transição configurável.
 *
 * Uso via HTML:
 *   <span id="hero-titulo-rotator"
 *         data-textos='[{"texto":"Fé","efeito":"fade","duracao":4000},...]'
 *         data-texto-fallback="Fé, Esperança e Amor">
 *   </span>
 *
 * Uso via JS:
 *   import TextRotator from './text-rotator.js';
 *   new TextRotator('#hero-titulo-rotator', window.heroData.titulos, { intervalo: 4000 });
 */

const EFEITOS_DISPONIVEIS = ['fade', 'slide', 'typewriter'];

/**
 * @param {HTMLElement} el
 * @param {Array<{texto: string, efeito?: string, cor?: string, duracao?: number}>} textos
 * @param {{ intervalo?: number }} opcoes
 */
class TextRotator {
  constructor(seletor, textos, opcoes = {}) {
    this.el = typeof seletor === 'string' ? document.querySelector(seletor) : seletor;

    if (!this.el) return;

    // Prioridade: parâmetro JS > data-textos > texto-fallback
    this.textos = textos || this._parseDataTextos();
    if (!this.textos || this.textos.length === 0) return;

    this.opcoes = { intervalo: opcoes.intervalo || 4000 };
    this.indice = 0;
    this.timer = null;
    this._typewriterTimer = null;

    this._aplicarTexto(this.textos[0]);
    if (this.textos.length > 1) {
      this._agendar();
    }
  }

  _parseDataTextos() {
    try {
      const raw = this.el.dataset.textos;
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  }

  _agendar() {
    const item = this.textos[this.indice];
    const duracao = item.duracao || this.opcoes.intervalo;
    this.timer = setTimeout(() => this._proximo(), duracao);
  }

  _proximo() {
    this.indice = (this.indice + 1) % this.textos.length;
    const item = this.textos[this.indice];
    const efeito = EFEITOS_DISPONIVEIS.includes(item.efeito) ? item.efeito : 'fade';
    this[`_efeito_${efeito}`](item);
  }

  _aplicarTexto(item) {
    if (item.cor) {
      this.el.style.color = item.cor;
    }
    this.el.textContent = item.texto;
    this.el.style.opacity = '1';
  }

  _efeito_fade(item) {
    this.el.style.transition = 'opacity 0.5s ease';
    this.el.style.opacity = '0';
    setTimeout(() => {
      this._aplicarTexto(item);
      this.el.style.opacity = '1';
      this._agendar();
    }, 500);
  }

  _efeito_slide(item) {
    this.el.style.transition = 'transform 0.4s ease, opacity 0.4s ease';
    this.el.style.transform = 'translateY(-20px)';
    this.el.style.opacity = '0';
    setTimeout(() => {
      this._aplicarTexto(item);
      this.el.style.transform = 'translateY(20px)';
      this.el.style.opacity = '0';
      // Força reflow
      void this.el.offsetHeight;
      this.el.style.transition = 'transform 0.4s ease, opacity 0.4s ease';
      this.el.style.transform = 'translateY(0)';
      this.el.style.opacity = '1';
      this._agendar();
    }, 400);
  }

  _efeito_typewriter(item) {
    if (item.cor) this.el.style.color = item.cor;
    this.el.textContent = '';
    this.el.style.opacity = '1';
    const chars = item.texto.split('');
    let i = 0;
    const velocidade = Math.min(80, Math.floor((item.duracao || this.opcoes.intervalo) / (chars.length * 2)));

    const digitar = () => {
      if (i < chars.length) {
        this.el.textContent += chars[i++];
        this._typewriterTimer = setTimeout(digitar, velocidade);
      } else {
        this._agendar();
      }
    };
    digitar();
  }

  /** Para a rotação */
  parar() {
    clearTimeout(this.timer);
    clearTimeout(this._typewriterTimer);
  }

  /** Reinicia a rotação */
  reiniciar() {
    this.parar();
    this.indice = 0;
    this._aplicarTexto(this.textos[0]);
    if (this.textos.length > 1) {
      this._agendar();
    }
  }
}

/**
 * Inicializa todos os elementos com [data-textos] na página automaticamente.
 * Chamado via DOMContentLoaded quando o módulo é importado.
 */
function inicializarTodos() {
  document.querySelectorAll('[data-textos]').forEach((el) => {
    const textos = (() => {
      try { return JSON.parse(el.dataset.textos); } catch { return null; }
    })();
    if (textos && textos.length > 0) {
      new TextRotator(el, textos);
    }
  });
}

if (typeof window !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarTodos);
  } else {
    inicializarTodos();
  }
}

export { TextRotator, inicializarTodos };
export default TextRotator;
