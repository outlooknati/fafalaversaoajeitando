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

// máscara do telefone
document.getElementById('telefone').addEventListener('input', function (e) {
    var n = e.target.value.replace(/\D/g, '').slice(0, 11);

    if (n.length > 6) {
        e.target.value =
            '(' + n.slice(0, 2) + ') ' +
            n.slice(2, n.length - 4) + '-' +
            n.slice(-4);
    } else if (n.length > 2) {
        e.target.value =
            '(' + n.slice(0, 2) + ') ' +
            n.slice(2);
    } else {
        e.target.value = n;
    }
});


// validar nascimento
var hoje = new Date();

hoje.setMinutes(hoje.getMinutes() - hoje.getTimezoneOffset());

document.getElementById('data_nascimento').max =
    hoje.toISOString().slice(0, 10);


// validar senha
var form = document.getElementById('form-acesso');
var senha = document.getElementById('senha');
var confirmar = document.getElementById('confirmar_senha');
var erroSenha = document.getElementById('erro-senha');

function verificarSenhas() {
    if (confirmar.value && senha.value !== confirmar.value) {
        confirmar.setCustomValidity('As senhas não são iguais.');
        erroSenha.textContent = 'As senhas não são iguais.';
    } else {
        confirmar.setCustomValidity('');
        erroSenha.textContent = '';
    }
}

senha.addEventListener('input', verificarSenhas);
confirmar.addEventListener('input', verificarSenhas);

// validar questionário
form.addEventListener('submit', function (e) {
    verificarSenhas();

    form.classList.add('tentou-enviar');

    // verifica se existem campos inválidos
    if (!form.checkValidity()) {
        e.preventDefault();

        var primeiroInvalido = form.querySelector(':invalid');

        if (primeiroInvalido) {
            primeiroInvalido.focus();
        }

        return;
    }
});
