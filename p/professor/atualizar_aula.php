<?php
// Define que o retorno será SEMPRE um JSON (evita que erros de PHP quebrem o JS)
header('Content-Type: application/json');
session_start();
require_once '../includes/conexao.php';

try {
    $dados = json_decode(file_get_contents('php://input'), true);

    if (isset($dados['id']) && isset($dados['novaData'])) {
        $id = $dados['id'];
        $novaDataCompleta = $dados['novaData']; 

        // O FullCalendar envia: 2025-12-20T10:00:00 ou com fuso
        $partes = explode('T', $novaDataCompleta);
        $data_aula = $partes[0];
        
        // Limpa a hora para o formato HH:MM:SS
        $hora_limpa = substr($partes[1], 0, 8); 

        $sql = "UPDATE aulas SET data_aula = :data, horario = :hora WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $executou = $stmt->execute([
            ':data' => $data_aula,
            ':hora' => $hora_limpa,
            ':id'   => $id
        ]);

        if ($executou) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar banco']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;