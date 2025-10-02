<?php
session_start();
require_once '../includes/conexao.php';

// 1. Verificação de Segurança
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    http_response_code(403); // Acesso negado
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// 2. Verifica se a requisição é POST e se os dados estão presentes
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['aula_id'], $_POST['conteudo_id'], $_POST['status'])) {
    http_response_code(400); // Requisição inválida
    echo json_encode(['success' => false, 'message' => 'Dados de requisição incompletos ou inválidos.']);
    exit;
}

// 3. Recebe e Sanitiza os dados
$aula_id = (int)$_POST['aula_id'];
$conteudo_id = (int)$_POST['conteudo_id'];
$status = (int)$_POST['status']; // 1 para Planejado, 0 para Não Planejado
$professor_id = $_SESSION['user_id'];

// 4. Executa o UPDATE no Banco de Dados
try {
    // A consulta garante que a atualização seja feita APENAS se a aula pertencer ao professor logado, 
    // prevenindo manipulação de dados de outras contas.
    $sql_update = "
        UPDATE aulas_conteudos ac
        JOIN aulas a ON ac.aula_id = a.id
        SET ac.planejado = :status
        WHERE ac.aula_id = :aula_id 
          AND ac.conteudo_id = :conteudo_id 
          AND a.professor_id = :professor_id
    ";
    $stmt = $pdo->prepare($sql_update);
    $stmt->execute([
        ':status' => $status,
        ':aula_id' => $aula_id,
        ':conteudo_id' => $conteudo_id,
        ':professor_id' => $professor_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!', 'novo_status' => $status]);
    } else {
        // Isso pode ocorrer se o conteúdo já estiver no status desejado ou se o professor não for o dono da aula.
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Registro não encontrado ou não autorizado.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>