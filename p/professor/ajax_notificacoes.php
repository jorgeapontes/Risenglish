<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$acao = $_GET['acao'] ?? '';

try {
    // Buscar notificações não lidas
    if ($acao === 'buscar_nao_lidas') {
        $sql = "SELECT id, titulo, mensagem, link, icone, cor, data_criacao 
                FROM notificacoes 
                WHERE usuario_id = :user_id AND lida = 0 
                ORDER BY data_criacao DESC 
                LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatar data para exibição
        foreach ($notificacoes as &$notif) {
            $data = new DateTime($notif['data_criacao']);
            $agora = new DateTime();
            $intervalo = $agora->diff($data);
            
            if ($intervalo->days == 0) {
                if ($intervalo->h == 0) {
                    if ($intervalo->i == 0) {
                        $notif['data_formatada'] = 'Agora mesmo';
                    } else {
                        $notif['data_formatada'] = $intervalo->i . ' min' . ($intervalo->i > 1 ? 's' : '') . ' atrás';
                    }
                } else {
                    $notif['data_formatada'] = $intervalo->h . ' h' . ($intervalo->h > 1 ? 's' : '') . ' atrás';
                }
            } elseif ($intervalo->days == 1) {
                $notif['data_formatada'] = 'Ontem às ' . $data->format('H:i');
            } else {
                $notif['data_formatada'] = $data->format('d/m/Y H:i');
            }
            
            // CORREÇÃO: Adicionar o caminho correto baseado no tipo de usuário
            if ($user_tipo === 'professor') {
                $notif['link'] = '/Risenglish/p/professor/' . $notif['link'];
            } else {
                $notif['link'] = '/Risenglish/p/aluno/' . $notif['link'];
            }
        }
        
        // Contar total não lidas
        $sql_count = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :user_id AND lida = 0";
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->execute([':user_id' => $user_id]);
        $total = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'notificacoes' => $notificacoes,
            'total_nao_lidas' => $total
        ]);
        exit;
    }
    
    // Marcar notificação como lida
    if ($acao === 'marcar_lida') {
        $notificacao_id = $_POST['notificacao_id'] ?? null;
        
        if ($notificacao_id) {
            $sql = "UPDATE notificacoes SET lida = 1, data_leitura = NOW() 
                    WHERE id = :id AND usuario_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $notificacao_id, ':user_id' => $user_id]);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Marcar todas como lidas
    if ($acao === 'marcar_todas_lidas') {
        $sql = "UPDATE notificacoes SET lida = 1, data_leitura = NOW() 
                WHERE usuario_id = :user_id AND lida = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['error' => 'Ação inválida']);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>