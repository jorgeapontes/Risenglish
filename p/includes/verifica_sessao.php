<?php
// Configurações de sessão antes de iniciar a sessão
$tempo_sessao = 1800;
ini_set('session.gc_maxlifetime', $tempo_sessao);
session_set_cookie_params([
    'lifetime' => $tempo_sessao,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443),
    'httponly' => true,
    'samesite' => 'Strict'
]);

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
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
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