<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Apenas admin pode baixar anexos
if ($_SESSION['user_tipo'] !== 'admin') {
    http_response_code(403);
    exit('Acesso negado.');
}

$anexo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($anexo_id <= 0) {
    http_response_code(400);
    exit('Requisição inválida.');
}

// Busca o arquivo no banco
$stmt = $pdo->prepare("SELECT nome_arquivo, caminho_arquivo FROM usuarios_anexos WHERE id = ?");
$stmt->execute([$anexo_id]);
$anexo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anexo) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$caminho_completo = '../' . $anexo['caminho_arquivo'];

if (!file_exists($caminho_completo)) {
    http_response_code(404);
    exit('Arquivo não encontrado no servidor.');
}

// Determina o tipo do arquivo para o header correto
$ext = strtolower(pathinfo($caminho_completo, PATHINFO_EXTENSION));
$mime_types = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];
$mime = $mime_types[$ext] ?? 'application/octet-stream';

// Serve o arquivo com os headers corretos
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($anexo['nome_arquivo']) . '"');
header('Content-Length: ' . filesize($caminho_completo));
header('Cache-Control: private, no-cache');
readfile($caminho_completo);
exit;
