<?php
// Segurança: só permite execução via linha de comando (cron), nunca pelo navegador
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado.');
}

require_once __DIR__ . '/conexao.php';

try {
    $stmt = $pdo->prepare("DELETE FROM logs_acesso WHERE data_acesso < NOW() - INTERVAL 6 MONTH");
    $stmt->execute();
    $deletados = $stmt->rowCount();
    echo date('Y-m-d H:i:s') . " — Limpeza concluida: {$deletados} registro(s) removido(s).\n";
} catch (PDOException $e) {
    error_log("Erro na limpeza de logs: " . $e->getMessage());
    echo "Erro na limpeza. Verifique o log do servidor.\n";
}