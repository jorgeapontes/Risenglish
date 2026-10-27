<?php
/**
 * Monitoramento do site — SÓ para o usuário master (dono do sistema), que não usa as páginas do admin.
 * Seções (no menu lateral): Saúde, Admins, Auditoria, Segurança, Erros, Backups e Configurações.
 * Os dados vêm de cron_execucoes e logs_auditoria (gravados pelo cron e pelas telas)
 * e dos arquivos da pasta privada (backups e log de erros), fora do public_html.
 */
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';
require_once '../includes/auditoria.php';
require_once '../includes/site_settings.php';
require_once '../includes/email_config.php'; // no escopo do arquivo: as funções de e-mail usam as variáveis globais do SMTP
require_once '../includes/backup.php';

// (verifica_sessao.php já registra na auditoria quem não é master e tenta abrir esta pasta)
if (($_SESSION['user_tipo'] ?? '') !== 'master') {
    header("Location: ../login?erro=acesso_negado");
    exit;
}

const CRON_ATRASO_HORAS = 30; // roda todo dia às 3h; passou disso, algo está errado

/** "1,2 MB" / "350 KB" */
function tamanho_legivel(?int $bytes): string
{
    if ($bytes === null) {
        return '—';
    }
    if ($bytes === 0) {
        return '0 KB';
    }
    return $bytes < 1048576
        ? max(1, round($bytes / 1024)) . ' KB'
        : number_format($bytes / 1048576, 1, ',', '.') . ' MB';
}

/** "há 3 h" / "há 2 dias" */
function tempo_atras(int $timestamp): string
{
    $segundos = time() - $timestamp;
    if ($segundos < 3600) {
        return 'há ' . max(1, floor($segundos / 60)) . ' min';
    }
    if ($segundos < 86400 * 2) {
        return 'há ' . floor($segundos / 3600) . ' h';
    }
    return 'há ' . floor($segundos / 86400) . ' dias';
}

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// ===== Configurações (e-mail de alerta e limite de disco) =====
if (empty($_SESSION['csrf_monitoramento'])) {
    $_SESSION['csrf_monitoramento'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'configuracoes') {
    $ok = hash_equals($_SESSION['csrf_monitoramento'], (string) ($_POST['csrf'] ?? ''));
    $email = trim((string) ($_POST['alerta_email'] ?? ''));
    $limite = (int) ($_POST['limite_disco_mb'] ?? 0);
    if ($ok && filter_var($email, FILTER_VALIDATE_EMAIL) && $limite >= 0) {
        set_setting('alerta_email', $email);
        set_setting('limite_disco_mb', (string) $limite);
        $_SESSION['monitoramento_msg'] = ['success', 'Configurações salvas.'];
    } else {
        $_SESSION['monitoramento_msg'] = ['danger', 'Não foi possível salvar: confira o e-mail informado.'];
    }
    header('Location: monitoramento?aba=configuracoes');
    exit;
}

// ===== E-mail de teste (confirma que os alertas da manutenção chegam) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'email_teste') {
    $destino = get_setting('alerta_email', '');
    if (!hash_equals($_SESSION['csrf_monitoramento'], (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['monitoramento_msg'] = ['danger', 'Requisição inválida. Tente novamente.'];
    } elseif (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['monitoramento_msg'] = ['danger', 'Cadastre um e-mail de alerta válido antes de testar.'];
    } elseif (enviarEmailSimples($destino, 'Risenglish', '[Risenglish] Teste do e-mail de alertas',
        '<p>Este é um teste enviado pela tela de Monitoramento em ' . date('d/m/Y H:i') . '.</p>'
        . '<p>Se você recebeu, os alertas da manutenção diária também vão chegar neste endereço.</p>')) {
        $_SESSION['monitoramento_msg'] = ['success', "E-mail de teste enviado para {$destino}. Confira a caixa de entrada (e o spam)."];
    } else {
        $_SESSION['monitoramento_msg'] = ['danger', 'O envio falhou. O motivo foi registrado na seção Erros.'];
    }
    header('Location: monitoramento?aba=configuracoes');
    exit;
}

// ===== Fazer backup agora (o mesmo backup do cron; o do dia é substituído) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'backup_agora') {
    if (!hash_equals($_SESSION['csrf_monitoramento'], (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['monitoramento_msg'] = ['danger', 'Requisição inválida. Tente novamente.'];
    } else {
        try {
            set_time_limit(300);
            $pastaBackups = PASTA_PRIVADA . '/backups';
            if (!is_dir($pastaBackups) && !@mkdir($pastaBackups, 0750, true)) {
                throw new RuntimeException('não foi possível criar a pasta de backups');
            }
            $novo = $pastaBackups . '/backup_' . date('Y-m-d') . '.sql.gz';
            gerar_backup($pdo, $novo);
            $alertasRotacao = [];
            rotacionar_backups($pastaBackups, $alertasRotacao);
            registrar_auditoria('backup_criado', 'Fez um backup manual do banco (' . mb(filesize($novo)) . ')', 'backup');
            $_SESSION['monitoramento_msg'] = ['success', 'Backup feito (' . mb(filesize($novo)) . '). Ele já aparece na lista abaixo.'];
        } catch (Throwable $e) {
            error_log('Backup manual: ' . $e->getMessage());
            $_SESSION['monitoramento_msg'] = ['danger', 'O backup falhou. O motivo foi registrado na seção Erros.'];
        }
    }
    header('Location: monitoramento?aba=backups');
    exit;
}

// ===== Contas de admin: nova senha, desbloquear, ativar/desativar =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['acao'] ?? '', ['admin_senha', 'admin_desbloquear', 'admin_status'], true)) {
    $acaoAdmin = $_POST['acao'];
    $adminId = (int) ($_POST['usuario_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, nome, status FROM usuarios WHERE id = ? AND tipo_usuario = 'admin'");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!hash_equals($_SESSION['csrf_monitoramento'], (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['monitoramento_msg'] = ['danger', 'Requisição inválida. Tente novamente.'];
    } elseif (!$admin) {
        $_SESSION['monitoramento_msg'] = ['danger', 'Conta de admin não encontrada.'];
    } elseif ($acaoAdmin === 'admin_senha') {
        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        if (strlen($novaSenha) < 8) {
            $_SESSION['monitoramento_msg'] = ['danger', 'A nova senha deve ter pelo menos 8 caracteres.'];
        } elseif ($novaSenha !== (string) ($_POST['confirma_senha'] ?? '')) {
            $_SESSION['monitoramento_msg'] = ['danger', 'A confirmação não confere com a nova senha.'];
        } else {
            // Também zera o bloqueio por senha errada, para a pessoa já conseguir entrar
            $pdo->prepare("UPDATE usuarios SET senha = ?, tentativas_falhas = 0, bloqueado_ate = NULL, reset_token = NULL, token_expira_em = NULL WHERE id = ?")
                ->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $adminId]);
            registrar_auditoria('senha_alterada', "Definiu uma nova senha para o admin {$admin['nome']} (pelo master)", 'usuario', $adminId);
            $_SESSION['monitoramento_msg'] = ['success', "Nova senha definida para {$admin['nome']}. Passe a senha para a pessoa por um canal seguro."];
        }
    } elseif ($acaoAdmin === 'admin_desbloquear') {
        $pdo->prepare("UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = ?")->execute([$adminId]);
        registrar_auditoria('usuario_desbloqueado', "Desbloqueou o admin {$admin['nome']} (pelo master)", 'usuario', $adminId);
        $_SESSION['monitoramento_msg'] = ['success', "{$admin['nome']} foi desbloqueado(a) e já pode tentar entrar."];
    } else {
        $novoStatus = $admin['status'] === 'ativo' ? 'desativado' : 'ativo';
        $ativos = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'admin' AND status = 'ativo'")->fetchColumn();
        if ($novoStatus === 'desativado' && $ativos <= 1) {
            $_SESSION['monitoramento_msg'] = ['danger', 'Esta é a única conta de admin ativa: desativá-la deixaria a escola sem acesso ao painel.'];
        } else {
            $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?")->execute([$novoStatus, $adminId]);
            registrar_auditoria('usuario_status', ($novoStatus === 'ativo' ? 'Ativou' : 'Desativou') . " o admin {$admin['nome']} (pelo master)", 'usuario', $adminId);
            $_SESSION['monitoramento_msg'] = ['success', "{$admin['nome']} foi " . ($novoStatus === 'ativo' ? 'ativado(a)' : 'desativado(a)') . '.'];
        }
    }
    header('Location: monitoramento?aba=admins');
    exit;
}

// ===== Troca da própria senha (o master não tem a página "Meu Perfil") =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'senha') {
    $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([(int) $_SESSION['user_id']]);
    $senhaAtualHash = (string) $stmt->fetchColumn();
    $novaSenha = (string) ($_POST['nova_senha'] ?? '');

    if (!hash_equals($_SESSION['csrf_monitoramento'], (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['monitoramento_msg'] = ['danger', 'Requisição inválida. Tente novamente.'];
    } elseif (!password_verify((string) ($_POST['senha_atual'] ?? ''), $senhaAtualHash)) {
        usleep(random_int(300000, 600000)); // dificulta tentativas em sequência
        $_SESSION['monitoramento_msg'] = ['danger', 'A senha atual está incorreta.'];
    } elseif (strlen($novaSenha) < 8) {
        $_SESSION['monitoramento_msg'] = ['danger', 'A nova senha deve ter pelo menos 8 caracteres.'];
    } elseif ($novaSenha !== (string) ($_POST['confirma_senha'] ?? '')) {
        $_SESSION['monitoramento_msg'] = ['danger', 'A confirmação não confere com a nova senha.'];
    } else {
        $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([password_hash($novaSenha, PASSWORD_DEFAULT), (int) $_SESSION['user_id']]);
        registrar_auditoria('senha_alterada', 'Trocou a própria senha (master)', 'usuario', $_SESSION['user_id']);
        session_regenerate_id(true);
        $_SESSION['monitoramento_msg'] = ['success', 'Senha alterada.'];
    }
    header('Location: monitoramento?aba=configuracoes');
    exit;
}
$mensagem = $_SESSION['monitoramento_msg'] ?? null;
unset($_SESSION['monitoramento_msg']);

$abas = ['saude' => 'Saúde', 'admins' => 'Admins', 'auditoria' => 'Auditoria', 'seguranca' => 'Segurança', 'erros' => 'Erros', 'backups' => 'Backups', 'configuracoes' => 'Configurações'];
$aba = isset($abas[$_GET['aba'] ?? '']) ? $_GET['aba'] : 'saude';
$pastaBackups = PASTA_PRIVADA . '/backups';
$arquivoErros = PASTA_PRIVADA . '/logs/erros_site.log';

// ===== Dados comuns: última execução do cron e backups =====
$execucoes = [];
try {
    $execucoes = $pdo->query("SELECT * FROM cron_execucoes ORDER BY data_hora DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Monitoramento: ' . $e->getMessage());
}
$ultimaExecucao = $execucoes[0] ?? null;
$cronAtrasado = !$ultimaExecucao || (time() - strtotime($ultimaExecucao['data_hora'])) > CRON_ATRASO_HORAS * 3600;

$backups = glob($pastaBackups . '/backup_*.sql.gz') ?: [];
rsort($backups);
$ultimoBackupTs = $backups ? filemtime($backups[0]) : null;
$backupAtrasado = !$ultimoBackupTs || (time() - $ultimoBackupTs) > 8 * 86400;


// ===== Aba Saúde =====
if ($aba === 'saude') {
    $haTrintaDias = null;
    try {
        $stmt = $pdo->prepare("SELECT tamanho_banco, tamanho_uploads FROM cron_execucoes WHERE data_hora <= ? ORDER BY data_hora DESC LIMIT 1");
        $stmt->execute([date('Y-m-d H:i:s', strtotime('-30 days'))]);
        $haTrintaDias = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
    }

    // Informações do servidor
    $versaoMysql = '—';
    try {
        $versaoMysql = (string) $pdo->query("SELECT VERSION()")->fetchColumn();
    } catch (Throwable $e) {
    }
    $emailUltimoOk = get_setting('email_ultimo_ok', '');
    $emailUltimaFalha = get_setting('email_ultima_falha', '');
    $servidor = [
        'Versão do PHP'               => PHP_VERSION,
        'Versão do MySQL'             => $versaoMysql,
        'Tamanho máximo de upload'    => ini_get('upload_max_filesize') . ' por arquivo · ' . ini_get('post_max_size') . ' por envio',
        'Memória do PHP'              => ini_get('memory_limit'),
        'Tempo máximo de uma página'  => ((int) ini_get('max_execution_time')) . ' s',
        'Fuso horário do site'        => date_default_timezone_get() . ' · agora: ' . date('d/m/Y H:i'),
        'Pasta privada (backups/logs)' => is_writable(PASTA_PRIVADA) ? 'ok, com permissão de escrita' : 'SEM permissão de escrita',
        'Último e-mail enviado'       => $emailUltimoOk !== '' ? date('d/m/Y H:i', strtotime($emailUltimoOk)) : 'nenhum registrado ainda',
    ];
    if ($emailUltimaFalha !== '') {
        $servidor['Última falha de e-mail'] = date('d/m/Y H:i', strtotime(substr($emailUltimaFalha, 0, 19))) . substr($emailUltimaFalha, 19);
    }
}

// ===== Admins =====
if ($aba === 'admins') {
    $admins = $pdo->query(
        "SELECT u.id, u.nome, u.email, u.status, u.tentativas_falhas, u.bloqueado_ate,
                (SELECT MAX(l.data_acesso) FROM logs_acesso l WHERE l.usuario_id = u.id AND l.tipo_evento = 'login_sucesso') AS ultimo_acesso
         FROM usuarios u WHERE u.tipo_usuario = 'admin' ORDER BY u.nome"
    )->fetchAll(PDO::FETCH_ASSOC);
}

// ===== Configurações =====
if ($aba === 'configuracoes') {
    $alertaEmail = get_setting('alerta_email', '');
    $limiteDiscoMb = (int) get_setting('limite_disco_mb', '0');
}

// ===== Aba Auditoria =====
if ($aba === 'auditoria') {
    $periodos = [7 => '7 dias', 30 => '30 dias', 90 => '90 dias', 365 => '1 ano'];
    $periodo = isset($periodos[(int) ($_GET['periodo'] ?? 0)]) ? (int) $_GET['periodo'] : 30;
    $filtroAcao = (string) ($_GET['acao_filtro'] ?? '');
    $busca = trim((string) ($_GET['busca'] ?? ''));
    $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
    $porPagina = 50;

    $rotulos = [];
    foreach (AUDITORIA_ACOES as $grupo => $acoes) {
        $rotulos += $acoes;
    }

    $where = ["data_hora >= ?"];
    $params = [date('Y-m-d H:i:s', strtotime("-{$periodo} days"))];
    if (str_starts_with($filtroAcao, 'grupo:') && isset(AUDITORIA_ACOES[substr($filtroAcao, 6)])) {
        $chaves = array_keys(AUDITORIA_ACOES[substr($filtroAcao, 6)]);
        $where[] = 'acao IN (' . implode(',', array_fill(0, count($chaves), '?')) . ')';
        $params = array_merge($params, $chaves);
    } elseif (isset($rotulos[$filtroAcao])) {
        $where[] = 'acao = ?';
        $params[] = $filtroAcao;
    } else {
        $filtroAcao = '';
    }
    if ($busca !== '') {
        $where[] = '(usuario_nome LIKE ? OR descricao LIKE ?)';
        $params[] = "%{$busca}%";
        $params[] = "%{$busca}%";
    }
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs_auditoria {$sqlWhere}");
    $stmt->execute($params);
    $totalAuditoria = (int) $stmt->fetchColumn();
    $totalPaginas = max(1, (int) ceil($totalAuditoria / $porPagina));
    $pagina = min($pagina, $totalPaginas);

    $stmt = $pdo->prepare("SELECT * FROM logs_auditoria {$sqlWhere} ORDER BY data_hora DESC, id DESC LIMIT {$porPagina} OFFSET " . (($pagina - 1) * $porPagina));
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $urlFiltros = 'monitoramento?aba=auditoria&periodo=' . $periodo . '&acao_filtro=' . urlencode($filtroAcao) . '&busca=' . urlencode($busca);
}

// ===== Aba Segurança (últimos 7 dias) =====
if ($aba === 'seguranca') {
    $seteDias = "NOW() - INTERVAL 7 DAY";
    $loginsOk = (int) $pdo->query("SELECT COUNT(*) FROM logs_acesso WHERE tipo_evento = 'login_sucesso' AND data_acesso >= {$seteDias}")->fetchColumn();
    $loginsFalha = (int) $pdo->query("SELECT COUNT(*) FROM logs_acesso WHERE tipo_evento = 'login_falha' AND data_acesso >= {$seteDias}")->fetchColumn();
    $falhasPorUsuario = $pdo->query(
        "SELECT u.nome, u.tipo_usuario, COUNT(*) AS falhas, MAX(l.data_acesso) AS ultima
         FROM logs_acesso l JOIN usuarios u ON u.id = l.usuario_id
         WHERE l.tipo_evento = 'login_falha' AND l.data_acesso >= {$seteDias}
         GROUP BY u.id, u.nome, u.tipo_usuario ORDER BY falhas DESC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT nome, tipo_usuario, bloqueado_ate FROM usuarios WHERE tentativas_falhas >= 5 AND bloqueado_ate > ? ORDER BY bloqueado_ate");
    $stmt->execute([date('Y-m-d H:i:s')]);
    $contasBloqueadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tentativas_ip WHERE bloqueado_ate > ?");
    $stmt->execute([date('Y-m-d H:i:s')]);
    $ipsBloqueados = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM logs_auditoria WHERE acao IN ('acesso_negado', 'conta_bloqueada', 'senha_alterada', 'senha_redefinida', 'reset_solicitado', 'usuario_desbloqueado', 'backup_baixado')
                           AND data_hora >= ? ORDER BY data_hora DESC LIMIT 50");
    $stmt->execute([date('Y-m-d H:i:s', strtotime('-30 days'))]);
    $eventosSeguranca = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rotulos = [];
    foreach (AUDITORIA_ACOES as $acoes) {
        $rotulos += $acoes;
    }
}

// ===== Aba Erros: fim do log de erros, mais recentes primeiro =====
if ($aba === 'erros') {
    $linhasErro = [];
    $tamanhoLogErros = is_file($arquivoErros) ? filesize($arquivoErros) : 0;
    if ($tamanhoLogErros > 0) {
        $inicio = max(0, $tamanhoLogErros - 128 * 1024);
        $trecho = (string) file_get_contents($arquivoErros, false, null, $inicio);
        $linhas = preg_split('/\R(?=\[\d{2}-\w{3}-\d{4} )/', trim($trecho)); // uma entrada por data (stack traces juntos)
        if ($inicio > 0) {
            array_shift($linhas); // a primeira pode estar cortada no meio
        }
        $linhasErro = array_slice(array_reverse($linhas), 0, 150);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoramento - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/master/base.css?v=<?= @filemtime(__DIR__ . '/../../css/master/base.css') ?>">
    <link rel="stylesheet" href="../../css/master/monitoramento.css?v=<?= @filemtime(__DIR__ . '/../../css/master/monitoramento.css') ?>">
</head>
<body>

<div class="d-flex">
    <?php $paginaAtiva = $aba; $tituloMobile = $abas[$aba]; require '../includes/layout/master_sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="page-header">
            <div>
                <div class="sobretitulo">Monitoramento</div>
                <h1 class="page-title"><?= e($abas[$aba]) ?></h1>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?= e($mensagem[0]) ?> alert-dismissible fade show" role="alert">
                <?= e($mensagem[1]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>


        <?php if ($aba === 'saude'): ?>
            <div class="row g-3 mb-4">
                <!-- Cron -->
                <div class="col-md-6 col-xl-3">
                    <div class="cartao-status <?= $cronAtrasado ? 'problema' : (($ultimaExecucao['status'] ?? '') === 'alerta' ? 'atencao' : 'ok') ?>">
                        <div class="rotulo"><i class="fas fa-clock"></i> Manutenção diária</div>
                        <?php if ($ultimaExecucao): ?>
                            <div class="valor"><?= tempo_atras(strtotime($ultimaExecucao['data_hora'])) ?></div>
                            <div class="detalhe">
                                <?= date('d/m/Y H:i', strtotime($ultimaExecucao['data_hora'])) ?> ·
                                <?= $cronAtrasado ? 'NÃO RODOU nas últimas ' . CRON_ATRASO_HORAS . ' h' : ($ultimaExecucao['status'] === 'alerta' ? 'com alertas' : 'tudo certo') ?>
                            </div>
                        <?php else: ?>
                            <div class="valor">Nunca rodou</div>
                            <div class="detalhe">Nenhuma execução registrada</div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Backup -->
                <div class="col-md-6 col-xl-3">
                    <div class="cartao-status <?= $backupAtrasado ? 'problema' : 'ok' ?>">
                        <div class="rotulo"><i class="fas fa-database"></i> Último backup</div>
                        <?php if ($ultimoBackupTs): ?>
                            <div class="valor"><?= tempo_atras($ultimoBackupTs) ?></div>
                            <div class="detalhe"><?= date('d/m/Y', $ultimoBackupTs) ?> · <?= tamanho_legivel(filesize($backups[0])) ?> · <?= count($backups) ?> guardado(s)</div>
                        <?php else: ?>
                            <div class="valor">Nenhum</div>
                            <div class="detalhe">O primeiro sai na próxima manutenção</div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Banco -->
                <div class="col-md-6 col-xl-3">
                    <div class="cartao-status neutro">
                        <div class="rotulo"><i class="fas fa-table"></i> Banco de dados</div>
                        <div class="valor"><?= tamanho_legivel($ultimaExecucao['tamanho_banco'] ?? null) ?></div>
                        <div class="detalhe">
                            <?php if ($haTrintaDias && $ultimaExecucao): ?>
                                há 30 dias: <?= tamanho_legivel((int) $haTrintaDias['tamanho_banco']) ?>
                            <?php else: ?>
                                medido pela manutenção diária
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Uploads -->
                <div class="col-md-6 col-xl-3">
                    <div class="cartao-status neutro">
                        <div class="rotulo"><i class="fas fa-folder-open"></i> Arquivos enviados</div>
                        <div class="valor"><?= tamanho_legivel($ultimaExecucao['tamanho_uploads'] ?? null) ?></div>
                        <div class="detalhe">
                            <?php if ($haTrintaDias && $ultimaExecucao): ?>
                                há 30 dias: <?= tamanho_legivel((int) $haTrintaDias['tamanho_uploads']) ?>
                            <?php else: ?>
                                PDFs, áudios, anexos e fotos
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($ultimaExecucao && $ultimaExecucao['alertas']): ?>
                <div class="alert alert-warning">
                    <strong>Alertas da última manutenção</strong>
                    <?= $ultimaExecucao['email_enviado'] ? '(enviados por e-mail)' : '(o e-mail NÃO foi enviado — confira o log de erros)' ?>
                    <ul class="mb-0 mt-2">
                        <?php foreach (explode("\n", $ultimaExecucao['alertas']) as $alerta): ?>
                            <li><?= e($alerta) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <h2 class="titulo-secao">Últimas execuções da manutenção</h2>
            <div class="table-responsive mb-4">
                <table class="table tabela-acessos tabela-cartoes">
                    <thead>
                        <tr><th>Data</th><th>Resultado</th><th>Duração</th><th>Resumo</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($execucoes as $execucao): ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($execucao['data_hora'])) ?></td>
                                <td class="text-nowrap">
                                    <?php if ($execucao['status'] === 'ok'): ?>
                                        <span class="selo ok">Tudo certo</span>
                                    <?php else: ?>
                                        <span class="selo atencao" title="<?= e($execucao['alertas']) ?>">Alerta<?= $execucao['email_enviado'] ? ' · e-mail enviado' : '' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="texto-secundario"><?= (int) $execucao['duracao_segundos'] ?> s</td>
                                <td class="texto-secundario resumo-cron"><?= e($execucao['resumo']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$execucoes): ?>
                            <tr><td colspan="4" class="text-center py-4 texto-vazio">Nenhuma execução registrada ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="titulo-secao">Servidor</h2>
            <dl class="lista-servidor mb-4">
                <?php foreach ($servidor as $rotulo => $valor): ?>
                    <div>
                        <dt><?= e($rotulo) ?></dt>
                        <dd class="<?= str_starts_with((string) $valor, 'SEM ') || $rotulo === 'Última falha de e-mail' ? 'text-danger' : '' ?>"><?= e($valor) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>

        <?php elseif ($aba === 'admins'): ?>
            <p class="contagem-acessos">
                Contas de admin da escola. Alunos e professores continuam sendo gerenciados pelo admin, na tela Usuários.
                Tudo o que for feito aqui fica registrado na auditoria.
            </p>
            <?php foreach ($admins as $adm):
                $bloqueado = (int) $adm['tentativas_falhas'] >= 5 && $adm['bloqueado_ate'] && strtotime($adm['bloqueado_ate']) > time();
            ?>
                <div class="cartao-admin mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold"><?= e($adm['nome']) ?></div>
                            <div class="texto-secundario small"><?= e($adm['email']) ?></div>
                            <div class="texto-secundario small">
                                Último acesso: <?= $adm['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($adm['ultimo_acesso'])) : 'nenhum nos últimos 90 dias' ?>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <?php if ($adm['status'] !== 'ativo'): ?><span class="selo atencao">Desativado</span><?php endif; ?>
                            <?php if ($bloqueado): ?><span class="selo atencao">Bloqueado até <?= date('H:i', strtotime($adm['bloqueado_ate'])) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#senhaAdmin<?= (int) $adm['id'] ?>" aria-expanded="false">
                            <i class="fas fa-key me-1"></i> Definir nova senha
                        </button>
                        <?php if ($bloqueado): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="acao" value="admin_desbloquear">
                                <input type="hidden" name="usuario_id" value="<?= (int) $adm['id'] ?>">
                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_monitoramento']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-unlock me-1"></i> Desbloquear</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('<?= $adm['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?> a conta de <?= e(addslashes($adm['nome'])) ?>?');">
                            <input type="hidden" name="acao" value="admin_status">
                            <input type="hidden" name="usuario_id" value="<?= (int) $adm['id'] ?>">
                            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_monitoramento']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="fas <?= $adm['status'] === 'ativo' ? 'fa-user-slash' : 'fa-user-check' ?> me-1"></i> <?= $adm['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                    </div>

                    <div class="collapse" id="senhaAdmin<?= (int) $adm['id'] ?>">
                        <form method="POST" class="row g-2 mt-2" autocomplete="off">
                            <input type="hidden" name="acao" value="admin_senha">
                            <input type="hidden" name="usuario_id" value="<?= (int) $adm['id'] ?>">
                            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_monitoramento']) ?>">
                            <div class="col-md-5">
                                <label class="form-label small" for="novaSenhaAdmin<?= (int) $adm['id'] ?>">Nova senha</label>
                                <input type="password" class="form-control" id="novaSenhaAdmin<?= (int) $adm['id'] ?>" name="nova_senha" minlength="8" autocomplete="new-password" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small" for="confirmaSenhaAdmin<?= (int) $adm['id'] ?>">Confirmar</label>
                                <input type="password" class="form-control" id="confirmaSenhaAdmin<?= (int) $adm['id'] ?>" name="confirma_senha" minlength="8" autocomplete="new-password" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-filtrar w-100">Salvar</button>
                            </div>
                            <div class="col-12 texto-secundario small">Mínimo de 8 caracteres. Também desbloqueia a conta, se estiver bloqueada.</div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$admins): ?>
                <p class="texto-vazio">Nenhuma conta de admin cadastrada.</p>
            <?php endif; ?>

        <?php elseif ($aba === 'configuracoes'): ?>
            <h2 class="titulo-secao">Alertas</h2>
            <form method="POST" class="filtros-acessos row g-3 mb-2">
                <input type="hidden" name="acao" value="configuracoes">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_monitoramento']) ?>">
                <div class="col-md-6">
                    <label class="form-label" for="alertaEmail">E-mail que recebe os alertas</label>
                    <input type="email" class="form-control" id="alertaEmail" name="alerta_email" value="<?= e($alertaEmail) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="limiteDisco">Limite de disco do plano (MB)</label>
                    <input type="number" class="form-control" id="limiteDisco" name="limite_disco_mb" min="0" step="1" value="<?= $limiteDiscoMb ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-filtrar w-100">Salvar</button>
                </div>
            </form>
            <p class="texto-secundario small mb-4">
                Os alertas chegam por e-mail só quando a manutenção diária encontra algum problema (no máximo um por noite).
                O alerta de disco dispara quando o site passa de 80% do limite. Use 0 para desligar.
            </p>

            <form method="POST" class="mb-4">
                <input type="hidden" name="acao" value="email_teste">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_monitoramento']) ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-paper-plane me-1"></i> Enviar e-mail de teste
                </button>
                <span class="texto-secundario small ms-2">Confirma que os alertas chegam no endereço acima.</span>
            </form>

            <h2 class="titulo-secao">Minha senha</h2>
            <form method="POST" class="filtros-acessos row g-3 mb-2" autocomplete="off">
                <input type="hidden" name="acao" value="senha">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_monitoramento']) ?>">
                <div class="col-md-4">
                    <label class="form-label" for="senhaAtual">Senha atual</label>
                    <input type="password" class="form-control" id="senhaAtual" name="senha_atual" autocomplete="current-password" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="novaSenha">Nova senha</label>
                    <input type="password" class="form-control" id="novaSenha" name="nova_senha" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="confirmaSenha">Confirmar</label>
                    <input type="password" class="form-control" id="confirmaSenha" name="confirma_senha" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-filtrar w-100">Trocar</button>
                </div>
            </form>
            <p class="texto-secundario small">Mínimo de 8 caracteres.</p>

        <?php elseif ($aba === 'auditoria'): ?>
            <form method="GET" class="filtros-acessos row g-3 mb-4">
                <input type="hidden" name="aba" value="auditoria">
                <div class="col-md-4">
                    <label class="form-label" for="buscaAuditoria">Buscar</label>
                    <input type="text" class="form-control" id="buscaAuditoria" name="busca" value="<?= e($busca) ?>" placeholder="Nome ou descrição...">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="acaoFiltro">Ação</label>
                    <select class="form-select" id="acaoFiltro" name="acao_filtro">
                        <option value="">Todas</option>
                        <?php foreach (AUDITORIA_ACOES as $grupo => $acoes): ?>
                            <optgroup label="<?= e($grupo) ?>">
                                <option value="grupo:<?= e($grupo) ?>" <?= $filtroAcao === 'grupo:' . $grupo ? 'selected' : '' ?>>Tudo de <?= e(mb_strtolower($grupo)) ?></option>
                                <?php foreach ($acoes as $chave => $rotulo): ?>
                                    <option value="<?= e($chave) ?>" <?= $filtroAcao === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="periodo">Período</label>
                    <select class="form-select" id="periodo" name="periodo">
                        <?php foreach ($periodos as $dias => $rotulo): ?>
                            <option value="<?= $dias ?>" <?= $periodo === $dias ? 'selected' : '' ?>>Últimos <?= e($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-filtrar w-100">Filtrar</button>
                </div>
            </form>

            <p class="contagem-acessos"><?= $totalAuditoria ?> registro(s) · mais recentes primeiro · guardados por 1 ano</p>

            <div class="table-responsive">
                <table class="table tabela-acessos tabela-cartoes">
                    <thead>
                        <tr><th>Data</th><th>Quem</th><th>Ação</th><th>Descrição</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($r['data_hora'])) ?></td>
                                <td>
                                    <?= e($r['usuario_nome'] ?? '—') ?>
                                    <?php if ($r['perfil']): ?><span class="texto-secundario small">· <?= e($r['perfil']) ?></span><?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= e($rotulos[$r['acao']] ?? $r['acao']) ?></td>
                                <td><?= e($r['descricao']) ?></td>
                                <td class="texto-secundario text-nowrap"><?= e($r['ip']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$registros): ?>
                            <tr><td colspan="5" class="text-center py-5 texto-vazio">Nenhum registro para esses filtros.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <nav class="d-flex justify-content-between align-items-center mt-3" aria-label="Páginas">
                    <a class="btn btn-outline-secondary <?= $pagina <= 1 ? 'disabled' : '' ?>" href="<?= e($urlFiltros . '&pagina=' . ($pagina - 1)) ?>">&larr; Mais recentes</a>
                    <span class="texto-secundario small">Página <?= $pagina ?> de <?= $totalPaginas ?></span>
                    <a class="btn btn-outline-secondary <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>" href="<?= e($urlFiltros . '&pagina=' . ($pagina + 1)) ?>">Mais antigos &rarr;</a>
                </nav>
            <?php endif; ?>

        <?php elseif ($aba === 'seguranca'): ?>
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="cartao-status neutro">
                        <div class="rotulo">Logins (7 dias)</div>
                        <div class="valor"><?= $loginsOk ?></div>
                        <div class="detalhe">entradas com sucesso</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cartao-status <?= $loginsFalha > 30 ? 'atencao' : 'neutro' ?>">
                        <div class="rotulo">Senhas erradas (7 dias)</div>
                        <div class="valor"><?= $loginsFalha ?></div>
                        <div class="detalhe">em contas existentes</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cartao-status <?= $contasBloqueadas ? 'atencao' : 'neutro' ?>">
                        <div class="rotulo">Contas bloqueadas agora</div>
                        <div class="valor"><?= count($contasBloqueadas) ?></div>
                        <div class="detalhe">por 5 senhas erradas</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cartao-status <?= $ipsBloqueados ? 'atencao' : 'neutro' ?>">
                        <div class="rotulo">IPs bloqueados agora</div>
                        <div class="valor"><?= $ipsBloqueados ?></div>
                        <div class="detalhe">por excesso de tentativas</div>
                    </div>
                </div>
            </div>

            <?php if ($contasBloqueadas): ?>
                <h2 class="titulo-secao">Contas bloqueadas agora</h2>
                <ul class="lista-simples mb-4">
                    <?php foreach ($contasBloqueadas as $c): ?>
                        <li><?= e($c['nome']) ?> <span class="texto-secundario">(<?= e($c['tipo_usuario']) ?>) · até <?= date('H:i', strtotime($c['bloqueado_ate'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h2 class="titulo-secao">Quem mais errou a senha (7 dias)</h2>
            <?php if ($falhasPorUsuario): ?>
                <ul class="lista-simples mb-4">
                    <?php foreach ($falhasPorUsuario as $f): ?>
                        <li><?= e($f['nome']) ?> <span class="texto-secundario">(<?= e($f['tipo_usuario']) ?>) · <?= (int) $f['falhas'] ?> erro(s), último em <?= date('d/m H:i', strtotime($f['ultima'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="texto-vazio mb-4">Ninguém errou a senha nos últimos 7 dias.</p>
            <?php endif; ?>

            <h2 class="titulo-secao">Eventos de segurança (30 dias)</h2>
            <div class="table-responsive">
                <table class="table tabela-acessos tabela-cartoes">
                    <thead><tr><th>Data</th><th>Quem</th><th>Evento</th><th>Descrição</th><th>IP</th></tr></thead>
                    <tbody>
                        <?php foreach ($eventosSeguranca as $r): ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($r['data_hora'])) ?></td>
                                <td><?= e($r['usuario_nome'] ?? '—') ?></td>
                                <td class="text-nowrap"><?= e($rotulos[$r['acao']] ?? $r['acao']) ?></td>
                                <td><?= e($r['descricao']) ?></td>
                                <td class="texto-secundario text-nowrap"><?= e($r['ip']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$eventosSeguranca): ?>
                            <tr><td colspan="5" class="text-center py-4 texto-vazio">Nenhum evento nos últimos 30 dias.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($aba === 'erros'): ?>
            <p class="contagem-acessos">
                Erros registrados pelo PHP no site (mais recentes primeiro) · arquivo com <?= tamanho_legivel($tamanhoLogErros) ?>,
                trocado automaticamente ao passar de 5 MB. Linhas em vermelho são erros graves.
            </p>
            <?php if ($linhasErro): ?>
                <div class="log-erros">
                    <?php foreach ($linhasErro as $linha): ?>
                        <div class="<?= preg_match('/PHP Fatal error|Uncaught|SQLSTATE/', $linha) ? 'grave' : '' ?>"><?= e($linha) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="texto-vazio">Nenhum erro registrado.</p>
            <?php endif; ?>

        <?php elseif ($aba === 'backups'): ?>
            <p class="contagem-acessos">
                Backup do banco feito toda semana pela manutenção diária. Ficam guardados os 4 mais recentes e 1 por mês dos últimos 6 meses
                (no máximo 50 MB), numa pasta fora do site. Os arquivos enviados (PDFs, áudios, fotos) não entram aqui: eles estão no backup da Hostinger.
            </p>
            <form method="POST" class="mb-3" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = 'Fazendo backup...';">
                <input type="hidden" name="acao" value="backup_agora">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_monitoramento']) ?>">
                <button type="submit" class="btn btn-filtrar"><i class="fas fa-database me-1"></i> Fazer backup agora</button>
                <span class="texto-secundario small ms-2">Útil antes de uma mudança grande. Um backup feito hoje substitui o anterior do mesmo dia.</span>
            </form>
            <div class="table-responsive">
                <table class="table tabela-acessos tabela-cartoes">
                    <thead><tr><th>Backup</th><th>Tamanho</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($backups as $arquivo): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', filemtime($arquivo)) ?> <span class="texto-secundario small">· <?= tempo_atras(filemtime($arquivo)) ?></span></td>
                                <td class="texto-secundario"><?= tamanho_legivel(filesize($arquivo)) ?></td>
                                <td class="td-acoes text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="baixar_backup?arquivo=<?= urlencode(basename($arquivo)) ?>">
                                        <i class="fas fa-download"></i> Baixar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$backups): ?>
                            <tr><td colspan="3" class="text-center py-4 texto-vazio">Nenhum backup ainda. O primeiro sai na próxima manutenção (meia-noite).</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="texto-secundario small mt-3">
                <i class="fas fa-lock"></i> O backup contém todos os dados do site (inclusive dados pessoais de alunos). Guarde o arquivo baixado em local seguro.
                Para restaurar: phpMyAdmin → banco → aba Importar → escolher o arquivo .sql.gz.
            </p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
