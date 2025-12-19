<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id'])) exit;

$professor_id = $_SESSION['user_id'];

// Buscamos as aulas e o nome da turma
$sql = "SELECT a.id, a.data_aula, a.horario, t.nome_turma 
        FROM aulas a 
        JOIN turmas t ON a.turma_id = t.id 
        WHERE a.professor_id = :professor_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['professor_id' => $professor_id]);
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventos = [];

foreach ($aulas as $aula) {
    // Formato YYYY-MM-DDTHH:MM:SS
    $start = $aula['data_aula'] . 'T' . $aula['horario'];
    
    $eventos[] = [
        'id' => $aula['id'], // ID da aula para o redirecionamento
        'title' => $aula['nome_turma'],
        'start' => $start,
        'backgroundColor' => '#081d40',
        'borderColor' => '#081d40',
        'allDay' => false
    ];
}

echo json_encode($eventos);