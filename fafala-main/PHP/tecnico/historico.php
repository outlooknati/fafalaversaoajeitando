<?php
// PHP/historico.php

require_once __DIR__ . "/conexao.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["id_usuario"])) {
    header("Location: acesso/login.php");
    exit;
}

$idUsuario = (int) $_SESSION["id_usuario"];

$idPaciente = filter_input(
    INPUT_GET,
    "id_paciente",
    FILTER_VALIDATE_INT
);

$idPrancha = filter_input(
    INPUT_GET,
    "id_prancha",
    FILTER_VALIDATE_INT
);

$idImagem = filter_input(
    INPUT_GET,
    "id_imagem",
    FILTER_VALIDATE_INT
);

$dataInicial = $_GET["data_inicial"] ?? "";
$dataFinal = $_GET["data_final"] ?? "";

// Lista somente os pacientes do responsável conectado.
$stmt = $pdo->prepare(
    "SELECT id_paciente, nome
     FROM paciente
     WHERE id_usuario = ?
     ORDER BY nome"
);
$stmt->execute([$idUsuario]);
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista as pranchas dos pacientes do responsável.
$stmt = $pdo->prepare(
    "SELECT pr.id_prancha, pr.nome_prancha, pr.id_paciente
     FROM prancha pr
     INNER JOIN paciente p ON p.id_paciente = pr.id_paciente
     WHERE p.id_usuario = ?
     ORDER BY pr.nome_prancha"
);
$stmt->execute([$idUsuario]);
$pranchas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista os pictogramas pertencentes ao responsável.
$stmt = $pdo->prepare(
    "SELECT id_imagem, nome_pictograma
     FROM imagem
     WHERE id_usuario = ?
     ORDER BY nome_pictograma"
);
$stmt->execute([$idUsuario]);
$imagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta o histórico do responsável conectado.
$sql = "
    SELECT
        r.data_horario_uso,
        p.id_paciente,
        p.nome AS nome_paciente,
        pr.id_prancha,
        pr.nome_prancha,
        i.id_imagem,
        i.nome_pictograma
    FROM registro_de_uso r
    INNER JOIN paciente p
        ON p.id_paciente = r.id_paciente
    INNER JOIN prancha pr
        ON pr.id_prancha = r.id_prancha
    INNER JOIN imagem i
        ON i.id_imagem = r.id_imagem
    WHERE p.id_usuario = ?
";

$params = [$idUsuario];

if ($idPaciente !== false && $idPaciente !== null && $idPaciente > 0) {
    $sql .= " AND p.id_paciente = ?";
    $params[] = $idPaciente;
}

if ($idPrancha !== false && $idPrancha !== null && $idPrancha > 0) {
    $sql .= " AND pr.id_prancha = ?";
    $params[] = $idPrancha;
}

if ($idImagem !== false && $idImagem !== null && $idImagem > 0) {
    $sql .= " AND i.id_imagem = ?";
    $params[] = $idImagem;
}

// Valida o formato das datas antes de filtrar.
if (
    $dataInicial !== "" &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicial) &&
    strtotime($dataInicial) !== false
) {
    $sql .= " AND r.data_horario_uso >= ?";
    $params[] = $dataInicial . " 00:00:00";
}

if (
    $dataFinal !== "" &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFinal) &&
    strtotime($dataFinal) !== false
) {
    $sql .= " AND r.data_horario_uso < DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $dataFinal;
}

$sql .= " ORDER BY r.data_horario_uso DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Escapa os valores exibidos no HTML.
function h($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Histórico | FAFALA</title>

    <link rel="stylesheet" href="../../CSS/style.css">
    <link rel="stylesheet" href="../../CSS/especifico/historico.css">
</head>

<body>


    <?php
    include __DIR__ . "/topo_paciente.php";
    ?>

    <main class="container pagina-historico">

        <header class="cabecalho-pagina">
            <h1>Histórico de utilização</h1>
            <p class="texto-suave">
                Consulte os registros de utilização dos pacientes, pranchas e pictogramas.
            </p>
        </header>

        <?php if (($_GET["registro"] ?? "") === "sucesso"): ?>
            <div class="alerta alerta-sucesso" role="status">
                Utilização registrada com sucesso!
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <section class="card">
            <h2>Filtrar histórico</h2>

            <form method="get" action="historico.php" class="grade">

                <div class="campo">
                    <label for="id_paciente">Paciente</label>
                    <select name="id_paciente" id="id_paciente">
                        <option value="">Todos os pacientes</option>
                        <?php foreach ($pacientes as $paciente): ?>
                            <option
                                value="<?= (int) $paciente["id_paciente"] ?>"
                                <?= $idPaciente == $paciente["id_paciente"] ? "selected" : "" ?>>
                                <?= h($paciente["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="id_prancha">Prancha</label>
                    <select name="id_prancha" id="id_prancha">
                        <option value="">Todas as pranchas</option>
                        <?php foreach ($pranchas as $prancha): ?>
                            <option
                                value="<?= (int) $prancha["id_prancha"] ?>"
                                <?= $idPrancha == $prancha["id_prancha"] ? "selected" : "" ?>>
                                <?= h($prancha["nome_prancha"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="data_inicial">Data inicial</label>
                    <input
                        type="date"
                        name="data_inicial"
                        id="data_inicial"
                        value="<?= h($dataInicial) ?>">
                </div>

                <div class="campo">
                    <label for="data_final">Data final</label>
                    <input
                        type="date"
                        name="data_final"
                        id="data_final"
                        value="<?= h($dataFinal) ?>">
                </div>

                <div class="campo campo-largo">
                    <label for="id_imagem">Pictograma</label>
                    <select name="id_imagem" id="id_imagem">
                        <option value="">Todos os pictogramas</option>
                        <?php foreach ($imagens as $imagem): ?>
                            <option
                                value="<?= (int) $imagem["id_imagem"] ?>"
                                <?= $idImagem == $imagem["id_imagem"] ? "selected" : "" ?>>
                                <?= h($imagem["nome_pictograma"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="botoes-filtro">
                    <button type="submit" class="botao botao-auto">
                        Consultar
                    </button>
                    <a href="historico.php" class="botao botao-auto botao-secundario">
                        Limpar filtros
                    </a>
                </div>

            </form>
        </section>

        <!-- Resultado da consulta -->
        <section class="card">
            <h2>Registros encontrados</h2>

            <p class="resumo-total">
                Total de registros encontrados:
                <strong><?= count($historico) ?></strong>
            </p>

            <div class="tabela-responsiva">
                <table class="tabela-historico">
                    <thead>
                        <tr>
                            <th>Data e horário</th>
                            <th>Paciente</th>
                            <th>Prancha</th>
                            <th>Pictograma</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$historico): ?>
                            <tr>
                                <td colspan="4" class="estado-vazio">
                                    Nenhum registro encontrado para os filtros selecionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historico as $registro): ?>
                                <tr>
                                    <td><?= h($registro["data_horario_uso"]) ?></td>
                                    <td><?= h($registro["nome_paciente"]) ?></td>
                                    <td><?= h($registro["nome_prancha"]) ?></td>
                                    <td><?= h($registro["nome_pictograma"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Acesso ao relatório -->
        <div class="acoes-pagina">
            <a href="relatorio.php" class="botao botao-auto">
                Ver relatório de utilização
            </a>
        </div>

    </main>

</body>
</html>