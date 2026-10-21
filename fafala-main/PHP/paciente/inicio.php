<?php
require_once __DIR__ . "/../tecnico/conexao.php";
require_once __DIR__ . "/../tecnico/estilo_paciente.php";
// Busca as categorias cadastradas.
$stmt = $pdo->query(
    "SELECT
        c.id_categoria,
        c.nome_categoria
     FROM categoria c
     ORDER BY c.nome_categoria"
);

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Imagens disponíveis na pasta do projeto.
$imagensCategorias = [
    "Alimentos" => "alimentacao.jpg",
    "Pessoas" => "cachorro.jpg",
    "Atividades" => "brinquedos.jpg",
    "Lugares" => "casa.jpg",
    "Emocoes" => "feliz.png",
    "Necessidades" => "agua.jpg"
];

$caminhoImagens = "/fafala-main/fafala/images_fafala_final/";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Minhas categorias | Fafala</title>

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

    <?php
    $paginaAtual = "inicio";
    include __DIR__ . "/../tecnico/topo_paciente.php";
    ?>

    <main class="container">

        <div class="cabecalho-inicio">
            <h1>Minhas categorias</h1>

            <label for="pesquisar" class="visualmente-oculto">
                Pesquisar categoria
            </label>

            <input
                type="search"
                id="pesquisar"
                class="campo-busca"
                placeholder="Pesquisar..."
                data-busca="#grade-pranchas"
            >
        </div>

        <?php if (empty($categorias)): ?>

            <p class="aviso-vazio">
                Nenhuma categoria cadastrada ainda.
            </p>

        <?php else: ?>

            <div class="grade-pranchas" id="grade-pranchas">

                <?php foreach ($categorias as $categoria): ?>

                    <?php
                    $nomeCategoria = $categoria["nome_categoria"];
                    $arquivoImagem = $imagensCategorias[$nomeCategoria] ?? null;
                    ?>

                    <a
                        href="/fafala-main/PHP/paciente/categorias.php?categoria=<?= (int) $categoria['id_categoria'] ?>"
                        class="card-prancha"
                        data-nome="<?= e($nomeCategoria) ?>"
                    >

                        <span class="card-prancha-imagem">

                            <?php if ($arquivoImagem !== null): ?>

                                <img
                                    src="<?= e($caminhoImagens . rawurlencode($arquivoImagem)) ?>"
                                    alt=""
                                    loading="lazy"
                                    onerror="this.style.display='none'; this.nextElementSibling.hidden=false;"
                                >

                                <span
                                    class="card-prancha-letra"
                                    aria-hidden="true"
                                    hidden
                                >
                                    <?= e(mb_strtoupper(mb_substr($nomeCategoria, 0, 1, 'UTF-8'))) ?>
                                </span>

                            <?php else: ?>

                                <span
                                    class="card-prancha-letra"
                                    aria-hidden="true"
                                >
                                    <?= e(mb_strtoupper(mb_substr($nomeCategoria, 0, 1, 'UTF-8'))) ?>
                                </span>

                            <?php endif; ?>

                        </span>

                        <span class="card-prancha-nome">
                            <?= e($nomeCategoria) ?>
                        </span>

                    </a>

                <?php endforeach; ?>

            </div>

            <p
                class="aviso-vazio"
                data-sem-resultado="#grade-pranchas"
                hidden
            >
                Nenhuma categoria encontrada.
            </p>

        <?php endif; ?>

    </main>

    <script src="/fafala-main/JS/busca.js"></script>

</body>
</html>