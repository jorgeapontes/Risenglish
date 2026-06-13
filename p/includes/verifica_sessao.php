<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// =============================================
// PROTEÇÃO CONTRA SESSION HIJACKING
// Valida IP e User Agent
// =============================================

function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'];
}

$ip_atual = getRealIP();
$user_agent_atual = $_SERVER['HTTP_USER_AGENT'];

if ($_SESSION['user_ip'] !== $ip_atual || $_SESSION['user_agent'] !== $user_agent_atual) {
    // Possível sequestro de sessão
    session_unset();
    session_destroy();
    header("Location: ../login.php?erro=sessao_invalida");
    exit;
}

// =============================================
// EXPIRAÇÃO AUTOMÁTICA DA SESSÃO (30 minutos)
// =============================================
$tempo_maximo_sessao = 1800; // 30 segundos

if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $tempo_maximo_sessao)) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?erro=sessao_expirada");
    exit;
}

// Renova o tempo da sessão a cada requisição
$_SESSION['login_time'] = time();
?>