<?php
/**
 * Manutenção diária do Risenglish — executada pelo cron da Hostinger, SOMENTE via linha de comando:
 *   php /home/u569225384/domains/risenglish.com.br/public_html/p/includes/limpar_logs.php
 *
 * O que faz:
 *   1. logs_acesso: apaga registros com mais de 45 dias
 *   2. tentativas_ip: apaga bloqueios/janelas de tentativa expirados há mais de 1 dia
 *   3. notificacoes: apaga as lidas com mais de 90 dias e as de usuários que não existem mais
 *   4. usuarios: limpa tokens de "esqueci a senha" vencidos
 *   5. Órfãos: arquivos em uploads (conteúdos, anexos, fotos de perfil) que nenhum registro usa,
 *      e arquivos/subpastas de temas que foram excluídos.
 *      Por segurança, roda em MODO SIMULAÇÃO: só lista em relatorio_orfaos.txt o que seria apagado.
 *      Para apagar de fato, rode com a opção --apagar-orfaos.
 *
 * O nome do arquivo foi mantido (antes só limpava logs_acesso) para não precisar mudar o cron.
 */

// Só pode rodar pelo cron (linha de comando). Pelo navegador, finge que não existe.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

set_time_limit(0);
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/avatar.php';

if (!isset($pdo)) {
    echo "Erro: Falha na conexão com o banco de dados.\n";
    exit(1);
}

$apagarOrfaos = in_array('--apagar-orfaos', $argv ?? [], true);
$resumo = [];

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

try {
    // 1. Logs de acesso com mais de 45 dias (data_acesso é gravada já no horário de Brasília)
    $resumo['logs_acesso'] = apagar_em_lotes($pdo, "DELETE FROM logs_acesso WHERE data_acesso < NOW() - INTERVAL 45 DAY");

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
    error_log('Erro na manutenção (banco): ' . $e->getMessage());
    echo "Erro ao limpar o banco. Verifique os logs do servidor.\n";
    exit(1);
}

// =====================================================================
// 5. Órfãos (registros e arquivos) — simulação, a menos que --apagar-orfaos
// =====================================================================
$relatorio = [];
$pastaP = dirname(__DIR__);        // public_html/p
$pastaRaiz = dirname(__DIR__, 2);  // public_html

try {
    // 5a. Arquivos/subpastas de temas excluídos (parent_id aponta para um conteúdo que não existe mais)
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

    // 5b. Arquivos que nenhum registro usa (os registros órfãos acima não contam como uso)
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

    $pastas = [
        $pastaP . '/uploads/conteudos',
        $pastaP . '/uploads/anexos_usuarios',
        $pastaRaiz . '/uploads/avatars',
    ];
    $umDiaAtras = time() - 86400; // não mexe em uploads recentes (podem estar em andamento)
    $arquivosOrfaos = 0;
    $bytesOrfaos = 0;
    foreach ($pastas as $pasta) {
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
        $suspeito = $orfaosDaPasta && (empty($emUso) || count($orfaosDaPasta) > max(5, $totalNaPasta * 0.3));
        $nomePasta = str_replace($pastaRaiz . DIRECTORY_SEPARATOR, '', $pasta);
        if ($suspeito) {
            $relatorio[] = "ATENÇÃO: {$nomePasta} tem " . count($orfaosDaPasta) . " de {$totalNaPasta} arquivos sem registro no banco"
                . " — acima do limite de segurança, NADA foi apagado nesta pasta. Verifique o banco.";
            $resumo['alerta'] = (isset($resumo['alerta']) ? $resumo['alerta'] . '; ' : '') . "muitos órfãos em {$nomePasta}, nada apagado";
        }

        foreach ($orfaosDaPasta as $arquivo) {
            $arquivosOrfaos++;
            $bytesOrfaos += $arquivo->getSize();
            $relatorio[] = 'arquivo ' . str_replace($pastaRaiz . DIRECTORY_SEPARATOR, '', $arquivo->getRealPath())
                . ' (' . round($arquivo->getSize() / 1024) . ' KB, ' . date('d/m/Y', $arquivo->getMTime()) . ')';
            if ($apagarOrfaos && !$suspeito) {
                @unlink($arquivo->getRealPath());
            }
        }
    }
    $resumo['conteudos_orfaos'] = count($orfaosConteudo);
    $resumo['arquivos_orfaos'] = $arquivosOrfaos . ' (' . round($bytesOrfaos / 1048576, 1) . ' MB)';
} catch (Throwable $e) {
    error_log('Erro na manutenção (órfãos): ' . $e->getMessage());
    $resumo['orfaos'] = 'erro — ver log do servidor';
}

// Relatório dos órfãos (substituído a cada execução). Fica em p/includes, bloqueado para a web.
file_put_contents(
    __DIR__ . '/relatorio_orfaos.txt',
    date('Y-m-d H:i:s') . ($apagarOrfaos ? " — APAGADOS (exceto onde houver ATENÇÃO):\n" : " — SIMULAÇÃO (nada foi apagado):\n")
        . ($relatorio ? implode("\n", $relatorio) : '(nenhum órfão encontrado)') . "\n"
);

// =====================================================================
// Log de execução: uma linha por dia, mantendo só os últimos 30 dias
// =====================================================================
$partes = [];
foreach ($resumo as $item => $valor) {
    $partes[] = "$item: $valor";
}
$linha = date('Y-m-d H:i:s') . ' - Manutenção ' . ($apagarOrfaos ? '(apagando órfãos)' : '(órfãos em simulação)')
    . ' - ' . implode(', ', $partes);
echo $linha . "\n";

$arquivoLog = __DIR__ . '/cron_log.txt';
$limite = date('Y-m-d', strtotime('-30 days'));
$linhas = is_file($arquivoLog) ? file($arquivoLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$linhas = array_filter($linhas, fn ($l) => substr($l, 0, 10) >= $limite);
$linhas[] = $linha;
file_put_contents($arquivoLog, implode("\n", $linhas) . "\n");
