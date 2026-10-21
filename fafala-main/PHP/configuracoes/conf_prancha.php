<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAFALA - Prancha</title>
    <link rel="stylesheet" href="paciente.css?v=12">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="paciente.css?v=12">
</head>
<body>
    <header class="cabecalho-prancha">
        <a href="index.php" class="botao-voltar">
            <span aria-hidden="true"><i class="bi bi-arrow-left-circle"></i></span>
            Pranchas
        </a>
        <button type="button" id="botao-editar" class="botao-editar" aria-pressed="false">Editar</button>
    </header>
    <main>
        <h1 class="titulo-prancha" id="titulo-prancha"></h1>
        <p class="aviso-edicao">Toque no pictograma que deseja editar.</p>

        <div class="container-palavras">
            <div class="palavra">Eu</div>
            <div class="palavra">Quero</div>
            <div class="palavra">Comer</div>
            <div class="palavra">Beber</div>
            <div class="palavra">Fazer</div>
            <div class="palavra">Sim</div>
            <div class="palavra">Não</div>
            <div class="palavra">Mãe</div>
            <div class="palavra">Pai</div>
            <div class="palavra">Dormir</div>
            <div class="palavra">Brincar</div>
            <div class="palavra">Abraçar</div>
            <div class="palavra">Assistir</div>
            <div class="palavra">Tomar</div>
            <div class="palavra">Banho</div>
        </div>

        <div class="container-pictogramas" id="container-pictogramas"></div>
    </main>

    <script src="fafala.js?v=1"></script>
    <script>
        const categoriaId = new URLSearchParams(location.search).get("prancha");
        const container = document.getElementById("container-pictogramas");
        const titulo = document.getElementById("titulo-prancha");
        let pictogramas = [];

        function falar(texto) {
            const mensagem = new SpeechSynthesisUtterance(texto);
            mensagem.lang = "pt-BR";
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(mensagem);
        }

        function desenhar() {
            container.replaceChildren();

            pictogramas.forEach(function (p) {
                container.appendChild(Fafala.el("div", { class: "pictograma", "data-id": p.id }, [
                    Fafala.imagemOuVazio(p.imagem_id, p.nome),
                    Fafala.el("h3", { text: p.nome })
                ]));
            });

            container.appendChild(Fafala.blocoAdicionar("pictograma", "Novo pictograma", novoPictograma));
        }

        async function carregar() {
            try {
                const resp = await Fafala.api("pictogramas", { categoria_id: categoriaId });
                titulo.textContent = resp.categoria.nome;
                pictogramas = resp.pictogramas;
                desenhar();
            } catch (e) {
                titulo.textContent = "Prancha não encontrada";
                container.replaceChildren();
            }
        }

        function novoPictograma() {
            Fafala.abrirFormulario({
                titulo: "Novo pictograma",
                aoSalvar: async function (dados) {
                    dados.append("categoria_id", categoriaId);
                    await Fafala.api("salvar_pictograma", {}, dados);
                    await carregar();
                }
            });
        }

        function editarPictograma(id) {
            const p = pictogramas.find(function (x) { return x.id == id; });
            Fafala.abrirFormulario({
                titulo: "Editar pictograma",
                nome: p.nome,
                imagemId: p.imagem_id,
                mensagemExcluir: 'Excluir o pictograma "' + p.nome + '"?',
                aoSalvar: async function (dados) {
                    dados.append("id", p.id);
                    await Fafala.api("salvar_pictograma", {}, dados);
                    await carregar();
                },
                aoExcluir: async function () {
                    const dados = new FormData();
                    dados.append("id", p.id);
                    await Fafala.api("excluir_pictograma", {}, dados);
                    await carregar();
                }
            });
        }

        container.addEventListener("click", function (e) {
            const card = e.target.closest(".pictograma[data-id]");
            if (!card) return;
            if (Fafala.emEdicao()) editarPictograma(card.dataset.id);
            else falar(card.querySelector("h3").textContent);
        });

        document.querySelectorAll(".palavra").forEach(function (palavra) {
            palavra.addEventListener("click", function () {
                falar(palavra.textContent.trim());
            });
        });

        Fafala.iniciarBotaoEditar();
        carregar();
    </script>

</body>
</html>
