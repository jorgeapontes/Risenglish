<?php
// Configurações de sessão antes de iniciar a sessão
$tempo_sessao = 0; // 0 = cookie de sessão dura até fechar o navegador
ini_set('session.gc_maxlifetime', 86400); // servidor mantém dados da sessão por 24h
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

if ($_SESSION['user_agent'] !== $user_agent_atual) {
    // Possível sequestro de sessão
    session_unset();
    session_destroy();
    header("Location: ../login.php?erro=sessao_invalida");
    exit;
}
?><?php
// =============================================
// MASTER: só usa o monitoramento. Qualquer outra página da área logada leva para lá.
// =============================================
if (!empty($_SESSION['user_master']) && !in_array(basename($_SERVER['SCRIPT_FILENAME'] ?? '', '.php'), ['monitoramento', 'baixar_backup'], true)) {
    header("Location: ../admin/monitoramento");
    exit;
}

// =============================================
// REGISTRO DE ACESSO NEGADO
// As páginas de p/admin, p/professor e p/aluno são de um perfil só. Cada página continua
// fazendo o próprio redirecionamento; aqui só fica o registro na auditoria.
// =============================================
$pasta_perfil = basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
if (in_array($pasta_perfil, ['admin', 'professor', 'aluno'], true)) {
    $pagina_atual = basename($_SERVER['SCRIPT_FILENAME'], '.php');
    $perfis_permitidos = [$pasta_perfil];
    if ($pasta_perfil === 'professor' && $pagina_atual === 'detalhes_aula') {
        $perfis_permitidos[] = 'admin'; // o admin também visualiza os detalhes da aula
    }
    if (!in_array($_SESSION['user_tipo'] ?? '', $perfis_permitidos, true)) {
        require_once __DIR__ . '/auditoria.php';
        registrar_auditoria('acesso_negado', "Tentou abrir {$pasta_perfil}/{$pagina_atual} sem permissão", 'pagina');
    }
}
