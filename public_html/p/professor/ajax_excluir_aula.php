<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';
require_once '../includes/auditoria.php';

// Verificar se é professor
if ($_SESSION['user_tipo'] !== 'professor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$aula_id = $_POST['aula_id'] ?? null;

// Verificar dados obrigatórios
if (!$aula_id || !is_numeric($aula_id)) {
    echo json_encode(['success' => false, 'message' => 'ID da aula inválido.']);
    exit;
}

// Buscar aula para verificar se pertence ao professor e obter a turma_id
$sql_verificar = "SELECT a.id, a.turma_id, a.titulo_aula, a.data_aula, t.nome_turma FROM aulas a LEFT JOIN turmas t ON t.id = a.turma_id WHERE a.id = :aula_id AND a.professor_id = :professor_id";
$stmt_verificar = $pdo->prepare($sql_verificar);
$stmt_verificar->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
$aula = $stmt_verificar->fetch(PDO::FETCH_ASSOC);

if (!$aula) {
    echo json_encode(['success' => false, 'message' => 'Aula não encontrada ou você não tem permissão para excluí-la.']);
    exit;
}

$turma_id = $aula['turma_id'];

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // 1. Excluir registros de presença relacionados à aula
    $sql_excluir_presenca = "DELETE FROM presenca_aula WHERE aula_id = :aula_id";
    $stmt_presenca = $pdo->prepare($sql_excluir_presenca);
    $stmt_presenca->execute([':aula_id' => $aula_id]);
    
    // 2. Excluir associações com conteúdos planejados
    $sql_excluir_conteudos = "DELETE FROM aulas_conteudos WHERE aula_id = :aula_id";
    $stmt_conteudos = $pdo->prepare($sql_excluir_conteudos);
    $stmt_conteudos->execute([':aula_id' => $aula_id]);
    
    // 3. Excluir a aula
    $sql_excluir_aula = "DELETE FROM aulas WHERE id = :aula_id";
    $stmt_aula = $pdo->prepare($sql_excluir_aula);
    $stmt_aula->execute([':aula_id' => $aula_id]);
    
    // Confirmar transação
    $pdo->commit();
    registrar_auditoria('aula_excluida', "Excluiu a aula \"{$aula['titulo_aula']}\" de " . date('d/m/Y', strtotime($aula['data_aula'])) . " ({$aula['nome_turma']})", 'aula', $aula_id);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Aula excluída com sucesso!',
        'turma_id' => $turma_id
    ]);
    
} catch (PDOException $e) {
    // Reverter transação em caso de erro
    $pdo->rollBack();
    error_log('Erro ao excluir aula: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao excluir aula no banco de dados.'
    ]);
}
?>