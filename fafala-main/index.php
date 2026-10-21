
<?php


require_once __DIR__ . "/PHP/tecnico/conexao.php";
require_once __DIR__ . "/PHP/tecnico/estilo_paciente.php";

// Identifica o paciente associado à sessão, se houver.
$idPaciente = $_SESSION["id_paciente"] ?? null;

// Busca as pranchas padrão e as pranchas do paciente.
if ($idPaciente !== null) {
    $stmt = $pdo->prepare(
        "SELECT
            p.id_prancha,
            p.nome_prancha,
            p.descricao_prancha,
            (
                SELECT pi.id_imagem
                FROM prancha_imagem pi
                WHERE pi.id_prancha = p.id_prancha
                ORDER BY pi.ordem_exibicao
                LIMIT 1
            ) AS id_capa
        FROM prancha p
        WHERE p.tipo_prancha = 'Padrão'
           OR p.id_paciente = ?
        ORDER BY p.tipo_prancha, p.nome_prancha"
    );

    $stmt->execute([$idPaciente]);
} else {
    $stmt = $pdo->prepare(
        "SELECT
            p.id_prancha,
            p.nome_prancha,
            p.descricao_prancha,
            (
                SELECT pi.id_imagem
                FROM prancha_imagem pi
                WHERE pi.id_prancha = p.id_prancha
                ORDER BY pi.ordem_exibicao
                LIMIT 1
            ) AS id_capa
        FROM prancha p
        WHERE p.tipo_prancha = 'Padrão'
        ORDER BY p.nome_prancha"
    );

    $stmt->execute();
}

$pranchas = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciar pranchas | Fafala</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/fafala-main/CSS/style.css">
    <link rel="stylesheet" href="/fafala-main/CSS/acessibilidade.css">
    <link rel="stylesheet" href="/fafala-main/CSS/especifico/paciente.css">
</head>

<body class="<?= e($classesPaciente ?? '') ?>">

    <?php
    $paginaAtual = "gerenciar";
    include __DIR__ . "/PHP/tecnico/topo_paciente.php";
    ?>

    <main class="container">

        <div class="cabecalho-inicio">
            <h1>Gerenciar pranchas</h1>

            <label for="pesquisar" class="visualmente-oculto">
                Pesquisar prancha
            </label>

            <input
                type="search"
                id="pesquisar"
                class="campo-busca"
                placeholder="Pesquisar prancha..."
                data-busca="#grade-pranchas"
            >
        </div>

        <?php if (count($pranchas) === 0): ?>

            <p class="aviso-vazio">
                Nenhuma prancha cadastrada ainda.
            </p>

        <?php else: ?>

            <div class="grade-pranchas" id="grade-pranchas">

                <?php foreach ($pranchas as $prancha): ?>

                    <a
                        href="/fafala-main/PHP/paciente/pranchas.php?prancha=<?= (int) $prancha["id_prancha"] ?>"
                        class="card-prancha"
                        data-nome="<?= e($prancha["nome_prancha"]) ?>"
                    >

                        <span class="card-prancha-imagem">

                            <?php if (!empty($prancha["id_capa"])): ?>

                                <img
                                    src="/fafala-main/PHP/tecnico/imagem.php?id=<?= (int) $prancha["id_capa"] ?>"
                                    alt=""
                                >

                            <?php else: ?>

                                <span class="card-prancha-letra" aria-hidden="true">
                                    <?= e(mb_strtoupper(mb_substr($prancha["nome_prancha"], 0, 1, "UTF-8"), "UTF-8")) ?>
                                </span>

                            <?php endif; ?>

                        </span>

                        <span class="card-prancha-nome">
                            <?= e($prancha["nome_prancha"]) ?>
                        </span>

                        <?php if (!empty($prancha["descricao_prancha"])): ?>
                            <span class="card-prancha-descricao">
                                <?= e($prancha["descricao_prancha"]) ?>
                            </span>
                        <?php endif; ?>

                    </a>

                <?php endforeach; ?>

            </div>

            <p
                class="aviso-vazio"
                data-sem-resultado="#grade-pranchas"
                hidden
            >
                Nenhuma prancha encontrada.
            </p>

        <?php endif; ?>

    </main>

    <script src="/fafala-main/JS/busca.js"></script>

</body>
</html>
