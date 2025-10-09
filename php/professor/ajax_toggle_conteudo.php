<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/conexao.php';

$response = [
    'success' => false,
    'message' => 'Requisição inválida.'
];

// 1. VERIFICAÇÃO DE SESSÃO E TIPO DE USUÁRIO
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    $response['message'] = 'Acesso não autorizado.';
    echo json_encode($response);
    exit;
}

$professor_id = $_SESSION['user_id'];

// 2. COLETA E VALIDAÇÃO DOS DADOS POST
$aula_id = filter_input(INPUT_POST, 'aula_id', FILTER_VALIDATE_INT);
$conteudo_id = filter_input(INPUT_POST, 'conteudo_id', FILTER_VALIDATE_INT);
// Garante que o status seja 0 ou 1
$status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

if (!$aula_id || !$conteudo_id || !isset($status)) {
    $response['message'] = 'Dados da aula ou conteúdo ausentes/inválidos.';
    echo json_encode($response);
    exit;
}

try {
    // --- 3. VERIFICAÇÃO DE PROPRIEDADE DA AULA (CRÍTICO PARA SEGURANÇA) ---
    // O professor SÓ PODE VINCULAR conteúdo se ele for o dono da aula.
    $sql_verifica_aula = "SELECT COUNT(id) FROM aulas WHERE id = :aula_id AND professor_id = :professor_id";
    $stmt_verifica_aula = $pdo->prepare($sql_verifica_aula);
    $stmt_verifica_aula->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
    
    if ($stmt_verifica_aula->fetchColumn() == 0) {
        $response['message'] = 'Permissão negada. Você não é o criador desta aula.';
        echo json_encode($response);
        exit;
    }

    // --- 4. VERIFICAÇÃO DA EXISTÊNCIA DO CONTEÚDO (CRÍTICO PARA INTEGRIDADE) ---
    // Apenas verifica se o tema/pasta existe, independente de quem o criou.
    $sql_verifica_conteudo = "SELECT COUNT(id) FROM conteudos WHERE id = :conteudo_id AND parent_id IS NULL";
    $stmt_verifica_conteudo = $pdo->prepare($sql_verifica_conteudo);
    $stmt_verifica_conteudo->execute([':conteudo_id' => $conteudo_id]);
    
    if ($stmt_verifica_conteudo->fetchColumn() == 0) {
        $response['message'] = 'Conteúdo (Tema) não encontrado ou inválido.';
        echo json_encode($response);
        exit;
    }

    // --- 5. LÓGICA DE INSERÇÃO/ATUALIZAÇÃO (UPSERT) ---
    // O status (planejado) é 0 ou 1.
    $sql_upsert = "
        INSERT INTO aulas_conteudos (aula_id, conteudo_id, planejado) 
        VALUES (:aula_id, :conteudo_id, :status)
        ON DUPLICATE KEY UPDATE 
            planejado = :status;
    ";

    $stmt_upsert = $pdo->prepare($sql_upsert);
    $stmt_upsert->execute([
        ':aula_id' => $aula_id,
        ':conteudo_id' => $conteudo_id,
        ':status' => $status
    ]);
    
    // Sucesso
    if ($status == 1) {
        $response['message'] = 'Conteúdo marcado como VISÍVEL para o aluno com sucesso!';
    } else {
        $response['message'] = 'Conteúdo marcado como NÃO VISÍVEL para o aluno com sucesso!';
    }
    $response['success'] = true;

} catch (PDOException $e) {
    // Captura e loga erros de banco de dados
    error_log("Erro PDO em ajax_toggle_conteudo.php: " . $e->getMessage());
    $response['message'] = 'Erro interno do servidor ao processar a solicitação.';
}

echo json_encode($response);

// Importante: Para que o UPSERT funcione, a tabela aulas_conteudos DEVE ter uma chave primária (PRIMARY KEY)
// ou chave única (UNIQUE KEY) combinada nos campos (aula_id, conteudo_id).
// Exemplo de SQL para criar a chave única (se não existir):
// ALTER TABLE aulas_conteudos ADD UNIQUE KEY unique_aula_conteudo (aula_id, conteudo_id);
?>
