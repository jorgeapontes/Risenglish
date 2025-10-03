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
$status_planejado = $_POST['status'] ?? null; // 0 (Desmarcado) ou 1 (Marcado)

// 2. Validar dados de entrada
if (!$aula_id || !is_numeric($aula_id) || !$conteudo_id || !is_numeric($conteudo_id) || !in_array($status_planejado, ['0', '1'])) {
    echo json_encode(['success' => false, 'message' => 'Dados de entrada inválidos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 3. Verificar a propriedade do professor sobre a aula e o conteúdo (Segurança)
    $stmt_check = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM aulas WHERE id = :aula_id AND professor_id = :professor_id_a) AS aula_ok,
            (SELECT COUNT(*) FROM conteudos WHERE id = :conteudo_id AND professor_id = :professor_id_c) AS conteudo_ok
    ");
    $stmt_check->execute([
        ':aula_id' => $aula_id, 
        ':professor_id_a' => $professor_id, 
        ':conteudo_id' => $conteudo_id, 
        ':professor_id_c' => $professor_id
    ]);
    $checks = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($checks['aula_ok'] == 0 || $checks['conteudo_ok'] == 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Permissão negada ou dados não encontrados.']);
        exit;
    }

    $message = '';

    if ((int)$status_planejado === 1) {
        // Se a checkbox foi MARCADA (status = 1):
        // 1. Garante que o vínculo exista (INSERT OR UPDATE)
        $sql = "
            INSERT INTO aulas_conteudos (aula_id, conteudo_id, planejado) 
            VALUES (:aula_id, :conteudo_id, 1)
            ON DUPLICATE KEY UPDATE planejado = 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':aula_id' => $aula_id, ':conteudo_id' => $conteudo_id]);
        $message = 'Conteúdo vinculado e definido como Planejado (Visível ao aluno).';

    } else {
        // Se a checkbox foi DESMARCADA (status = 0):
        // 1. Atualiza o status 'planejado' para 0.
        // O VÍNCULO NÃO É REMOVIDO, apenas a VISIBILIDADE é desativada.
        $sql = "UPDATE aulas_conteudos SET planejado = 0 WHERE aula_id = :aula_id AND conteudo_id = :conteudo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':aula_id' => $aula_id, ':conteudo_id' => $conteudo_id]);
        $message = 'Conteúdo definido como Não Usado (Escondido do aluno).';
        
        // Se a linha não existia, a query acima não fará nada, mas isso é OK, pois 
        // a professora só interage com conteúdos que já existem no seu cadastro geral.
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message, 'new_status' => (int)$status_planejado]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro no AJAX de planejamento/vinculação: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao processar a solicitação: ' . $e->getMessage()]);
}
?>
