<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function responder($dados, int $status = 200): never {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function erro(string $mensagem, int $status = 400): never {
    responder(['erro' => $mensagem], $status);
}


function guardarImagem(PDO $db): ?int {
    if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES['imagem'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        erro('Falha no upload da imagem (código ' . $f['error'] . ').');
    }
    if ($f['size'] > UPLOAD_MAX_BYTES) {
        erro('Imagem muito grande (máximo ' . (UPLOAD_MAX_BYTES / 1024 / 1024) . ' MB).');
    }

    $dados = file_get_contents($f['tmp_name']);
    $mime  = (new finfo(FILEINFO_MIME_TYPE))->buffer($dados);
    if ($mime === 'application/octet-stream' && substr($dados, 4, 8) === 'ftypavif') {
        $mime = 'image/avif';
    }
    $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
    if (!in_array($mime, $permitidos, true)) {
        erro('Formato não permitido. Use JPG, PNG, GIF, WEBP ou AVIF.');
    }

    $stmt = $db->prepare('INSERT INTO imagens (mime, dados) VALUES (?, ?)');
    $stmt->bindValue(1, $mime);
    $stmt->bindValue(2, $dados, PDO::PARAM_LOB);
    $stmt->execute();
    return (int)$db->lastInsertId();
}


function apagarImagem(PDO $db, ?int $imagemId): void {
    if ($imagemId) {
        $db->prepare('DELETE FROM imagens WHERE id = ?')->execute([$imagemId]);
    }
}


function nomeValido(): string {
    $nome = trim($_POST['nome'] ?? '');
    if ($nome === '') erro('Digite um nome.');
    if (mb_strlen($nome) > 100) erro('Nome muito longo (máximo 100 caracteres).');
    return $nome;
}


try {
    $db   = conectar();
    $acao = $_GET['acao'] ?? '';

    if ((str_starts_with($acao, 'salvar') || str_starts_with($acao, 'excluir')) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        erro('Use POST.', 405);
    }

    switch ($acao) {

        case 'categorias':
            responder($db->query('SELECT id, nome, imagem_id FROM categorias ORDER BY id')->fetchAll());

        case 'pictogramas':
            $cid  = (int)($_GET['categoria_id'] ?? 0);
            $stmt = $db->prepare('SELECT id, nome, imagem_id FROM categorias WHERE id = ?');
            $stmt->execute([$cid]);
            $categoria = $stmt->fetch();
            if (!$categoria) erro('Categoria não encontrada.', 404);
            $stmt = $db->prepare('SELECT id, nome, imagem_id FROM pictogramas WHERE categoria_id = ? ORDER BY id');
            $stmt->execute([$cid]);
            responder(['categoria' => $categoria, 'pictogramas' => $stmt->fetchAll()]);

        case 'salvar_categoria':
            $id   = (int)($_POST['id'] ?? 0);
            $nome = nomeValido();
            $novaImagem = guardarImagem($db);

            if ($id) {
                $stmt = $db->prepare('SELECT imagem_id FROM categorias WHERE id = ?');
                $stmt->execute([$id]);
                $atual = $stmt->fetch();
                if (!$atual) erro('Categoria não encontrada.', 404);

                if ($novaImagem) {
                    $db->prepare('UPDATE categorias SET nome = ?, imagem_id = ? WHERE id = ?')->execute([$nome, $novaImagem, $id]);
                    apagarImagem($db, $atual['imagem_id'] ? (int)$atual['imagem_id'] : null);
                } else {
                    $db->prepare('UPDATE categorias SET nome = ? WHERE id = ?')->execute([$nome, $id]);
                }
            } else {
                $db->prepare('INSERT INTO categorias (nome, imagem_id) VALUES (?, ?)')->execute([$nome, $novaImagem]);
                $id = (int)$db->lastInsertId();
            }
            responder(['ok' => true, 'id' => $id]);

        case 'excluir_categoria':
            $id = (int)($_POST['id'] ?? 0);
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT imagem_id FROM pictogramas WHERE categoria_id = ? AND imagem_id IS NOT NULL');
            $stmt->execute([$id]);
            $imagens = array_column($stmt->fetchAll(), 'imagem_id');
            $stmt = $db->prepare('SELECT imagem_id FROM categorias WHERE id = ?');
            $stmt->execute([$id]);
            $cat = $stmt->fetch();
            if (!$cat) { $db->rollBack(); erro('Categoria não encontrada.', 404); }
            if ($cat['imagem_id']) $imagens[] = $cat['imagem_id'];

            $db->prepare('DELETE FROM pictogramas WHERE categoria_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
            foreach ($imagens as $imgId) apagarImagem($db, (int)$imgId);
            $db->commit();
            responder(['ok' => true]);

        case 'salvar_pictograma':
            $id   = (int)($_POST['id'] ?? 0);
            $nome = nomeValido();
            $novaImagem = guardarImagem($db);

            if ($id) {
                $stmt = $db->prepare('SELECT imagem_id FROM pictogramas WHERE id = ?');
                $stmt->execute([$id]);
                $atual = $stmt->fetch();
                if (!$atual) erro('Pictograma não encontrado.', 404);

                if ($novaImagem) {
                    $db->prepare('UPDATE pictogramas SET nome = ?, imagem_id = ? WHERE id = ?')->execute([$nome, $novaImagem, $id]);
                    apagarImagem($db, $atual['imagem_id'] ? (int)$atual['imagem_id'] : null);
                } else {
                    $db->prepare('UPDATE pictogramas SET nome = ? WHERE id = ?')->execute([$nome, $id]);
                }
            } else {
                $cid  = (int)($_POST['categoria_id'] ?? 0);
                $stmt = $db->prepare('SELECT id FROM categorias WHERE id = ?');
                $stmt->execute([$cid]);
                if (!$stmt->fetch()) erro('Categoria não encontrada.', 404);

                $db->prepare('INSERT INTO pictogramas (categoria_id, nome, imagem_id) VALUES (?, ?, ?)')->execute([$cid, $nome, $novaImagem]);
                $id = (int)$db->lastInsertId();
            }
            responder(['ok' => true, 'id' => $id]);

        case 'excluir_pictograma':
            $id   = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare('SELECT imagem_id FROM pictogramas WHERE id = ?');
            $stmt->execute([$id]);
            $p = $stmt->fetch();
            if (!$p) erro('Pictograma não encontrado.', 404);

            $db->prepare('DELETE FROM pictogramas WHERE id = ?')->execute([$id]);
            apagarImagem($db, $p['imagem_id'] ? (int)$p['imagem_id'] : null);
            responder(['ok' => true]);

        default:
            erro('Ação inválida.', 404);
    }
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    erro('Erro no servidor: ' . $e->getMessage(), 500);
}



