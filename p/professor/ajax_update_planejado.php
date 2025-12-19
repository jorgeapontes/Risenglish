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
$status = $_POST['status'] ?? null; // 0 ou 1

// 2. Validar dados de entrada
if (!$aula_id || !is_numeric($aula_id) || !$conteudo_id || !is_numeric($conteudo_id) || !in_array($status, ['0', '1'])) {
    echo json_encode(['success' => false, 'message' => 'Dados de status inválidos fornecidos.']);
    exit;
}

try {
    // 3. Verificar se a aula e o conteúdo pertencem ao professor (Segurança)
    // A segurança é crucial aqui: o professor só pode manipular vinculações de suas próprias aulas e conteúdos.
    
    // Verifica a aula (confirma que o professor é dono da aula)
    $stmt_check_aula = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE id = :aula_id AND professor_id = :professor_id");
    $stmt_check_aula->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
    if ($stmt_check_aula->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada: Aula não pertence a este professor.']);
        exit;
    }
    
    // Verifica o conteúdo (confirma que o professor é dono do conteúdo)
    $stmt_check_conteudo = $pdo->prepare("SELECT COUNT(*) FROM conteudos WHERE id = :conteudo_id AND professor_id = :professor_id");
    $stmt_check_conteudo->execute([':conteudo_id' => $conteudo_id, ':professor_id' => $professor_id]);
    if ($stmt_check_conteudo->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada: Conteúdo não pertence a este professor.']);
        exit;
    }


    // 4. Atualizar o campo 'planejado' na tabela aulas_conteudos
    $sql = "UPDATE aulas_conteudos SET planejado = :status WHERE aula_id = :aula_id AND conteudo_id = :conteudo_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':status' => $status,
        ':aula_id' => $aula_id,
        ':conteudo_id' => $conteudo_id
    ]);

    // Verificar se a atualização afetou alguma linha (se o vínculo existe)
    if ($stmt->rowCount() > 0) {
        $message = ($status == 1) ? 'Conteúdo liberado para o aluno (Planejado).': 'Conteúdo escondido do aluno (Não Planejado).';
        echo json_encode(['success' => true, 'message' => $message, 'new_status' => (int)$status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vínculo de conteúdo/aula não encontrado.']);
    }

} catch (PDOException $e) {
    error_log("Erro no AJAX de planejamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao processar a solicitação de planejamento.']);
}
?>
