<?php
require_once __DIR__ . "/../tecnico/conexao.php";

// Só o responsável logado entra aqui
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../acesso/login.php?destino=configuracoes");
    exit;
}

// Nome do paciente atual (só para mostrar na tela)
$nomePaciente = "";
if (isset($_SESSION["id_paciente"])) {
    $stmt = $pdo->prepare(
        "SELECT nome FROM paciente WHERE id_paciente = ? AND id_usuario = ?"
    );
    $stmt->execute([$_SESSION["id_paciente"], $_SESSION["id_usuario"]]);
    $nomePaciente = (string) $stmt->fetchColumn();
}

// =====================================================
// ÍCONES (mesmo estilo dos outros: traço, sem preenchimento)
// =====================================================
$icones = [
    "inicio"        => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
    "paciente"      => '<circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1"/><path d="M19 8v6M16 11h6"/>',
    "questionario"  => '<path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h11"/>',
    "historico"     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    "pictogramas"   => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
    "pranchas"      => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    "cadastro"      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    "sair"          => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
];

// =====================================================
// BOTÕES DA TELA
// Para mudar o destino de um botão, é só trocar o "link" aqui.
// =====================================================
$secoes = [
    [
        "titulo" => "Paciente",
        "itens" => [
            [
                "titulo" => "Cadastrar paciente",
                "texto"  => "Adicionar um novo paciente",
                "link"   => "conf_paciente.php",
                "icone"  => "paciente",
            ],
            [
                "titulo" => "Refazer questionário",
                "texto"  => "Ajustar a acessibilidade e o visual",
                "link"   => "../acesso/questionario.php",
                "icone"  => "questionario",
            ],
            [
                "titulo" => "Histórico de uso",
                "texto"  => "Ver o uso das pranchas",
                "link"   => "/fafala-main/PHP/tecnico/historico.php",
                "icone"  => "historico",
            ],
        ],
    ],
    [
        "titulo" => "Conteúdo",
        "itens" => [
            [
                "titulo" => "Editar pictogramas",
                "texto"  => "Criar, trocar e excluir pictogramas",
                "link"   => "conf_pictograma.php",
                "icone"  => "pictogramas",
            ],
            [
                "titulo" => "Editar pranchas",
                "texto"  => "Organizar as pranchas de comunicação",
                "link"   => "conf_prancha.php",
                "icone"  => "pranchas",
            ],
        ],
    ],
    [
        "titulo" => "Conta",
        "itens" => [
            [
                "titulo" => "Editar cadastro",
                "texto"  => "Alterar seus dados e sua senha",
                "link"   => "conf_responsavel.php",
                "icone"  => "cadastro",
            ],
            [
                "titulo" => "Sair da conta",
                "texto"  => "Encerrar o acesso do responsável",
                "link"   => "../acesso/sair.php",
                "icone"  => "sair",
                "perigo" => true,
            ],
        ],
    ],
];

// Desenha um ícone svg a partir do nome
function icone(array $icones, string $nome): string {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icones[$nome] ?? "") . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações | Fafala</title>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../CSS/style.css">
    <link rel="stylesheet" href="../../CSS/especifico/gerenciamento.css">
</head>

<body>
    <main class="pagina-config">

        <section class="card card-config" aria-labelledby="titulo-config">

            <!-- A LOGO FICA AQUI (igual ao cadastro) -->

            <!-- Botão: Início -->
            <a href="../paciente/inicio.php" class="botao botao-secundario botao-auto voltar-config">
                <?= icone($icones, "inicio") ?>
                Início
            </a>

            <header class="cabecalho-config">
                <h1 id="titulo-config">Configurações</h1>
                <p>Área do responsável, olá, <?= e($_SESSION["nome_usuario"] ?? "") ?>.</p>

                <?php if ($nomePaciente !== ""): ?>
                    <span class="paciente-ativo">Paciente: <?= e($nomePaciente) ?></span>
                <?php endif; ?>
            </header>

            <?php foreach ($secoes as $i => $secao): ?>
                <section class="secao-config" aria-labelledby="secao-<?= $i ?>">
                    <h2 id="secao-<?= $i ?>"><?= e($secao["titulo"]) ?></h2>

                    <ul class="lista-config">
                        <?php foreach ($secao["itens"] as $item): ?>
                            <li>
                                <a href="<?= e($item["link"]) ?>"
                                   class="opcao-config<?= !empty($item["perigo"]) ? " opcao-perigo" : "" ?>">
                                    <span class="opcao-icone"><?= icone($icones, $item["icone"]) ?></span>
                                    <span class="opcao-texto">
                                        <strong><?= e($item["titulo"]) ?></strong>
                                        <span><?= e($item["texto"]) ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>

        </section>

    </main>
</body>
</html>
