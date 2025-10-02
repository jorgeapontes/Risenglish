<?php
session_start();
require_once '../includes/conexao.php';

header('Content-Type: application/json');

// 1. Verificar autenticação e permissão
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$aula_id = $_POST['aula_id'] ?? null;
$conteudo_id = $_POST['conteudo_id'] ?? null;
$action = $_POST['action'] ?? null; // 'vincular' ou 'desvincular'

// 2. Validar dados de entrada
if (!$aula_id || !is_numeric($aula_id) || !$conteudo_id || !is_numeric($conteudo_id) || !in_array($action, ['vincular', 'desvincular'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos fornecidos.']);
    exit;
}

try {
    // 3. Verificar se a aula e o conteúdo pertencem ao professor (Segurança)
    // Verifica a aula (usa a coluna 'professor_id' da tabela 'aulas')
    $stmt_check_aula = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE id = :aula_id AND professor_id = :professor_id");
    $stmt_check_aula->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
    if ($stmt_check_aula->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada para esta aula.']);
        exit;
    }
    
    // Verifica o conteúdo (usa a coluna 'professor_id' da tabela 'conteudos')
    $stmt_check_conteudo = $pdo->prepare("SELECT COUNT(*) FROM conteudos WHERE id = :conteudo_id AND professor_id = :professor_id");
    $stmt_check_conteudo->execute([':conteudo_id' => $conteudo_id, ':professor_id' => $professor_id]);
    if ($stmt_check_conteudo->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada para este conteúdo.']);
        exit;
    }


    if ($action === 'vincular') {
        // Tenta inserir o vínculo. `planejado` é 0 (conteúdo inicialmente escondido do aluno)
        $sql = "INSERT INTO aulas_conteudos (aula_id, conteudo_id, planejado) VALUES (:aula_id, :conteudo_id, 0) 
                ON DUPLICATE KEY UPDATE aula_id = aula_id"; // Atualiza a si mesmo para evitar erro de duplicidade se já existir
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':aula_id' => $aula_id, ':conteudo_id' => $conteudo_id]);

        echo json_encode(['success' => true, 'message' => 'Conteúdo vinculado com sucesso.']);

    } elseif ($action === 'desvincular') {
        // Remove o vínculo
        $sql = "DELETE FROM aulas_conteudos WHERE aula_id = :aula_id AND conteudo_id = :conteudo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':aula_id' => $aula_id, ':conteudo_id' => $conteudo_id]);

        echo json_encode(['success' => true, 'message' => 'Conteúdo desvinculado com sucesso.']);
    }

} catch (PDOException $e) {
    // Se for um erro de chave duplicada, ainda assim é sucesso para o usuário
    if (strpos($e->getMessage(), 'Duplicate entry') !== false && $action === 'vincular') {
        echo json_encode(['success' => true, 'message' => 'Conteúdo já estava vinculado.']);
    } else {
        error_log("Erro no AJAX de vinculação: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno ao processar a solicitação.']);
    }
}
?>
