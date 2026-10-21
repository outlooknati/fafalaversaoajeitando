// =====================================================
// OBSSSSSSSSSSS (NO HTML DA PÁGINA TEM QUE COLOCAR EXATAMENTE ASSIM):
//   - o campo de busca ter data-busca="#id-da-lista"
//   - cada item da lista ter data-nome="Nome do item"
//   - (opcional) uma mensagem com data-sem-resultado="#id-da-lista"
// =====================================================

// deixa o texto minúsculo e sem acento, EX: para "Refeição" achar "refeicao"
function normalizar(texto) {
    return texto
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

document.querySelectorAll('[data-busca]').forEach(function (campo) {
    var lista = document.querySelector(campo.dataset.busca);
    var semResultado = document.querySelector('[data-sem-resultado="' + campo.dataset.busca + '"]');

    if (!lista) return;

    campo.addEventListener('input', function () {
        var busca = normalizar(campo.value);
        var algumVisivel = false;

        lista.querySelectorAll('[data-nome]').forEach(function (item) {
            var aparece = normalizar(item.dataset.nome).includes(busca);
            item.hidden = !aparece;
            if (aparece) algumVisivel = true;
        });

        if (semResultado) semResultado.hidden = algumVisivel;
    });
});