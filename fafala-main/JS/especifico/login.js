// mostrar / ocultar senha
document.querySelectorAll('.mostrar-senha').forEach(function (botao) {
    botao.addEventListener('click', function () {
        var campo = document.getElementById(botao.dataset.alvo);
        var mostrando = campo.type === 'text';

        campo.type = mostrando ? 'password' : 'text';

        botao.setAttribute('aria-pressed', String(!mostrando));
        botao.setAttribute(
            'aria-label',
            mostrando ? 'Mostrar senha' : 'Ocultar senha'
        );
    });
});
