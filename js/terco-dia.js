/**
 * terco-dia.js — Terço do Dia
 * Determina o mistério do rosário para o dia de hoje e exibe
 * o roteiro passo a passo para rezar o Santo Terço.
 *
 * Uso:
 *   import TercoDia from './terco-dia.js';
 *   const terco = new TercoDia();
 *   terco.render('#terco-container');
 */

const MISTERIOS = {
  gozosos: {
    nome: 'Mistérios Gozosos',
    subtitulo: 'Segunda-feira e Sábado',
    cor: '#f0e68c',
    icone: 'fa-star',
    lista: [
      'A Anunciação do Anjo Gabriel a Maria Santíssima',
      'A Visitação de Nossa Senhora a Santa Isabel',
      'O Nascimento de Jesus em Belém',
      'A Apresentação de Jesus no Templo',
      'Jesus encontrado entre os Doutores no Templo',
    ],
  },
  luminosos: {
    nome: 'Mistérios Luminosos',
    subtitulo: 'Quinta-feira',
    cor: '#add8e6',
    icone: 'fa-sun',
    lista: [
      'O Batismo de Jesus no Rio Jordão',
      'A Auto-revelação de Jesus nas Bodas de Caná',
      'O Anúncio do Reino de Deus com apelo à conversão',
      'A Transfiguração de Jesus no Monte Tabor',
      'A Instituição da Eucaristia na Última Ceia',
    ],
  },
  dolorosos: {
    nome: 'Mistérios Dolorosos',
    subtitulo: 'Terça-feira e Sexta-feira',
    cor: '#c0392b',
    icone: 'fa-cross',
    lista: [
      'A Agonia de Jesus no Jardim do Getsêmani',
      'A Flagelação de Jesus na coluna',
      'A Coroação de espinhos',
      'Jesus carregando a Cruz até o Calvário',
      'A Crucificação e Morte de Nosso Senhor Jesus Cristo',
    ],
  },
  gloriosos: {
    nome: 'Mistérios Gloriosos',
    subtitulo: 'Quarta-feira, Domingo e Sábado (alt.)',
    cor: '#f39c12',
    icone: 'fa-crown',
    lista: [
      'A Ressurreição de Jesus Cristo',
      'A Ascensão de Jesus ao Céu',
      'A Vinda do Espírito Santo no Pentecostes',
      'A Assunção de Nossa Senhora ao Céu',
      'A Coroação de Nossa Senhora como Rainha do Universo',
    ],
  },
};

/** Orações do Rosário */
const ORACOES = {
  credo: {
    titulo: 'Credo Apostólico',
    texto: 'Creio em Deus Pai todo-poderoso, criador do céu e da terra; e em Jesus Cristo, seu único Filho, Nosso Senhor; que foi concebido pelo poder do Espírito Santo; nasceu da Virgem Maria; padeceu sob Pôncio Pilatos; foi crucificado, morto e sepultado; desceu à mansão dos mortos; ressuscitou ao terceiro dia; subiu aos céus; está sentado à direita de Deus Pai todo-poderoso, donde há de vir julgar os vivos e os mortos. Creio no Espírito Santo; na Santa Igreja Católica; na comunhão dos santos; na remissão dos pecados; na ressurreição da carne; na vida eterna. Amém.',
  },
  pai_nosso: {
    titulo: 'Pai-Nosso',
    texto: 'Pai Nosso, que estais no céu, santificado seja o Vosso nome, venha a nós o Vosso reino, seja feita a Vossa vontade, assim na terra como no céu. O pão nosso de cada dia nos dai hoje, perdoai-nos as nossas ofensas, assim como nós perdoamos a quem nos tem ofendido, e não nos deixeis cair em tentação, mas livrai-nos do mal. Amém.',
  },
  ave_maria: {
    titulo: 'Ave-Maria',
    texto: 'Ave Maria, cheia de graça, o Senhor é convosco; bendita sois vós entre as mulheres e bendito é o fruto do vosso ventre, Jesus. Santa Maria, Mãe de Deus, rogai por nós pecadores, agora e na hora de nossa morte. Amém.',
  },
  gloria: {
    titulo: 'Glória ao Pai',
    texto: 'Glória ao Pai, ao Filho e ao Espírito Santo. Como era no princípio, agora e sempre, por todos os séculos dos séculos. Amém.',
  },
  salve_rainha: {
    titulo: 'Salve Rainha',
    texto: 'Salve Rainha, Mãe de misericórdia, vida, doçura, esperança nossa, salve! A Vós bradamos, os degredados filhos de Eva; a Vós suspiramos, gemendo e chorando neste vale de lágrimas. Eia pois, Advogada nossa, esses Vossos olhos misericordiosos a nós voltai; e depois deste desterro, mostrai-nos Jesus, o fruto bendito do Vosso ventre. Ó clemente, ó piedosa, ó doce sempre Virgem Maria!',
  },
};

/** Retorna o tipo de mistério para um dado dia da semana (0=Dom…6=Sáb) */
function getMisterioParaDia(diaSemana) {
  const mapa = {
    0: 'gloriosos',   // Domingo
    1: 'gozosos',     // Segunda
     2: 'dolorosos', // Terça
    3: 'gloriosos',   // Quarta
    4: 'luminosos',   // Quinta
     5: 'dolorosos', // Sexta
    6: 'gozosos',     // Sábado
  };
  return mapa[diaSemana] ?? 'gloriosos';
}

class TercoDia {
  constructor() {
    const hoje = new Date();
    const chave = getMisterioParaDia(hoje.getDay());
    this.misterio = MISTERIOS[chave];
    this.chave = chave;
  }

  /**
   * Renderiza o terço completo em um elemento do DOM.
   * @param {string|HTMLElement} seletor — CSS selector ou elemento
   */
  render(seletor) {
    const el = typeof seletor === 'string' ? document.querySelector(seletor) : seletor;
    if (!el) return;
    el.innerHTML = this._buildHTML();
  }

  _buildHTML() {
    const { nome, subtitulo, lista } = this.misterio;

    const misteriosHTML = lista.map((m, i) => `
      <div class="terco-misterio mb-4">
        <h5 class="fw-bold" style="color:var(--primary-color)">${i + 1}º Mistério</h5>
        <p class="mb-2 fst-italic">${m}</p>
        <div class="terco-oracoes ps-3 border-start border-2" style="border-color:var(--primary-color)!important">
          <p class="small mb-1"><strong>${ORACOES.pai_nosso.titulo}:</strong> ${ORACOES.pai_nosso.texto}</p>
          <p class="small mb-1 text-muted">🔁 <em>Diga 10 Ave-Marias meditando no mistério acima.</em></p>
          <p class="small mb-1"><strong>${ORACOES.ave_maria.titulo}:</strong> ${ORACOES.ave_maria.texto}</p>
          <p class="small mb-0"><strong>${ORACOES.gloria.titulo}:</strong> ${ORACOES.gloria.texto}</p>
        </div>
      </div>`).join('');

    return `
      <div class="terco-dia">
        <div class="terco-header text-center mb-4">
          <i class="fas ${this.misterio.icone} fa-2x mb-2" style="color:var(--primary-color)"></i>
          <h4 class="fw-bold mb-0">${nome}</h4>
          <p class="text-muted small">${subtitulo}</p>
        </div>

        <div class="terco-introducao mb-4">
          <h5 class="fw-bold">Como começar</h5>
          <ol class="small">
            <li>Faça o Sinal da Cruz</li>
            <li>Recite o <strong>Credo Apostólico</strong></li>
            <li>Diga um <strong>Pai-Nosso</strong></li>
            <li>Diga 3 <strong>Ave-Marias</strong> pela fé, esperança e caridade</li>
            <li>Diga o <strong>Glória ao Pai</strong></li>
          </ol>
          <details class="mt-2">
            <summary class="small text-primary" style="cursor:pointer">Ver orações iniciais completas</summary>
            <div class="mt-2 p-3 bg-light rounded small">
              <p><strong>${ORACOES.credo.titulo}:</strong><br>${ORACOES.credo.texto}</p>
              <p><strong>${ORACOES.pai_nosso.titulo}:</strong><br>${ORACOES.pai_nosso.texto}</p>
              <p><strong>${ORACOES.ave_maria.titulo}:</strong><br>${ORACOES.ave_maria.texto}</p>
              <p class="mb-0"><strong>${ORACOES.gloria.titulo}:</strong><br>${ORACOES.gloria.texto}</p>
            </div>
          </details>
        </div>

        <div class="terco-misterios mb-4">
          <h5 class="fw-bold mb-3">Os 5 Mistérios de Hoje</h5>
          ${misteriosHTML}
        </div>

        <div class="terco-encerramento p-3 bg-light rounded">
          <h5 class="fw-bold">Encerramento</h5>
          <p class="small"><strong>${ORACOES.salve_rainha.titulo}:</strong><br>${ORACOES.salve_rainha.texto}</p>
        </div>
      </div>`;
  }

  /** Retorna o nome do mistério atual */
  getNomeMisterio() {
    return this.misterio.nome;
  }

  /** Retorna a lista dos 5 mistérios do dia */
  getMisterios() {
    return this.misterio.lista;
  }
}

export { TercoDia, getMisterioParaDia, MISTERIOS, ORACOES };
export default TercoDia;
