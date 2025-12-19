<?php
// Define o tipo de conteúdo como JSON para o JavaScript saber como processar a resposta.
header('Content-Type: application/json');
session_start();
require_once '../includes/conexao.php'; // Ajuste o caminho conforme a estrutura de pastas

$response = ['success' => false, 'message' => ''];

// 1. Verificação de Sessão e Permissão
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    $response['message'] = 'Acesso negado. Sessão inválida ou usuário não é professor.';
    echo json_encode($response);
    exit;
}

// 2. Coleta e Validação dos Dados
$professor_id = $_SESSION['user_id'];
$aula_id = $_POST['aula_id'] ?? null;
$conteudo_id = $_POST['conteudo_id'] ?? null; // Aqui é o ID do Tema/Pasta
$status = $_POST['status'] ?? null; // 1 ou 0

// Validação básica dos inputs
if (!is_numeric($aula_id) || !is_numeric($conteudo_id) || !in_array($status, ['0', '1'])) {
    $response['message'] = 'Dados inválidos fornecidos.';
    echo json_encode($response);
    exit;
}

$status = (int)$status; // Converte para inteiro (1 ou 0)

try {
    // 3. Verifica se a aula pertence ao professor (Segurança!)
    $sql_check = "SELECT professor_id FROM aulas WHERE id = :aula_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([':aula_id' => $aula_id]);
    $aula_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$aula_data || $aula_data['professor_id'] != $professor_id) {
        $response['message'] = 'Aula não encontrada ou você não tem permissão para editá-la.';
        echo json_encode($response);
        exit;
    }

    // 4. Lógica de Inserção/Remoção na tabela aulas_conteudos
    if ($status === 1) {
        // Tornar visível (planejado = 1): Insere ou ignora se já existir
        $sql = "INSERT INTO aulas_conteudos (aula_id, conteudo_id, planejado) 
                VALUES (:aula_id, :conteudo_id, 1)
                ON DUPLICATE KEY UPDATE planejado = 1"; // Atualiza para 1 se já existir
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':aula_id' => $aula_id,
            ':conteudo_id' => $conteudo_id
        ]);

        $response['success'] = true;
        $response['message'] = 'Tema marcado como VISÍVEL para os alunos.';
        
    } else {
        // Tornar não visível (planejado = 0): Remove o registro da tabela
        // O COALESCE em detalhes_aula.php garante que a ausência do registro seja interpretada como '0'
        $sql = "DELETE FROM aulas_conteudos 
                WHERE aula_id = :aula_id AND conteudo_id = :conteudo_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':aula_id' => $aula_id,
            ':conteudo_id' => $conteudo_id
        ]);

        $response['success'] = true;
        $response['message'] = 'Tema marcado como NÃO VISÍVEL para os alunos.';
    }

} catch (PDOException $e) {
    // Em caso de erro no banco de dados
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
    // Você pode logar o erro aqui
} catch (Exception $e) {
    // Outros erros
    $response['message'] = 'Erro interno: ' . $e->getMessage();
}

echo json_encode($response);
?>
