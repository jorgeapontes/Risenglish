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
$sql = "SELECT titulo, caminho_arquivo, tipo_arquivo, parent_id, professor_id FROM conteudos WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $conteudo_id]);
$conteudo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conteudo || empty($conteudo['caminho_arquivo'])) {
    http_response_code(404);
    die("Arquivo não encontrado no banco de dados.");
}

// 2.1 Verificação de posse: garante que o usuário logado tem direito a ver ESTE conteúdo
$user_tipo = $_SESSION['user_tipo'] ?? '';
$tem_acesso = false;

if ($user_tipo === 'admin') {
    $tem_acesso = true;
} elseif ($user_tipo === 'professor') {
    $tem_acesso = ((int) $conteudo['professor_id'] === (int) $_SESSION['user_id']);
} elseif ($user_tipo === 'aluno') {
    // Sobe a árvore de pastas (parent_id) até a raiz, coletando todos os IDs no caminho
    $ids_ancestrais = [$conteudo_id];
    $atual = $conteudo['parent_id'];
    for ($i = 0; $atual && $i < 20; $i++) {
        $ids_ancestrais[] = (int) $atual;
        $stmt_pai = $pdo->prepare("SELECT parent_id FROM conteudos WHERE id = :id");
        $stmt_pai->execute([':id' => $atual]);
        $atual = $stmt_pai->fetchColumn();
    }

    // Só tem acesso se algum item dessa árvore estiver vinculado (e planejado) a uma
    // aula de uma turma na qual o aluno está matriculado
    $placeholders = implode(',', array_fill(0, count($ids_ancestrais), '?'));
    $sql_acesso = "SELECT COUNT(*) FROM aulas_conteudos ac
                   JOIN aulas a ON ac.aula_id = a.id
                   JOIN alunos_turmas at ON a.turma_id = at.turma_id
                   WHERE ac.conteudo_id IN ($placeholders)
                     AND ac.planejado = 1
                     AND at.aluno_id = ?";
    $stmt_acesso = $pdo->prepare($sql_acesso);
    $stmt_acesso->execute(array_merge($ids_ancestrais, [$_SESSION['user_id']]));
    $tem_acesso = ((int) $stmt_acesso->fetchColumn()) > 0;
}

if (!$tem_acesso) {
    http_response_code(403);
    die("Acesso negado. Você não tem permissão para visualizar este conteúdo.");
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