<?php
// PHP/registrar_uso.php

require_once __DIR__ . "/tecnicos/conexao.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION["id_usuario"])) {
    header("Location: acesso/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

$idPaciente = filter_input(INPUT_POST, "id_paciente", FILTER_VALIDATE_INT);
$idPrancha = filter_input(INPUT_POST, "id_prancha", FILTER_VALIDATE_INT);
$idImagem = filter_input(INPUT_POST, "id_imagem", FILTER_VALIDATE_INT);

if (!$idPaciente || !$idPrancha || !$idImagem) {
    http_response_code(400);
    exit("Dados de utilização inválidos.");
}

try {
    // Confere se o paciente pertence ao responsável
    // e se a imagem está na prancha selecionada.
    $consulta = $pdo->prepare(
        "SELECT p.id_paciente
         FROM paciente p
         INNER JOIN prancha pr
            ON pr.id_paciente = p.id_paciente
         INNER JOIN prancha_imagem pi
            ON pi.id_prancha = pr.id_prancha
         INNER JOIN imagem i
            ON i.id_imagem = pi.id_imagem
         WHERE p.id_paciente = ?
           AND pr.id_prancha = ?
           AND i.id_imagem = ?
           AND p.id_usuario = ?
         LIMIT 1"
    );

    $consulta->execute([
        $idPaciente,
        $idPrancha,
        $idImagem,
        $_SESSION["id_usuario"]
    ]);

    if (!$consulta->fetch()) {
        http_response_code(403);
        exit("Não foi possível validar esse uso.");
    }

    // Registra a utilização.
    $registro = $pdo->prepare(
        "INSERT INTO registro_de_uso
            (data_horario_uso, id_prancha, id_paciente, id_imagem)
         VALUES (NOW(), ?, ?, ?)"
    );

    $registro->execute([
        $idPrancha,
        $idPaciente,
        $idImagem
    ]);

    header(
        "Location: historico.php?id_paciente=" . $idPaciente .
        "&registro=sucesso"
    );
    exit;

} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    exit("Erro ao registrar a utilização.");
}

