<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$professor_nome = $_SESSION['user_nome'] ?? 'Professor';

$aula_id = $_GET['aula_id'] ?? null;

// Verificação do ID da aula
if (!$aula_id || !is_numeric($aula_id)) {
    header("Location: gerenciar_aulas.php");
    exit;
}

// Buscar detalhes da aula
$sql_detalhes = "SELECT
    a.id AS aula_id, a.titulo_aula, a.data_aula, a.horario, a.descricao AS desc_aula,
    t.id AS turma_id, t.nome_turma, t.link_aula,
    p.nome AS nome_professor
    FROM aulas a
    JOIN turmas t ON a.turma_id = t.id
    JOIN usuarios p ON a.professor_id = p.id
    WHERE a.id = :aula_id AND a.professor_id = :professor_id";

$stmt_detalhes = $pdo->prepare($sql_detalhes);
$stmt_detalhes->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);

$detalhes_aula = $stmt_detalhes->fetch(PDO::FETCH_ASSOC);

if(!$detalhes_aula) {
    header("Location: gerenciar_aulas.php");
    exit;
}

// ========== SISTEMA DE ANOTAÇÕES COMPARTILHADAS ==========
// Verificar se a tabela de anotações existe
$sql_check_table = "SHOW TABLES LIKE 'anotacoes_aula'";
$table_exists = $pdo->query($sql_check_table)->rowCount() > 0;

if (!$table_exists) {
    // Criar tabela de anotações se não existir
    $sql_create_table = "CREATE TABLE anotacoes_aula (
        id INT(11) NOT NULL AUTO_INCREMENT,
        aula_id INT(11) NOT NULL,
        aluno_id INT(11) NOT NULL,
        conteudo TEXT NOT NULL,
        comentario_professor TEXT NULL,
        data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY aula_aluno_unique (aula_id, aluno_id),
        KEY aluno_id (aluno_id),
        CONSTRAINT anotacoes_aula_ibfk_1 FOREIGN KEY (aula_id) REFERENCES aulas (id) ON DELETE CASCADE,
        CONSTRAINT anotacoes_aula_ibfk_2 FOREIGN KEY (aluno_id) REFERENCES usuarios (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    try {
        $pdo->exec($sql_create_table);
    } catch (PDOException $e) {
        // Ignora erros se a tabela já existir
    }
} else {
    // Verificar se a coluna comentario_professor existe
    $sql_check_column = "SHOW COLUMNS FROM anotacoes_aula LIKE 'comentario_professor'";
    $column_exists = $pdo->query($sql_check_column)->rowCount() > 0;
    
    if (!$column_exists) {
        // Adicionar coluna comentario_professor se não existir
        try {
            $sql_add_column = "ALTER TABLE anotacoes_aula ADD COLUMN comentario_professor TEXT NULL AFTER conteudo";
            $pdo->exec($sql_add_column);
        } catch (PDOException $e) {
            // Ignora erros se a coluna já existir
        }
    }
}

// Buscar todos os alunos da turma
$sql_alunos_turma = "SELECT 
    u.id AS aluno_id, 
    u.nome AS aluno_nome,
    u.email AS aluno_email
FROM usuarios u
INNER JOIN alunos_turmas at ON u.id = at.aluno_id
WHERE at.turma_id = :turma_id
AND u.tipo_usuario = 'aluno'
ORDER BY u.nome ASC";

$stmt_alunos_turma = $pdo->prepare($sql_alunos_turma);
$stmt_alunos_turma->execute([':turma_id' => $detalhes_aula['turma_id']]);
$alunos_turma = $stmt_alunos_turma->fetchAll(PDO::FETCH_ASSOC);

// Processar salvamento do comentário do professor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_comentario'])) {
    $aluno_id = $_POST['aluno_id'] ?? null;
    $comentario = $_POST['comentario_professor'] ?? '';
    
    if ($aluno_id) {
        // Verificar se já existe uma anotação para este aluno nesta aula
        $sql_check = "SELECT id FROM anotacoes_aula WHERE aula_id = :aula_id AND aluno_id = :aluno_id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':aula_id' => $aula_id, ':aluno_id' => $aluno_id]);
        $anotacao_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($anotacao_existente) {
            // Atualizar comentário do professor
            $sql_update = "UPDATE anotacoes_aula SET comentario_professor = :comentario WHERE id = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                ':comentario' => $comentario,
                ':id' => $anotacao_existente['id']
            ]);
        } else {
            // Criar novo registro com apenas o comentário do professor
            $sql_insert = "INSERT INTO anotacoes_aula (aula_id, aluno_id, conteudo, comentario_professor) VALUES (:aula_id, :aluno_id, '', :comentario)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                ':aula_id' => $aula_id,
                ':aluno_id' => $aluno_id,
                ':comentario' => $comentario
            ]);
        }
        
        // Redirecionar para evitar reenvio do formulário
        header("Location: detalhes_aula.php?aula_id=" . $aula_id . "&saved=1");
        exit;
    }
}

// Buscar anotações dos alunos (incluindo registros vazios criados pelo professor)
$anotacoes_alunos = [];
foreach ($alunos_turma as $aluno) {
    $sql_anotacao = "SELECT 
        aa.id,
        aa.conteudo,
        aa.comentario_professor,
        aa.data_atualizacao
    FROM anotacoes_aula aa
    WHERE aa.aula_id = :aula_id AND aa.aluno_id = :aluno_id";
    
    $stmt_anotacao = $pdo->prepare($sql_anotacao);
    $stmt_anotacao->execute([':aula_id' => $aula_id, ':aluno_id' => $aluno['aluno_id']]);
    $anotacao = $stmt_anotacao->fetch(PDO::FETCH_ASSOC);
    
    // Se não existe registro, criar um array vazio
    if (!$anotacao) {
        $anotacao = [
            'id' => null,
            'conteudo' => '',
            'comentario_professor' => '',
            'data_atualizacao' => null
        ];
    }
    
    // Combinar dados do aluno com a anotação
    $anotacoes_alunos[] = array_merge($aluno, $anotacao);
}
// ========== FIM SISTEMA DE ANOTAÇÕES COMPARTILHADAS ==========

// Buscar todos os temas e suas subpastas
$sql_temas = "
    SELECT 
        c.id AS tema_id, 
        c.titulo, 
        c.descricao, 
        u.nome AS autor_tema,
        COALESCE(ac.planejado, 0) AS planejado 
    FROM 
        conteudos c
    JOIN
        usuarios u ON c.professor_id = u.id 
    LEFT JOIN 
        aulas_conteudos ac ON c.id = ac.conteudo_id AND ac.aula_id = :aula_id
    WHERE 
        c.parent_id IS NULL
        AND c.professor_id = :professor_id
    ORDER BY 
        c.titulo ASC
";

$stmt_temas = $pdo->prepare($sql_temas);
$stmt_temas->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
$temas = $stmt_temas->fetchAll(PDO::FETCH_ASSOC);

// Buscar subpastas para cada tema
$subpastas_por_tema = [];
foreach ($temas as $tema) {
    $sql_subpastas = "
        SELECT 
            c.id AS subpasta_id,
            c.titulo AS subpasta_titulo,
            c.descricao AS subpasta_descricao,
            COALESCE(ac.planejado, 0) AS planejado,
            (SELECT COUNT(*) FROM conteudos WHERE parent_id = c.id AND eh_subpasta = 0) AS total_arquivos
        FROM 
            conteudos c
        LEFT JOIN 
            aulas_conteudos ac ON c.id = ac.conteudo_id AND ac.aula_id = :aula_id
        WHERE 
            c.parent_id = :tema_id 
            AND c.eh_subpasta = 1
        ORDER BY 
            c.titulo ASC
    ";
    
    $stmt_subpastas = $pdo->prepare($sql_subpastas);
    $stmt_subpastas->execute([
        ':aula_id' => $aula_id,
        ':tema_id' => $tema['tema_id']
    ]);
    $subpastas_por_tema[$tema['tema_id']] = $stmt_subpastas->fetchAll(PDO::FETCH_ASSOC);
}

// Contar total de itens planejados
$count_planejados = 0;
foreach ($temas as $tema) {
    if ($tema['planejado'] == 1) $count_planejados++;
    if (isset($subpastas_por_tema[$tema['tema_id']])) {
        foreach ($subpastas_por_tema[$tema['tema_id']] as $subpasta) {
            if ($subpasta['planejado'] == 1) $count_planejados++;
        }
    }
}

// Função para converter URLs em links clicáveis
function makeLinksClickable($text) {
    if (empty($text)) return '';
    $pattern = '/(https?:\/\/[^\s]+)/';
    $replacement = '<a href="$1" target="_blank" class="link-descricao">$1</a>';
    return preg_replace($pattern, $replacement, htmlspecialchars($text));
}

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '';
    $date = new DateTime($data);
    return $date->format('d/m/Y H:i');
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Aula - <?= htmlspecialchars($detalhes_aula['titulo_aula']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        body {
            background-color: #FAF9F6;
            overflow-x: hidden;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 16.666667%;
            background-color: #081d40;
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
            transition: 0.3s;
        }
        .sidebar .active {
            background-color: #c0392b;
        }
        .sidebar .active:hover{
            background-color: #c0392b;
        }
        .main-content {
            margin-left: 16.666667%;
            width: 83.333333%;
            min-height: 100vh;
            overflow-y: auto;
        }
        .card-header {
            background-color: #081d40;
            color: white;
        }
        .btn-danger {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        .btn-danger:hover {
            background-color: #a93226;
            border-color: #a93226;
        }
        .btn-warning {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        .btn-warning:hover {
            background-color: #218838;
            border-color: #218838;
            color: white;
        }
        .conteudo-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .conteudo-item:last-child {
            border-bottom: none;
        }
        .link-tema {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .link-tema:hover {
            text-decoration: underline;
        }
        .link-descricao {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
        }
        .link-descricao:hover {
            text-decoration: underline;
            color: #0a58ca;
        }
        .planejado {
            background-color: #f0fff0;
        }
        .nao-planejado {
            background-color: #fff;
            opacity: 0.8;
        }
        .status-label {
            font-weight: bold;
        }
        .subpasta-item {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            margin-left: 20px;
        }
        .subpasta-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .subpasta-toggle.rotated {
            transform: rotate(90deg);
        }
        .subpastas-container {
            display: none;
        }
        .badge-subpasta {
            background-color: #6c757d;
        }
        .tema-header {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .tema-header:hover {
            background-color: #f8f9fa;
        }
        .arquivo-count {
            font-size: 0.85em;
        }
        .presenca-item {
            transition: background-color 0.3s ease;
        }

        .presenca-item.presente {
            background-color: #f0fff0;
        }

        .presenca-item.ausente {
            background-color: #fff0f0;
        }

        .presenca-item:hover {
            background-color: #f8f9fa;
        }
        #botao-sair {
            border: none;
        }
        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }
        .switch-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .loading {
            color: #6c757d;
        }
        .link-aula {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
        }
        .link-aula:hover {
            text-decoration: underline;
            color: #0a58ca;
        }
        .modal-header {
            background-color: #081d40;
            color: white;
        }
        .btn-group-actions {
            margin-bottom: 15px;
        }
        
        /* Estilos para o container de anotações - AZUL MARINHO */
        .anotacoes-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .anotacoes-header {
            background: linear-gradient(135deg, #081d40 0%, #0a2351 100%);
            border-radius: 8px 8px 0 0;
            color: white;
            padding: 15px;
        }
        
        .anotacoes-body {
            padding: 20px;
            overflow-y: visible;
            max-height: none;
        }
        
        .anotacao-aluno {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .aluno-info {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .anotacao-conteudo {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .comentario-professor {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .anotacoes-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 0 0 8px 8px;
        }
        
        .btn-salvar-comentario {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            border: none;
            color: white;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-salvar-comentario:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
        }
        
        .data-atualizacao {
            font-size: 12px;
            color: #666;
            text-align: right;
        }
        
        .sem-anotacoes {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .comentario-textarea {
            background: white;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.5;
            min-height: 120px;
            padding: 12px;
            resize: vertical;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .comentario-textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }
        
        .badge-aluno {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
        }
        
        .badge-professor {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .aluno-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .avatar-container {
            display: flex;
            align-items: center;
        }
        
        .contador-caracteres {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .anotacao-aluno {
                padding: 15px;
            }
            
            .avatar-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .aluno-avatar {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center">
                    <h5 class="mt-4">Prof. <?= htmlspecialchars($professor_nome) ?></h5>
                </div>
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 style="color: #081d40;">Detalhes da Aula</h1>
                    <div>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editarAulaModal">
                            <i class="fas fa-edit me-2"></i> Editar Aula
                        </button>
                        <button class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#excluirAulaModal">
                            <i class="fas fa-trash me-2"></i> Excluir Aula
                        </button>
                        <a href="detalhes_turma.php?turma_id=<?= $detalhes_aula['turma_id'] ?>" class="btn btn-secondary ms-2">
                            <i class="fas fa-arrow-left me-2"></i> Voltar para Turma
                        </a>
                    </div>
                </div>
                
                <div id="ajax-message-container"></div>
                
                <!-- Mensagem de sucesso -->
                <?php if (isset($_GET['saved'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        Comentário salvo com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>ID da Aula:</strong> <?= $detalhes_aula['aula_id'] ?></p>
                                <p class="mb-1"><strong>Professor:</strong> <?= htmlspecialchars($detalhes_aula['nome_professor']) ?></p>
                                <p class="mb-1"><strong>Tópico:</strong> <?= htmlspecialchars($detalhes_aula['titulo_aula']) ?></p>
                                <p class="mb-1"><strong>Descrição:</strong> 
                                    <?php 
                                        $descricao = $detalhes_aula['desc_aula'] ?? 'N/A';
                                        if ($descricao !== 'N/A') {
                                            echo makeLinksClickable($descricao);
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Turma:</strong> <?= htmlspecialchars($detalhes_aula['nome_turma']) ?></p>
                                <p class="mb-1"><strong>Data:</strong> <?= (new DateTime($detalhes_aula['data_aula']))->format('d/m/Y') ?></p>
                                <p class="mb-1"><strong>Horário:</strong> <?= substr($detalhes_aula['horario'], 0, 5) ?></p>
                                <p class="mb-1">
                                    <strong>Link da Aula:</strong> 
                                    <?php if (!empty($detalhes_aula['link_aula'])): ?>
                                        <a href="<?= htmlspecialchars($detalhes_aula['link_aula']) ?>" target="_blank" class="link-aula">
                                            <i class="fas fa-external-link-alt me-1"></i>Acessar Aula
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Nenhum link configurado</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <span>Controle de Presença</span>
                    </div>
                    
                    <div class="card-body">
                        <?php
                        // Buscar alunos da turma com presença
                        $sql_alunos = "SELECT 
                            u.id AS aluno_id, 
                            u.nome AS aluno_nome,
                            COALESCE(p.presente, 1) AS presente
                        FROM usuarios u
                        INNER JOIN alunos_turmas at ON u.id = at.aluno_id
                        LEFT JOIN presenca_aula p ON u.id = p.aluno_id AND p.aula_id = :aula_id
                        WHERE at.turma_id = :turma_id
                        AND u.tipo_usuario = 'aluno'
                        ORDER BY u.nome ASC";
                        
                        $stmt_alunos = $pdo->prepare($sql_alunos);
                        $stmt_alunos->execute([
                            ':aula_id' => $aula_id,
                            ':turma_id' => $detalhes_aula['turma_id']
                        ]);
                        $alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if (empty($alunos)): ?>
                            <p class="text-center text-muted">Não há alunos matriculados nesta turma.</p>
                        <?php else: ?>
                            <div class="row mb-3 align-items-center border-bottom pb-2 text-muted small">
                                <div class="col-1 text-center"><strong>Presente?</strong></div>
                                <div class="col-8"><strong>Aluno</strong></div>
                                <div class="col-3 text-center"><strong>Status</strong></div>
                            </div>
                            
                            <div id="lista-presenca-container">
                                <?php foreach ($alunos as $aluno): ?>
                                    <?php 
                                        $is_presente = $aluno['presente'] == 1;
                                        $presenca_class = $is_presente ? 'presente' : 'ausente';
                                        $status_text = $is_presente ? 'Presente' : 'Faltou';
                                        $status_class = $is_presente ? 'bg-success' : 'bg-danger';
                                    ?>
                                    
                                    <div class="presenca-item row mx-0 align-items-center mb-2 py-2 border-bottom <?= $presenca_class ?>" 
                                        data-aluno-id="<?= $aluno['aluno_id'] ?>" 
                                        data-presente="<?= $aluno['presente'] ?>">
                                        
                                        <div class="col-1 text-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input presenca-switch" 
                                                    type="checkbox" 
                                                    role="switch" 
                                                    id="presenca_<?= $aluno['aluno_id'] ?>" 
                                                    data-aula-id="<?= $detalhes_aula['aula_id'] ?>"
                                                    data-aluno-id="<?= $aluno['aluno_id'] ?>"
                                                    <?= $is_presente ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="presenca_<?= $aluno['aluno_id'] ?>">
                                                    <?= $is_presente ? 'Sim' : 'Não' ?>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-8">
                                            <strong><?= htmlspecialchars($aluno['aluno_nome']) ?></strong>
                                        </div>
                                        
                                        <div class="col-3 text-center">
                                            <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php
                            $total_alunos = count($alunos);
                            $presentes = array_filter($alunos, function($aluno) {
                                return $aluno['presente'] == 1;
                            });
                            $total_presentes = count($presentes);
                            $total_faltas = $total_alunos - $total_presentes;
                            ?>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <h4 class="mb-0"><?= $total_alunos ?></h4>
                                        <small class="text-muted">Total de Alunos</small>
                                    </div>
                                    <div class="col-md-4">
                                        <h4 class="mb-0 text-success"><?= $total_presentes ?></h4>
                                        <small class="text-muted">Presentes</small>
                                    </div>
                                    <div class="col-md-4">
                                        <h4 class="mb-0 text-danger"><?= $total_faltas ?></h4>
                                        <small class="text-muted">Faltas</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- ========== CONTAINER DE ANOTAÇÕES DOS ALUNOS ========== -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Anotações dos Alunos</h5>
                        <small class="text-white">Veja as anotações dos alunos e adicione seus comentários e correções. Você pode comentar mesmo que o aluno não tenha feito anotações.</small>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($anotacoes_alunos)): ?>
                            <div class="sem-anotacoes">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum aluno nesta turma</h6>
                                <p class="text-muted small">Não há alunos matriculados para esta aula.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($anotacoes_alunos as $anotacao): ?>
                                <div class="anotacao-aluno">
                                    <div class="aluno-info d-flex justify-content-between align-items-center">
                                        <div class="avatar-container">
                                            <div class="aluno-avatar">
                                                <?= strtoupper(substr($anotacao['aluno_nome'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($anotacao['aluno_nome']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($anotacao['aluno_email']) ?></small>
                                            </div>
                                        </div>
                                        <span class="badge badge-aluno">Aluno</span>
                                    </div>
                                    
                                    <?php if (!empty($anotacao['conteudo'])): ?>
                                        <div class="anotacao-conteudo mt-3">
                                            <strong class="d-block mb-2"><i class="fas fa-sticky-note me-2 text-success"></i>Anotações do Aluno:</strong>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($anotacao['conteudo'])) ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            Este aluno ainda não fez anotações para esta aula.
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="" class="mt-3">
                                        <input type="hidden" name="aluno_id" value="<?= $anotacao['aluno_id'] ?>">
                                        
                                        <div class="comentario-professor mb-2">
                                            <strong class="d-block mb-2"><i class="fas fa-comment-dots me-2 text-primary"></i>Seu Comentário:</strong>
                                            <?php if (!empty($anotacao['comentario_professor'])): ?>
                                                <p class="mb-2"><?= nl2br(htmlspecialchars($anotacao['comentario_professor'])) ?></p>
                                            <?php else: ?>
                                                <p class="text-muted mb-2"><i>Nenhum comentário ainda. Adicione um comentário para o aluno.</i></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="comentario_<?= $anotacao['aluno_id'] ?>" class="form-label"><small><i class="fas fa-edit me-1"></i> Editar comentário:</small></label>
                                            <textarea 
                                                name="comentario_professor" 
                                                id="comentario_<?= $anotacao['aluno_id'] ?>" 
                                                class="comentario-textarea" 
                                                placeholder="Digite seu comentário, correção ou feedback para o aluno aqui..."
                                            ><?= htmlspecialchars($anotacao['comentario_professor'] ?? '') ?></textarea>
                                            <div class="contador-caracteres">
                                                Caracteres: <span id="contador_<?= $anotacao['aluno_id'] ?>"><?= strlen($anotacao['comentario_professor'] ?? '') ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <?php if (!empty($anotacao['data_atualizacao'])): ?>
                                                <div class="data-atualizacao">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Última atualização: <?= formatarData($anotacao['data_atualizacao']) ?>
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <div></div>
                                            <?php endif; ?>
                                            <button type="submit" name="salvar_comentario" class="btn btn-salvar-comentario">
                                                <i class="fas fa-save me-1"></i>Salvar Comentário
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <i class="fas fa-info-circle text-primary me-1"></i>
                                <small class="text-muted">
                                    Total de alunos: <strong><?= count($anotacoes_alunos) ?></strong>
                                    <?php 
                                        $alunos_com_anotacoes = array_filter($anotacoes_alunos, function($a) {
                                            return !empty($a['conteudo']);
                                        });
                                    ?>
                                    | Com anotações: <strong><?= count($alunos_com_anotacoes) ?></strong>
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    <i class="fas fa-user-graduate me-1"></i>
                                    Os alunos podem ver seus comentários em suas telas
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ========== FIM CONTAINER DE ANOTAÇÕES DOS ALUNOS ========== -->

                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Conteúdo da Aula - Controle de Visibilidade</span>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="filtroPlanejadoSwitch">
                            <label class="form-check-label text-white" for="filtroPlanejadoSwitch" id="filtroPlanejadoLabel">
                                Mostrar Apenas Itens Visíveis (<?= $count_planejados ?>)
                            </label>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($temas)): ?>
                            <p class="text-center text-muted">Não há temas cadastrados.</p>
                        <?php else: ?>
                            
                            <div class="row mb-3 align-items-center border-bottom pb-2 text-muted small">
                                <div class="col-1 text-center"><strong>Visível?</strong></div>
                                <div class="col-5"><strong>Item</strong></div>
                                <div class="col-4"><strong>Detalhes</strong></div>
                                <div class="col-2 text-center"><strong>Conteúdo</strong></div>
                            </div>
                            
                            <div id="lista-conteudos-container">
                                <?php foreach ($temas as $tema): ?>
                                    <?php 
                                        $is_planejado = $tema['planejado'] == 1;
                                        $planejado_class = $is_planejado ? 'planejado' : 'nao-planejado';
                                        $tem_subpastas = !empty($subpastas_por_tema[$tema['tema_id']]);
                                        $total_subpastas = count($subpastas_por_tema[$tema['tema_id']] ?? []);
                                    ?>
                                    
                                    <div class="conteudo-item tema-header row mx-0 align-items-center <?= $planejado_class ?>" 
                                        data-conteudo-id="<?= $tema['tema_id'] ?>" 
                                        data-planejado="<?= $tema['planejado'] ?>" 
                                        data-tipo="tema">
                                        
                                        <div class="col-1 text-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input planejado-switch" 
                                                    type="checkbox" 
                                                    role="switch" 
                                                    id="switch_<?= $tema['tema_id'] ?>" 
                                                    data-aula-id="<?= $detalhes_aula['aula_id'] ?>" 
                                                    data-conteudo-id="<?= $tema['tema_id'] ?>" 
                                                    data-tipo="tema" 
                                                    <?= $is_planejado ? 'checked' : '' ?>>
                                                <label class="form-check-label small status-label" for="switch_<?= $tema['tema_id'] ?>">
                                                    <?= $is_planejado ? 'Sim' : 'Não' ?>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-5">
                                            <div class="d-flex align-items-center">
                                                <?php if ($tem_subpastas): ?>
                                                    <i class="fas fa-chevron-right subpasta-toggle me-2" data-tema-id="<?= $tema['tema_id'] ?>" style="cursor: pointer;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-folder me-2 text-primary"></i>
                                                <?php endif; ?>
                                                <a href="gerenciar_arquivos_tema.php?tema_id=<?= $tema['tema_id'] ?>" class="link-tema">
                                                    <strong><?= htmlspecialchars($tema['titulo']) ?></strong>
                                                </a>
                                            </div>
                                            <?php if (!empty($tema['descricao'])): ?>
                                                <small class="text-muted d-block ms-4">
                                                    <?= htmlspecialchars($tema['descricao']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-4">
                                            <span class="badge bg-primary ms-2" title="Criado por">
                                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($tema['autor_tema']) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-2 text-center">
                                            <span class="badge bg-secondary arquivo-count">
                                                <?= $total_subpastas ?> subpasta(s)
                                            </span>
                                        </div>
                                    </div>

                                    <?php if ($tem_subpastas): ?>
                                        <div class="subpastas-container" id="subpastas-<?= $tema['tema_id'] ?>">
                                            <?php foreach ($subpastas_por_tema[$tema['tema_id']] as $subpasta): ?>
                                                <?php 
                                                    $is_subpasta_planejada = $subpasta['planejado'] == 1;
                                                    $subpasta_planejado_class = $is_subpasta_planejada ? 'planejado' : 'nao-planejado';
                                                ?>
                                                <div class="conteudo-item subpasta-item row mx-0 align-items-center <?= $subpasta_planejado_class ?>" 
                                                    data-conteudo-id="<?= $subpasta['subpasta_id'] ?>" 
                                                    data-planejado="<?= $subpasta['planejado'] ?>" 
                                                    data-tipo="subpasta">
                                                    
                                                    <div class="col-1 text-center">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input planejado-switch" 
                                                                type="checkbox" 
                                                                role="switch" 
                                                                id="switch_<?= $subpasta['subpasta_id'] ?>" 
                                                                data-aula-id="<?= $detalhes_aula['aula_id'] ?>" 
                                                                data-conteudo-id="<?= $subpasta['subpasta_id'] ?>" 
                                                                data-tipo="subpasta" 
                                                                <?= $is_subpasta_planejada ? 'checked' : '' ?>>
                                                            <label class="form-check-label small status-label" for="switch_<?= $subpasta['subpasta_id'] ?>">
                                                                <?= $is_subpasta_planejada ? 'Sim' : 'Não' ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-5">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-folder me-2 text-primary" style="margin-left: 20px;"></i>
                                                            <span><strong><?= htmlspecialchars($subpasta['subpasta_titulo']) ?></strong></span>
                                                        </div>
                                                        <?php if (!empty($subpasta['subpasta_descricao'])): ?>
                                                            <small class="text-muted d-block" style="margin-left: 40px;">
                                                                <?= htmlspecialchars($subpasta['subpasta_descricao']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="col-4">
                                                        <span class="badge bg-light text-dark">
                                                            <i class="fas fa-folder-open me-1"></i> Subpasta
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="col-2 text-center">
                                                        <span class="badge badge-subpasta arquivo-count">
                                                            <?= $subpasta['total_arquivos'] ?> arquivo(s)
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Aula -->
    <div class="modal fade" id="editarAulaModal" tabindex="-1" aria-labelledby="editarAulaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarAulaModalLabel">Editar Detalhes da Aula</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditarAula" method="POST" action="ajax_editar_aula.php">
                    <div class="modal-body">
                        <div id="editarAulaMessageContainer"></div>
                        <input type="hidden" name="aula_id" value="<?= $detalhes_aula['aula_id'] ?>">
                        
                        <div class="mb-3">
                            <label for="titulo_aula" class="form-label">Tópico da Aula *</label>
                            <input type="text" class="form-control" id="titulo_aula" name="titulo_aula" 
                                   value="<?= htmlspecialchars($detalhes_aula['titulo_aula']) ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="data_aula" class="form-label">Data *</label>
                                <input type="date" class="form-control" id="data_aula" name="data_aula" 
                                       value="<?= $detalhes_aula['data_aula'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="horario" class="form-label">Horário *</label>
                                <input type="time" class="form-control" id="horario" name="horario" 
                                       value="<?= substr($detalhes_aula['horario'], 0, 5) ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($detalhes_aula['desc_aula'] ?? '') ?></textarea>
                            <small class="text-muted">Você pode inserir links que serão clicáveis automaticamente.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Excluir Aula -->
    <div class="modal fade" id="excluirAulaModal" tabindex="-1" aria-labelledby="excluirAulaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="excluirAulaModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atenção!</strong> Esta ação não pode ser desfeita.
                    </div>
                    <p>Tem certeza que deseja excluir a aula <strong>"<?= htmlspecialchars($detalhes_aula['titulo_aula']) ?>"</strong>?</p>
                    <p class="text-muted small">
                        Serão excluídos: 
                        <br>• Os registros de presença desta aula
                        <br>• As associações com conteúdos planejados
                        <br>• As anotações dos alunos
                        <br>• A aula será removida permanentemente
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="formExcluirAula" method="POST" action="ajax_excluir_aula.php" style="display: inline;">
                        <input type="hidden" name="aula_id" value="<?= $detalhes_aula['aula_id'] ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i> Sim, Excluir Aula
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function displayAlert(message, type) {
        const container = document.getElementById('ajax-message-container');
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        container.innerHTML = alertHtml;
        
        setTimeout(() => {
            const alertElement = container.querySelector('.alert');
            if (alertElement) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                bsAlert.close();
            }
        }, 5000);
    }

    function displayModalAlert(containerId, message, type) {
        const container = document.getElementById(containerId);
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        container.innerHTML = alertHtml;
        
        setTimeout(() => {
            const alertElement = container.querySelector('.alert');
            if (alertElement) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                bsAlert.close();
            }
        }, 5000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const listaConteudosContainer = document.getElementById('lista-conteudos-container');
        const filtroSwitch = document.getElementById('filtroPlanejadoSwitch');
        const filtroLabel = document.getElementById('filtroPlanejadoLabel');

        // INICIALIZAÇÃO: Fechar todas as subpastas
        document.querySelectorAll('.subpastas-container').forEach(container => {
            container.style.display = 'none';
        });

        // TOGGLES DE SUBPASTAS - SIMPLES E FUNCIONAL
        document.querySelectorAll('.subpasta-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const temaId = this.getAttribute('data-tema-id');
                const subpastasContainer = document.getElementById(`subpastas-${temaId}`);

                if (subpastasContainer) {
                    const isHidden = subpastasContainer.style.display === 'none' || 
                                   subpastasContainer.style.display === '';
                    
                    if (isHidden) {
                        subpastasContainer.style.display = 'block';
                        this.classList.add('rotated');
                    } else {
                        subpastasContainer.style.display = 'none';
                        this.classList.remove('rotated');
                    }
                }
            });
        });

        // Atualizar contagem de itens visíveis
        function atualizarContagemPlanejados() {
            const count = listaConteudosContainer.querySelectorAll('.conteudo-item[data-planejado="1"]').length;
            filtroLabel.innerHTML = `Mostrar Apenas Itens Visíveis (${count})`;
            return count;
        }

        // Lógica do Filtro
        function aplicarFiltro() {
            const mostrarApenasPlanejados = filtroSwitch.checked;
            
            listaConteudosContainer.querySelectorAll('.conteudo-item').forEach(item => {
                const isPlanejado = item.dataset.planejado === '1';
                
                if (mostrarApenasPlanejados && !isPlanejado) {
                    item.style.display = 'none';
                } else {
                    item.style.display = 'flex';
                }
            });

            // Gerencia a exibição dos containers de subpastas baseado no filtro
            document.querySelectorAll('.subpastas-container').forEach(container => {
                const temaId = container.id.replace('subpastas-', '');
                const toggle = document.querySelector(`.subpasta-toggle[data-tema-id="${temaId}"]`);
                const temaPai = document.querySelector(`.conteudo-item[data-conteudo-id="${temaId}"]`);

                // Se o tema pai está escondido, esconde as subpastas também
                if (temaPai && temaPai.style.display === 'none') {
                    container.style.display = 'none';
                    if (toggle) toggle.classList.remove('rotated');
                    return;
                }

                // Se estamos filtrando, mostra apenas containers que têm itens visíveis
                if (mostrarApenasPlanejados) {
                    const hasVisibleItems = container.querySelector('.conteudo-item[data-planejado="1"]') !== null;
                    if (hasVisibleItems) {
                        container.style.display = 'block';
                        if (toggle) toggle.classList.add('rotated');
                    } else {
                        container.style.display = 'none';
                        if (toggle) toggle.classList.remove('rotated');
                    }
                }
            });
        }

        filtroSwitch.addEventListener('change', aplicarFiltro);

        // LÓGICA AJAX PARA OS SWITCHES DE CONTEÚDO
        document.querySelectorAll('.planejado-switch').forEach(function(switchElement) {
            switchElement.addEventListener('change', function() {
                const aulaId = this.dataset.aulaId;
                const conteudoId = this.dataset.conteudoId;
                const tipo = this.dataset.tipo;
                const novoStatus = this.checked ? 1 : 0;
                
                const statusLabel = this.closest('.form-switch').querySelector('.form-check-label');
                const conteudoItem = this.closest('.conteudo-item');
                
                const formData = new FormData();
                formData.append('aula_id', aulaId);
                formData.append('conteudo_id', conteudoId);
                formData.append('status', novoStatus);
                
                const estadoAnterior = this.checked ? 0 : 1;

                // Feedback visual
                this.disabled = true;
                this.classList.add('switch-disabled');
                statusLabel.textContent = '...';
                statusLabel.classList.add('loading');

                fetch('ajax_toggle_conteudo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na comunicação com o servidor. Status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    this.disabled = false;
                    this.classList.remove('switch-disabled');
                    statusLabel.classList.remove('loading');

                    if (data.success) {
                        displayAlert(data.message, 'success');
                        
                        // Atualiza o estado no DOM
                        conteudoItem.dataset.planejado = String(novoStatus);
                        
                        if (novoStatus === 1) {
                            statusLabel.textContent = 'Sim';
                            conteudoItem.classList.add('planejado');
                            conteudoItem.classList.remove('nao-planejado');
                        } else {
                            statusLabel.textContent = 'Não';
                            conteudoItem.classList.remove('planejado');
                            conteudoItem.classList.add('nao-planejado');
                        }

                        // Atualiza a contagem e aplica filtro se necessário
                        atualizarContagemPlanejados();
                        if (filtroSwitch.checked) {
                            aplicarFiltro();
                        }

                    } else {
                        console.error('Erro:', data.message);
                        displayAlert('Erro ao atualizar visibilidade: ' + data.message, 'danger');
                        // Reverte o estado do switch
                        this.checked = !this.checked;
                        statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
                    }
                })
                .catch(error => {
                    this.disabled = false;
                    this.classList.remove('switch-disabled');
                    statusLabel.classList.remove('loading');
                    console.error('Erro de conexão:', error);
                    displayAlert('Erro de comunicação. A visibilidade não foi atualizada.', 'danger');
                    // Reverte o estado do switch
                    this.checked = !this.checked;
                    statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
                });
            });
        });

        // Lógica do Controle de Presença (se necessário)
        document.querySelectorAll('.presenca-switch').forEach(function(switchElement) {
            switchElement.addEventListener('change', function() {
                const aulaId = this.dataset.aulaId;
                const alunoId = this.dataset.alunoId;
                const novoStatus = this.checked ? 1 : 0;
                
                const statusLabel = this.closest('.form-switch').querySelector('.form-check-label');
                const presencaItem = this.closest('.presenca-item');
                const statusBadge = presencaItem.querySelector('.badge');
                
                const formData = new FormData();
                formData.append('aula_id', aulaId);
                formData.append('aluno_id', alunoId);
                formData.append('presente', novoStatus);
                
                const estadoAnterior = this.checked ? 0 : 1;

                this.disabled = true;
                statusLabel.textContent = '...';

                fetch('ajax_controle_presenca.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na comunicação com o servidor. Status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    this.disabled = false;

                    if (data.success) {
                        displayAlert(data.message, 'success');
                        
                        presencaItem.dataset.presente = String(novoStatus);
                        
                        if (novoStatus === 1) {
                            statusLabel.textContent = 'Sim';
                            presencaItem.classList.add('presente');
                            presencaItem.classList.remove('ausente');
                            statusBadge.textContent = 'Presente';
                            statusBadge.classList.remove('bg-danger');
                            statusBadge.classList.add('bg-success');
                        } else {
                            statusLabel.textContent = 'Não';
                            presencaItem.classList.remove('presente');
                            presencaItem.classList.add('ausente');
                            statusBadge.textContent = 'Faltou';
                            statusBadge.classList.remove('bg-success');
                            statusBadge.classList.add('bg-danger');
                        }

                        // Atualizar contadores
                        location.reload();

                    } else {
                        console.error('Erro:', data.message);
                        displayAlert('Erro ao atualizar presença: ' + data.message, 'danger');
                        this.checked = !this.checked;
                        statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
                    }
                })
                .catch(error => {
                    this.disabled = false;
                    console.error('Erro de conexão:', error);
                    displayAlert('Erro de comunicação. A presença não foi atualizada.', 'danger');
                    this.checked = !this.checked;
                    statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
                });
            });
        });

        // Formulário de Edição de Aula
        document.getElementById('formEditarAula').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Server response:', text);
                        throw new Error('Erro na comunicação com o servidor. Status: ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    displayModalAlert('editarAulaMessageContainer', data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    displayModalAlert('editarAulaMessageContainer', 'Erro: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Erro de conexão:', error);
                displayModalAlert('editarAulaMessageContainer', 'Erro de comunicação. Tente novamente.', 'danger');
            });
        });

        // Formulário de Exclusão de Aula
        document.getElementById('formExcluirAula').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            const modal = bootstrap.Modal.getInstance(document.getElementById('excluirAulaModal'));
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Excluindo...';
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Server response:', text);
                        throw new Error('Erro na comunicação com o servidor. Status: ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    modal.hide();
                    displayAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'detalhes_turma.php?turma_id=<?= $detalhes_aula['turma_id'] ?>';
                    }, 1500);
                } else {
                    displayAlert('Erro: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Erro de conexão:', error);
                displayAlert('Erro de comunicação. Tente novamente.', 'danger');
            });
        });

        // ========== FUNCIONALIDADES DAS ANOTAÇÕES ==========
        // Contador de caracteres para os textareas de comentário
        document.querySelectorAll('.comentario-textarea').forEach(function(textarea) {
            const alunoId = textarea.id.replace('comentario_', '');
            const contador = document.getElementById('contador_' + alunoId);
            
            if (contador) {
                // Atualizar contador inicial
                contador.textContent = textarea.value.length;
                
                textarea.addEventListener('input', function() {
                    contador.textContent = this.value.length;
                });
            }
        });
        
        // Auto-salvar depois de 30 segundos de inatividade (opcional)
        document.querySelectorAll('.comentario-textarea').forEach(function(textarea) {
            let timeout;
            
            textarea.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    const form = textarea.closest('form');
                    if (form) {
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            // Mostrar feedback visual
                            const originalText = submitBtn.innerHTML;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Auto-salvando...';
                            submitBtn.disabled = true;
                            
                            submitBtn.click();
                            
                            // Restaurar após 2 segundos
                            setTimeout(() => {
                                submitBtn.innerHTML = originalText;
                                submitBtn.disabled = false;
                            }, 2000);
                        }
                    }
                }, 30000);
            });
        });
        // ========== FIM FUNCIONALIDADES DAS ANOTAÇÕES ==========

        // Inicializar
        atualizarContagemPlanejados();
    });
    </script>
</body>
</html>