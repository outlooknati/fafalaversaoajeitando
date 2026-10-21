<?php

require_once __DIR__ . "/../tecnico/conexao.php";
require_once __DIR__ . "/../tecnico/estilo_paciente.php";
require_once __DIR__ . "/pranchas_dados.php";

if (!function_exists("e")) {
    function e($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
    }
}

$slug        = $_GET["prancha"] ?? "";
$idCategoria = (int) ($_GET["categoria"] ?? 0);

if (!isset($pranchasDados[$slug])) {
    http_response_code(404);
    exit("Prancha não encontrada.");
}

$prancha     = $pranchasDados[$slug];
$titulo      = $prancha["titulo"];
$pictogramas = $prancha["pictogramas"];

// O botão voltar leva à categoria de onde a pessoa veio.
$voltar = $idCategoria > 0
    ? "/fafala-main/PHP/paciente/categorias.php?categoria=" . $idCategoria
    : "/fafala-main/PHP/paciente/inicio.php";

// Barra de palavras fixas (igual ao protótipo).
$palavras = [
    "Eu", "Quero", "Comer", "Beber", "Fazer",
    "Sim", "Não", "Mãe", "Pai", "Dormir",
    "Brincar", "Abraçar", "Assistir", "Tomar", "Banho",
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | Fafala</title>

    <link rel="stylesheet" href="/fafala-main/CSS/style.css">
    <link rel="stylesheet" href="/fafala-main/CSS/acessibilidade.css">
    <link rel="stylesheet" href="/fafala-main/CSS/especifico/paciente.css">

    <?= $estiloPaciente ?? "" ?>

    <style>
        .pagina-prancha { max-width: 1100px; margin: 0 auto; padding: 20px; }
        .pagina-prancha .cabecalho-prancha { margin-bottom: 30px; }

        .pagina-prancha .botao-voltar {
            display: inline-flex; align-items: center; gap: 16px;
            color: #21758B; font-size: 32px; font-weight: bold; text-decoration: none;
        }
        .pagina-prancha .botao-voltar span { font-size: 48px; line-height: 1; }

        .pagina-prancha .titulo-prancha {
            text-align: center; color: #21758B; font-size: 40px;
            font-style: italic; margin: 0 0 40px;
        }

        .pagina-prancha .container-palavras {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; margin: 0 0 40px;
        }
        .pagina-prancha .container-pictogramas {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;
        }

        .pagina-prancha .palavra,
        .pagina-prancha .pictograma {
            background-color: #fff; border: 1px solid #ddd; border-radius: 20px;
            text-align: center; color: #21758B; font-family: inherit;
            font-weight: bold; cursor: pointer; min-width: 0; overflow-wrap: anywhere;
        }
        .pagina-prancha .palavra { padding: 20px; font-size: 26px; }
        .pagina-prancha .pictograma { padding: 30px; }

        .pagina-prancha .pictograma img {
            display: block; width: 200px; height: 200px; max-width: 100%;
            margin: 0 auto; object-fit: contain;
        }
        .pagina-prancha .pictograma h3 { color: #21758B; font-size: 26px; margin: 15px 0 0; }

        .pagina-prancha .palavra:hover,
        .pagina-prancha .pictograma:hover,
        .pagina-prancha .palavra:focus-visible,
        .pagina-prancha .pictograma:focus-visible {
            border-color: #21758B; outline: 3px solid #21758B; outline-offset: 2px;
        }
        .pagina-prancha .falando { background-color: #e4f2f5; border-color: #21758B; }
        .pagina-prancha .aviso-vazio { text-align: center; padding: 20px; }

        @media (max-width: 800px) {
            .pagina-prancha .container-palavras,
            .pagina-prancha .container-pictogramas { grid-template-columns: repeat(2, 1fr); gap: 14px; }
            .pagina-prancha .titulo-prancha { font-size: 30px; }
            .pagina-prancha .botao-voltar { font-size: 24px; }
            .pagina-prancha .palavra { font-size: 20px; padding: 14px; }
            .pagina-prancha .pictograma { padding: 16px; }
            .pagina-prancha .pictograma img { width: 140px; height: 140px; }
            .pagina-prancha .pictograma h3 { font-size: 20px; }
        }
    </style>
</head>

<body class="<?= e($classesPaciente ?? '') ?>">

    <main class="pagina-prancha">

        <header class="cabecalho-prancha">
            <a href="<?= e($voltar) ?>" class="botao-voltar">
                <span aria-hidden="true">←</span>
                Pranchas
            </a>
        </header>

        <h1 class="titulo-prancha"><?= e($titulo) ?></h1>

        <div class="container-palavras">
            <?php foreach ($palavras as $palavra): ?>
                <button type="button" class="palavra" data-fala="<?= e($palavra) ?>">
                    <?= e($palavra) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (count($pictogramas) > 0): ?>

            <div class="container-pictogramas">
                <?php foreach ($pictogramas as $picto): ?>
                    <?php
                    $nome    = $picto[0];
                    $arquivo = $picto[1];
                    $fala    = $picto[2] ?? $nome;
                    ?>
                    <button
                        type="button"
                        class="pictograma"
                        data-fala="<?= e($fala) ?>"
                        aria-label="Falar: <?= e($fala) ?>"
                    >
                        <img
                            src="<?= e($caminhoImagens . rawurlencode($arquivo)) ?>"
                            alt=""
                        >
                        <h3><?= e($nome) ?></h3>
                    </button>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <p class="aviso-vazio">Esta prancha ainda não possui pictogramas.</p>

        <?php endif; ?>

    </main>

    <script>
        function falar(texto) {
            if (!("speechSynthesis" in window)) {
                alert("Seu navegador não oferece suporte à leitura de voz.");
                return;
            }

            window.speechSynthesis.cancel();

            const mensagem = new SpeechSynthesisUtterance(texto);
            mensagem.lang = "pt-BR";
            mensagem.rate = 0.9;

            const vozPt = window.speechSynthesis.getVoices()
                .find(v => v.lang && v.lang.toLowerCase().startsWith("pt"));
            if (vozPt) { mensagem.voice = vozPt; }

            window.speechSynthesis.speak(mensagem);
        }

        document.querySelectorAll(".palavra, .pictograma").forEach(function (item) {
            item.addEventListener("click", function () {
                falar(item.dataset.fala);
                item.classList.add("falando");
                setTimeout(function () { item.classList.remove("falando"); }, 600);
            });
        });
    </script>

</body>
</html>