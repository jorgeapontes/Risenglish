<?php
require_once __DIR__ . '/env.php';

// Pasta privada, FORA do public_html (o navegador não alcança): backups do banco e log de erros do site.
// Produção: /home/u569225384/domains/risenglish.com.br/privado
if (!defined('PASTA_PRIVADA')) {
    define('PASTA_PRIVADA', dirname(__DIR__, 3) . '/privado');
    if (!is_dir(PASTA_PRIVADA . '/logs') && @mkdir(PASTA_PRIVADA . '/logs', 0750, true)) {
        @file_put_contents(PASTA_PRIVADA . '/.htaccess', "Require all denied\n");
    }
    // Erros do PHP vão para um arquivo próprio (a tela de monitoramento mostra e o cron faz a rotação)
    if (is_dir(PASTA_PRIVADA . '/logs')) {
        ini_set('log_errors', '1');
        ini_set('error_log', PASTA_PRIVADA . '/logs/erros_site.log');
    }
}

// Read DB credentials from environment with sensible defaults
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'risenglish';

// Conexão PDO
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexão estabelecida com sucesso!"; // **Remova esta linha após o teste!**
} catch (PDOException $e) {
    // Registra o erro real em log interno (não expõe ao usuário)
    error_log("Erro de conexão com o banco: " . $e->getMessage());
    // No cron (linha de comando) segue sem $pdo, para o script de manutenção avisar por e-mail
    if (PHP_SAPI === 'cli') {
        $pdo = null;
        return;
    }
    // Exibe mensagem genérica ao usuário
    http_response_code(503);
    die("Serviço temporariamente indisponível. Tente novamente em alguns instantes.");
}
?>