<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/conexao.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_tipo = $_SESSION['user_tipo'] ?? null;
$user_nome = $_SESSION['user_nome'] ?? 'Usuário';

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    if ($acao === 'create') {
        $aula_id = $_POST['aula_id'] ?? null;
        $conteudo = trim($_POST['conteudo'] ?? '');
        if (!$aula_id || $conteudo === '') throw new Exception('Dados inválidos');

        // Buscar informações da aula e do professor
        $sql_aula_info = "SELECT a.professor_id, a.titulo_aula, t.nome_turma 
                          FROM aulas a 
                          JOIN turmas t ON a.turma_id = t.id 
                          WHERE a.id = :aula_id";
        $stmt_aula = $pdo->prepare($sql_aula_info);
        $stmt_aula->execute([':aula_id' => $aula_id]);
        $aula_info = $stmt_aula->fetch(PDO::FETCH_ASSOC);
        
        if (!$aula_info) throw new Exception('Aula não encontrada');
        
        $professor_id = $aula_info['professor_id'];
        $titulo_aula = $aula_info['titulo_aula'];
        $nome_turma = $aula_info['nome_turma'];

        // garantir thread
        $sql_thread = "SELECT id, aluno_id FROM anotacoes_aula WHERE aula_id = :aula_id AND aluno_id = :aluno_id";
        $stmt = $pdo->prepare($sql_thread);
        
        if ($user_tipo === 'aluno') {
            $stmt->execute([':aula_id' => $aula_id, ':aluno_id' => $user_id]);
            $thread = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // professor must provide aluno_id
            $aluno_id = $_POST['aluno_id'] ?? null;
            if (!$aluno_id) throw new Exception('aluno_id necessário para professor');
            $stmt->execute([':aula_id' => $aula_id, ':aluno_id' => $aluno_id]);
            $thread = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$thread) {
            // criar thread
            $sql_ct = "INSERT INTO anotacoes_aula (aula_id, aluno_id, conteudo) VALUES (:aula_id, :aluno_id, '')";
            $stmt_ct = $pdo->prepare($sql_ct);
            $stmt_ct->execute([':aula_id' => $aula_id, ':aluno_id' => ($user_tipo === 'aluno' ? $user_id : $aluno_id)]);
            $thread_id = $pdo->lastInsertId();
        } else {
            $thread_id = $thread['id'];
        }

        $autor = ($user_tipo === 'aluno') ? 'aluno' : 'professor';
        $sql_insert = "INSERT INTO anotacoes_itens (anotacao_id, autor, conteudo) VALUES (:anotacao_id, :autor, :conteudo)";
        $stmt_i = $pdo->prepare($sql_insert);
        $stmt_i->execute([':anotacao_id' => $thread_id, ':autor' => $autor, ':conteudo' => $conteudo]);
        $item_id = $pdo->lastInsertId();

        // atualizar thread
        $sql_update_thread = "UPDATE anotacoes_aula SET visto = 0, data_visto = NULL, data_atualizacao = NOW() WHERE id = :id";
        $stmt_up = $pdo->prepare($sql_update_thread);
        $stmt_up->execute([':id' => $thread_id]);

       // ===== CRIAR NOTIFICAÇÃO PARA O PROFESSOR =====
if ($user_tipo === 'aluno') {
    $excerpt = substr(strip_tags($conteudo), 0, 100) . (strlen($conteudo) > 100 ? '...' : '');
    
    $sql_notificacao = "INSERT INTO notificacoes 
                        (usuario_id, tipo, titulo, mensagem, link, icone, cor, aula_id) 
                        VALUES 
                        (:professor_id, 'anotacao_aluno', :titulo, :mensagem, :link, 'fas fa-pen', '#28a745', :aula_id)";
    
    // Link direto para a página da aula com a anotação em destaque
    // CORREÇÃO: Removido /Risenglish/ do início, pois o caminho já é relativo
    $link_notificacao = "detalhes_aula.php?id=" . $aula_id . "#anotacoes";
    
    $titulo_notif = "Nova anotação de " . htmlspecialchars($user_nome);
    $mensagem_notif = "O aluno " . htmlspecialchars($user_nome) . " adicionou uma anotação na aula \"" . 
                      htmlspecialchars($titulo_aula) . "\" (Turma: " . htmlspecialchars($nome_turma) . ").\n\n" .
                      "Trecho: \"" . $excerpt . "\"";
    
    $stmt_notif = $pdo->prepare($sql_notificacao);
    $stmt_notif->execute([
        ':professor_id' => $professor_id,
        ':titulo' => $titulo_notif,
        ':mensagem' => $mensagem_notif,
        ':link' => $link_notificacao
    ,
        ':aula_id' => $aula_id
    ]);
}

// Se for professor comentando, notificar o aluno
if ($user_tipo === 'professor' && isset($aluno_id)) {
    $excerpt = substr(strip_tags($conteudo), 0, 100) . (strlen($conteudo) > 100 ? '...' : '');
    
    $sql_notificacao = "INSERT INTO notificacoes 
                        (usuario_id, tipo, titulo, mensagem, link, icone, cor) 
                        VALUES 
                        (:aluno_id, 'comentario_professor', :titulo, :mensagem, :link, 'fas fa-chalkboard-teacher', '#007bff')";
    
    // Link direto para a página da aula com a anotação em destaque
    // CORREÇÃO: Removido /Risenglish/ do início, pois o caminho já é relativo
    $link_notificacao = "detalhes_aula.php?id=" . $aula_id . "#anotacoes";
    
    $titulo_notif = "Professor respondeu sua anotação";
    $mensagem_notif = "O professor " . htmlspecialchars($user_nome) . " respondeu à sua anotação na aula \"" . 
                      htmlspecialchars($titulo_aula) . "\".\n\n" .
                      "Resposta: \"" . $excerpt . "\"";
    
    $stmt_notif = $pdo->prepare($sql_notificacao);
    $stmt_notif->execute([
        ':aluno_id' => $aluno_id,
        ':titulo' => $titulo_notif,
        ':mensagem' => $mensagem_notif,
        ':link' => $link_notificacao
    ,
        ':aula_id' => $aula_id
    ]);
}

        // retornar dados do item
        $sql_sel = "SELECT id, autor, conteudo, data_criacao FROM anotacoes_itens WHERE id = :id";
        $stmt_sel = $pdo->prepare($sql_sel);
        $stmt_sel->execute([':id' => $item_id]);
        $item = $stmt_sel->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'item' => $item, 'thread_id' => $thread_id]);
        exit;
    }

    if ($acao === 'edit') {
        $item_id = $_POST['item_id'] ?? null;
        $conteudo = trim($_POST['conteudo'] ?? '');
        if (!$item_id || $conteudo === '') throw new Exception('Dados inválidos');

        $sql_sel = "SELECT ai.id, ai.autor, aa.aluno_id, aa.aula_id 
                    FROM anotacoes_itens ai 
                    JOIN anotacoes_aula aa ON ai.anotacao_id = aa.id 
                    WHERE ai.id = :id";
        $stmt = $pdo->prepare($sql_sel);
        $stmt->execute([':id' => $item_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception('Item não encontrado');

        // permissões: aluno só edita seus itens; professor só edita seus itens
        if ($user_tipo === 'aluno' && $row['autor'] !== 'aluno') throw new Exception('Permissão negada');
        if ($user_tipo === 'professor' && $row['autor'] !== 'professor') throw new Exception('Permissão negada');

        $sql_up = "UPDATE anotacoes_itens SET conteudo = :conteudo WHERE id = :id";
        $stmt_up = $pdo->prepare($sql_up);
        $stmt_up->execute([':conteudo' => $conteudo, ':id' => $item_id]);

        $sql_get = "SELECT id, autor, conteudo, data_criacao FROM anotacoes_itens WHERE id = :id";
        $stmt_get = $pdo->prepare($sql_get);
        $stmt_get->execute([':id' => $item_id]);
        $item = $stmt_get->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'item' => $item]);
        exit;
    }

    if ($acao === 'delete') {
        $item_id = $_POST['item_id'] ?? null;
        if (!$item_id) throw new Exception('Dados inválidos');

        $sql_sel = "SELECT ai.id, ai.autor FROM anotacoes_itens ai WHERE ai.id = :id";
        $stmt = $pdo->prepare($sql_sel);
        $stmt->execute([':id' => $item_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception('Item não encontrado');

        if ($user_tipo === 'aluno' && $row['autor'] !== 'aluno') throw new Exception('Permissão negada');
        if ($user_tipo === 'professor' && $row['autor'] !== 'professor') throw new Exception('Permissão negada');

        $sql_del = "DELETE FROM anotacoes_itens WHERE id = :id";
        $stmt_del = $pdo->prepare($sql_del);
        $stmt_del->execute([':id' => $item_id]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Ação inválida']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>