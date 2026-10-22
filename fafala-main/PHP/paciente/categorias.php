<?php
// PHP/paciente/categorias.php


require_once __DIR__ . "/../tecnico/conexao.php";
require_once __DIR__ . "/../tecnico/estilo_paciente.php";
require_once __DIR__ . "/pranchas_dados.php";

if (!function_exists("e")) {
    function e($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
    }
}

$idCategoria = (int) ($_GET["categoria"] ?? 0);

if ($idCategoria <= 0) {
    http_response_code(400);
    exit("Categoria não informada.");
}

// Só o nome da categoria vem do banco.
$stmt = $pdo->prepare("
    SELECT id_categoria, nome_categoria
    FROM categoria
    WHERE id_categoria = ?
    LIMIT 1
");
$stmt->execute([$idCategoria]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    http_response_code(404);
    exit("Categoria não encontrada.");
}

// Pranchas da categoria (se o nome não estiver no mapa, mostra todas).
$chave = normalizarNome($categoria["nome_categoria"]);
$slugs = $categoriasPranchas[$chave] ?? array_keys($pranchasDados);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($categoria["nome_categoria"]) ?> | Fafala</title>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="/fafala-main/CSS/style.css">
    <link rel="stylesheet" href="/fafala-main/CSS/acessibilidade.css">
    <link rel="stylesheet" href="/fafala-main/CSS/especifico/paciente.css">

    <?= $estiloPaciente ?? "" ?>
</head>

<body class="<?= e($classesPaciente ?? '') ?>">

    <?php
    $paginaAtual = "inicio";
    include __DIR__ . "/../tecnico/topo_paciente.php";
    ?>

    <main class="container">

        <p>
            <a href="/fafala-main/PHP/paciente/inicio.php">← Voltar às categorias</a>
        </p>

        <div class="cabecalho-inicio">
            <h1><?= e($categoria["nome_categoria"]) ?></h1>

            <label for="pesquisar" class="visualmente-oculto">Pesquisar prancha</label>
            <input
                type="search"
                id="pesquisar"
                class="campo-busca"
                placeholder="Pesquisar..."
                data-busca="#grade-pranchas"
            >
        </div>

        <?php if (empty($slugs)): ?>

            <p class="aviso-vazio">Nenhuma prancha nesta categoria ainda.</p>

        <?php else: ?>

            <div class="grade-pranchas" id="grade-pranchas">

                <?php foreach ($slugs as $slug): ?>
                    <?php
                    if (!isset($pranchasDados[$slug])) { continue; }

                    $p     = $pranchasDados[$slug];
                    $nome  = $p["titulo"];
                    $letra = mb_strtoupper(mb_substr($nome, 0, 1, "UTF-8"), "UTF-8");
                    ?>

                    <a
                        href="/fafala-main/PHP/paciente/prancha.php?prancha=<?= e($slug) ?>&categoria=<?= (int) $idCategoria ?>"
                        class="card-prancha"
                        data-nome="<?= e($nome) ?>"
                    >
                        <span class="card-prancha-imagem">
                            <img
                                src="<?= e($caminhoImagens . rawurlencode($p["capa"])) ?>"
                                alt=""
                                loading="lazy"
                                onerror="this.style.display='none'; this.nextElementSibling.hidden=false;"
                            >
                            <span class="card-prancha-letra" aria-hidden="true" hidden><?= e($letra) ?></span>
                        </span>

                        <span class="card-prancha-nome"><?= e($nome) ?></span>
                    </a>
                <?php endforeach; ?>

            </div>

            <p class="aviso-vazio" data-sem-resultado="#grade-pranchas" hidden>
                Nenhuma prancha encontrada.
            </p>

        <?php endif; ?>

    </main>

    <script src="/fafala-main/JS/busca.js"></script>
</body>
</html>