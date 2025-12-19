<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['turma_id'])) exit;

$professor_id = $_SESSION['user_id'];
$turma_id = $_GET['turma_id'];

$sql = "SELECT a.id, a.data_aula, a.horario, t.nome_turma 
        FROM aulas a 
        JOIN turmas t ON a.turma_id = t.id 
        WHERE a.professor_id = :professor_id AND a.turma_id = :turma_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['professor_id' => $professor_id, 'turma_id' => $turma_id]);
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventos = [];
foreach ($aulas as $aula) {
    $eventos[] = [
        'id' => $aula['id'],
        'title' => $aula['nome_turma'],
        'start' => $aula['data_aula'] . 'T' . $aula['horario'],
        'backgroundColor' => '#081d40',
        'borderColor' => '#081d40',
        'allDay' => false
    ];
}
echo json_encode($eventos);