<?php


$caminhoImagens = "/fafala-main/fafala/images_fafala_final/";

$pranchasDados = [
    "alimentacao" => [
        "titulo" => "Alimentação",
        "capa"   => "alimentacao.jpg",
        "pictogramas" => [
            ["Água", "agua.jpg"],
            ["Pão",  "pao.jpg"],
            ["Bolo", "bolo.jpg"],
        ],
    ],
    "escola" => [
        "titulo" => "Escola",
        "capa"   => "escola.jpg",
        "pictogramas" => [
            ["Borracha", "borracha.png"],
            ["Lápis",    "lapis.jpg"],
            ["Caderno",  "caderno.jpg"],
        ],
    ],
    "sentimentos" => [
        "titulo" => "Sentimentos",
        "capa"   => "sentimentos.jpg",
        "pictogramas" => [
            ["Feliz",  "feliz.png"],
            ["Triste", "triste.png"],
            ["Doente", "doente2.avif"],
        ],
    ],
    "terapia" => [
        "titulo" => "Terapia",
        "capa"   => "terapia.jpg",
        "pictogramas" => [
            // Ainda sem pictogramas. Exemplo: ["Nome", "arquivo.jpg"],
        ],
    ],
    "casa" => [
        "titulo" => "Casa",
        "capa"   => "casa.jpg",
        "pictogramas" => [
            ["Sala",    "sala.avif"],
            ["Quarto",  "quarto.avif"],
            ["Cozinha", "cozinha.avif"],
        ],
    ],
    "brinquedos" => [
        "titulo" => "Brinquedos",
        "capa"   => "brinquedos.jpg",
        "pictogramas" => [
            ["Porquinho Toy Story",   "porquinho.jpg",  "Porquinho"],
            ["Cachorro Toy Story",    "cahorro.jpg",    "Cachorro"],
            ["Dinossauro Toy Story",  "dinossauro.jpg", "Dinossauro"],
        ],
    ],
    "saude" => [
        "titulo" => "Saúde",
        "capa"   => "saude.jpg",
        "pictogramas" => [
            ["Doente", "doente2.avif"],
        ],
    ],
];

// Quais pranchas aparecem dentro de cada categoria (nome da categoria
// em minúsculas e sem acento, como está na tabela "categoria").
$categoriasPranchas = [
    "alimentos"    => ["alimentacao"],
    "pessoas"      => ["terapia"],
    "atividades"   => ["escola", "brinquedos"],
    "lugares"      => ["casa"],
    "emocoes"      => ["sentimentos"],
    "necessidades" => ["alimentacao", "saude"],
];

// Deixa o nome em minúsculas e sem acento para comparar.
function normalizarNome(string $texto): string
{
    $texto = mb_strtolower(trim($texto), "UTF-8");

    $de   = ["á","à","â","ã","ä","é","è","ê","ë","í","ì","î","ï","ó","ò","ô","õ","ö","ú","ù","û","ü","ç"];
    $para = ["a","a","a","a","a","e","e","e","e","i","i","i","i","o","o","o","o","o","u","u","u","u","c"];

    return str_replace($de, $para, $texto);
}