<?php
/**
 * Backup do banco (usado pelo cron de manutenção e pelo botão "Fazer backup agora" do master).
 * Os arquivos ficam em PASTA_PRIVADA/backups, fora do public_html.
 */
require_once __DIR__ . '/conexao.php';

const BACKUP_INTERVALO_DIAS = 7;
const BACKUP_MANTER_RECENTES = 4;
const BACKUP_MANTER_MESES = 6;
const BACKUP_TETO_BYTES = 50 * 1024 * 1024;

/** "0 KB" / "350 KB" / "1,2 MB" */
function mb(int $bytes): string
{
    if ($bytes === 0) {
        return '0 KB';
    }
    return $bytes < 1048576
        ? max(1, round($bytes / 1024)) . ' KB'
        : number_format($bytes / 1048576, 1, ',', '.') . ' MB';
}

/** Gera o dump completo do banco em .sql.gz (PHP puro, sem depender do mysqldump). */
function gerar_backup(PDO $pdo, string $destino): void
{
    $temporario = $destino . '.parcial';
    $gz = gzopen($temporario, 'wb6');
    if (!$gz) {
        throw new RuntimeException('não foi possível criar o arquivo de backup');
    }
    $pdo->exec("SET NAMES utf8mb4");
    gzwrite($gz, "-- Backup do banco Risenglish — " . date('d/m/Y H:i:s') . "\n"
        . "-- Para restaurar: importe este arquivo no phpMyAdmin (aba Importar) ou com o comando mysql.\n\n"
        . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

    $tabelas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tabelas as $tabela) {
        $criacao = $pdo->query("SHOW CREATE TABLE `{$tabela}`")->fetch(PDO::FETCH_NUM)[1];
        gzwrite($gz, "DROP TABLE IF EXISTS `{$tabela}`;\n{$criacao};\n\n");

        // Lê sem carregar a tabela inteira na memória
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $linhas = $pdo->query("SELECT * FROM `{$tabela}`");
        $lote = [];
        $colunas = null;
        while ($linha = $linhas->fetch(PDO::FETCH_ASSOC)) {
            $colunas = $colunas ?? '`' . implode('`, `', array_keys($linha)) . '`';
            $valores = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $linha);
            $lote[] = '(' . implode(', ', $valores) . ')';
            if (count($lote) === 200) {
                gzwrite($gz, "INSERT INTO `{$tabela}` ({$colunas}) VALUES\n" . implode(",\n", $lote) . ";\n");
                $lote = [];
            }
        }
        $linhas->closeCursor();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        if ($lote) {
            gzwrite($gz, "INSERT INTO `{$tabela}` ({$colunas}) VALUES\n" . implode(",\n", $lote) . ";\n");
        }
        gzwrite($gz, "\n");
    }
    gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\n-- fim do backup (" . count($tabelas) . " tabelas)\n");
    gzclose($gz);

    // Confere se o arquivo abre e chega até o fim antes de considerá-lo válido
    $leitura = gzopen($temporario, 'rb');
    $final = '';
    while (!gzeof($leitura)) {
        $final = substr($final . gzread($leitura, 65536), -200);
    }
    gzclose($leitura);
    if (strpos($final, '-- fim do backup') === false) {
        @unlink($temporario);
        throw new RuntimeException('o arquivo gerado está incompleto');
    }
    rename($temporario, $destino);
}

/** Aplica a rotação: 4 mais recentes + o último de cada mês dos últimos 6 meses; depois o teto de tamanho. */
function rotacionar_backups(string $pasta, array &$alertas): int
{
    $arquivos = glob($pasta . '/backup_*.sql.gz') ?: [];
    rsort($arquivos); // nome tem a data (AAAA-MM-DD): ordem alfabética = cronológica
    $manter = array_slice($arquivos, 0, BACKUP_MANTER_RECENTES);
    $limiteMes = date('Y-m', strtotime('first day of -' . (BACKUP_MANTER_MESES - 1) . ' months'));
    $mesesVistos = [];
    foreach ($arquivos as $arquivo) {
        $mes = substr(basename($arquivo), 7, 7); // backup_AAAA-MM-DD
        if ($mes >= $limiteMes && !isset($mesesVistos[$mes])) {
            $mesesVistos[$mes] = true;
            $manter[] = $arquivo;
        }
    }
    $apagados = 0;
    foreach (array_diff($arquivos, $manter) as $arquivo) {
        @unlink($arquivo) && $apagados++;
    }

    // Teto de segurança: se a pasta passar do limite, apaga os mais antigos (sempre fica pelo menos 1)
    $restantes = array_values(array_intersect($arquivos, $manter));
    $total = array_sum(array_map('filesize', $restantes));
    if ($total > BACKUP_TETO_BYTES) {
        $alertas[] = 'A pasta de backups passou de ' . mb(BACKUP_TETO_BYTES) . ' (' . mb($total) . '); os backups mais antigos foram apagados.';
        while ($total > BACKUP_TETO_BYTES && count($restantes) > 1) {
            $maisAntigo = array_pop($restantes);
            $total -= filesize($maisAntigo);
            @unlink($maisAntigo) && $apagados++;
        }
    }
    return $apagados;
}
