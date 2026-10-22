<?php
require_once __DIR__ . "/../tecnico/conexao.php";

// só entra aqui quem acabou de se cadastrar (ou fez login)
if (!isset($_SESSION['id_paciente'])) {
    header("Location: login.php");
    exit;
}

$idPaciente = $_SESSION['id_paciente'];
$erro = "";

// cores do personalizado
$temas = [
    "vermelho" => ["#FBE1DF", "#F2A49E"],
    "laranja"  => ["#FDE7D3", "#F7B98A"],
    "amarelo"  => ["#FFF8DC", "#F3E3A2"],
    "verde"    => ["#E1F2DC", "#A9D89A"],
    "turquesa" => ["#D9F1EF", "#8FD3CC"],
    "azul"     => ["#DDEBFA", "#9CC2EE"],
    "roxo"     => ["#EAE2F7", "#BFA6E6"],
    "rosa"     => ["#FBE2EE", "#F2A9C9"],
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $respostas = $_POST["resposta"] ?? [];

    $permitidas = [
        1 => ["Sim", "Não", "Não sei"],
        2 => ["Sim", "Não", "Não sei"],
        3 => ["Sim", "Não", "Não sei"],
        4 => ["Sim", "Não", "Não sei"],
        5 => ["Sim", "Não", "Não sei"],
        6 => ["Infantil", "Adulto", "Personalizado"],
    ];

    foreach ($permitidas as $idPergunta => $valores) {
        if (!isset($respostas[$idPergunta]) || !in_array($respostas[$idPergunta], $valores, true)) {
            $erro = "Responda todas as perguntas para continuar.";
            break;
        }
    }

    if ($erro === "") {

        $r = $respostas;

        // ===== Configuração de acessibilidade a partir das respostas =====
        // (regras combinadas pelo grupo; ajustar aqui se mudarem)

        // P1 enxerga mal / P2 dificuldade de tocar → cards grandes
        $tamanhoCard = ($r[1] !== "Não" || $r[2] === "Sim") ? "Grande" : "Médio";

        // P1 enxerga mal → alto contraste
        $contraste = ($r[1] === "Sim") ? "Alto" : "Normal";

        // P2 dificuldade de tocar / P3 toca sem querer → mais espaço entre os cards
        $espacamento = ($r[2] === "Sim" || $r[3] === "Sim") ? "Grande" : "Normal";

        // P3 toca sem querer → tempo maior entre toques (em milissegundos)
        $debounce = match ($r[3]) {
            "Sim" => 1000,
            "Não" => 300,
            default => 600,
        };

        // P4 prefere poucos elementos → menos cards por tela
        $quantidadeCards = match ($r[4]) {
            "Sim" => 4,
            "Não" => 8,
            default => 6,
        };

        // P5 não sabe ler → esconde o texto embaixo do pictograma
        $mostrarTexto = ($r[5] === "Não") ? 0 : 1;

        try {
            $pdo->beginTransaction();

            // registra cada resposta
            $stmt = $pdo->prepare(
                "INSERT INTO resposta (resposta, data_resposta, id_pergunta, id_paciente)
                 VALUES (?, NOW(), ?, ?)"
            );

            foreach ($permitidas as $idPergunta => $valores) {
                $stmt->execute([$r[$idPergunta], $idPergunta, $idPaciente]);
            }

            // busca o perfil visual escolhido (pergunsta 6)
            $stmt = $pdo->prepare(
                "SELECT id_visual, cor_fundo_tema_padrao, cor_card_tema_padrao, modo_escuro_padrao
                 FROM perfil_visual
                 WHERE nome_perfil_visual = ?"
            );
            $stmt->execute([$r[6]]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$perfil) {
                throw new Exception("Perfil visual não encontrado no banco.");
            }

            // qnd visual eh personalizado, usa as cores do tema escolhido
            if ($r[6] === "Personalizado") {
                $tema = $_POST["tema_cor"] ?? "azul";
                if (isset($temas[$tema])) {
                    $perfil["cor_fundo_tema_padrao"] = $temas[$tema][0];
                    $perfil["cor_card_tema_padrao"]  = $temas[$tema][1];
                }
            }

            // salva a configuração no paciente
            $stmt = $pdo->prepare(
                "UPDATE paciente SET
                    tamanho_card = ?,
                    quantidade_cards = ?,
                    espacamento_cards = ?,
                    contraste = ?,
                    mostrar_texto = ?,
                    tempo_debounce = ?,
                    id_visual = ?,
                    cor_fundo_tema = ?,
                    cor_card_tema = ?,
                    modo_escuro = ?
                 WHERE id_paciente = ?"
            );

            $stmt->execute([
                $tamanhoCard,
                $quantidadeCards,
                $espacamento,
                $contraste,
                $mostrarTexto,
                $debounce,
                $perfil["id_visual"],
                $perfil["cor_fundo_tema_padrao"],
                $perfil["cor_card_tema_padrao"],
                $perfil["modo_escuro_padrao"],
                $idPaciente,
            ]);

            $pdo->commit();

            header("Location: ../paciente/inicio.php");
            exit;

        } catch (Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = "Não foi possível salvar as respostas. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questionário | Fafala</title>

    <!-- fonte -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS (deixar o geral por priemiro-->
    <link rel="stylesheet" href="../../CSS/style.css">
    <link rel="stylesheet" href="../../CSS/acessibilidade.css">
    <link rel="stylesheet" href="../../CSS/especifico/acesso_dados.css">
</head>

<body>

    <main class="pagina-acesso">

        <div class="questionario">

            <header class="cabecalho-acesso">

                <h1>Vamos conhecer melhor você!</h1>

                <p>
                    Responda às perguntas para que o Fafala possa
                    se adaptar às necessidades de quem vai utilizá-lo.
                </p>

            </header>

            <?php if ($erro !== ""): ?>
                <div class="alerta alerta-erro" role="alert">
                    <?= e($erro) ?>
                </div>
            <?php endif; ?>

            <form id="formOnboarding" method="post" class="card card-questionario">

                <!-- barra de progresso -->
                <div class="progresso" aria-live="polite">
                    <div class="progresso-topo">
                        <span class="progresso-grupo" id="progressoGrupo">Acessibilidade</span>
                        <span id="progressoTexto">Pergunta 1 de 6</span>
                    </div>
                    <div class="progresso-barra">
                        <div class="progresso-preenchido" id="progressoPreenchido"></div>
                    </div>
                </div>

                <!-- espaço do mascote NÃO ESQUECER DE COLOCARRRRRRRRRRRRRRRRRRRRRRRR!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!1
                     <img src="../../IMG/fafala.png" alt=""> -->
                <div class="mascote" aria-hidden="true"></div>

                <!-- GRUPO 1: ACESSIBILIDADE -->
                <section class="grupo-perguntas">

                    <h2>Acessibilidade</h2>

                    <!-- Pergunta 1 -->
                    <fieldset class="pergunta" data-grupo="Acessibilidade">

                        <legend>
                            O usuário tem dificuldade para enxergar
                            elementos pequenos?
                        </legend>

                        <div class="respostas">
                            <label class="resposta">
                                <input type="radio" name="resposta[1]" value="Sim" required>
                                Sim
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[1]" value="Não">
                                Não
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[1]" value="Não sei">
                                Não sei
                            </label>
                        </div>

                    </fieldset>

                    <!-- Pergunta 2 -->
                    <fieldset class="pergunta" data-grupo="Acessibilidade">

                        <legend>
                            O usuário tem dificuldade para tocar
                            com precisão em elementos pequenos?
                        </legend>

                        <div class="respostas">
                            <label class="resposta">
                                <input type="radio" name="resposta[2]" value="Sim" required>
                                Sim
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[2]" value="Não">
                                Não
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[2]" value="Não sei">
                                Não sei
                            </label>
                        </div>

                    </fieldset>

                    <!-- Pergunta 3 -->
                    <fieldset class="pergunta" data-grupo="Acessibilidade">

                        <legend>
                            O usuário costuma tocar acidentalmente
                            em elementos próximos?
                        </legend>

                        <div class="respostas">
                            <label class="resposta">
                                <input type="radio" name="resposta[3]" value="Sim" required>
                                Sim
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[3]" value="Não">
                                Não
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[3]" value="Não sei">
                                Não sei
                            </label>
                        </div>

                    </fieldset>

                    <!-- Pergunta 4 -->
                    <fieldset class="pergunta" data-grupo="Acessibilidade">

                        <legend>
                            O usuário se comunica melhor quando há
                            poucos elementos na tela?
                        </legend>

                        <div class="respostas">
                            <label class="resposta">
                                <input type="radio" name="resposta[4]" value="Sim" required>
                                Sim
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[4]" value="Não">
                                Não
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[4]" value="Não sei">
                                Não sei
                            </label>
                        </div>

                    </fieldset>

                    <!-- Pergunta 5 -->
                    <fieldset class="pergunta" data-grupo="Acessibilidade">

                        <legend>
                            O usuário sabe ler ou está em processo
                            de aprendizagem da leitura?
                        </legend>

                        <div class="respostas">
                            <label class="resposta">
                                <input type="radio" name="resposta[5]" value="Sim" required>
                                Sim
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[5]" value="Não">
                                Não
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[5]" value="Não sei">
                                Não sei
                            </label>
                        </div>

                    </fieldset>

                </section>

                <!-- GRUPO 2: PERFIL VISUAL -->
                <section class="grupo-perguntas">

                    <h2>Perfil visual</h2>

                    <!-- Pergunta 6 -->
                    <fieldset class="pergunta" data-grupo="Perfil visual">

                        <legend>
                            Qual tema visual é mais adequado
                            para o usuário?
                        </legend>

                        <div class="respostas">
                            <label class="resposta">
                                <input type="radio" name="resposta[6]" value="Infantil" required>
                                Infantil
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[6]" value="Adulto">
                                Adulto
                            </label>

                            <label class="resposta">
                                <input type="radio" name="resposta[6]" value="Personalizado">
                                Personalizado
                            </label>
                        </div>

                        <div class="escolha-cor" role="radiogroup" aria-labelledby="titulo-cor">

                            <p id="titulo-cor" class="escolha-cor-titulo">Escolha sua cor favorita</p>

                            <div class="cores">
                                <label class="cor-opcao" style="--fundo:#FBE1DF; --card:#F2A49E">
                                    <input type="radio" name="tema_cor" value="vermelho">
                                    <span class="cor-bolinha"></span>
                                    Vermelho
                                </label>
                                <label class="cor-opcao" style="--fundo:#FDE7D3; --card:#F7B98A">
                                    <input type="radio" name="tema_cor" value="laranja">
                                    <span class="cor-bolinha"></span>
                                    Laranja
                                </label>
                                <label class="cor-opcao" style="--fundo:#FFF8DC; --card:#F3E3A2">
                                    <input type="radio" name="tema_cor" value="amarelo">
                                    <span class="cor-bolinha"></span>
                                    Amarelo
                                </label>
                                <label class="cor-opcao" style="--fundo:#E1F2DC; --card:#A9D89A">
                                    <input type="radio" name="tema_cor" value="verde">
                                    <span class="cor-bolinha"></span>
                                    Verde
                                </label>
                                <label class="cor-opcao" style="--fundo:#D9F1EF; --card:#8FD3CC">
                                    <input type="radio" name="tema_cor" value="turquesa">
                                    <span class="cor-bolinha"></span>
                                    Turquesa
                                </label>
                                <label class="cor-opcao" style="--fundo:#DDEBFA; --card:#9CC2EE">
                                    <input type="radio" name="tema_cor" value="azul" checked>
                                    <span class="cor-bolinha"></span>
                                    Azul
                                </label>
                                <label class="cor-opcao" style="--fundo:#EAE2F7; --card:#BFA6E6">
                                    <input type="radio" name="tema_cor" value="roxo">
                                    <span class="cor-bolinha"></span>
                                    Roxo
                                </label>
                                <label class="cor-opcao" style="--fundo:#FBE2EE; --card:#F2A9C9">
                                    <input type="radio" name="tema_cor" value="rosa">
                                    <span class="cor-bolinha"></span>
                                    Rosa
                                </label>
                            </div>

                            <p class="dica">Você pode mudar isso depois nas configurações.</p>

                        </div>

                    </fieldset>

                </section>

                <!-- aviso quando tenta avançar sem responder -->
                <p class="mensagem-erro aviso-etapa" id="avisoEtapa" aria-live="assertive"></p>

                <!-- mensagem final -->
                <div class="mensagem-final">
                    <p>
                        Com base nas suas respostas, o Fafala definirá
                        as configurações iniciais de acessibilidade
                        e aparência. O responsável poderá alterá-las
                        posteriormente.
                    </p>
                </div>

                <!-- botões -->
                <div class="navegacao">

                    <button type="button" id="btnVoltar" class="botao botao-secundario">
                        Voltar
                    </button>

                    <button type="button" id="btnProxima" class="botao">
                        Próxima
                    </button>

                    <!-- envia o formulário para o PHP do topo desta página -->
                    <button type="submit" id="btnConcluir" class="botao">
                        Concluir configuração
                    </button>

                </div>

            </form>

        </div>

    </main>

    <script src="../../JS/especifico/questionario.js"></script>

</body>

</html>