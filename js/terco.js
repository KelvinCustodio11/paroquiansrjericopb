/**
 * Terço do Dia — Paróquia NSR Jericó/PB
 * Versão completa com:
 *  • Seção 1: Cabeçalho litúrgico + rosário interativo (SVG clicável)
 *  • Seção 2: Reflexão e intenções do dia (cards colapsáveis)
 *  • Seção 3: Catequese didática do Terço (blocos colapsáveis)
 */
(function () {
    'use strict';

    /* ── Estado global ──────────────────────────────────────────────────── */
    var _step  = 0;
    var _data  = null; /* { m, passos } */

    /* ── Orações completas ──────────────────────────────────────────────── */
    var ORACOES = {
        sinaldacruz: {
            titulo: 'Sinal da Cruz',
            texto: 'Em nome do Pai, e do Filho, e do Espírito Santo. Amém.'
        },
        credo: {
            titulo: 'Credo Apostólico',
            texto: 'Creio em Deus Pai todo-poderoso, criador do céu e da terra; e em Jesus Cristo, seu único Filho, Nosso Senhor; que foi concebido pelo poder do Espírito Santo; nasceu da Virgem Maria; padeceu sob Pôncio Pilatos, foi crucificado, morto e sepultado; desceu à mansão dos mortos; ressuscitou ao terceiro dia; subiu aos céus, está sentado à direita de Deus Pai todo-poderoso; donde há de vir a julgar os vivos e os mortos. Creio no Espírito Santo, na Santa Igreja Católica, na comunhão dos santos, na remissão dos pecados, na ressurreição da carne, na vida eterna. Amém.'
        },
        painosso: {
            titulo: 'Pai-Nosso',
            texto: 'Pai nosso que estais no céu, santificado seja o Vosso nome, venha a nós o Vosso reino, seja feita a Vossa vontade, assim na terra como no céu. O pão nosso de cada dia nos dai hoje, perdoai as nossas ofensas, assim como nós perdoamos a quem nos tem ofendido, e não nos deixeis cair em tentação, mas livrai-nos do mal. Amém.'
        },
        avemaria: {
            titulo: 'Ave-Maria',
            texto: 'Ave, Maria, cheia de graça, o Senhor é convosco. Bendita sois vós entre as mulheres, e bendito é o fruto do vosso ventre, Jesus. Santa Maria, Mãe de Deus, rogai por nós pecadores, agora e na hora de nossa morte. Amém.'
        },
        gloria: {
            titulo: 'Glória ao Pai',
            texto: 'Glória ao Pai, ao Filho e ao Espírito Santo. Assim como era no princípio, agora e sempre, por todos os séculos dos séculos. Amém.'
        },
        fatima: {
            titulo: 'Oração de Fátima',
            texto: 'Ó meu Jesus, perdoai-nos, livrai-nos do fogo do inferno, levai as almas todas para o Céu, principalmente as que mais precisarem da Vossa misericórdia. Amém.'
        },
        salverainha: {
            titulo: 'Salve Rainha',
            texto: 'Salve Rainha, Mãe de misericórdia, vida, doçura, esperança nossa, salve! A Vós bradamos, os degredados filhos de Eva; a Vós suspiramos, gemendo e chorando neste vale de lágrimas. Eia pois, Advogada nossa, esses Vossos olhos misericordiosos a nós voltai; e depois deste desterro, mostrai-nos Jesus, o fruto bendito do Vosso ventre. Ó clemente, ó piedosa, ó doce sempre Virgem Maria!'
        },
        letanias: {
            titulo: 'Oração Final',
            texto: 'Rainha do Santo Rosário, rogai por nós. São José, rogai por nós. Santo Anjo da Guarda, rogai por nós. Santos Apóstolos Pedro e Paulo, rogai por nós. Todos os Santos e Santas de Deus, rogai por nós. Que possamos ser dignos das promessas de Cristo. Senhor, dai-nos a paz. Amém.'
        }
    };

    /* ── Dados dos Mistérios ─────────────────────────────────────────────── */
    var MISTERIOS = {
        gozosos: {
            nome: 'Mistérios Gozosos', subtitulo: 'Segunda-feira e Sábado',
            icone: 'fa-star', cor: '#b8941a',
            reflexao_geral: 'Hoje contemplamos a alegria da salvação: desde o "sim" de Maria até a sabedoria de Jesus no Templo. Que possamos receber com alegria a Palavra de Deus em nossas vidas.',
            lista: [
                {
                    titulo: '1.º Mistério — A Anunciação',
                    texto: 'O Anjo Gabriel anuncia a Maria que ela conceberá o Filho de Deus pelo poder do Espírito Santo. Maria responde com plena confiança: "Eis aqui a serva do Senhor; faça-se em mim segundo a tua palavra." (Lc 1,38)',
                    reflexao: 'Senhor, concedei-nos a humildade e docilidade de Maria para acolhermos a Vossa vontade em nossas vidas, mesmo quando não a compreendemos totalmente.',
                    fruto: 'Humildade'
                },
                {
                    titulo: '2.º Mistério — A Visitação',
                    texto: 'Maria parte apressada às montanhas para visitar sua prima Isabel. Ao ouvir a saudação de Maria, o bebê saltou de alegria no ventre de Isabel, que exclamou: "Bendita és tu entre as mulheres, e bendito é o fruto do teu ventre!" (Lc 1,42)',
                    reflexao: 'Senhor, ensinai-nos a levar Cristo a todos que encontramos, como Maria levou Jesus na Visitação, sendo instrumentos de bênção e alegria para as famílias.',
                    fruto: 'Amor ao próximo'
                },
                {
                    titulo: '3.º Mistério — O Nascimento de Jesus',
                    texto: 'Jesus nasce em Belém numa estrebaria, envolto em faixas e deitado numa manjedoura, pois não havia lugar para eles na hospedaria. Os anjos anunciam: "Glória a Deus no mais alto dos céus e paz na terra aos homens que ele ama!" (Lc 2,14)',
                    reflexao: 'Ó Jesus, nascido pobre e humilde, ajudai-nos a encontrar-Vos nas pessoas simples e necessitadas, e a acolher-Vos sempre em nossas almas.',
                    fruto: 'Pobreza de espírito'
                },
                {
                    titulo: '4.º Mistério — A Apresentação no Templo',
                    texto: 'Maria e José apresentam o Menino Jesus no Templo, cumprindo a Lei. O ancião Simeão, guiado pelo Espírito Santo, toma o menino nos braços e proclama: "Agora, Senhor, podeis deixar vosso servo partir em paz." (Lc 2,29)',
                    reflexao: 'Senhor, como Simeão esperava o Messias, que possamos esperar com fé e paciência o cumprimento das Vossas promessas em nossas vidas.',
                    fruto: 'Espírito de sacrifício'
                },
                {
                    titulo: '5.º Mistério — Jesus no Templo',
                    texto: 'Jesus, com doze anos, fica no Templo de Jerusalém conversando com os doutores, que ficavam admirados de sua inteligência e respostas. Maria diz: "Filho, por que nos fizeste isso?" E Ele responde: "Não sabíeis que devo ocupar-me das coisas de meu Pai?" (Lc 2,48-49)',
                    reflexao: 'Ó Jesus Mestre, inflamai em nossos corações o desejo de conhecer-Vos, de aprender Vossa Palavra e de estar sempre em Vossa presença.',
                    fruto: 'Busca de Deus'
                }
            ]
        },
        luminosos: {
            nome: 'Mistérios Luminosos', subtitulo: 'Quinta-feira',
            icone: 'fa-sun', cor: '#b07000',
            reflexao_geral: 'Contemplamos hoje a vida pública de Jesus: sua missão, seus milagres e sua entrega total por nós. Que a Sua luz ilumine cada passo de nosso caminho.',
            lista: [
                {
                    titulo: '1.º Mistério — O Batismo no Jordão',
                    texto: 'Jesus vai ao Rio Jordão e pede a João que O batize. O céu se abre e o Espírito Santo desce sobre Ele em forma de pomba. Uma voz do céu proclama: "Tu és o meu Filho amado, em Ti me comprazo." (Lc 3,22)',
                    reflexao: 'Senhor, em nosso Batismo também fomos chamados de filhos de Deus. Que vivamos com dignidade este dom, sendo luz no mundo como Vós nos pedistes.',
                    fruto: 'Abertura ao Espírito Santo'
                },
                {
                    titulo: '2.º Mistério — As Bodas de Caná',
                    texto: 'Num casamento em Caná da Galileia, Maria diz a Jesus: "Eles não têm vinho." Então Jesus manda encher seis talhas de água e as transforma em vinho excelente. Foi o seu primeiro sinal, e os discípulos creram nEle. (Jo 2,1-11)',
                    reflexao: 'Maria Santíssima, intercedei por nós junto ao Seu Filho divino, especialmente em nossos momentos de necessidade. Que, como em Caná, Jesus transforme nossas carências em abundância.',
                    fruto: 'Confiança em Maria'
                },
                {
                    titulo: '3.º Mistério — O Anúncio do Reino',
                    texto: 'Jesus percorre toda a Galileia, ensinando nas sinagogas e proclamando a Boa Nova do Reino: "Convertei-vos, porque o Reino dos Céus está próximo!" (Mt 4,17). Chama os primeiros discípulos e eles imediatamente O seguem.',
                    reflexao: 'Senhor, dai-nos coragem para anunciar o Evangelho com palavras e obras, e disponibilidade para deixar tudo e seguir-Vos com o coração livre.',
                    fruto: 'Conversão e missão'
                },
                {
                    titulo: '4.º Mistério — A Transfiguração',
                    texto: 'Jesus leva Pedro, Tiago e João ao Monte Tabor. Ali Ele se transfigura diante deles: seu rosto brilha como o sol e suas vestes tornam-se brancas como luz. Uma voz do céu diz: "Este é o meu Filho amado, escutai-O!" (Mt 17,5)',
                    reflexao: 'Ó Jesus transfigurado, que a contemplação da Vossa glória fortaleça nossa fé nos momentos de trevas, lembrando que a ressurreição aguarda todos os que creem.',
                    fruto: 'Desejo do céu'
                },
                {
                    titulo: '5.º Mistério — A Instituição da Eucaristia',
                    texto: 'Na Última Ceia, Jesus toma o pão, dá graças, parte-o e diz: "Isto é o meu Corpo, que é dado por vós. Fazei isto em memória de mim." Do mesmo modo, tomou o cálice e disse: "Este cálice é a nova aliança no meu Sangue." (Lc 22,19-20)',
                    reflexao: 'Jesus Eucarístico, que cada Missa e cada comunhão aprofundem em nós o amor por Vós e a disposição de nos darmos, como Vós, pelos irmãos.',
                    fruto: 'Amor à Eucaristia'
                }
            ]
        },
        dolorosos: {
            nome: 'Mistérios Dolorosos', subtitulo: 'Terça-feira e Sexta-feira',
            icone: 'fa-cross', cor: '#8b1a1a',
            reflexao_geral: 'Contemplamos hoje a Paixão de Cristo. Em cada sofrimento nosso, Jesus já passou por ali. Que possamos unir nossas cruzes à Cruz de Cristo, transformando a dor em redenção.',
            lista: [
                {
                    titulo: '1.º Mistério — A Agonia no Getsêmani',
                    texto: 'Jesus vai ao Jardim do Getsêmani e, prostrado, ora: "Pai, se queres, afasta de mim este cálice! Todavia, não se faça a minha vontade, mas a tua." (Lc 22,42). Suando sangue, Ele permanece em oração enquanto os discípulos dormem.',
                    reflexao: 'Senhor, ensinai-nos a rezar nos momentos de angústia, a confiar na Vossa vontade mesmo quando o coração treme, e a permanecer acordados em espírito.',
                    fruto: 'Contrição e docilidade'
                },
                {
                    titulo: '2.º Mistério — A Flagelação',
                    texto: 'Pilatos, querendo satisfazer a multidão, manda flagelar Jesus. Os soldados O açoitam brutalmente. Jesus suporta tudo em silêncio, oferecendo Seu sofrimento pela salvação de nossas almas.',
                    reflexao: 'Ó Jesus flagelado, pelo Vosso sofrimento, purificai-nos de nossos pecados. Que a Vossa paciência nos ensine a suportar com fé os sofrimentos desta vida.',
                    fruto: 'Mortificação e pureza'
                },
                {
                    titulo: '3.º Mistério — A Coroação de Espinhos',
                    texto: 'Os soldados trançam uma coroa de espinhos e a colocam na cabeça de Jesus, vestem-nO de púrpura e O chamam por escárnio de "Rei dos Judeus". Pilatos O apresenta ao povo: "Ecce homo — Eis o homem!" (Jo 19,5)',
                    reflexao: 'Ó Jesus coroado de espinhos, oferecestes Vossa humilhação pelos nossos orgulhos e vaidades. Que possamos aceitar as humilhações da vida com humildade e serenidade.',
                    fruto: 'Humildade'
                },
                {
                    titulo: '4.º Mistério — O Caminho da Cruz',
                    texto: 'Jesus carrega a Cruz pesada pelo caminho do Calvário. Cai três vezes, encontra Sua Mãe, Simão de Cirene O ajuda, Verônica limpa Seu rosto. Jesus oferece cada passo como sacrifício pela nossa redenção.',
                    reflexao: 'Senhor, quando a nossa cruz pesar demais, dai-nos a graça de encontrar em Vós a força para seguir em frente, sabendo que nunca estamos sozinhos no caminho.',
                    fruto: 'Paciência nas dificuldades'
                },
                {
                    titulo: '5.º Mistério — A Crucificação e Morte',
                    texto: 'Jesus é crucificado no Calvário entre dois ladrões. Perdoa a seus algozes: "Pai, perdoai-lhes, porque não sabem o que fazem." (Lc 23,34). Ao lado de Maria e do discípulo amado, Jesus entrega o espírito ao Pai, abrindo-nos as portas da salvação.',
                    reflexao: 'Ó Jesus crucificado, que Vosso amor misericordioso nos transforme, nos faça instrumentos de perdão e nos dê a esperança certa da ressurreição.',
                    fruto: 'Salvação e perdão'
                }
            ]
        },
        gloriosos: {
            nome: 'Mistérios Gloriosos', subtitulo: 'Quarta-feira, Domingo e Sábado (alt.)',
            icone: 'fa-crown', cor: '#6a0dad',
            reflexao_geral: 'Contemplamos hoje o triunfo do amor de Deus: a Ressurreição, a Ascensão, o Pentecostes e a glória de Maria. Nossa fé não é em um Deus morto, mas no Deus vivo que nos promete a vida eterna.',
            lista: [
                {
                    titulo: '1.º Mistério — A Ressurreição',
                    texto: 'No terceiro dia após a crucificação, o túmulo está vazio. Jesus ressuscitado aparece primeiro a Maria Madalena e depois aos discípulos, mostrando as chagas e dizendo: "A paz esteja convosco!" (Jo 20,19). A morte foi vencida!',
                    reflexao: 'Ó Jesus ressuscitado, renovai em nós a esperança da vida eterna. Que a certeza da Ressurreição nos liberte do medo da morte e nos encha de alegria pascal.',
                    fruto: 'Fé e esperança'
                },
                {
                    titulo: '2.º Mistério — A Ascensão',
                    texto: 'Quarenta dias após a Ressurreição, Jesus leva os discípulos ao Monte das Oliveiras e, diante deles, sobe ao céu enquanto os abençoa. Os anjos dizem: "Este Jesus que foi arrebatado de vós voltará do mesmo modo." (At 1,11)',
                    reflexao: 'Senhor que subistes ao céu, elevai nossos corações acima das coisas terrenas. Que vivamos com olhos voltados para o céu, nossa verdadeira pátria.',
                    fruto: 'Desejo das coisas celestiais'
                },
                {
                    titulo: '3.º Mistério — O Pentecostes',
                    texto: 'Os apóstolos reunidos com Maria oram quando, de repente, um vento impetuoso enche a casa e línguas de fogo pousam sobre cada um. Todos são repletos do Espírito Santo e começam a pregar corajosamente. A Igreja nasce! (At 2,1-4)',
                    reflexao: 'Vem, Espírito Santo! Renova em nós os dons que recebemos no Batismo e na Crisma. Que sejamos corajosos testemunhos do Evangelho no mundo de hoje.',
                    fruto: 'Dons do Espírito Santo'
                },
                {
                    titulo: '4.º Mistério — A Assunção de Maria',
                    texto: 'Ao fim de sua vida terrena, Maria Santíssima é elevada ao céu em corpo e alma pelo poder de Deus. Ela que sempre disse "sim" a Deus recebe agora o prêmio prometido a todos os que amam o Senhor: a glória eterna.',
                    reflexao: 'Maria Assunta, sois nossa mãe e nossa esperança. Intercedei por nós nesta peregrinação terrena para que um dia possamos estar convosco na glória do céu.',
                    fruto: 'Graça de uma boa morte'
                },
                {
                    titulo: '5.º Mistério — A Coroação de Maria',
                    texto: 'No céu, Maria é coroada Rainha do universo, dos anjos e dos santos, sentada à direita do Filho. Ela intercede continuamente por nós diante de Deus, como nossa mãe e advogada. "E um grande sinal apareceu no céu: uma mulher vestida de sol." (Ap 12,1)',
                    reflexao: 'Ó Rainha do Rosário, reinai em nossos corações, famílias e comunidade. Que sob o Vosso manto encontremos proteção, consolo e o caminho seguro para Deus.',
                    fruto: 'Confiança em Maria'
                }
            ]
        }
    };

    /* ── Utilitários ────────────────────────────────────────────────────── */
    function esc(s) {
        return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ── Sequência de passos do terço ───────────────────────────────────── */
    /*
     * Sequência correta (80 passos):
     *  0  Sinal da Cruz  — crucifixo (conta "cruz", badge 1/2)
     *  1  Credo          — crucifixo (mesma conta, badge 2/2)
     *  2  Pai-Nosso      — g0
     *  3  Ave-Maria 1    — p1 (pela Fé)
     *  4  Ave-Maria 2    — p2 (pela Esperança)
     *  5  Ave-Maria 3    — p3 (pela Caridade)
     *  6  Glória ao Pai  — p3
     *  7  Oração Fátima  — p3
     *  Por década (d=0..4):
     *   BASE+d*14+ 0  Anúncio do Mistério  (conta gd d)
     *   BASE+d*14+ 1  Pai-Nosso            (conta gd d)
     *   BASE+d*14+ 2  Ave-Maria 1..10
     *   ...
     *   BASE+d*14+11  Ave-Maria 10
     *   BASE+d*14+12  Glória ao Pai        (conta gd d)
     *   BASE+d*14+13  Oração de Fátima     (conta gd d)
     *  70  Salve Rainha  — medal
     *  71  Oração Final  — medal
     */
    function buildPassos(m) {
        var BASE = 8;
        var PER  = 14; /* por década */
        var passos = [];
        /* Abertura */
        passos.push({ tipo: 'oracao', key: 'sinaldacruz', conta: 'cruz',
            badge: '1/2', badgeTip: 'Sinal da Cruz — 1.ª de 2 orações no Crucifixo' });
        passos.push({ tipo: 'oracao', key: 'credo', conta: 'cruz',
            badge: '2/2', badgeTip: 'Credo — 2.ª de 2 orações no Crucifixo' });
        passos.push({ tipo: 'oracao', key: 'painosso', conta: 'g0',
            label: 'Abertura do Terço' });
        /* Layout rabicho (de cima pra baixo): medal→p3→p2→p1→g0→cruz
           Oração sobe da cruz: cruz→g0(PN)→p1(1ªAve)→p2→p3(3ªAve)→medal */
        passos.push({ tipo: 'oracao', key: 'avemaria', conta: 'p1',
            badge: '1/3', label: 'pela Fé' });
        passos.push({ tipo: 'oracao', key: 'avemaria', conta: 'p2',
            badge: '2/3', label: 'pela Esperança' });
        passos.push({ tipo: 'oracao', key: 'avemaria', conta: 'p3',
            badge: '3/3', label: 'pela Caridade' });
        passos.push({ tipo: 'oracao', key: 'gloria', conta: 'p3',
            label: 'Após as 3 Ave-Marias iniciais' });
        passos.push({ tipo: 'oracao', key: 'fatima', conta: 'p3',
            label: 'Após o Glória inicial' });
        /* 5 Décadas */
        for (var d = 0; d < 5; d++) {
            passos.push({ tipo: 'misterio', idx: d, conta: 'gd' + d, decada: d + 1 });
            passos.push({ tipo: 'oracao', key: 'painosso', conta: 'gd' + d,
                label: (d + 1) + '.ª Dezena' });
            for (var a = 0; a < 10; a++) {
                passos.push({ tipo: 'oracao', key: 'avemaria',
                    conta: 'a' + d + '_' + a,
                    badge: (a + 1) + '/10',
                    label: (a + 1) + '.ª Ave-Maria — ' + (d + 1) + '.ª Dezena' });
            }
            /* Glória e Fátima ficam na ÚLTIMA Ave-Maria da dezena (a{d}_9),
               pois não voltamos ao Pai-Nosso — continuamos à frente */
            passos.push({ tipo: 'oracao', key: 'gloria', conta: 'a' + d + '_9',
                badge: '1/2', label: 'Glória — ' + (d + 1) + '.ª Dezena' });
            passos.push({ tipo: 'oracao', key: 'fatima', conta: 'a' + d + '_9',
                badge: '2/2', label: 'Fátima — ' + (d + 1) + '.ª Dezena' });
        }
        /* Encerramento */
        passos.push({ tipo: 'oracao', key: 'salverainha', conta: 'medalha' });
        passos.push({ tipo: 'oracao', key: 'letanias',    conta: 'medalha' });
        return passos;
    }

    /* ── SVG do rosário ─────────────────────────────────────────────────── */
    /*
     * Layout realista: anel oval com rabicho descendo para o crucifixo.
     * - Cordão escuro (como fio de seda preto dos terços tradicionais)
     * - Contas com gradiente pérola / ouro 3D
     * - Área de toque invisível r+12 em cada conta
     * - Badges de sub-orações nas contas multi-oração
     * - Numeração suave das décadas dentro do anel
     */
    function buildSVG() {
        /*
         * Rosário em formato de coração.
         * Geometria: dois círculos raio R, centros (CX±D, CY_c),
         * intersecção exata em topo=(CX, CY_c-h) e medal=(CX, CY_c+h).
         * R=130 garante ~7 px de gap visível entre Aves.
         * Rabicho (top→bottom): medal → 3Aves(p3→p2→p1) → g0(PN) → cruz.
         * Oração (bottom→top): cruz→g0→p1→p2→p3→medal→anel.
         */
        var W = 300, H_SVG = 508;
        var CX = 150;
        var R    = 130;
        var D    = 14;
        var CY_c = 163;
        var h    = Math.sqrt(R * R - D * D);   /* ≈ 129.2 */

        var MEDX = CX;
        var MEDY = Math.round(CY_c + h);        /* ≈ 292 */

        var LCx  = CX - D;   /* 136 */
        var RCx  = CX + D;   /* 164 */

        /* Ângulos das junções */
        var angMedR = Math.atan2(MEDY - CY_c, MEDX - RCx);
        var angMedL = Math.atan2(MEDY - CY_c, MEDX - LCx);
        var span    = 2 * Math.PI - 2 * angMedL;

        /* Rabicho: medal → p3 → p2 → p1 → g0(PN) → cruz */
        var p3Y      = MEDY + 30;   /* 3ª Ave — mais perto da medal   */
        var p2Y      = p3Y  + 24;   /* 2ª Ave                         */
        var p1Y      = p2Y  + 24;   /* 1ª Ave — mais perto do PN      */
        var g0Y      = p1Y  + 34;   /* Pai-Nosso — mais perto da cruz */
        var cruzTopY = g0Y  + 28;
        var cruzH    = 64;
        var cruzW    = 40;
        var armY     = cruzTopY + Math.round(cruzH * 0.28);
        var armH     = Math.round(cruzH * 0.14);

        var CORD = '#2a1800';
        var CW   = 1.5;
        var ORO  = '#c9a45a';
        var BASE = 8, PER = 14;
        var H    = '';

        /* ── helpers ──────────────────────────────────────────────────── */
        function fio(x1, y1, x2, y2) {
            return '<line x1="' + x1 + '" y1="' + y1
                 + '" x2="' + x2 + '" y2="' + y2
                 + '" stroke="' + CORD + '" stroke-width="' + CW
                 + '" stroke-linecap="round"/>';
        }
        function conta(id, cx, cy, r, grad, cls, nav, title) {
            var oc = (nav >= 0) ? ' onclick="Terco.irPara(' + nav + ')"' : '';
            var cu = (nav >= 0) ? 'pointer' : 'default';
            var tk = (nav >= 0)
                ? ' tabindex="0" onkeydown="if(event.key===\'Enter\'||event.key===\' \')Terco.irPara(' + nav + ')"'
                : '';
            return '<g id="' + id + '" class="rc-bead ' + cls + '" data-g="' + grad + '"' + oc
                 + ' style="cursor:' + cu + ';" role="' + (nav >= 0 ? 'button' : 'img') + '"' + tk + ' >'
                 + '<title>' + title + '</title>'
                 + '<circle cx="' + cx + '" cy="' + cy + '" r="' + (r + 13) + '" fill="transparent"/>'
                 + '<circle class="rc-vis" cx="' + cx + '" cy="' + cy + '" r="' + r
                 + '" fill="url(#' + grad + ')" filter="url(#f-sh)"/>'
                 + '</g>';
        }

        /* ── 55 posições do anel ───────────────────────────────────────── */
        /* ring55[0..26]  arco direito  (medal→topo)                       */
        /* ring55[27..54] arco esquerdo (topo→medal)                       */
        var ring55 = [];
        var i, t;
        for (i = 0; i < 27; i++) {
            t = angMedR - span * (i + 0.5) / 27;
            ring55.push({ x: Math.round(RCx + R * Math.cos(t)),
                           y: Math.round(CY_c + R * Math.sin(t)) });
        }
        for (i = 0; i < 28; i++) {
            t = angMedL + span * (1 - (i + 0.5) / 28);
            ring55.push({ x: Math.round(LCx + R * Math.cos(t)),
                           y: Math.round(CY_c + R * Math.sin(t)) });
        }

        /* ── SVG root ─────────────────────────────────────────────────── */
        H += '<svg id="rosario-svg" viewBox="0 0 ' + W + ' ' + H_SVG + '"'
           + ' xmlns="http://www.w3.org/2000/svg"'
           + ' style="width:100%;max-width:290px;display:block;margin:0 auto;"'
           + ' role="img" aria-label="Rosário interativo">';

        /* ── Defs ─────────────────────────────────────────────────────── */
        H += '<defs>'
           + '<radialGradient id="rg-am" cx="33%" cy="27%" r="70%">'
           + '<stop offset="0%" stop-color="#fff9f0"/>'
           + '<stop offset="48%" stop-color="#dfc99e"/>'
           + '<stop offset="100%" stop-color="#9e7238"/>'
           + '</radialGradient>'
           + '<radialGradient id="rg-pn" cx="33%" cy="25%" r="70%">'
           + '<stop offset="0%" stop-color="#fffbe0"/>'
           + '<stop offset="42%" stop-color="#d9a714"/>'
           + '<stop offset="100%" stop-color="#5a2e00"/>'
           + '</radialGradient>'
           + '<radialGradient id="rg-sp" cx="33%" cy="25%" r="70%">'
           + '<stop offset="0%" stop-color="#f8d870"/>'
           + '<stop offset="45%" stop-color="#a06210"/>'
           + '<stop offset="100%" stop-color="#3c1a00"/>'
           + '</radialGradient>'
           + '<radialGradient id="rg-wood" cx="35%" cy="25%" r="75%">'
           + '<stop offset="0%" stop-color="#d0925e"/>'
           + '<stop offset="60%" stop-color="#7a3b10"/>'
           + '<stop offset="100%" stop-color="#3a1600"/>'
           + '</radialGradient>'
           + '<filter id="f-sh" x="-50%" y="-50%" width="200%" height="200%">'
           + '<feDropShadow dx="0" dy="1.5" stdDeviation="1.6" flood-color="rgba(0,0,0,.38)"/>'
           + '</filter>'
           + '<filter id="f-gw" x="-80%" y="-80%" width="260%" height="260%">'
           + '<feGaussianBlur stdDeviation="4.5" result="b"/>'
           + '<feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>'
           + '</filter>'
           + '</defs>';

        /* ── Camada 1: halo ──────────────────────────────────────────── */
        H += '<ellipse cx="' + CX + '" cy="' + CY_c + '" rx="' + (R + D + 16)
           + '" ry="' + (h + 16) + '" fill="rgba(212,180,120,.06)" stroke="none"/>';

        /* ── Camada 2: cordão do anel ────────────────────────────────── */
        H += fio(MEDX, MEDY, ring55[0].x, ring55[0].y);
        for (i = 0; i < 54; i++) {
            H += fio(ring55[i].x, ring55[i].y, ring55[i + 1].x, ring55[i + 1].y);
        }
        H += fio(ring55[54].x, ring55[54].y, MEDX, MEDY);

        /* ── Camada 3: cordão do rabicho (medal→p3→p2→p1→g0→cruz) ───── */
        H += fio(MEDX, MEDY + 15,  MEDX, p3Y -  8);
        H += fio(MEDX, p3Y +  8,   MEDX, p2Y -  8);
        H += fio(MEDX, p2Y +  8,   MEDX, p1Y -  8);
        H += fio(MEDX, p1Y +  8,   MEDX, g0Y - 11);
        H += fio(MEDX, g0Y + 11,   MEDX, cruzTopY);

        /* ── Camada 4: crucifixo ─────────────────────────────────────── */
        H += '<g id="rc-cruz" class="rc-bead rc-special" data-g="rg-sp"'
           + ' onclick="Terco.irPara(0)" style="cursor:pointer;" role="button" tabindex="0"'
           + ' onkeydown="if(event.key===\'Enter\'||event.key===\' \')Terco.irPara(0)">'
           + '<title>Crucifixo — Sinal da Cruz e Credo</title>'
           + '<rect x="' + (MEDX - 24) + '" y="' + (cruzTopY - 4)
           + '" width="48" height="' + (cruzH + 8) + '" fill="transparent"/>'
           + '<rect x="' + (MEDX - cruzW / 2 - 2) + '" y="' + (cruzTopY - 2)
           + '" width="' + (cruzW + 4) + '" height="' + (cruzH + 4)
           + '" rx="4" fill="none" stroke="#d8d0b0" stroke-width="2" filter="url(#f-sh)"/>'
           + '<rect class="rc-cruz-v"'
           + ' x="' + (MEDX - 5) + '" y="' + cruzTopY
           + '" width="10" height="' + cruzH + '"'
           + ' rx="3" fill="url(#rg-wood)" stroke="#b89060" stroke-width="1"/>'
           + '<rect class="rc-cruz-h"'
           + ' x="' + (MEDX - cruzW / 2) + '" y="' + armY
           + '" width="' + cruzW + '" height="' + armH + '"'
           + ' rx="3" fill="url(#rg-wood)" stroke="#b89060" stroke-width="1"/>'
           + '<ellipse cx="' + MEDX + '" cy="' + (armY - 7)
           + '" rx="3.5" ry="4" fill="#f2dfc0" opacity=".92"/>'
           + '<ellipse cx="' + MEDX + '" cy="' + (armY + armH / 2 + 4)
           + '" rx="3" ry="6.5" fill="#f2dfc0" opacity=".92"/>'
           + '<line x1="' + (MEDX - cruzW / 2 + 4) + '" y1="' + (armY + armH / 2)
           + '" x2="' + (MEDX - 3) + '" y2="' + (armY + armH / 2)
           + '" stroke="#f2dfc0" stroke-width="2" stroke-linecap="round" opacity=".92"/>'
           + '<line x1="' + (MEDX + cruzW / 2 - 4) + '" y1="' + (armY + armH / 2)
           + '" x2="' + (MEDX + 3) + '" y2="' + (armY + armH / 2)
           + '" stroke="#f2dfc0" stroke-width="2" stroke-linecap="round" opacity=".92"/>'
           + '<text x="' + MEDX + '" y="' + (cruzTopY + 10)
           + '" text-anchor="middle" font-size="5" font-weight="700"'
           + ' fill="#e0c878" letter-spacing=".5" pointer-events="none">INRI</text>'
           + '</g>';

        /* ── Camada 5: Aves do anel ───────────────────────────────────── */
        for (var d = 0; d < 5; d++) {
            for (var a = 0; a < 10; a++) {
                var ri = d * 11 + 1 + a;
                H += conta('rc-a' + d + '_' + a,
                    ring55[ri].x, ring55[ri].y, 5, 'rg-am', 'rc-am',
                    BASE + d * PER + 2 + a,
                    (a + 1) + '.ª Ave-Maria — ' + (d + 1) + '.ª Dezena');
            }
        }

        /* ── Camada 6: Pai-Nossos do anel ─────────────────────────────── */
        for (var d = 0; d < 5; d++) {
            var ri = d * 11;
            H += conta('rc-gd' + d, ring55[ri].x, ring55[ri].y,
                10, 'rg-pn', 'rc-pn', BASE + d * PER,
                'Pai-Nosso — ' + (d + 1) + '.ª Dezena');
            H += '<text x="' + (ring55[ri].x + 13) + '" y="' + (ring55[ri].y - 12)
               + '" text-anchor="middle" font-size="8" font-weight="700"'
               + ' fill="' + ORO + '" pointer-events="none">' + (d + 1) + '</text>';
        }

        /* ── Camada 7: rabicho — contas (medal→p3→p2→p1→g0→cruz) ──────── */
        /* 3 Aves do rabicho: p3=topo(perto medal), p1=base(perto g0) */
        var avData = [
            { id: 'rc-p3', y: p3Y, nav: 5, t: 'Ave-Maria 3/3 — pela Caridade (próx. medal)' },
            { id: 'rc-p2', y: p2Y, nav: 4, t: 'Ave-Maria 2/3 — pela Esperança' },
            { id: 'rc-p1', y: p1Y, nav: 3, t: 'Ave-Maria 1/3 — pela Fé (próx. Pai-Nosso)' }
        ];
        avData.forEach(function (av) {
            H += conta(av.id, MEDX, av.y, 8, 'rg-am', 'rc-am', av.nav, av.t);
        });
        /* Pai-Nosso de abertura — perto da cruz */
        H += conta('rc-g0', MEDX, g0Y, 10, 'rg-pn', 'rc-pn', 2,
            'Pai-Nosso — abertura do Terço (próx. cruz)');

        /* ── Camada 8: medal ─────────────────────────────────────────── */
        H += conta('rc-medalha', MEDX, MEDY, 14, 'rg-sp', 'rc-special', 70,
            'Medal — Salve Rainha e Oração Final');
        H += '<text x="' + MEDX + '" y="' + (MEDY + 5) + '"'
           + ' text-anchor="middle" font-size="12" fill="#fff8d8"'
           + ' font-family="Georgia,serif" font-weight="bold" pointer-events="none">✦</text>';

        /* ── Numeração tênue das décadas ─────────────────────────────── */
        for (var d = 0; d < 5; d++) {
            var ri0 = d * 11, rim = d * 11 + 5;
            var lx  = Math.round((ring55[ri0].x + ring55[rim].x) / 2);
            var ly  = Math.round((ring55[ri0].y + ring55[rim].y) / 2);
            /* empurra para dentro do anel */
            var vx = lx - CX, vy = ly - CY_c;
            var vl = Math.sqrt(vx * vx + vy * vy) || 1;
            lx = Math.round(lx - vx / vl * 22);
            ly = Math.round(ly - vy / vl * 22);
            H += '<text x="' + lx + '" y="' + (ly + 5) + '" text-anchor="middle"'
               + ' font-size="13" font-weight="700" fill="rgba(160,100,20,.12)"'
               + ' pointer-events="none">' + (d + 1) + '</text>';
        }

        H += '</svg>';

        /* ── Legenda ─────────────────────────────────────────────────── */
        H += '<div style="margin-top:8px;font-size:.63rem;color:#999;text-align:center;line-height:2.2;">'
           + '<span style="display:inline-flex;align-items:center;gap:3px;margin-right:8px;">'
           + '<span style="width:11px;height:11px;border-radius:50%;background:linear-gradient(135deg,#fffbe0,#d9a714);display:inline-block;"></span>Pai-Nosso</span>'
           + '<span style="display:inline-flex;align-items:center;gap:3px;margin-right:8px;">'
           + '<span style="width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,#fff9f0,#dfc99e);display:inline-block;"></span>Ave-Maria</span>'
           + '<span style="display:inline-flex;align-items:center;gap:3px;">'
           + '<span style="width:10px;height:10px;border-radius:50%;background:#f0c040;box-shadow:0 0 6px #f0c040;display:inline-block;"></span>Ativa</span>'
           + '<br>Toque em qualquer conta para navegar.</div>';

        return H;
    }

    /* ── Destaque no SVG ────────────────────────────────────────────────── */
    function highlightBead(contaId) {
        /* Reseta todas as contas para a cor original */
        document.querySelectorAll('#rosario-svg .rc-bead').forEach(function (g) {
            var grad = g.getAttribute('data-g') || 'rg-am';
            g.querySelectorAll('.rc-vis').forEach(function (v) {
                v.setAttribute('fill', 'url(#' + grad + ')');
                v.setAttribute('filter', 'url(#f-sh)');
                v.setAttribute('stroke', 'none');
                v.setAttribute('stroke-width', '0');
            });
            if (g.id === 'rc-cruz') {
                g.querySelectorAll('.rc-cruz-v, .rc-cruz-h').forEach(function (r) {
                    r.setAttribute('fill', 'url(#rg-wood)');
                    r.setAttribute('stroke', '#c8c8c8');
                    r.setAttribute('filter', 'url(#f-sh)');
                });
            }
        });
        if (!contaId) return;
        var bead = document.getElementById('rc-' + contaId);
        if (!bead) return;
        /* Ativo: brilho dourado intenso + borda branca/ouro */
        if (contaId === 'cruz') {
            bead.querySelectorAll('.rc-cruz-v, .rc-cruz-h').forEach(function (r) {
                r.setAttribute('fill', '#f0c040');
                r.setAttribute('stroke', '#fff');
                r.setAttribute('filter', 'url(#f-gw)');
            });
        } else {
            var vis = bead.querySelector('.rc-vis');
            if (vis) {
                vis.setAttribute('fill', '#f0c040');
                vis.setAttribute('stroke', '#fff');
                vis.setAttribute('stroke-width', '2.5');
                vis.setAttribute('filter', 'url(#f-gw)');
            }
        }
    }

    /* ── Render de um passo ──────────────────────────────────────────────── */
    function renderPasso(idx) {
        if (!_data) return;
        var passos = _data.passos;
        var m = _data.m;
        if (idx < 0) idx = 0;
        if (idx >= passos.length) idx = passos.length - 1;
        _step = idx;

        var passo = passos[idx];
        var el = document.getElementById('terco-passo-conteudo');
        if (!el) return;

        var total = passos.length;
        var pct   = Math.round((idx / (total - 1)) * 100);
        var html  = '';

        /* Barra de progresso */
        html += '<div style="margin-bottom:16px;">';
        /* Contexto textual sem badge — mostra posição clara e legível */
        var ctxLabel = '';
        if (passo.label)              ctxLabel = passo.label;
        else if (passo.tipo === 'misterio') ctxLabel = passo.decada + '.ª Dezena — contemple o mistério';
        var ctxHtml = ctxLabel
            ? '<span style="font-size:.78rem;color:var(--primary-color);font-weight:600;">' + esc(ctxLabel) + '</span> · '
            : '';
        html += '<div style="display:flex;justify-content:space-between;align-items:center;font-size:.76rem;color:#999;margin-bottom:5px;gap:6px;flex-wrap:wrap;">';
        html += '<span>' + ctxHtml + 'Passo ' + (idx + 1) + ' de ' + total + '</span><span>' + pct + '%</span></div>';
        html += '<div style="background:#eee;border-radius:6px;height:7px;">';
        html += '<div style="background:var(--primary-color);height:7px;border-radius:6px;width:' + pct + '%;transition:width .4s;"></div></div>';
        html += '</div>';

        if (passo.tipo === 'misterio') {
            var mis = m.lista[passo.idx];
            html += '<div style="background:rgba(var(--primary-rgb,172,170,89),.07);border-left:4px solid var(--primary-color);border-radius:8px;padding:20px 22px;margin-bottom:14px;">';
            html += '<p style="font-size:.72rem;text-transform:uppercase;letter-spacing:.09em;color:#999;margin:0 0 5px;">✨ Contemple antes do Pai-Nosso</p>';
            html += '<h4 style="color:var(--primary-color);font-weight:700;margin:0 0 10px;font-size:1.05rem;">' + esc(mis.titulo) + '</h4>';
            html += '<p style="margin:0 0 12px;line-height:1.8;font-size:.95rem;color:#333;">' + esc(mis.texto) + '</p>';
            html += '<div style="background:rgba(0,0,0,.03);border-radius:6px;padding:12px 14px;">';
            html += '<p style="margin:0 0 6px;font-size:.85rem;font-style:italic;color:#555;">' + esc(mis.reflexao) + '</p>';
            html += '<span style="font-size:.72rem;background:var(--primary-color);color:#fff;padding:2px 10px;border-radius:20px;">Fruto: ' + esc(mis.fruto) + '</span>';
            html += '</div>';
            html += '<p style="margin:12px 0 0;font-size:.78rem;color:#888;"><i class="fa-solid fa-arrow-right me-1"></i>Após meditar, clique em <strong>Próximo</strong> para o Pai-Nosso da ' + passo.decada + '.ª dezena.</p>';
            html += '</div>';
        } else {
            var oracao = ORACOES[passo.key];
            var isAve  = passo.key === 'avemaria';
            var isPai  = passo.key === 'painosso';
            var icon   = isAve ? '🙏' : isPai ? '✝' : '📿';
            var labelExtra = passo.label ? '<span style="font-size:.75rem;color:#aaa;font-weight:400;"> — ' + esc(passo.label) + '</span>' : '';
            var bgColor = isPai ? 'rgba(212,160,23,.07)' : '#fff';
            var bdColor = isPai ? 'rgba(212,160,23,.4)' : '#e4e4e4';
            html += '<div style="background:' + bgColor + ';border:1px solid ' + bdColor + ';border-radius:8px;padding:20px 22px;">';
            html += '<p style="font-size:.72rem;text-transform:uppercase;letter-spacing:.09em;color:#999;margin:0 0 5px;">' + icon + ' Oração</p>';
            html += '<h5 style="font-weight:700;margin:0 0 14px;color:#111;">' + esc(oracao.titulo) + labelExtra + '</h5>';
            html += '<p style="line-height:2.05;font-size:.98rem;color:#333;font-style:italic;margin:0;">' + esc(oracao.texto) + '</p>';
            html += '</div>';
        }

        /* Botões */
        html += '<div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">';
        if (idx > 0) {
            html += '<button onclick="Terco.anterior()" style="flex:1;min-width:100px;padding:11px 18px;background:#f4f4f4;border:1px solid #ddd;border-radius:7px;font-weight:600;cursor:pointer;">';
            html += '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Anterior</button>';
        }
        if (idx < total - 1) {
            html += '<button onclick="Terco.proximo()" style="flex:2;padding:11px 18px;background:var(--primary-color);color:#fff;border:none;border-radius:7px;font-weight:600;cursor:pointer;">';
            html += 'Próximo <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>';
        } else {
            html += '<div style="flex:2;padding:14px 18px;background:#2E7D32;color:#fff;border-radius:7px;font-weight:600;text-align:center;">';
            html += '<i class="fa-solid fa-circle-check" aria-hidden="true"></i> Terço concluído! Que Deus te abençoe.';
            html += '<br><button onclick="Terco.irPara(0)" style="margin-top:10px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.5);color:#fff;padding:6px 16px;border-radius:20px;cursor:pointer;font-size:.85rem;">';
            html += '<i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Rezar novamente</button></div>';
        }
        html += '</div>';

        el.innerHTML = html;
        highlightBead(passo.conta);

        /* Scroll suave */
        var wrapper = document.getElementById('terco-passo-conteudo');
        if (wrapper) wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ── API pública ─────────────────────────────────────────────────────── */
    window.Terco = {
        proximo: function () { renderPasso(_step + 1); },
        anterior: function () { renderPasso(_step - 1); },
        irPara: function (idx) { renderPasso(idx); }
    };

    /* ── Render da Seção 1 ───────────────────────────────────────────────── */
    function renderAbertura(m) {
        var s1 = document.getElementById('terco-section-abertura');
        if (!s1) return;
        var cor = m.cor || 'var(--primary-color)';
        var h = '';
        /* Cabeçalho do dia */
        h += '<div style="background:linear-gradient(135deg,rgba(var(--primary-rgb,172,170,89),.09) 0%,rgba(var(--primary-rgb,172,170,89),.02) 100%);border-left:5px solid ' + cor + ';border-radius:10px;padding:22px 26px;margin-bottom:28px;">';
        h += '<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">';
        h += '<i class="fas ' + m.icone + ' fa-2x" style="color:' + cor + ';" aria-hidden="true"></i>';
        h += '<div style="flex:1;">';
        h += '<p style="margin:0 0 2px;font-size:.74rem;text-transform:uppercase;letter-spacing:.1em;color:#999;">Rezar com Maria hoje</p>';
        h += '<h3 style="margin:0 0 2px;font-size:1.35rem;font-weight:700;">' + esc(m.nome) + '</h3>';
        h += '<p style="margin:0;font-size:.85rem;color:#777;">' + esc(m.subtitulo) + '</p>';
        h += '</div></div>';
        h += '<p style="margin:14px 0 0;font-size:.95rem;color:#555;line-height:1.75;">' + esc(m.reflexao_geral) + '</p>';
        h += '</div>';

        /* ── Oferecimento do dia ─────────────────────────────────────────── */
        var _diasOfer = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
        var _diaOfer  = _diasOfer[new Date().getDay()];
        h += '<div id="terco-oferecimento" style="border:1px solid rgba(var(--primary-rgb,172,170,89),.3);border-radius:10px;padding:20px 24px;margin-bottom:28px;background:rgba(var(--primary-rgb,172,170,89),.04)">';
        h += '<p style="font-size:.74rem;text-transform:uppercase;letter-spacing:.1em;color:var(--primary-color);margin:0 0 8px;">✝ Oferecimento do Terço — ' + _diaOfer + '</p>';
        h += '<p style="margin:0 0 14px;font-size:.94rem;color:#333;line-height:1.9;font-style:italic;">';
        h += 'Eu vos ofereço, meu Deus, este Terço, meditando nos Santos Mistérios, pedindo pelas intenções da Santa Igreja, do Santo Padre e por ';
        h += '<span id="terco-intencao-preview" style="color:var(--primary-color);font-weight:600;">[suas intenções]</span>. Amém.';
        h += '</p>';
        h += '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">';
        h += '<input id="terco-intencao" type="text" maxlength="120"';
        h += ' placeholder="Sua intenção de hoje (' + _diaOfer + ')…"';
        h += ' aria-label="Intenção de oração"';
        h += ' style="flex:1;min-width:160px;border:1px solid #ddd;border-radius:6px;padding:9px 12px;font-size:.88rem;font-style:italic;"';
        h += ' oninput="var pv=document.getElementById(\'terco-intencao-preview\');if(pv)pv.textContent=this.value||\'[suas intenções]\';"';
        h += '/>';
        h += '<button onclick="var inp=document.getElementById(\'terco-intencao\');if(inp&&inp.value.trim()){inp.style.borderColor=\'var(--primary-color)\';inp.style.outline=\'none\';}"';
        h += ' style="padding:9px 18px;background:var(--primary-color);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:.85rem;">Confirmar</button>';
        h += '</div></div>';

        /* Layout: rosário + oração */
        h += '<div class="row g-4 align-items-start">';
        /* Coluna SVG — visível em todos os tamanhos */
        h += '<div class="col-12 col-md-5 col-lg-4">';
        h += '<div style="text-align:center;">';
        h += buildSVG();
        h += '</div></div>';
        /* Coluna passo a passo */
        h += '<div class="col-12 col-md-7 col-lg-8">';
        h += '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px;">';
        h += '<h5 style="margin:0;font-weight:700;font-size:1rem;"><i class="fa-solid fa-hands-praying me-2" style="color:var(--primary-color);" aria-hidden="true"></i>Rezar acompanhando</h5>';
        h += '<button onclick="Terco.irPara(0);" style="font-size:.77rem;padding:5px 14px;border-radius:20px;background:#f4f4f4;border:1px solid #ddd;cursor:pointer;"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Recomeçar</button>';
        h += '</div>';
        h += '<div id="terco-passo-conteudo"></div>';
        h += '</div></div>'; /* row */
        s1.innerHTML = h;
        renderPasso(0);
    }

    /* ── Render da Seção 2 ───────────────────────────────────────────────── */
    function renderReflexao(m) {
        var s2 = document.getElementById('terco-section-reflexao');
        if (!s2) return;
        var h = '<div style="border-top:2px solid #f0f0f0;padding-top:40px;">';
        h += '<div class="section-title" style="margin-bottom:28px;">';
        h += '<h3 style="font-size:.82rem;text-transform:uppercase;letter-spacing:.1em;color:var(--primary-color);">reflexão e intenções</h3>';
        h += '<h2 style="font-size:1.5rem;font-weight:700;margin-bottom:8px;">Frutos dos <span style="color:var(--primary-color);">Mistérios de Hoje</span></h2>';
        h += '<p style="color:#777;font-size:.93rem;">Abra cada mistério para meditar o texto bíblico e a reflexão. Reze em silêncio pelos frutos espirituais de cada um.</p>';
        h += '</div>';
        /* Cards colapsáveis */
        m.lista.forEach(function (mis, i) {
            var bid = 'terco-ref-' + i;
            var open = (i === 0);
            h += '<div style="margin-bottom:10px;border:1px solid #e9e9e9;border-radius:8px;overflow:hidden;">';
            h += '<button type="button" aria-expanded="' + open + '" aria-controls="' + bid + '"';
            h += ' onclick="(function(btn,id){var b=document.getElementById(id);var o=b.style.display===\'block\';b.style.display=o?\'none\':\'block\';btn.setAttribute(\'aria-expanded\',!o);btn.querySelector(\'.tc-chev\').style.transform=o?\'rotate(0deg)\':\'rotate(180deg)\';})(this,\'' + bid + '\')"';
            h += ' style="width:100%;text-align:left;background:#fff;border:none;padding:14px 20px;cursor:pointer;display:flex;align-items:center;gap:12px;">';
            h += '<span style="flex:1;font-weight:700;font-size:.93rem;color:#111;">' + esc(mis.titulo) + '</span>';
            h += '<span style="font-size:.76rem;background:rgba(var(--primary-rgb,172,170,89),.13);color:var(--primary-color);padding:2px 10px;border-radius:20px;white-space:nowrap;">' + esc(mis.fruto) + '</span>';
            h += '<i class="fa-solid fa-chevron-down tc-chev" style="font-size:.8rem;color:#aaa;transition:transform .25s;' + (open ? 'transform:rotate(180deg);' : '') + '"></i>';
            h += '</button>';
            h += '<div id="' + bid + '" style="display:' + (open ? 'block' : 'none') + ';padding:18px 22px;background:#fafafa;border-top:1px solid #eee;">';
            h += '<p style="line-height:1.8;color:#333;margin:0 0 14px;font-size:.94rem;">' + esc(mis.texto) + '</p>';
            h += '<div style="background:rgba(var(--primary-rgb,172,170,89),.07);border-left:3px solid var(--primary-color);border-radius:6px;padding:12px 16px;">';
            h += '<p style="font-size:.86rem;font-style:italic;color:#555;margin:0;">' + esc(mis.reflexao) + '</p>';
            h += '</div></div></div>';
        });
        /* Intenções */
        h += '<div style="background:rgba(var(--primary-rgb,172,170,89),.06);border-radius:10px;padding:22px 26px;margin-top:26px;">';
        h += '<h5 style="font-weight:700;margin-bottom:10px;"><i class="fa-solid fa-heart me-2" style="color:var(--primary-color);" aria-hidden="true"></i>Intenções para este Terço</h5>';
        h += '<p style="color:#666;font-size:.9rem;line-height:1.7;margin-bottom:8px;">Ao rezar, ofereça o terço por intenções como:</p>';
        h += '<ul style="list-style:none;padding:0;margin:0;color:#555;font-size:.92rem;line-height:2.1;">';
        h += '<li>🙏 Pela paz no mundo e na sua família</li>';
        h += '<li>✝ Pela conversão dos pecadores</li>';
        h += '<li>💚 Pelos doentes e pelos que sofrem</li>';
        h += '<li>⭐ Pelas almas do purgatório</li>';
        h += '<li>🕊 Pelas intenções do Papa e da Igreja</li>';
        h += '</ul></div>';
        h += '</div>';
        s2.innerHTML = h;
    }

    /* ── Render da Seção 3 ───────────────────────────────────────────────── */
    function renderCatequese() {
        var s3 = document.getElementById('terco-section-catequese');
        if (!s3) return;
        var blocos = [
            {
                icon: '📿',
                titulo: 'O que é o Terço (ou Rosário)?',
                corpo: [
                    'O Terço (ou Rosário) é uma oração vocal e meditativa que combina orações simples — Pai-Nosso, Ave-Maria e Glória ao Pai — com a contemplação dos mistérios da vida de Jesus Cristo. Enquanto repetimos as palavras com os lábios, nosso coração e mente são convidados a mergulhar nos eventos centrais da fé cristã, com Maria como guia.',
                    'A palavra "rosário" vem do latim rosarium, significando "jardim de rosas". Diz-se que rezar o Rosário é oferecer um buquê de rosas espirituais a Nossa Senhora. Cada Ave-Maria é uma rosa; cada décadas, uma coroa floral.'
                ]
            },
            {
                icon: '📖',
                titulo: 'Origem e história do Rosário',
                corpo: [
                    'O uso de contas para contar orações é antigo nas tradições cristãs. A forma atual do Rosário foi desenvolvida gradualmente na Idade Média, e São Domingos de Gusmão (séc. XIII) é tradicionalmente associado à sua popularização após uma aparição de Nossa Senhora.',
                    'O Papa São João Paulo II, em 2002, acrescentou os Mistérios Luminosos, completando o ciclo com os 20 mistérios distribuídos pelos dias da semana. O Rosário é hoje uma das orações mais rezadas no mundo e foi recomendado por todos os papas dos últimos séculos.'
                ]
            },
            {
                icon: '🗓',
                titulo: 'Os 4 grupos de Mistérios — quando rezar cada um?',
                corpo: [
                    'Os 20 mistérios do Rosário estão divididos em 4 grupos, cada um com 5 mistérios contemplados em 5 décadas:',
                    '• Mistérios GOZOSOS (Segunda-feira e Sábado): Anunciação · Visitação · Nascimento · Apresentação · Jesus no Templo. Contemplamos a alegria da salvação.\n\n• Mistérios LUMINOSOS (Quinta-feira): Batismo · Caná · Anúncio do Reino · Transfiguração · Eucaristia. Contemplamos a vida pública de Jesus.\n\n• Mistérios DOLOROSOS (Terça-feira e Sexta-feira): Getsêmani · Flagelação · Coroação de espinhos · Caminho da Cruz · Crucificação. Contemplamos a Paixão.\n\n• Mistérios GLORIOSOS (Quarta-feira, Domingo e Sábado alt.): Ressurreição · Ascensão · Pentecostes · Assunção · Coroação de Maria. Contemplamos o triunfo de Deus.'
                ]
            },
            {
                icon: '🙏',
                titulo: 'Como rezar o Rosário — passo a passo',
                corpo: [
                    '1. SINAL DA CRUZ — na cruz/crucifixo: "Em nome do Pai…"\n2. CREDO — na mesma conta: afirmação de fé.\n3. PAI-NOSSO — na conta grande: a oração do Senhor.\n4. TRÊS AVE-MARIAS — pelas virtudes de fé, esperança e caridade.\n5. GLÓRIA AO PAI — louvor à Santíssima Trindade.',
                    'Para cada uma das 5 décadas:\n6. Anunciar o MISTÉRIO e meditar brevemente.\n7. PAI-NOSSO na conta grande de cada grupo.\n8. DEZ AVE-MARIAS nas 10 contas pequenas.\n9. GLÓRIA AO PAI.\n10. ORAÇÃO DE FÁTIMA: "Ó meu Jesus…"\n\nEncerramento:\n11. SALVE RAINHA.\n12. Oração final pelas intenções do Papa e do bispo diocesano.'
                ]
            },
            {
                icon: '💬',
                titulo: 'Por que repetir tantas vezes as mesmas orações?',
                corpo: [
                    'A repetição das Ave-Marias não é mecânica nem vazia — ela cria um ritmo contemplativo semelhante à respiração. É como um mantra de fé: enquanto os lábios repetem, o coração se aprofunda na meditação do mistério anunciado.',
                    'O Papa João Paulo II ensinou que o Rosário, rezado com atenção e devoção, é "súmula do Evangelho" — uma recapitulação profunda de toda a vida de Cristo. Cada Ave-Maria é um degrau que nos aproxima de Maria e, por ela, de Jesus.\n\nSão Luís de Montfort disse: "O Rosário é a oração mais bela depois da Liturgia das Horas." Santa Teresa de Calcutá rezava o Rosário andando, nas ruas, entre os mais pobres.'
                ]
            },
            {
                icon: '⭐',
                titulo: 'Promessas de Nossa Senhora do Rosário',
                corpo: [
                    'Segundo a tradição, Nossa Senhora prometeu a São Domingos e ao Beato Alano de Rupe graças especiais para os devotos do Rosário, entre as quais: proteção especial de Deus, preservação dos perigos e infortúnios, conversão dos pecadores, preservação da fé e aumento das virtudes.',
                    'Embora o texto exato das promessas seja de tradição devocional (não definição dogmática), elas expressam a confiança da Igreja no poder intercessório de Maria e na eficácia do Rosário rezado com fé. O Concílio Vaticano II e todos os papas modernos recomendam especialmente esta oração.'
                ]
            }
        ];

        var h = '<div style="border-top:2px solid #f0f0f0;padding-top:40px;">';
        h += '<div class="section-title" style="margin-bottom:28px;">';
        h += '<h3 style="font-size:.82rem;text-transform:uppercase;letter-spacing:.1em;color:var(--primary-color);">aprenda mais</h3>';
        h += '<h2 style="font-size:1.5rem;font-weight:700;margin-bottom:8px;">Catequese do <span style="color:var(--primary-color);">Santo Terço</span></h2>';
        h += '<p style="color:#777;font-size:.93rem;">Tudo o que você precisa saber para rezar o Rosário com entendimento, devoção e profundidade.</p>';
        h += '</div>';

        blocos.forEach(function (bloco, bi) {
            var catId = 'terco-cat-' + bi;
            var open  = (bi === 0);
            h += '<div style="margin-bottom:10px;border:1px solid #e9e9e9;border-radius:8px;overflow:hidden;">';
            h += '<button type="button" aria-expanded="' + open + '" aria-controls="' + catId + '"';
            h += ' onclick="(function(btn,id){var b=document.getElementById(id);var o=b.style.display===\'block\';b.style.display=o?\'none\':\'block\';btn.setAttribute(\'aria-expanded\',!o);btn.querySelector(\'.tc-chev\').style.transform=o?\'rotate(0deg)\':\'rotate(180deg)\';})(this,\'' + catId + '\')"';
            h += ' style="width:100%;text-align:left;background:#fff;border:none;padding:16px 20px;cursor:pointer;display:flex;align-items:center;gap:12px;">';
            h += '<span style="font-size:1.3em;" aria-hidden="true">' + bloco.icon + '</span>';
            h += '<span style="flex:1;font-weight:700;font-size:.93rem;color:#111;">' + esc(bloco.titulo) + '</span>';
            h += '<i class="fa-solid fa-chevron-down tc-chev" style="font-size:.8rem;color:#aaa;transition:transform .25s;' + (open ? 'transform:rotate(180deg);' : '') + '"></i>';
            h += '</button>';
            h += '<div id="' + catId + '" style="display:' + (open ? 'block' : 'none') + ';padding:20px 22px;background:#fafafa;border-top:1px solid #eee;">';
            bloco.corpo.forEach(function (par) {
                par.split('\n\n').forEach(function (p) {
                    h += '<p style="line-height:1.85;color:#333;margin:0 0 12px;white-space:pre-line;">' + esc(p) + '</p>';
                });
            });
            h += '</div></div>';
        });

        /* CTA final */
        h += '<div style="background:rgba(var(--primary-rgb,172,170,89),.07);border-radius:10px;padding:26px;margin-top:22px;text-align:center;">';
        h += '<i class="fa-solid fa-church fa-2x mb-3" style="color:var(--primary-color);" aria-hidden="true"></i>';
        h += '<h5 style="font-weight:700;margin-bottom:8px;">Reze conosco na Paróquia</h5>';
        h += '<p style="color:#666;font-size:.9rem;margin-bottom:16px;">O Rosário é rezado comunitariamente antes das missas e nas festas marianas. Venha rezar conosco!</p>';
        h += '<a href="agenda-liturgica.html" style="display:inline-block;background:var(--primary-color);color:#fff;padding:10px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:.9rem;">Ver Agenda de Missas</a>';
        h += '</div></div>';
        s3.innerHTML = h;
    }

    /* ── Função principal (chamada pelo devocoes-diarias.html) ──────────── */
    window.carregarTerco = function () {
        var mapa  = { 0:'gloriosos', 1:'gozosos', 2:'dolorosos', 3:'gloriosos', 4:'luminosos', 5:'dolorosos', 6:'gozosos' };
        var chave = mapa[new Date().getDay()];
        var m     = MISTERIOS[chave];
        _data  = { m: m, passos: buildPassos(m) };
        _step  = 0;

        /* Oculta loading, exibe container */
        var loading   = document.getElementById('terco-loading');
        var container = document.getElementById('terco-container');
        if (loading)   loading.hidden = true;
        if (container) container.removeAttribute('hidden');

        renderAbertura(m);
        renderReflexao(m);
        renderCatequese();
    };

}());
