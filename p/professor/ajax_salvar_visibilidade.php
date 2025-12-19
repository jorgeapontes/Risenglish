<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$conteudo_id = $_POST['conteudo_id'] ?? null;
$visibilidades = $_POST['visibilidades'] ?? [];

if (!$conteudo_id || !is_array($visibilidades)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

try {
    // Verifica se o conteúdo pertence ao professor
    $sql_verifica = "SELECT c.id FROM conteudos c 
                     WHERE c.id = :conteudo_id AND c.professor_id = :professor_id";
    $stmt_verifica = $pdo->prepare($sql_verifica);
    $stmt_verifica->execute([':conteudo_id' => $conteudo_id, ':professor_id' => $professor_id]);
    
    if (!$stmt_verifica->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Conteúdo não encontrado ou acesso negado.']);
        exit;
    }

    // Processa cada visibilidade
    foreach ($visibilidades as $visibilidade) {
        $aula_id = $visibilidade['aula_id'];
        $visivel = $visibilidade['visivel'];
        
        // Verifica se a aula pertence ao professor
        $sql_verifica_aula = "SELECT id FROM aulas WHERE id = :aula_id AND professor_id = :professor_id";
        $stmt_verifica_aula = $pdo->prepare($sql_verifica_aula);
        $stmt_verifica_aula->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
        
        if (!$stmt_verifica_aula->fetch()) {
            continue; // Pula aulas que não pertencem ao professor
        }
        
        if ($visivel) {
            // Insere ou atualiza como visível
            $sql_upsert = "INSERT INTO arquivos_visiveis (aula_id, conteudo_id, visivel) 
                           VALUES (:aula_id, :conteudo_id, 1)
                           ON DUPLICATE KEY UPDATE visivel = 1";
        } else {
            // Insere ou atualiza como não visível
            $sql_upsert = "INSERT INTO arquivos_visiveis (aula_id, conteudo_id, visivel) 
                           VALUES (:aula_id, :conteudo_id, 0)
                           ON DUPLICATE KEY UPDATE visivel = 0";
        }
        
        $stmt_upsert = $pdo->prepare($sql_upsert);
        $stmt_upsert->execute([':aula_id' => $aula_id, ':conteudo_id' => $conteudo_id]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Visibilidade atualizada com sucesso.']);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}