<?php
session_start();

require_once 'includes/conexao.php'; 

// 1. Validação de Acesso
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Acesso negado. Você precisa estar logado para visualizar o conteúdo.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("ID de conteúdo inválido.");
}

$conteudo_id = (int)$_GET['id'];

// 2. Busca o arquivo no banco de dados
$sql = "SELECT titulo, caminho_arquivo, tipo_arquivo, parent_id FROM conteudos WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $conteudo_id]);
$conteudo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conteudo || empty($conteudo['caminho_arquivo'])) {
    http_response_code(404);
    die("Arquivo não encontrado no banco de dados.");
}

// 3. CONSTRUÇÃO DO CAMINHO FÍSICO CORRIGIDO
// Isso deve retornar a pasta 'Risenglish' (raiz)
$raiz_projeto = dirname(__DIR__); 
$caminho_completo = $raiz_projeto . DIRECTORY_SEPARATOR . $conteudo['caminho_arquivo'];

// Normaliza barras para garantir que funcione em qualquer sistema
$caminho_completo = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminho_completo);


// 4. VERIFICAÇÃO FINAL (AGORA COM DEBUG ATIVO)
if (!file_exists($caminho_completo)) {
    http_response_code(404);
    
    // LINHA DE DEBUG ATIVA:
    die("DEBUG FINAL: Tentando acessar: " . $caminho_completo); 
    
    // die("Arquivo físico não encontrado no servidor."); // Originalmente desativada
}

// 5. Envio dos Headers para visualização INLINE
$mime_type = $conteudo['tipo_arquivo'];
$nome_arquivo = basename($caminho_completo);

header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . $nome_arquivo . '"');
header('Content-Length: ' . filesize($caminho_completo));

// Headers para evitar problemas de cache
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// 6. Lê e envia o arquivo
readfile($caminho_completo);

exit;

?>