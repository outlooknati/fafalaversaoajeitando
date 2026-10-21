<?php

require_once __DIR__ . "/conexao.php";

// Verifica se existe usuário conectado
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../acesso/login.php");
    exit;
}

$idUsuario = (int) $_SESSION["id_usuario"];

// Protege os textos exibidos na página
function h($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

// Valores iniciais
$totalUsos = 0;
$totalSemana = 0;
$pictogramas = [];
$porPaciente = [];
$erroRelatorio = false;

try {
    // 1. Total de utilizações dos pacientes do usuário conectado
    $sql = "
        SELECT COUNT(*)
        FROM registro_de_uso r
        INNER JOIN paciente p
            ON p.id_paciente = r.id_paciente
        WHERE p.id_usuario = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);
    $totalUsos = (int) $stmt->fetchColumn();


    // 2. Utilizações realizadas nos últimos 7 dias
    $sql = "
        SELECT COUNT(*)
        FROM registro_de_uso r
        INNER JOIN paciente p
            ON p.id_paciente = r.id_paciente
        WHERE p.id_usuario = ?
          AND r.data_horario_uso >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND r.data_horario_uso <= NOW()
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);
    $totalSemana = (int) $stmt->fetchColumn();


    // 3. Pictogramas mais utilizados
    $sql = "
        SELECT
            i.nome_pictograma,
            COUNT(*) AS total
        FROM registro_de_uso r
        INNER JOIN imagem i
            ON i.id_imagem = r.id_imagem
        INNER JOIN paciente p
            ON p.id_paciente = r.id_paciente
        WHERE p.id_usuario = ?
        GROUP BY i.id_imagem, i.nome_pictograma
        ORDER BY total DESC, i.nome_pictograma ASC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);
    $pictogramas = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // 4. Quantidade de utilizações por paciente
    $sql = "
        SELECT
            p.id_paciente,
            p.nome,
            COUNT(r.id_registro) AS total
        FROM paciente p
        LEFT JOIN registro_de_uso r
            ON r.id_paciente = p.id_paciente
        WHERE p.id_usuario = ?
        GROUP BY p.id_paciente, p.nome
        ORDER BY total DESC, p.nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);
    $porPaciente = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro no relatório FAFALA: " . $e->getMessage());
    $erroRelatorio = true;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Relatório | FAFALA</title>

    <link rel="stylesheet" href="../../CSS/style.css">
    <link rel="stylesheet" href="../../CSS/historico.css">
</head>

<body>

<main class="pagina-relatorio">

    <header class="cabecalho-pagina">
        <h1>Relatório de utilização</h1>
        <p>
            Confira os dados de utilização das pranchas e dos pictogramas.
        </p>
    </header>

    <?php if ($erroRelatorio): ?>
        <div class="alerta alerta-erro">
            Não foi possível carregar os dados do relatório.
            Verifique a conexão com o banco de dados.
        </div>
    <?php endif; ?>


    <!-- Resumo das utilizações -->
    <section class="secao-relatorio">

        <h2>Resumo</h2>

        <div class="resumo-relatorio">

            <article class="indicador-relatorio">
                <h2>Total de utilizações</h2>
                <strong><?= $totalUsos ?></strong>
            </article>

            <article class="indicador-relatorio">
                <h2>Utilizações nos últimos 7 dias</h2>
                <strong><?= $totalSemana ?></strong>
            </article>

        </div>

    </section>


    <!-- Pictogramas mais utilizados -->
    <section class="secao-relatorio card">

        <h2>Pictogramas mais utilizados</h2>

        <div class="tabela-responsiva">

            <table class="tabela-relatorio">

                <thead>
                    <tr>
                        <th>Pictograma</th>
                        <th>Quantidade de usos</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($pictogramas)): ?>

                    <tr>
                        <td colspan="2" class="texto-centralizado">
                            Ainda não há utilizações registradas.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($pictogramas as $item): ?>

                        <tr>
                            <td>
                                <?= h($item["nome_pictograma"]) ?>
                            </td>

                            <td>
                                <?= (int) $item["total"] ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- Utilizações por paciente -->
    <section class="secao-relatorio card">

        <h2>Utilizações por paciente</h2>

        <div class="tabela-responsiva">

            <table class="tabela-relatorio">

                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>Total de utilizações</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($porPaciente)): ?>

                    <tr>
                        <td colspan="2" class="texto-centralizado">
                            Nenhum paciente encontrado.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($porPaciente as $item): ?>

                        <tr>
                            <td>
                                <?= h($item["nome"]) ?>
                            </td>

                            <td>
                                <?= (int) $item["total"] ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- Ações da página -->
    <div class="acoes-pagina">

        <a
            href="historico.php"
            class="botao botao-secundario"
        >
            Voltar ao histórico
        </a>

    </div>

</main>

</body>
</html>
