<?php
/** Download de um backup do banco — só para o master. Os backups ficam fora do public_html. */
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';
require_once '../includes/auditoria.php';

if (empty($_SESSION['user_master'])) {
    registrar_auditoria('acesso_negado', 'Tentou baixar um backup sem ser master', 'pagina');
    header("Location: ../login?erro=acesso_negado");
    exit;
}

$nome = (string) ($_GET['arquivo'] ?? '');
$arquivo = PASTA_PRIVADA . '/backups/' . $nome;
if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}\.sql\.gz$/', $nome) || !is_file($arquivo)) {
    http_response_code(404);
    exit('Backup não encontrado.');
}

registrar_auditoria('backup_baixado', "Baixou o backup {$nome}", 'backup');

header('Content-Type: application/gzip');
header('Content-Disposition: attachment; filename="risenglish_' . $nome . '"');
header('Content-Length: ' . filesize($arquivo));
header('Cache-Control: no-store');
readfile($arquivo);
