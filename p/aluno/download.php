<?php
session_start();
require_once '../includes/conexao.php';

// Verifica se usuário está logado e é aluno
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login.php");
    exit;
}

$aluno_id = $_SESSION['user_id'];

// Verifica se foi passado o ID do documento
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Arquivo não especificado.");
}

$doc_id = (int)$_GET['id'];

// Busca o documento no banco, garantindo que pertence ao aluno logado
$sql = "SELECT nome_arquivo, caminho_arquivo FROM usuarios_anexos WHERE id = :id AND usuario_id = :aluno_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $doc_id, ':aluno_id' => $aluno_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    die("Arquivo não encontrado ou você não tem permissão para acessá-lo.");
}

// Monta o caminho completo do arquivo
$arquivo_path = "../" . $doc['caminho_arquivo'];

// Verifica se o arquivo realmente existe no servidor
if (!file_exists($arquivo_path)) {
    die("O arquivo não está mais disponível no servidor. Caminho buscado: " . $arquivo_path);
}

// Força o download do arquivo
$nome_original = $doc['nome_arquivo'];
$mime_type = mime_content_type($arquivo_path);

// Se não conseguir detectar o MIME, usa um padrão
if (!$mime_type) {
    $mime_type = 'application/octet-stream';
}

// Limpa os buffers para evitar erro de "headers already sent"
if (ob_get_level()) {
    ob_end_clean();
}

// Headers para forçar o download
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . basename($nome_original) . '"');
header('Content-Length: ' . filesize($arquivo_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Envia o arquivo
readfile($arquivo_path);
exit;