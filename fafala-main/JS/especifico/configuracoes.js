// =====================================================
// FAFALA - CONFIGURAÇÕES DE ACESSIBILIDADE
// Atualiza a prévia na hora, enquanto o responsável muda as opções.
// (A configuração só é salva de verdade ao clicar em "Salvar".)
// =====================================================

var formAcessibilidade = document.getElementById('formAcessibilidade');
var previa = document.getElementById('previa');
var quantidade = document.getElementById('quantidade');
var quantidadeValor = document.getElementById('quantidadeValor');

// cards de exemplo da prévia
var exemplos = [
    ['💧', 'Água'],
    ['🍽️', 'Comer'],
    ['🛁', 'Banho'],
    ['🧸', 'Brincar'],
    ['😴', 'Dormir'],
    ['😊', 'Feliz'],
    ['😢', 'Triste'],
    ['🙋', 'Ajuda'],
    ['👍', 'Sim'],
    ['👎', 'Não']
];

// tamanho de cada card na prévia (menor que o real, para caber)
var tamanhosPrevia = {
    'Pequeno': '56px',
    'Médio': '72px',
    'Grande': '92px'
};

var espacosPrevia = {
    'Normal': '8px',
    'Grande': '18px'
};

function valorMarcado(nome) {
    var marcado = formAcessibilidade.querySelector('input[name="' + nome + '"]:checked');
    return marcado ? marcado.value : '';
}

// cores da prévia: no Personalizado vêm da cor escolhida,
// nos outros perfis vêm do padrão do perfil
function atualizarCores() {
    var perfil = formAcessibilidade.querySelector('input[name="id_visual"]:checked');
    if (!perfil) return;

    var origem = perfil;

    if (perfil.dataset.nome === 'Personalizado') {
        var cor = formAcessibilidade.querySelector('input[name="tema_cor"]:checked');
        if (cor) origem = cor;
    }

    previa.style.setProperty('--previa-fundo', origem.dataset.fundo);
    previa.style.setProperty('--previa-card', origem.dataset.card);

    previa.classList.toggle('perfil-infantil', perfil.dataset.nome === 'Infantil');
    previa.classList.toggle('modo-escuro', formAcessibilidade.elements['modo_escuro'].checked);
}

function atualizarPrevia() {
    var total = parseInt(quantidade.value, 10);
    quantidadeValor.textContent = total;

    atualizarCores();

    previa.style.setProperty('--previa-tamanho', tamanhosPrevia[valorMarcado('tamanho_card')] || '72px');
    previa.style.setProperty('--previa-espaco', espacosPrevia[valorMarcado('espacamento_cards')] || '8px');

    previa.classList.toggle('sem-texto', !formAcessibilidade.elements['mostrar_texto'].checked);
    previa.classList.toggle('alto-contraste', formAcessibilidade.elements['alto_contraste'].checked);

    // desenha a quantidade de cards escolhida
    previa.replaceChildren();
    exemplos.slice(0, total).forEach(function (exemplo) {
        var card = document.createElement('div');
        card.className = 'previa-card';
        card.innerHTML =
            '<span class="previa-emoji" aria-hidden="true">' + exemplo[0] + '</span>' +
            '<span class="previa-nome">' + exemplo[1] + '</span>';
        previa.appendChild(card);
    });
}

formAcessibilidade.addEventListener('input', atualizarPrevia);
formAcessibilidade.addEventListener('change', atualizarPrevia);

atualizarPrevia();