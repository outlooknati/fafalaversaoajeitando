
<?php
// PHP/paciente/prancha.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../tecnico/conexao.php";
require_once __DIR__ . "/../tecnico/estilo_paciente.php";

// Verifica se existe um paciente na sessão.
$idPaciente = $_SESSION["id_paciente"] ?? null;

// Recebe o nome da prancha pela URL.
$nomePrancha = trim($_GET["prancha"] ?? "");

if ($nomePrancha === "") {
    http_response_code(400);
    exit("Nome da prancha não informado.");
}

// Busca a prancha pelo nome.
$stmt = $pdo->prepare("
    SELECT
        id_prancha,
        nome_prancha,
        descricao_prancha
    FROM prancha
    WHERE nome_prancha = ?
      AND (
          tipo_prancha = 'Padrão'
          OR id_paciente = ?
      )
    LIMIT 1
");

$stmt->execute([$nomePrancha, $idPaciente]);
$prancha = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prancha) {
    http_response_code(404);
    exit("Prancha não encontrada.");
}

// Busca os pictogramas associados à prancha.
$stmtImagens = $pdo->prepare("
    SELECT
        i.id_imagem,
        i.nome_pictograma,
        i.texto_fala
    FROM prancha_imagem pi
    INNER JOIN imagem i
        ON pi.id_imagem = i.id_imagem
    WHERE pi.id_prancha = ?
    ORDER BY pi.ordem_exibicao
");

$stmtImagens->execute([$prancha["id_prancha"]]);
$imagens = $stmtImagens->fetchAll(PDO::FETCH_ASSOC);

// Prepara o título e a descrição.
$titulo = $prancha["nome_prancha"];
$descricao = $prancha["descricao_prancha"] ?? "";

// Função de segurança para exibir textos.
if (!function_exists("e")) {
    function e($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($titulo) ?> - Fafala</title>

    <link rel="stylesheet" href="/fafala-main/CSS/style.css">
    <link rel="stylesheet" href="/fafala-main/CSS/acessibilidade.css">
    <link rel="stylesheet" href="/fafala-main/CSS/especifico/paciente.css">

    <style>
        .conteudo-prancha {
            width: min(100% - 32px, 1100px);
            margin: 32px auto;
        }

        .cabecalho-prancha {
            margin-bottom: 24px;
        }

        .grade-pictogramas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .cartao-pictograma {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px;
            min-width: 0;
            border: 2px solid #dce8df;
            border-radius: 16px;
            background: #fffdf8;
            color: #355c4a;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            overflow-wrap: anywhere;
        }

        .cartao-pictograma:hover,
        .cartao-pictograma:focus-visible {
            border-color: #7a9e7e;
            outline: 3px solid #7a9e7e;
            outline-offset: 2px;
        }

        .imagem-pictograma {
            display: block;
            width: 100%;
            max-width: 150px;
            height: 130px;
            object-fit: contain;
        }

        .mensagem-sem-imagens {
            padding: 20px;
            border-radius: 12px;
            background: #fffdf8;
        }

        .botao-voltar {
            display: inline-block;
            margin-bottom: 20px;
        }

        @media (max-width: 480px) {
            .grade-pictogramas {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .cartao-pictograma {
                padding: 10px;
            }

            .imagem-pictograma {
                height: 100px;
            }
        }
    </style>
</head>

<body class="<?= e($classesPaciente ?? '') ?>" style="<?= e($estiloPaciente ?? '') ?>">

    <main class="conteudo-prancha">

        <a class="botao-voltar" href="inicio.php">
            ← Voltar às pranchas
        </a>

        <header class="cabecalho-prancha">
            <h1><?= e($titulo) ?></h1>

            <?php if ($descricao !== ""): ?>
                <p><?= e($descricao) ?></p>
            <?php endif; ?>
        </header>

        <?php if (count($imagens) > 0): ?>

            <div class="grade-pictogramas">
                <?php foreach ($imagens as $imagem): ?>
                    <?php
                    $textoFala = trim(
                        $imagem["texto_fala"]
                        ?? $imagem["nome_pictograma"]
                        ?? ""
                    );

                    if ($textoFala === "") {
                        $textoFala = "Pictograma";
                    }
                    ?>

                    <button
                        type="button"
                        class="cartao-pictograma"
                        onclick="falarTexto(this.dataset.fala)"
                        data-fala="<?= e($textoFala) ?>"
                        aria-label="Falar: <?= e($textoFala) ?>"
                    >
                        <img
                            class="imagem-pictograma"
                            src="/fafala-main/PHP/tecnico/imagem.php?id=<?= (int) $imagem["id_imagem"] ?>"
                            alt="<?= e($imagem["nome_pictograma"] ?? $textoFala) ?>"
                        >

                        <span><?= e($imagem["nome_pictograma"] ?? $textoFala) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <p class="mensagem-sem-imagens">
                Esta prancha ainda não possui pictogramas cadastrados.
            </p>

        <?php endif; ?>

    </main>

    <script>
        function falarTexto(texto) {
            if (!("speechSynthesis" in window)) {
                alert("Seu navegador não oferece suporte à leitura de voz.");
                return;
            }

            window.speechSynthesis.cancel();

            const mensagem = new SpeechSynthesisUtterance(texto);
            mensagem.lang = "pt-BR";
            mensagem.rate = 0.9;

            window.speechSynthesis.speak(mensagem);
        }
    </script>

</body>
</html>
