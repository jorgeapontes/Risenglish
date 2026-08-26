<?php
// Garante que o script não seja interrompido por tempo limite no cron
set_time_limit(0);

// 1. Carrega o ambiente e a conexão (usando o mesmo caminho do seu sistema)
require_once __DIR__ . '/conexao.php';

// O seu conexao.php já define a variável $pdo. Verificamos se ela existe.
if (!isset($pdo)) {
    echo "Erro: Falha na conexão com o banco de dados.\n";
    exit(1);
}

echo "Iniciando limpeza de logs...\n";

try {
    $totalDeletados = 0;
    
    do {
        // Prepara a query de deleção com 45 dias e limite de 1000 linhas
        $sql = "DELETE FROM logs_acesso 
                WHERE data_acesso < NOW() - INTERVAL 45 DAY 
                LIMIT 1000;";
        
        // Executa a query usando PDO
        $stmt = $pdo->exec($sql);
        
        // Pega quantas linhas foram afetadas. No PDO, exec() retorna o número de linhas afetadas.
        $rows_affected = $stmt;
        
        if ($rows_affected > 0) {
            $totalDeletados += $rows_affected;
            echo "Lote deletado: $rows_affected linhas. Total até agora: $totalDeletados\n";
        }

        // Pausa de 1 segundo entre os lotes para não sobrecarregar o servidor
        sleep(1);

    } while ($rows_affected > 0);

    echo "Limpeza concluída! Total de registros antigos removidos: $totalDeletados.\n";

} catch (PDOException $e) {
    // Registra o erro no log do sistema (sem expor senhas)
    error_log("Erro no cronjob de limpeza de logs: " . $e->getMessage());
    echo "Erro ao executar a limpeza. Verifique os logs do servidor.\n";
    exit(1);
}

// ==========================================================
// LOG INTELIGENTE (Opção B) - Registra e mantém apenas 30 dias
// ==========================================================

$arquivo_log = __DIR__ . '/cron_log.txt';

// 1. Adiciona a nova linha de execução no final do arquivo
file_put_contents(
    $arquivo_log, 
    date('Y-m-d H:i:s') . " - Cron executado com sucesso. (Deletados: $totalDeletados)\n", 
    FILE_APPEND
);

// 2. Verifica se o arquivo existe e remove as linhas mais antigas que 30 dias
if (file_exists($arquivo_log)) {
    // Lê todas as linhas do arquivo
    $linhas = file($arquivo_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $linhas_filtradas = [];
    
    // Define a data limite (hoje menos 30 dias)
    $limite = date('Y-m-d', strtotime('-30 days'));
    
    // Filtra apenas as linhas dos últimos 30 dias
    foreach ($linhas as $linha) {
        // Pega apenas os 10 primeiros caracteres (formato YYYY-MM-DD)
        $data_log = substr($linha, 0, 10);
        if ($data_log >= $limite) {
            $linhas_filtradas[] = $linha;
        }
    }
    
    // Sobrescreve o arquivo apenas com o histórico dos últimos 30 dias
    file_put_contents($arquivo_log, implode("\n", $linhas_filtradas) . "\n");
}

echo "Log atualizado com sucesso.\n";
?>