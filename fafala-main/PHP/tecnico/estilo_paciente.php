<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a conexão está disponível.
if (!isset($pdo)) {
    require_once __DIR__ . "/conexao.php";
}

// Função para proteger os valores exibidos no HTML.
if (!function_exists("e")) {
    function e($valor) {
        return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
    }
}

// Verifica se existe um paciente na sessão.
$idPaciente = $_SESSION["id_paciente"] ?? null;

if (empty($idPaciente)) {
    // Não tenta consultar o banco sem o ID.
    $estiloPaciente = "";
    $classesPaciente = "";
} else {
    // Busca os dados e as preferências visuais do paciente.
    $stmt = $pdo->prepare(
        "SELECT p.*, pv.nome_perfil_visual
         FROM paciente p
         LEFT JOIN perfil_visual pv
            ON pv.id_visual = p.id_visual
         WHERE p.id_paciente = ?
         LIMIT 1"
    );

    $stmt->execute([$idPaciente]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paciente) {
        $estiloPaciente = "";
        $classesPaciente = "";
    } else {
        // Tamanhos dos cards.
        $tamanhos = [
            "Pequeno" => "110px",
            "Médio" => "140px",
            "Grande" => "180px"
        ];

        // Espaçamento entre os cards.
        $espacos = [
            "Normal" => "16px",
            "Grande" => "28px"
        ];

        $tamanhoCard = $tamanhos[$paciente["tamanho_card"] ?? "Médio"] ?? "140px";
        $espacoCards = $espacos[$paciente["espacamento_cards"] ?? "Normal"] ?? "16px";

        $corFundo = $paciente["cor_fundo_tema"] ?? "#FFFDF8";
        $corCard = $paciente["cor_card_tema"] ?? "#F5EFE6";

        $estiloPaciente =
            "<style>
                :root {
                    --cor-fundo-paciente: " . e($corFundo) . ";
                    --cor-card: " . e($corCard) . ";
                    --tamanho-card: " . $tamanhoCard . ";
                    --espaco-cards: " . $espacoCards . ";
                }
            </style>";

        // Classes visuais.
        $classes = [];

        if (($paciente["nome_perfil_visual"] ?? "") === "Infantil") {
            $classes[] = "perfil-infantil";
        }

        if (!empty($paciente["modo_escuro"])) {
            $classes[] = "modo-escuro";
        }

        if (($paciente["contraste"] ?? "") === "Alto") {
            $classes[] = "alto-contraste";
        }

        if (isset($paciente["mostrar_texto"]) && !$paciente["mostrar_texto"]) {
            $classes[] = "sem-texto";
        }

        $classesPaciente = implode(" ", $classes);
    }
}

