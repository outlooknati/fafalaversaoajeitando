<?php
require_once __DIR__ . '/conexao.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT arquivo_imagem FROM imagem WHERE id_imagem = ?');
$stmt->execute([$id]);
$img = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$img) {
    http_response_code(404);
    exit('Imagem não encontrada');
}

$dados = $img['arquivo_imagem'];
if (is_resource($dados)) {
    $dados = stream_get_contents($dados);
}

$tipo = (new finfo(FILEINFO_MIME_TYPE))->buffer($dados);

header('Content-Type: ' . $tipo);
header('Content-Length: ' . strlen($dados));
header('Cache-Control: public, max-age=86400');
echo $dados;