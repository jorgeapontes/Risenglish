<?php
require_once __DIR__ . '/env.php';

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
    // Exibe mensagem genérica ao usuário
    http_response_code(503);
    die("Serviço temporariamente indisponível. Tente novamente em alguns instantes.");
}
?>