<?php
/**
 * Manutenção diária do Risenglish — executada pelo cron da Hostinger, SOMENTE via linha de comando:
 *   php /home/u569225384/domains/risenglish.com.br/public_html/p/includes/limpar_logs.php --apagar-orfaos
 *
 * O que faz:
 *   1. logs_acesso: apaga registros com mais de 90 dias
 *   2. tentativas_ip: apaga bloqueios/janelas de tentativa expirados há mais de 1 dia
 *   3. notificacoes: apaga as lidas com mais de 90 dias e as de usuários que não existem mais
 *   4. usuarios: limpa tokens de "esqueci a senha" vencidos
 *   5. logs_auditoria e cron_execucoes: apaga registros com mais de 1 ano
 *   6. Órfãos: arquivos em uploads (conteúdos e fotos de perfil) que nenhum registro usa,
 *      e arquivos/subpastas de temas que foram excluídos. Tudo vai para relatorio_orfaos.txt.
 *      Só apaga com a opção --apagar-orfaos (sem ela, é uma simulação).
 *      Anexos de usuários removidos (ex-alunos) são só listados, NUNCA apagados: a escola guarda esses documentos.
 *   7. Backup semanal do banco (compactado) em PASTA_PRIVADA/backups, fora do public_html.
 *      Guarda os 4 mais recentes + 1 por mês dos últimos 6 meses; teto de 50 MB para a pasta.
 *      Opção --backup-agora força um backup mesmo que o último tenha menos de 7 dias.
 *   8. Log de erros do site (PASTA_PRIVADA/logs/erros_site.log): rotação acima de 5 MB
 *      e contagem dos erros graves surgidos desde a última execução.
 *   9. Tamanhos (banco, uploads, backups) e o resultado da execução vão para a tabela cron_execucoes
 *      (é o que a tela de Monitoramento do master mostra).
 *   7b. Apaga sobras que não devem existir no site (log de depuração antigo, endpoints sem uso e,
 *      se já houver backup novo, dumps .sql esquecidos na raiz do public_html).
 *  10. Se houver qualquer problema, envia UM e-mail de alerta para o endereço em site_settings.alerta_email.
 *
 * O nome do arquivo foi mantido (antes só limpava logs_acesso) para não precisar mudar o cron.
 */

// Só pode rodar pelo cron (linha de comando). Pelo navegador, finge que não existe.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

set_time_limit(0);
$inicioExecucao = microtime(true);
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/avatar.php';
require_once __DIR__ . '/email_config.php'; // precisa ser no escopo do arquivo: as funções de e-mail usam as variáveis globais do SMTP

const BACKUP_INTERVALO_DIAS = 7;
const BACKUP_MANTER_RECENTES = 4;
const BACKUP_MANTER_MESES = 6;
const BACKUP_TETO_BYTES = 50 * 1024 * 1024;
const ERROS_LOG_MAX_BYTES = 5 * 1024 * 1024;
const DISCO_ALERTA_PERCENTUAL = 80;

$apagarOrfaos = in_array('--apagar-orfaos', $argv ?? [], true);
$backupAgora = in_array('--backup-agora', $argv ?? [], true);
$resumo = [];
$alertas = [];
$arquivoEmailAlerta = PASTA_PRIVADA . '/alerta_email.txt'; // cópia do endereço, para avisar mesmo com o banco fora do ar

/** Envia o e-mail de alerta (no máximo um por execução). Retorna se conseguiu enviar. */
function enviar_alerta(array $alertas, string $destino): bool
{
    if (!$alertas || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $itens = '';
    foreach ($alertas as $alerta) {
        $itens .= '<li>' . htmlspecialchars($alerta, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $html = "<p>A manutenção diária do Risenglish de " . date('d/m/Y H:i') . " encontrou o seguinte:</p><ul>{$itens}</ul>"
        . "<p>Detalhes na área do master: entre com o usuário master e abra <strong>Saúde</strong>.</p>";
    return enviarEmailSimples($destino, 'Risenglish', '[Risenglish] Alerta da manutenção diária', $html,
        "Alertas da manutenção do Risenglish:\n- " . implode("\n- ", $alertas));
}

/** Registra uma falha fatal (banco fora do ar etc.): avisa por e-mail e encerra. */
function falha_fatal(string $mensagem, string $arquivoEmailAlerta): void
{
    error_log('Manutenção: ' . $mensagem);
    echo "Erro: {$mensagem}\n";
    $destino = is_file($arquivoEmailAlerta) ? trim((string) file_get_contents($arquivoEmailAlerta)) : '';
    enviar_alerta(["A manutenção NÃO terminou: {$mensagem}"], $destino);
    exit(1);
}

if (!isset($pdo)) {
    falha_fatal('falha na conexão com o banco de dados.', $arquivoEmailAlerta);
}

/** Executa um DELETE em lotes de 1000 para não travar o banco. Retorna o total apagado. */
function apagar_em_lotes(PDO $pdo, string $sql, array $params = []): int
{
    $total = 0;
    do {
        $stmt = $pdo->prepare($sql . ' LIMIT 1000');
        $stmt->execute($params);
        $apagados = $stmt->rowCount();
        $total += $apagados;
        if ($apagados > 0) {
            sleep(1);
        }
    } while ($apagados > 0);
    return $total;
}

/** Tamanho total (bytes) de uma pasta, incluindo subpastas. */
function tamanho_pasta(string $pasta): int
{
    if (!is_dir($pasta)) {
        return 0;
    }
    $total = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS)) as $arquivo) {
        if ($arquivo->isFile()) {
            $total += $arquivo->getSize();
        }
    }
    return $total;
}

function mb(int $bytes): string
{
    if ($bytes === 0) {
        return '0 KB';
    }
    return $bytes < 1048576
        ? max(1, round($bytes / 1024)) . ' KB'
        : number_format($bytes / 1048576, 1, ',', '.') . ' MB';
}

try {
    // 1. Logs de acesso com mais de 90 dias (data_acesso é gravada já no horário de Brasília)
    $resumo['logs_acesso'] = apagar_em_lotes($pdo, "DELETE FROM logs_acesso WHERE data_acesso < NOW() - INTERVAL 90 DAY");

    // 2. Tentativas de login por IP expiradas há mais de 1 dia.
    //    bloqueado_ate é gravado pelo PHP (horário de São Paulo), então o limite também vem do PHP.
    $resumo['tentativas_ip'] = apagar_em_lotes(
        $pdo,
        "DELETE FROM tentativas_ip WHERE bloqueado_ate IS NULL OR bloqueado_ate < ?",
        [date('Y-m-d H:i:s', strtotime('-1 day'))]
    );

    // 3. Notificações lidas há mais de 90 dias e notificações de usuários removidos
    $resumo['notificacoes_lidas'] = apagar_em_lotes(
        $pdo,
        "DELETE FROM notificacoes WHERE lida = 1 AND COALESCE(data_leitura, data_criacao) < NOW() - INTERVAL 90 DAY"
    );
    $resumo['notificacoes_sem_usuario'] = apagar_em_lotes(
        $pdo,
        "DELETE FROM notificacoes WHERE usuario_id NOT IN (SELECT id FROM usuarios)"
    );

    // 4. Tokens de redefinição de senha vencidos (a expiração é calculada pelo próprio MySQL)
    $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = NULL, token_expira_em = NULL
                           WHERE reset_token IS NOT NULL AND token_expira_em < NOW()");
    $stmt->execute();
    $resumo['tokens_vencidos'] = $stmt->rowCount();

} catch (PDOException $e) {
    falha_fatal('erro ao limpar o banco (' . $e->getMessage() . ').', $arquivoEmailAlerta);
}

// 5. Auditoria e histórico do cron: 1 ano (datas gravadas pelo PHP, horário de São Paulo).
//    Separado do bloco acima: se as tabelas ainda não existirem, o resto da manutenção segue normalmente.
try {
    $umAnoAtras = date('Y-m-d H:i:s', strtotime('-1 year'));
    $resumo['auditoria'] = apagar_em_lotes($pdo, "DELETE FROM logs_auditoria WHERE data_hora < ?", [$umAnoAtras]);
    apagar_em_lotes($pdo, "DELETE FROM cron_execucoes WHERE data_hora < ?", [$umAnoAtras]);
} catch (PDOException $e) {
    error_log('Manutenção (auditoria): ' . $e->getMessage());
    $alertas[] = 'As tabelas de auditoria/monitoramento não estão disponíveis (o SQL de instalação foi executado?).';
}

// Endereço dos alertas: fica no banco (site_settings) e é copiado para um arquivo na pasta privada
$emailAlerta = '';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'alerta_email'");
    $stmt->execute();
    $emailAlerta = trim((string) $stmt->fetchColumn());
    if ($emailAlerta !== '' && is_dir(PASTA_PRIVADA)) {
        @file_put_contents($arquivoEmailAlerta, $emailAlerta);
    }
} catch (Throwable $e) {
    $alertas[] = 'Não foi possível ler o e-mail de alerta no banco.';
}

// =====================================================================
// 6. Órfãos (registros e arquivos) — simulação, a menos que --apagar-orfaos
// =====================================================================
$relatorio = [];
$pastaP = dirname(__DIR__);        // public_html/p
$pastaRaiz = dirname(__DIR__, 2);  // public_html

try {
    // 6a. Arquivos/subpastas de temas excluídos (parent_id aponta para um conteúdo que não existe mais)
    $orfaosConteudo = $pdo->query(
        "SELECT c.id, c.caminho_arquivo FROM conteudos c
         WHERE c.parent_id IS NOT NULL AND c.parent_id NOT IN (SELECT id FROM conteudos)"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orfaosConteudo as $c) {
        $relatorio[] = "registro conteudos #{$c['id']} (tema excluído) " . ($c['caminho_arquivo'] ?: '(subpasta)');
    }
    if ($apagarOrfaos && $orfaosConteudo) {
        // Repete até não sobrar nada, porque apagar uma subpasta pode deixar os filhos dela órfãos
        do {
            $n = $pdo->exec("DELETE FROM conteudos WHERE parent_id IS NOT NULL AND parent_id NOT IN (SELECT id FROM (SELECT id FROM conteudos) t)");
        } while ($n > 0);
    }

    // 6b. Arquivos que nenhum registro usa (os registros órfãos acima não contam como uso)
    $idsOrfaos = array_column($orfaosConteudo, 'id');
    $emUso = [];
    foreach ($pdo->query("SELECT id, caminho_arquivo FROM conteudos WHERE caminho_arquivo LIKE 'uploads/%'") as $c) {
        if (!in_array($c['id'], $idsOrfaos)) {
            $emUso[realpath($pastaP . '/' . $c['caminho_arquivo']) ?: $pastaP . '/' . $c['caminho_arquivo']] = true;
        }
    }
    foreach ($pdo->query("SELECT caminho_arquivo FROM usuarios_anexos") as $a) {
        $emUso[realpath($pastaP . '/' . $a['caminho_arquivo']) ?: $pastaP . '/' . $a['caminho_arquivo']] = true;
    }
    foreach ($pdo->query("SELECT foto_perfil FROM usuarios WHERE foto_perfil IS NOT NULL") as $u) {
        foreach (AVATAR_TAMANHOS as $tamanho) {
            $arquivo = avatar_dir() . '/avatar_' . $u['foto_perfil'] . '_' . $tamanho . '.webp';
            $emUso[realpath($arquivo) ?: $arquivo] = true;
        }
    }

    // pasta => pode apagar os órfãos? Anexos de ex-alunos ficam guardados (decisão da escola).
    $pastas = [
        $pastaP . '/uploads/conteudos'        => true,
        $pastaP . '/uploads/anexos_usuarios'  => false,
        $pastaRaiz . '/uploads/avatars'       => true,
    ];
    $umDiaAtras = time() - 86400; // não mexe em uploads recentes (podem estar em andamento)
    $arquivosOrfaos = 0;
    $bytesOrfaos = 0;
    $anexosGuardados = 0;
    foreach ($pastas as $pasta => $podeApagar) {
        if (!is_dir($pasta)) {
            continue;
        }
        $totalNaPasta = 0;
        $orfaosDaPasta = [];
        foreach (new DirectoryIterator($pasta) as $arquivo) {
            if (!$arquivo->isFile() || $arquivo->getFilename()[0] === '.' || $arquivo->getFilename() === 'index.php') {
                continue; // ignora .htaccess e afins
            }
            $totalNaPasta++;
            $caminho = $arquivo->getRealPath();
            if (isset($emUso[$caminho]) || $arquivo->getMTime() > $umDiaAtras) {
                continue;
            }
            $orfaosDaPasta[] = $arquivo->getFileInfo();
        }

        // TRAVA DE SEGURANÇA: órfãos de verdade são poucos. Se muitos arquivos parecerem órfãos,
        // é sinal de problema na leitura do banco (ou banco de teste) — nesse caso não apaga nada.
        $suspeito = $podeApagar && $orfaosDaPasta && (empty($emUso) || count($orfaosDaPasta) > max(5, $totalNaPasta * 0.3));
        $nomePasta = str_replace($pastaRaiz . DIRECTORY_SEPARATOR, '', $pasta);
        if ($suspeito) {
            $relatorio[] = "ATENÇÃO: {$nomePasta} tem " . count($orfaosDaPasta) . " de {$totalNaPasta} arquivos sem registro no banco"
                . " — acima do limite de segurança, NADA foi apagado nesta pasta. Verifique o banco.";
            $resumo['alerta'] = (isset($resumo['alerta']) ? $resumo['alerta'] . '; ' : '') . "muitos órfãos em {$nomePasta}, nada apagado";
            $alertas[] = "Trava de segurança dos órfãos: {$nomePasta} tem " . count($orfaosDaPasta) . " de {$totalNaPasta} arquivos sem registro no banco. Nada foi apagado.";
        }

        foreach ($orfaosDaPasta as $arquivo) {
            $descricao = str_replace($pastaRaiz . DIRECTORY_SEPARATOR, '', $arquivo->getRealPath())
                . ' (' . round($arquivo->getSize() / 1024) . ' KB, ' . date('d/m/Y', $arquivo->getMTime()) . ')';
            if (!$podeApagar) {
                $anexosGuardados++;
                $relatorio[] = 'guardado (anexo de usuário removido, não é apagado) ' . $descricao;
                continue;
            }
            $arquivosOrfaos++;
            $bytesOrfaos += $arquivo->getSize();
            $relatorio[] = 'arquivo ' . $descricao;
            if ($apagarOrfaos && !$suspeito) {
                @unlink($arquivo->getRealPath());
            }
        }
    }
    $resumo['conteudos_orfaos'] = count($orfaosConteudo);
    $resumo['arquivos_orfaos'] = $arquivosOrfaos . ' (' . round($bytesOrfaos / 1048576, 1) . ' MB)';
    $resumo['anexos_guardados'] = $anexosGuardados;
} catch (Throwable $e) {
    error_log('Erro na manutenção (órfãos): ' . $e->getMessage());
    $resumo['orfaos'] = 'erro — ver log de erros';
    $alertas[] = 'Erro ao verificar arquivos órfãos: ' . $e->getMessage();
}

// Relatório dos órfãos (substituído a cada execução). Fica em p/includes, bloqueado para a web.
file_put_contents(
    __DIR__ . '/relatorio_orfaos.txt',
    date('Y-m-d H:i:s') . ($apagarOrfaos ? " — APAGADOS (exceto onde houver ATENÇÃO):\n" : " — SIMULAÇÃO (nada foi apagado):\n")
        . ($relatorio ? implode("\n", $relatorio) : '(nenhum órfão encontrado)') . "\n"
);

// =====================================================================
// 7. Backup semanal do banco
// =====================================================================

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

$pastaBackups = PASTA_PRIVADA . '/backups';
$ultimoBackup = null;
try {
    if (!is_dir($pastaBackups) && !@mkdir($pastaBackups, 0750, true)) {
        throw new RuntimeException('não foi possível criar a pasta ' . $pastaBackups);
    }
    $existentes = glob($pastaBackups . '/backup_*.sql.gz') ?: [];
    rsort($existentes);
    $idadeDias = $existentes ? (time() - filemtime($existentes[0])) / 86400 : INF;
    if ($backupAgora || $idadeDias >= BACKUP_INTERVALO_DIAS - 0.1) { // folga para o horário do cron variar alguns minutos
        $novo = $pastaBackups . '/backup_' . date('Y-m-d') . '.sql.gz';
        gerar_backup($pdo, $novo);
        $resumo['backup'] = 'novo (' . mb(filesize($novo)) . ')';
    } else {
        $resumo['backup'] = 'último há ' . floor($idadeDias) . ' dia(s)';
    }
    $resumo['backups_apagados'] = rotacionar_backups($pastaBackups, $alertas);
    $existentes = glob($pastaBackups . '/backup_*.sql.gz') ?: [];
    rsort($existentes);
    $ultimoBackup = $existentes ? date('Y-m-d H:i:s', filemtime($existentes[0])) : null;
} catch (Throwable $e) {
    error_log('Erro na manutenção (backup): ' . $e->getMessage());
    $resumo['backup'] = 'ERRO';
    $alertas[] = 'O backup do banco falhou: ' . $e->getMessage();
}

// =====================================================================
// 7b. Arquivos que não devem existir no site (sobras antigas com dados pessoais e código sem uso).
//     Dumps .sql na raiz do public_html só são apagados se já existir um backup novo válido.
// =====================================================================
$obsoletos = [
    $pastaP . '/debug_anotacoes.log',                    // log de depuração antigo com texto de anotações e IPs completos
    $pastaP . '/professor/ajax_salvar_visibilidade.php', // endpoints que nenhuma tela usa mais
    $pastaP . '/professor/ajax_update_planejado.php',
    $pastaP . '/professor/ajax_vincular_conteudo.php',
    $pastaP . '/admin/monitoramento.php',                // a área do master mudou para p/master
    $pastaP . '/admin/baixar_backup.php',
    $pastaRaiz . '/css/admin/monitoramento.css',
];
if ($ultimoBackup !== null) {
    $obsoletos = array_merge($obsoletos, glob($pastaRaiz . '/*.sql') ?: [], glob($pastaRaiz . '/*.sql.gz') ?: []);
}
$removidos = [];
foreach ($obsoletos as $arquivo) {
    if (is_file($arquivo) && @unlink($arquivo)) {
        $removidos[] = basename($arquivo);
    }
}
if (is_dir($pastaRaiz . '/Risenglish') && count(scandir($pastaRaiz . '/Risenglish')) === 2 && @rmdir($pastaRaiz . '/Risenglish')) {
    $removidos[] = 'pasta vazia Risenglish/';
}
if ($removidos) {
    $resumo['obsoletos_removidos'] = implode(' | ', $removidos);
}

// =====================================================================
// 8. Log de erros do site: rotação e erros graves desde a última execução
// =====================================================================
$arquivoErros = PASTA_PRIVADA . '/logs/erros_site.log';
$errosGraves = 0;
try {
    $posicao = 0;
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'cron_erros_posicao'");
    $stmt->execute();
    $posicao = (int) $stmt->fetchColumn();

    if (is_file($arquivoErros)) {
        $tamanho = filesize($arquivoErros);
        if ($tamanho < $posicao) {
            $posicao = 0; // o arquivo foi trocado/rotacionado
        }
        $novos = (string) file_get_contents($arquivoErros, false, null, $posicao, 5 * 1024 * 1024);
        $errosGraves = preg_match_all('/PHP Fatal error|Uncaught|SQLSTATE/', $novos);
        $posicao = $tamanho;
        if ($tamanho > ERROS_LOG_MAX_BYTES) {
            rename($arquivoErros, PASTA_PRIVADA . '/logs/erros_site.anterior.log'); // guarda só a versão anterior
            $posicao = 0;
        }
    }
    $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('cron_erros_posicao', ?)
                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([(string) $posicao]);
    $resumo['erros_graves'] = $errosGraves;
    if ($errosGraves > 0) {
        $alertas[] = "{$errosGraves} erro(s) grave(s) registrado(s) no site desde a última manutenção. Veja a aba Erros no Monitoramento.";
    }
} catch (Throwable $e) {
    error_log('Erro na manutenção (log de erros): ' . $e->getMessage());
}

// =====================================================================
// 9. Tamanhos e espaço em disco
// =====================================================================
$tamanhoBanco = (int) $pdo->query("SELECT COALESCE(SUM(data_length + index_length), 0) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
$tamanhoUploads = tamanho_pasta($pastaP . '/uploads') + tamanho_pasta($pastaRaiz . '/uploads');
$tamanhoBackups = tamanho_pasta($pastaBackups);
$resumo['tamanhos'] = 'banco ' . mb($tamanhoBanco) . ', uploads ' . mb($tamanhoUploads) . ', backups ' . mb($tamanhoBackups);

// Limite de disco do plano (em MB), configurável em site_settings.limite_disco_mb
$limiteDiscoMb = 0;
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'limite_disco_mb'");
    $stmt->execute();
    $limiteDiscoMb = (int) $stmt->fetchColumn();
} catch (Throwable $e) {
}
if ($limiteDiscoMb > 0) {
    $usoSite = tamanho_pasta($pastaRaiz) + tamanho_pasta(PASTA_PRIVADA);
    $percentual = $usoSite / ($limiteDiscoMb * 1048576) * 100;
    if ($percentual >= DISCO_ALERTA_PERCENTUAL) {
        $alertas[] = 'O site já usa ' . mb($usoSite) . ' (' . round($percentual) . '% do limite de ' . number_format($limiteDiscoMb, 0, ',', '.') . ' MB do plano).';
    }
}

// =====================================================================
// 10. Registro da execução, log em texto e e-mail de alerta
// =====================================================================
$emailEnviado = enviar_alerta($alertas, $emailAlerta);
if ($alertas && !$emailEnviado) {
    error_log('Manutenção: havia alertas, mas o e-mail não foi enviado (verifique o SMTP e o alerta_email).');
}

$partes = [];
foreach ($resumo as $item => $valor) {
    $partes[] = "$item: $valor";
}
$linha = date('Y-m-d H:i:s') . ' - Manutenção ' . ($apagarOrfaos ? '(apagando órfãos)' : '(órfãos em simulação)')
    . ' - ' . implode(', ', $partes) . ($alertas ? ' - ALERTAS: ' . count($alertas) . ($emailEnviado ? ' (e-mail enviado)' : ' (e-mail NÃO enviado)') : '');
echo $linha . "\n";

try {
    $pdo->prepare(
        "INSERT INTO cron_execucoes (data_hora, duracao_segundos, status, resumo, alertas, email_enviado, tamanho_banco, tamanho_uploads, tamanho_backups, ultimo_backup)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        date('Y-m-d H:i:s'),
        (int) round(microtime(true) - $inicioExecucao),
        $alertas ? 'alerta' : 'ok',
        implode(', ', $partes),
        $alertas ? implode("\n", $alertas) : null,
        $emailEnviado ? 1 : 0,
        $tamanhoBanco,
        $tamanhoUploads,
        $tamanhoBackups,
        $ultimoBackup,
    ]);
} catch (Throwable $e) {
    error_log('Manutenção: não foi possível gravar em cron_execucoes: ' . $e->getMessage());
}

// Log de execução em texto: uma linha por dia, mantendo só os últimos 30 dias
$arquivoLog = __DIR__ . '/cron_log.txt';
$limite = date('Y-m-d', strtotime('-30 days'));
$linhas = is_file($arquivoLog) ? file($arquivoLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$linhas = array_filter($linhas, fn ($l) => substr($l, 0, 10) >= $limite);
$linhas[] = $linha;
file_put_contents($arquivoLog, implode("\n", $linhas) . "\n");
