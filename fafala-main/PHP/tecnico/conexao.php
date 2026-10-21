<?php
session_start();

$host = "localhost";
$banco = "FAFALA";
$usuario = "root";
$senha = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados. Confira o XAMPP e o banco FAFALA.");
}

function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function exigir_login() {
    if (!isset($_SESSION["id_usuario"])) {
        header("Location: login.php");
        exit;
    }
}

function cabecalho($titulo = "Fafala") {
    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . e($titulo) . ' - Fafala</title>
    </head>
    <body>
        <header>
            <h1>Fafala</h1>
            <nav>
                <a href="index.php">Início</a> |
                <a href="cadastro.php">Cadastrar paciente</a> |
                <a href="pictogramas.php">Pictogramas</a> |
                <a href="pranchas.php">Pranchas</a> |
                <a href="questionario.php">Questionário</a> |
                <a href="historico.php">Histórico</a> |
                <a href="configuracoes.php">Configurações</a> |
                <a href="sair.php">Sair</a>
            </nav>
            <hr>
        </header>
        <main>
        <h2>' . e($titulo) . '</h2>';
}

function rodape() {
    echo '</main>
        <hr>
        <footer><p>Fafala - Comunicação acessível</p></footer>
    </body>
    </html>';
}
?>