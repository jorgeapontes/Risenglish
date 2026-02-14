<?php
session_start();
require_once '../includes/conexao.php';

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$professor_id = $_SESSION['user_id'];

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Receber dados
$anotacao_id = $_POST['anotacao_id'] ?? null;
$acao = $_POST['acao'] ?? 'toggle'; // 'marcar', 'desmarcar', 'toggle'

if (!$anotacao_id || !is_numeric($anotacao_id)) {
    echo json_encode(['success' => false, 'message' => 'ID da anotação inválido']);
    exit;
}

try {
    // Buscar informações da anotação para verificar permissão
    $sql_check = "SELECT aa.id, aa.aula_id, a.professor_id 
                  FROM anotacoes_aula aa
                  INNER JOIN aulas a ON aa.aula_id = a.id
                  WHERE aa.id = :anotacao_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([':anotacao_id' => $anotacao_id]);
    $anotacao = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$anotacao) {
        echo json_encode(['success' => false, 'message' => 'Anotação não encontrada']);
        exit;
    }

    // Verificar se a aula pertence ao professor
    if ($anotacao['professor_id'] != $professor_id) {
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para modificar esta anotação']);
        exit;
    }

    // Determinar novo status baseado na ação
    if ($acao === 'toggle') {
        $sql_status = "SELECT visto FROM anotacoes_aula WHERE id = :id";
        $stmt_status = $pdo->prepare($sql_status);
        $stmt_status->execute([':id' => $anotacao_id]);
        $status_atual = $stmt_status->fetchColumn();
        $novo_status = $status_atual ? 0 : 1;
    } else {
        $novo_status = ($acao === 'marcar') ? 1 : 0;
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Atualizar status de visto
    $sql_update = "UPDATE anotacoes_aula 
                   SET visto = :visto, 
                       data_visto = IF(:visto = 1, NOW(), NULL) 
                   WHERE id = :id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([
        ':visto' => $novo_status,
        ':id' => $anotacao_id
    ]);

    // Registrar no histórico se foi marcado como visto
    if ($novo_status == 1) {
        $sql_historico = "INSERT INTO anotacoes_visualizacoes (anotacao_id, professor_id) 
                          VALUES (:anotacao_id, :professor_id)";
        $stmt_historico = $pdo->prepare($sql_historico);
        $stmt_historico->execute([
            ':anotacao_id' => $anotacao_id,
            ':professor_id' => $professor_id
        ]);
    }

    $pdo->commit();

    // Buscar contagem de visualizações
    $sql_count = "SELECT COUNT(*) FROM anotacoes_visualizacoes WHERE anotacao_id = :anotacao_id";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute([':anotacao_id' => $anotacao_id]);
    $total_visualizacoes = $stmt_count->fetchColumn();

    echo json_encode([
        'success' => true,
        'message' => $novo_status ? 'Marcado como visto' : 'Marcado como não visto',
        'visto' => $novo_status,
        'total_visualizacoes' => $total_visualizacoes
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao marcar visto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação']);
}
?>