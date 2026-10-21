
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../tecnico/conexao.php";
require_once __DIR__ . "/../tecnico/estilo_paciente.php";

$idPrancha = filter_input(
    INPUT_GET,
    "prancha",
    FILTER_VALIDATE_INT
);

$idPaciente = $_SESSION["id_paciente"] ?? null;

$prancha = null;
$pictogramas = [];

if ($idPrancha && $idPrancha > 0) {
    // Busca a prancha e verifica se é padrão
    // ou pertence ao paciente da sessão.
    $stmt = $pdo->prepare(
        "SELECT
            p.id_prancha,
            p.nome_prancha,
            p.descricao_prancha
         FROM prancha p
         WHERE p.id_prancha = ?
           AND (
               p.tipo_prancha = 'Padrão'
               OR p.id_paciente = ?
           )"
    );

    $stmt->execute([$idPrancha, $idPaciente]);
    $prancha = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prancha) {
        // Busca as imagens relacionadas à prancha.
        $stmt = $pdo->prepare(
            "SELECT
                i.id_imagem,
                i.nome_pictograma,
                i.texto_fala
             FROM prancha_imagem pi
             INNER JOIN imagem i
                ON pi.id_imagem = i.id_imagem
             WHERE pi.id_prancha = ?
             ORDER BY pi.ordem_exibicao"
        );

        $stmt->execute([$idPrancha]);
        $pictogramas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $prancha ? e($prancha["nome_prancha"]) . " | Fafala" : "Prancha | Fafala" ?>
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="/fafala-main/CSS/style.css">
    <link rel="stylesheet" href="/fafala-main/CSS/acessibilidade.css">
    <link rel="stylesheet" href="/fafala-main/CSS/especifico/paciente.css">

    <?= $estiloPaciente ?>
</head>

<body class="<?= e($classesPaciente ?? '') ?>">

    <header class="cabecalho-prancha">
        <a
            href="/fafala-main/PHP/paciente/inicio.php"
            class="botao-voltar"
        >
            <i class="bi bi-arrow-left-circle" aria-hidden="true"></i>
            Voltar às pranchas
        </a>
    </header>

    <main class="container">

        <?php if (!$prancha): ?>

            <h1 class="titulo-prancha">Prancha não encontrada</h1>

            <p class="aviso-vazio">
                Não foi possível encontrar essa prancha.
                Volte à página inicial e escolha uma prancha disponível.
            </p>

        <?php else: ?>

            <h1 class="titulo-prancha">
                <?= e($prancha["nome_prancha"]) ?>
            </h1>

            <?php if (!empty($prancha["descricao_prancha"])): ?>
                <p>
                    <?= e($prancha["descricao_prancha"]) ?>
                </p>
            <?php endif; ?>

            <div class="container-pictogramas">

                <?php if (empty($pictogramas)): ?>

                    <p class="aviso-vazio">
                        Esta prancha ainda não possui pictogramas.
                    </p>

                <?php else: ?>

                    <?php foreach ($pictogramas as $pictograma): ?>

                        <button
                            type="button"
                            class="pictograma"
                            data-falar="<?= e(
                                $pictograma["texto_fala"]
                                ?: $pictograma["nome_pictograma"]
                            ) ?>"
                            aria-label="Falar: <?= e(
                                $pictograma["texto_fala"]
                                ?: $pictograma["nome_pictograma"]
                            ) ?>"
                        >

                            <img
                                src="/fafala-main/PHP/tecnico/imagem.php?id=<?= (int) $pictograma["id_imagem"] ?>"
                                alt=""
                                loading="lazy"
                            >

                            <span>
                                <?= e($pictograma["nome_pictograma"]) ?>
                            </span>

                        </button>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </main>

    <script>
        function falar(texto) {
            if (!("speechSynthesis" in window)) {
                alert("A fala não é compatível com este navegador.");
                return;
            }

            window.speechSynthesis.cancel();

            const mensagem = new SpeechSynthesisUtterance(texto);
            mensagem.lang = "pt-BR";
            mensagem.rate = 0.9;

            window.speechSynthesis.speak(mensagem);
        }

        document.querySelectorAll("[data-falar]").forEach(function (botao) {
            botao.addEventListener("click", function () {
                falar(botao.dataset.falar);
            });
        });
    </script>

</body>
</html>
