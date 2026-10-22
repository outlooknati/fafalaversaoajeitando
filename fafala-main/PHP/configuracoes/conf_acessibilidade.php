<?php
require_once __DIR__ . "/../tecnico/conexao.php";
require_once __DIR__ . "/../tecnico/temas.php";

// só o responsável logado pode mudar as configurações
exigir_login();

$idUsuario = $_SESSION['id_usuario'];

// paciente do responsável (se ainda não estiver na sessão, busca no banco)
$idPaciente = $_SESSION['id_paciente'] ?? null;

if (!$idPaciente) {
    $stmt = $pdo->prepare("SELECT id_paciente FROM paciente WHERE id_usuario = ? ORDER BY id_paciente LIMIT 1");
    $stmt->execute([$idUsuario]);
    $idPaciente = $stmt->fetchColumn();
    $_SESSION['id_paciente'] = $idPaciente;
}

if (!$idPaciente) {
    die("Nenhum paciente encontrado para esta conta.");
}


/* ---------- Opções de cada configuração ---------- */

// perfis visuais vêm do banco (com a descrição de cada um)
$perfis = $pdo->query(
    "SELECT id_visual, nome_perfil_visual, cor_fundo_tema_padrao, cor_card_tema_padrao, descricao_visual
     FROM perfil_visual
     ORDER BY id_visual"
)->fetchAll(PDO::FETCH_ASSOC);

$perfisPorId = array_column($perfis, null, "id_visual");

$tamanhos = ["Pequeno", "Médio", "Grande"];

$espacamentos = ["Normal", "Grande"];

// tempo entre toques, em milissegundos
$tempos = [
    0    => "Desligado",
    300  => "Curto",
    600  => "Médio",
    1000 => "Longo",
    1500 => "Muito longo",
];


/* ---------- Salvar ---------- */

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // aparência
    $idVisual   = (int)($_POST["id_visual"] ?? 0);
    $tema       = $_POST["tema_cor"] ?? "azul";
    $modoEscuro = isset($_POST["modo_escuro"]) ? 1 : 0;

    // acessibilidade
    $tamanho      = $_POST["tamanho_card"] ?? "";
    $quantidade   = (int)($_POST["quantidade_cards"] ?? 0);
    $espacamento  = $_POST["espacamento_cards"] ?? "";
    $tempo        = (int)($_POST["tempo_debounce"] ?? -1);
    $mostrarTexto = isset($_POST["mostrar_texto"]) ? 1 : 0;
    $contraste    = isset($_POST["alto_contraste"]) ? "Alto" : "Normal";

    if (!isset($perfisPorId[$idVisual])
        || !in_array($tamanho, $tamanhos, true)
        || $quantidade < 1 || $quantidade > 10
        || !in_array($espacamento, $espacamentos, true)
        || !array_key_exists($tempo, $tempos)) {

        $erro = "Algum valor não é válido. Confira as opções e tente de novo.";

    } else {

        // cores: no Personalizado vêm do tema escolhido,
        // nos outros perfis vêm do padrão do perfil
        $perfil = $perfisPorId[$idVisual];

        if ($perfil["nome_perfil_visual"] === "Personalizado" && isset($temas[$tema])) {
            $corFundo = $temas[$tema]["fundo"];
            $corCard  = $temas[$tema]["card"];
        } else {
            $corFundo = $perfil["cor_fundo_tema_padrao"];
            $corCard  = $perfil["cor_card_tema_padrao"];
        }

        $stmt = $pdo->prepare(
            "UPDATE paciente SET
                id_visual = ?,
                cor_fundo_tema = ?,
                cor_card_tema = ?,
                modo_escuro = ?,
                tamanho_card = ?,
                quantidade_cards = ?,
                espacamento_cards = ?,
                tempo_debounce = ?,
                mostrar_texto = ?,
                contraste = ?
             WHERE id_paciente = ? AND id_usuario = ?"
        );
        $stmt->execute([
            $idVisual, $corFundo, $corCard, $modoEscuro,
            $tamanho, $quantidade, $espacamento, $tempo, $mostrarTexto, $contraste,
            $idPaciente, $idUsuario,
        ]);

        // recarrega a página (assim o F5 não envia de novo)
        header("Location: conf_acessibilidade.php?salvo=1");
        exit;
    }
}


/* ---------- Valores atuais ---------- */

$stmt = $pdo->prepare(
    "SELECT nome, id_visual, cor_fundo_tema, cor_card_tema, modo_escuro,
            tamanho_card, quantidade_cards, espacamento_cards, tempo_debounce,
            mostrar_texto, contraste
     FROM paciente
     WHERE id_paciente = ? AND id_usuario = ?"
);
$stmt->execute([$idPaciente, $idUsuario]);
$atual = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$atual) {
    die("Paciente não encontrado.");
}

// qual tema de cor está em uso (pela cor de fundo salva); se nenhum, Azul
$temaAtual = "azul";
foreach ($temas as $chave => $t) {
    if (strcasecmp($t["fundo"], $atual["cor_fundo_tema"]) === 0) {
        $temaAtual = $chave;
    }
}

// se o tempo salvo não estiver na lista (ex.: 500), marca a opção mais próxima
$tempoAtual = (int)$atual["tempo_debounce"];
$tempoMarcado = 0;
foreach (array_keys($tempos) as $valor) {
    if (abs($valor - $tempoAtual) < abs($tempoMarcado - $tempoAtual)) {
        $tempoMarcado = $valor;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aparência e acessibilidade | Fafala</title>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../CSS/style.css">
    <link rel="stylesheet" href="../../CSS/acessibilidade.css">
    <link rel="stylesheet" href="../../CSS/especifico/configuracoes.css">
</head>

<body>

    <main class="container pagina-config">

        <a href="configuracoes.php" class="voltar">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            Configurações
        </a>

        <header class="cabecalho-config">
            <h1>Aparência e acessibilidade</h1>
            <p class="texto-suave">Ajuste como o Fafala aparece para <strong><?= e($atual["nome"]) ?></strong>.</p>
        </header>

        <?php if (isset($_GET["salvo"])): ?>
            <div class="alerta alerta-sucesso" role="status">Configurações salvas!</div>
        <?php endif; ?>

        <?php if ($erro !== ""): ?>
            <div class="alerta alerta-erro" role="alert"><?= e($erro) ?></div>
        <?php endif; ?>

        <div class="config-layout">

            <form method="post" class="card config-form" id="formAcessibilidade">

                <!-- PRÉVIA: mostra na hora como vai ficar -->
                <div class="previa" aria-label="Prévia de como vai ficar">
                    <p class="previa-titulo">Prévia</p>
                    <div class="previa-tela" id="previa"
                         style="--previa-fundo: <?= e($atual["cor_fundo_tema"]) ?>; --previa-card: <?= e($atual["cor_card_tema"]) ?>;">
                    </div>
                </div>

                <!-- =============== APARÊNCIA =============== -->
                <h2 class="config-secao">Aparência</h2>

                <!-- PERFIL VISUAL -->
                <fieldset class="config-grupo">
                    <legend>Perfil visual</legend>

                    <div class="perfis">
                        <?php foreach ($perfis as $perfil): ?>
                            <?php
                            $ehInfantil = $perfil["nome_perfil_visual"] === "Infantil";
                            ?>
                            <label class="perfil-opcao">
                                <input type="radio" name="id_visual" value="<?= (int)$perfil["id_visual"] ?>"
                                       data-nome="<?= e($perfil["nome_perfil_visual"]) ?>"
                                       data-fundo="<?= e($perfil["cor_fundo_tema_padrao"]) ?>"
                                       data-card="<?= e($perfil["cor_card_tema_padrao"]) ?>"
                                    <?= (int)$atual["id_visual"] === (int)$perfil["id_visual"] ? "checked" : "" ?>>

                                <span class="perfil-conteudo">
                                    <span class="perfil-amostra <?= $ehInfantil ? "perfil-amostra-infantil" : "" ?>"
                                          style="--fundo: <?= e($perfil["cor_fundo_tema_padrao"]) ?>; --card: <?= e($perfil["cor_card_tema_padrao"]) ?>;"
                                          aria-hidden="true"></span>
                                    <strong><?= e($perfil["nome_perfil_visual"]) ?></strong>
                                    <small><?= e($perfil["descricao_visual"]) ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- cores: só aparecem com "Personalizado" marcado -->
                    <div class="escolha-cor" role="radiogroup" aria-labelledby="titulo-cor">
                        <p id="titulo-cor" class="escolha-cor-titulo">Escolha a cor favorita</p>

                        <div class="cores">
                            <?php foreach ($temas as $chave => $t): ?>
                                <label class="cor-opcao" style="--fundo: <?= $t["fundo"] ?>; --card: <?= $t["card"] ?>;">
                                    <input type="radio" name="tema_cor" value="<?= $chave ?>"
                                           data-fundo="<?= $t["fundo"] ?>" data-card="<?= $t["card"] ?>"
                                        <?= $temaAtual === $chave ? "checked" : "" ?>>
                                    <span class="cor-bolinha"></span>
                                    <?= e($t["nome"]) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </fieldset>

                <!-- MODO ESCURO -->
                <div class="config-grupo">
                    <label class="interruptor">
                        <span class="interruptor-texto">
                            <strong>Modo escuro</strong>
                            <small>Fundo escuro, que cansa menos a vista em ambientes com pouca luz.</small>
                        </span>
                        <input type="checkbox" name="modo_escuro" role="switch"
                            <?= $atual["modo_escuro"] ? "checked" : "" ?>>
                        <span class="interruptor-trilho" aria-hidden="true"></span>
                    </label>
                </div>


                <!-- =============== ACESSIBILIDADE =============== -->
                <h2 class="config-secao">Acessibilidade</h2>

                <!-- TAMANHO DOS CARDS -->
                <fieldset class="config-grupo">
                    <legend>Tamanho dos cards</legend>
                    <p class="dica">Cards maiores são mais fáceis de ver e de tocar.</p>

                    <div class="segmentado">
                        <?php foreach ($tamanhos as $tamanho): ?>
                            <label class="segmento">
                                <input type="radio" name="tamanho_card" value="<?= e($tamanho) ?>"
                                    <?= $atual["tamanho_card"] === $tamanho ? "checked" : "" ?>>
                                <span><?= e($tamanho) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <!-- QUANTIDADE DE CARDS -->
                <div class="config-grupo">
                    <label for="quantidade" class="config-titulo">Cards por tela</label>
                    <p class="dica">Menos cards deixam a tela mais simples e com menos distrações.</p>

                    <div class="controle-faixa">
                        <input type="range" id="quantidade" name="quantidade_cards"
                               min="1" max="10" step="1"
                               value="<?= (int)$atual["quantidade_cards"] ?>">
                        <output id="quantidadeValor" for="quantidade"><?= (int)$atual["quantidade_cards"] ?></output>
                    </div>
                </div>

                <!-- ESPAÇAMENTO -->
                <fieldset class="config-grupo">
                    <legend>Espaço entre os cards</legend>
                    <p class="dica">Mais espaço ajuda a não tocar no card errado.</p>

                    <div class="segmentado">
                        <?php foreach ($espacamentos as $espacamento): ?>
                            <label class="segmento">
                                <input type="radio" name="espacamento_cards" value="<?= e($espacamento) ?>"
                                    <?= $atual["espacamento_cards"] === $espacamento ? "checked" : "" ?>>
                                <span><?= e($espacamento) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <!-- TEMPO ENTRE TOQUES (DEBOUNCE) -->
                <fieldset class="config-grupo">
                    <legend>Tempo entre toques</legend>
                    <p class="dica">Ignora toques repetidos feitos muito rápido, sem querer.</p>

                    <div class="segmentado">
                        <?php foreach ($tempos as $valor => $nome): ?>
                            <label class="segmento">
                                <input type="radio" name="tempo_debounce" value="<?= $valor ?>"
                                    <?= $tempoMarcado === $valor ? "checked" : "" ?>>
                                <span>
                                    <?= e($nome) ?>
                                    <?php if ($valor > 0): ?>
                                        <small><?= $valor / 1000 ?>s</small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <!-- LIGA / DESLIGA -->
                <div class="config-grupo">

                    <label class="interruptor">
                        <span class="interruptor-texto">
                            <strong>Mostrar texto</strong>
                            <small>Mostra o nome embaixo de cada pictograma.</small>
                        </span>
                        <input type="checkbox" name="mostrar_texto" role="switch"
                            <?= $atual["mostrar_texto"] ? "checked" : "" ?>>
                        <span class="interruptor-trilho" aria-hidden="true"></span>
                    </label>

                    <label class="interruptor">
                        <span class="interruptor-texto">
                            <strong>Alto contraste</strong>
                            <small>Bordas e textos mais escuros, para enxergar melhor.</small>
                        </span>
                        <input type="checkbox" name="alto_contraste" role="switch"
                            <?= $atual["contraste"] === "Alto" ? "checked" : "" ?>>
                        <span class="interruptor-trilho" aria-hidden="true"></span>
                    </label>

                </div>

                <button type="submit" class="botao">Salvar configurações</button>

            </form>

        </div>

    </main>

    <script src="../../JS/especifico/configuracoes.js"></script>

</body>
</html>