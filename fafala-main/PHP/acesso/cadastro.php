
<?php
require_once __DIR__ . "/../tecnico/conexao.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome_usuario"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmarSenha = $_POST["confirmar_senha"] ?? "";

    $nomePaciente = trim($_POST["nome_paciente"] ?? "");
    $nascimento = $_POST["data_nascimento"] ?? "";

    if (
        $nome === "" ||
        $email === "" ||
        $telefone === "" ||
        $nomePaciente === "" ||
        $nascimento === "" ||
        $senha === "" ||
        $confirmarSenha === ""
    ) {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Informe um e-mail válido.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha precisa ter pelo menos 8 caracteres.";
    } elseif ($senha !== $confirmarSenha) {
        $erro = "As senhas não coincidem.";
    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nascimento) ||
        !checkdate(
            (int) substr($nascimento, 5, 2),
            (int) substr($nascimento, 8, 2),
            (int) substr($nascimento, 0, 4)
        ) ||
        $nascimento > date("Y-m-d")
    ) {
        $erro = "Informe uma data de nascimento válida.";
    } else {
        try {
            $pdo->beginTransaction();

            // Cadastra o responsável.
            $stmt = $pdo->prepare(
                "INSERT INTO usuario
                (nome_usuario, email, senha_hash, data_cadastro, telefone)
                VALUES (?, ?, ?, CURDATE(), ?)"
            );

            $stmt->execute([
                $nome,
                $email,
                password_hash($senha, PASSWORD_DEFAULT),
                $telefone
            ]);

            $idUsuario = $pdo->lastInsertId();

            // Cadastra o paciente vinculado ao responsável.
            $stmt = $pdo->prepare(
                "INSERT INTO paciente
                (nome, data_nascimento, data_cadastro,
                 cor_card_tema, cor_fundo_tema, tamanho_card,
                 quantidade_cards, espacamento_cards, modo_escuro,
                 contraste, mostrar_texto, tempo_debounce, id_usuario)
                VALUES (?, ?, CURDATE(), '#FFFFFF', '#F5EFE6',
                        'Grande', 6, 'Normal', 0, 'Normal', 1, 500, ?)"
            );

            $stmt->execute([
                $nomePaciente,
                $nascimento,
                $idUsuario
            ]);

            $idPaciente = $pdo->lastInsertId();

            // Confirma os dois cadastros.
            $pdo->commit();

            // Guarda os IDs na sessão.
            session_regenerate_id(true);
            $_SESSION["id_usuario"] = $idUsuario;
            $_SESSION["id_paciente"] = $idPaciente;
            $_SESSION["nome_usuario"] = $nome;

            // Questionário na mesma pasta de cadastro.php.
            header("Location: questionario.php");
            exit;

        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($ex->getCode() === "23000") {
                $erro = "Este e-mail já está cadastrado.";
            } else {
                error_log("Erro no cadastro FAFALA: " . $ex->getMessage());
                $erro = "Não foi possível realizar o cadastro. Verifique os dados e tente novamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | Fafala</title>

    <link rel="stylesheet" href="../../CSS/style.css">
    <link rel="stylesheet" href="../../CSS/especifico/acesso_dados.css">
</head>

<body>
    <main class="pagina-acesso">
        <section class="card card-acesso" aria-labelledby="titulo-acesso">

            <!-- Coloque a logo aqui quando estiver pronta. -->

            <header class="cabecalho-acesso">
                <h1 id="titulo-acesso">Crie sua conta</h1>
                <p>Configure o Fafala para atender às necessidades do paciente.</p>
            </header>

            <?php if ($erro !== ""): ?>
                <div class="alerta alerta-erro" role="alert">
                    <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form action="cadastro.php" method="post" id="form-acesso">

                <fieldset>
                    <legend>Dados do responsável</legend>

                    <div class="campo">
                        <label for="nome_usuario">Nome completo</label>
                        <input
                            type="text"
                            id="nome_usuario"
                            name="nome_usuario"
                            placeholder="Ex.: Maria da Silva"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="grade">
                        <div class="campo">
                            <label for="email">E-mail</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="exemplo@email.com"
                                maxlength="100"
                                autocomplete="email"
                                required
                            >
                        </div>

                        <div class="campo">
                            <label for="telefone">Telefone</label>
                            <input
                                type="tel"
                                id="telefone"
                                name="telefone"
                                placeholder="(00) 00000-0000"
                                maxlength="15"
                                inputmode="numeric"
                                autocomplete="tel"
                                required
                            >
                        </div>
                    </div>

                    <div class="grade">
                        <div class="campo">
                            <label for="senha">Senha</label>
                            <div class="campo-senha">
                                <input
                                    type="password"
                                    id="senha"
                                    name="senha"
                                    placeholder="Crie uma senha"
                                    minlength="8"
                                    autocomplete="new-password"
                                    aria-describedby="dica-senha"
                                    required
                                >

                                <button
                                    type="button"
                                    class="mostrar-senha"
                                    aria-label="Mostrar senha"
                                    aria-pressed="false"
                                    data-alvo="senha"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>

                            <small id="dica-senha" class="dica">
                                Use pelo menos 8 caracteres.
                            </small>
                        </div>

                        <div class="campo">
                            <label for="confirmar_senha">Confirmar senha</label>
                            <div class="campo-senha">
                                <input
                                    type="password"
                                    id="confirmar_senha"
                                    name="confirmar_senha"
                                    placeholder="Digite a senha novamente"
                                    autocomplete="new-password"
                                    aria-describedby="erro-senha"
                                    required
                                >

                                <button
                                    type="button"
                                    class="mostrar-senha"
                                    aria-label="Mostrar senha"
                                    aria-pressed="false"
                                    data-alvo="confirmar_senha"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>

                            <small id="erro-senha" class="mensagem-erro" aria-live="polite"></small>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Dados do paciente</legend>

                    <div class="grade">
                        <div class="campo">
                            <label for="nome_paciente">Nome do paciente</label>
                            <input
                                type="text"
                                id="nome_paciente"
                                name="nome_paciente"
                                placeholder="Ex.: João"
                                maxlength="100"
                                required
                            >
                        </div>

                        <div class="campo">
                            <label for="data_nascimento">D de nascimento</label>
                            <input
                                type="date"
                                id="data_nascimento"
                                name="data_nascimento"
                                max="<?= date("Y-m-d") ?>"
                                required
                            >
                        </div>
                    </div>
                </fieldset>

                <button type="submit" id="botao-acesso" class="botao">
                    Criar conta e continuar
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9 6l6 6-6 6"/>
                    </svg>
                </button>
            </form>

            <footer class="rodape-acesso">
                <p>
                    <a href="login.php">Já tem uma conta? Entrar</a>
                </p>
            </footer>

        </section>
    </main>

    <script src="../../JS/especifico/cadastro.js" defer></script>
</body>
</html>
