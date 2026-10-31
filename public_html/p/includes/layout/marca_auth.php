<?php
/**
 * Cabeçalho com a marca das telas de acesso (login, esqueci a senha, nova senha).
 *   marca_auth_head()            dentro do <head>: fonte Montserrat + css/marca.css
 *   marca_auth('Entrar')         no topo do cartão: nome da escola, traço tricolor e subtítulo
 */

function marca_auth_head(): string
{
    $versao = @filemtime(__DIR__ . '/../../../css/marca.css');
    return '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n"
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n"
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500&display=swap">' . "\n"
        . '<link rel="stylesheet" href="../css/marca.css?v=' . $versao . '">' . "\n";
}

function marca_auth(string $subtitulo): string
{
    return '<div class="marca">'
        . '<span class="marca-nome">RISENGLISH</span>'
        . '<span class="marca-traco" aria-hidden="true"></span>'
        . '<h1 class="marca-subtitulo">' . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . '</h1>'
        . '</div>';
}
