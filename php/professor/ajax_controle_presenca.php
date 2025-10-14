<?php
session_start();
require_once '../includes/conexao.php';

// Verificar se é uma requisição AJAX válida
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$aula_id = $_POST['aula_id'] ?? null;
$aluno_id = $_POST['aluno_id'] ?? null;
$presente = $_POST['presente'] ?? 1;

// Validar dados
if (!$aula_id || !$aluno_id || !is_numeric($aula_id) || !is_numeric($aluno_id)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

try {
    // Verificar se o professor tem acesso a esta aula
    $sql_verificar_aula = "SELECT a.id FROM aulas a WHERE a.id = :aula_id AND a.professor_id = :professor_id";
    $stmt_verificar = $pdo->prepare($sql_verificar_aula);
    $stmt_verificar->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
    
    if (!$stmt_verificar->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Aula não encontrada ou acesso negado.']);
        exit;
    }

    // Verificar se já existe um registro de presença
    $sql_verificar_presenca = "SELECT id FROM presenca_aula WHERE aula_id = :aula_id AND aluno_id = :aluno_id";
    $stmt_verificar_presenca = $pdo->prepare($sql_verificar_presenca);
    $stmt_verificar_presenca->execute([':aula_id' => $aula_id, ':aluno_id' => $aluno_id]);
    $existe_presenca = $stmt_verificar_presenca->fetch();

    if ($existe_presenca) {
        // Atualizar presença existente
        $sql_atualizar = "UPDATE presenca_aula SET presente = :presente WHERE aula_id = :aula_id AND aluno_id = :aluno_id";
        $stmt_atualizar = $pdo->prepare($sql_atualizar);
        $stmt_atualizar->execute([
            ':presente' => $presente,
            ':aula_id' => $aula_id,
            ':aluno_id' => $aluno_id
        ]);
    } else {
        // Inserir novo registro de presença
        $sql_inserir = "INSERT INTO presenca_aula (aula_id, aluno_id, presente) VALUES (:aula_id, :aluno_id, :presente)";
        $stmt_inserir = $pdo->prepare($sql_inserir);
        $stmt_inserir->execute([
            ':aula_id' => $aula_id,
            ':aluno_id' => $aluno_id,
            ':presente' => $presente
        ]);
    }

    $status_text = $presente ? 'presente' : 'faltou';
    echo json_encode(['success' => true, 'message' => 'Presença atualizada com sucesso! Aluno marcado como ' . $status_text . '.']);

} catch (PDOException $e) {
    error_log("Erro ao atualizar presença: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados.']);
}
?>