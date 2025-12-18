<?php
session_start();
require_once '../includes/conexao.php';

// Verificar se é professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
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

// Verificar campos obrigatórios
$required_fields = ['titulo_aula', 'data_aula', 'horario'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "O campo '{$field}' é obrigatório."]);
        exit;
    }
}

// Buscar aula para verificar se pertence ao professor
$sql_verificar = "SELECT id FROM aulas WHERE id = :aula_id AND professor_id = :professor_id";
$stmt_verificar = $pdo->prepare($sql_verificar);
$stmt_verificar->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
$aula = $stmt_verificar->fetch(PDO::FETCH_ASSOC);

if (!$aula) {
    echo json_encode(['success' => false, 'message' => 'Aula não encontrada ou você não tem permissão para editá-la.']);
    exit;
}

// Preparar dados para atualização
$titulo_aula = trim($_POST['titulo_aula']);
$data_aula = $_POST['data_aula'];
$horario = $_POST['horario'];
$descricao = trim($_POST['descricao'] ?? '');

// Validar data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_aula)) {
    echo json_encode(['success' => false, 'message' => 'Formato de data inválido. Use YYYY-MM-DD.']);
    exit;
}

// Validar horário
if (!preg_match('/^\d{2}:\d{2}$/', $horario)) {
    echo json_encode(['success' => false, 'message' => 'Formato de horário inválido. Use HH:MM.']);
    exit;
}

// Adicionar segundos ao horário se necessário
if (strlen($horario) === 5) {
    $horario .= ':00';
}

// Atualizar aula
try {
    $sql_atualizar = "UPDATE aulas SET 
        titulo_aula = :titulo_aula,
        data_aula = :data_aula,
        horario = :horario,
        descricao = :descricao
        WHERE id = :aula_id AND professor_id = :professor_id";
    
    $stmt_atualizar = $pdo->prepare($sql_atualizar);
    $resultado = $stmt_atualizar->execute([
        ':titulo_aula' => $titulo_aula,
        ':data_aula' => $data_aula,
        ':horario' => $horario,
        ':descricao' => $descricao,
        ':aula_id' => $aula_id,
        ':professor_id' => $professor_id
    ]);
    
    if ($resultado) {
        echo json_encode([
            'success' => true, 
            'message' => 'Aula atualizada com sucesso!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Nenhuma alteração foi realizada.'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao atualizar aula: ' . $e->getMessage()
    ]);
}
?>