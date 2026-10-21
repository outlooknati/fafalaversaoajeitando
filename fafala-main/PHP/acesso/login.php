
<?php
require_once __DIR__ . "/../tecnico/conexao.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado, vai para a página inicial.
if (!empty($_SESSION["id_usuario"]) && !empty($_SESSION["id_paciente"])) {
    header("Location: ../../index.php");
    exit;
}

// Token de segurança CSRF.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$erro = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = mb_strtolower(trim($_POST["email"] ?? ""));
    $senhaDigitada = $_POST["senha"] ?? "";
    $token = $_POST["csrf_token"] ?? "";

    if (
        !isset($_SESSION["csrf_token"]) ||
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $erro = "Requisição inválida. Recarregue a página e tente novamente.";
    } elseif ($email === "" || $senhaDigitada === "") {
        $erro = "Preencha e-mail e senha.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT
                u.id_usuario,
                u.nome_usuario,
                u.senha_hash,
                p.id_paciente
             FROM usuario u
             LEFT JOIN paciente p
                ON p.id_usuario = u.id_usuario
             WHERE u.email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $usuario &&
            password_verify($senhaDigitada, $usuario["senha_hash"])
        ) {
            // Verifica se existe paciente vinculado à conta.
            if (empty($usuario["id_paciente"])) {
                $erro = "Não existe um paciente vinculado a esta conta. Verifique o cadastro no banco de dados.";
            } else {
                session_regenerate_id(true);

                $_SESSION["id_usuario"] = $usuario["id_usuario"];
                $_SESSION["nome_usuario"] = $usuario["nome_usuario"];
                $_SESSION["id_paciente"] = $usuario["id_paciente"];

                // Cria um novo token após entrar.
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                // Atualiza o hash caso seja necessário.
                if (password_needs_rehash($usuario["senha_hash"], PASSWORD_DEFAULT)) {
                    $novoHash = password_hash(
                        $senhaDigitada,
                        PASSWORD_DEFAULT
                    );

                    $upd = $pdo->prepare(
                        "UPDATE usuario
                         SET senha_hash = ?
                         WHERE id_usuario = ?"
                    );

                    $upd->execute([
                        $novoHash,
                        $usuario["id_usuario"]
                    ]);
                }

                // Vai para a página inicial.
                header("Location: ../../index.php");
                exit;
            }
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    }
}

cabecalho("Entrar");
?>

<link rel="stylesheet" href="../../CSS/style.css">
<link rel="stylesheet" href="../../CSS/especifico/login.css">

<main class="login-pagina">
    <div class="login-card card">

        <div class="login-intro">
            <h1>Bem-vindo ao FAFALA!</h1>
            <p class="texto-suave">Entre na sua conta para continuar.</p>
        </div>

        <?php if ($erro !== ""): ?>
            <div class="alerta alerta-erro" role="alert">
                <?= e($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="login-form">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION["csrf_token"]) ?>"
            >

            <div class="campo">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Digite seu e-mail"
                    autocomplete="email"
                    value="<?= e($email) ?>"
                    required
                    autofocus
                >
            </div>

            <div class="campo">
                <label for="senha">Senha</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    placeholder="Digite sua senha"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="botao">Entrar</button>
        </form>

        <div class="login-cadastro">
            <p class="texto-suave">Ainda não tem conta?</p>
            <a href="cadastro.php?tipo=conta">Cadastre-se</a>
        </div>

    </div>
</main>

<?php rodape(); ?>
