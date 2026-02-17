<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$mensagem = '';
$sucesso = false;
$tipo_mensagem = '';

// --- LÓGICA DE CADASTRO/EDIÇÃO DE GRUPO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_grupo'])) {
    $nome = trim($_POST['nome_grupo']);
    $descricao = trim($_POST['descricao_grupo']);
    $cor = $_POST['cor_grupo'] ?? '#081d40';
    $icone = $_POST['icone_grupo'] ?? 'fas fa-layer-group';
    $acao = $_POST['acao_grupo'];
    $grupo_id = $_POST['grupo_id'] ?? null;
    
    if ($acao === 'cadastrar') {
        $sql = "INSERT INTO grupos_conteudos (professor_id, nome, descricao, cor, icone, ordem) 
                VALUES (:professor_id, :nome, :descricao, :cor, :icone, 0)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
            ':professor_id' => $professor_id, 
            ':nome' => $nome, 
            ':descricao' => $descricao,
            ':cor' => $cor,
            ':icone' => $icone
        ])) {
            $mensagem = "Grupo cadastrado com sucesso!";
            $sucesso = true;
            $tipo_mensagem = 'success';
        }
    } elseif ($acao === 'editar' && $grupo_id) {
        $sql = "UPDATE grupos_conteudos SET nome = :nome, descricao = :descricao, cor = :cor, icone = :icone 
                WHERE id = :id AND professor_id = :professor_id";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
            ':nome' => $nome, 
            ':descricao' => $descricao,
            ':cor' => $cor,
            ':icone' => $icone,
            ':id' => $grupo_id, 
            ':professor_id' => $professor_id
        ])) {
            $mensagem = "Grupo atualizado com sucesso!";
            $sucesso = true;
            $tipo_mensagem = 'success';
        }
    }
}

// --- LÓGICA DE EXCLUSÃO DE GRUPO ---
if (isset($_GET['excluir_grupo'])) {
    $id_excluir = $_GET['excluir_grupo'];
    
    // Primeiro, atualizar os conteúdos para remover o vínculo com o grupo
    $sql_update = "UPDATE conteudos SET grupo_id = NULL WHERE grupo_id = :grupo_id AND professor_id = :professor_id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([':grupo_id' => $id_excluir, ':professor_id' => $professor_id]);
    
    // Depois, excluir o grupo
    $sql = "DELETE FROM grupos_conteudos WHERE id = :id AND professor_id = :professor_id";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([':id' => $id_excluir, ':professor_id' => $professor_id])) {
        $mensagem = "Grupo excluído com sucesso!";
        $sucesso = true;
        $tipo_mensagem = 'success';
    }
}

// --- LÓGICA DE CADASTRO/EDIÇÃO DE TEMA (agora com grupo) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_tema'])) {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $grupo_id = !empty($_POST['grupo_id']) ? $_POST['grupo_id'] : null;
    $acao = $_POST['acao_tema'];
    $conteudo_id = $_POST['conteudo_id'] ?? null;
    
    if ($acao === 'cadastrar') {
        $sql = "INSERT INTO conteudos (professor_id, parent_id, grupo_id, titulo, descricao, tipo_arquivo, caminho_arquivo, data_upload) 
                VALUES (:professor_id, NULL, :grupo_id, :titulo, :descricao, 'TEMA', '', NOW())";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
            ':professor_id' => $professor_id, 
            ':grupo_id' => $grupo_id,
            ':titulo' => $titulo, 
            ':descricao' => $descricao
        ])) {
            $mensagem = "Tema cadastrado com sucesso!";
            $sucesso = true;
            $tipo_mensagem = 'success';
        }
    } elseif ($acao === 'editar' && $conteudo_id) {
        $sql = "UPDATE conteudos SET titulo = :titulo, descricao = :descricao, grupo_id = :grupo_id 
                WHERE id = :id AND professor_id = :professor_id";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
            ':titulo' => $titulo, 
            ':descricao' => $descricao,
            ':grupo_id' => $grupo_id,
            ':id' => $conteudo_id, 
            ':professor_id' => $professor_id
        ])) {
            $mensagem = "Tema atualizado com sucesso!";
            $sucesso = true;
            $tipo_mensagem = 'success';
        }
    }
}

// --- LÓGICA DE EXCLUSÃO DE TEMA ---
if (isset($_GET['excluir_tema'])) {
    $id_excluir = $_GET['excluir_tema'];
    $sql = "DELETE FROM conteudos WHERE id = :id AND professor_id = :professor_id";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([':id' => $id_excluir, ':professor_id' => $professor_id])) {
        $mensagem = "Tema excluído com sucesso!";
        $sucesso = true;
        $tipo_mensagem = 'success';
    }
}

// --- LÓGICA PARA MOVER TEMA PARA OUTRO GRUPO ---
if (isset($_POST['mover_tema'])) {
    $tema_id = $_POST['tema_id'];
    $grupo_destino = !empty($_POST['grupo_destino']) ? $_POST['grupo_destino'] : null;
    
    $sql = "UPDATE conteudos SET grupo_id = :grupo_id WHERE id = :id AND professor_id = :professor_id";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([
        ':grupo_id' => $grupo_destino,
        ':id' => $tema_id,
        ':professor_id' => $professor_id
    ])) {
        $mensagem = "Tema movido com sucesso!";
        $sucesso = true;
        $tipo_mensagem = 'success';
    }
}

// --- CONSULTA DOS GRUPOS ---
$sql_grupos = "SELECT * FROM grupos_conteudos WHERE professor_id = :professor_id ORDER BY ordem ASC, nome ASC";
$stmt_grupos = $pdo->prepare($sql_grupos);
$stmt_grupos->execute([':professor_id' => $professor_id]);
$grupos = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);

// --- CONSULTA DOS TEMAS NÃO AGRUPADOS (sem grupo) ---
$sql_temas_sem_grupo = "SELECT c.*, u.nome as nome_professor FROM conteudos c 
                         JOIN usuarios u ON c.professor_id = u.id 
                         WHERE c.parent_id IS NULL AND c.tipo_arquivo = 'TEMA' 
                         AND c.grupo_id IS NULL 
                         AND c.professor_id = :professor_id
                         ORDER BY c.titulo ASC";
$stmt_temas_sem_grupo = $pdo->prepare($sql_temas_sem_grupo);
$stmt_temas_sem_grupo->execute([':professor_id' => $professor_id]);
$temas_sem_grupo = $stmt_temas_sem_grupo->fetchAll(PDO::FETCH_ASSOC);

// --- CONSULTA DOS TEMAS POR GRUPO ---
$temas_por_grupo = [];
foreach ($grupos as $grupo) {
    $sql_temas = "SELECT c.*, u.nome as nome_professor FROM conteudos c 
                  JOIN usuarios u ON c.professor_id = u.id 
                  WHERE c.parent_id IS NULL AND c.tipo_arquivo = 'TEMA' 
                  AND c.grupo_id = :grupo_id
                  AND c.professor_id = :professor_id
                  ORDER BY c.titulo ASC";
    $stmt_temas = $pdo->prepare($sql_temas);
    $stmt_temas->execute([
        ':grupo_id' => $grupo['id'],
        ':professor_id' => $professor_id
    ]);
    $temas_por_grupo[$grupo['id']] = $stmt_temas->fetchAll(PDO::FETCH_ASSOC);
}

// --- CONSULTA DE TODOS OS TEMAS (para usar nos selects) ---
$sql_todos_temas = "SELECT * FROM conteudos WHERE professor_id = :professor_id AND parent_id IS NULL AND tipo_arquivo = 'TEMA' ORDER BY titulo ASC";
$stmt_todos_temas = $pdo->prepare($sql_todos_temas);
$stmt_todos_temas->execute([':professor_id' => $professor_id]);
$todos_temas = $stmt_todos_temas->fetchAll(PDO::FETCH_ASSOC);

// ===== BUSCAR NOTIFICAÇÕES NÃO LIDAS =====
$sql_notificacoes = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :professor_id AND lida = 0";
$stmt_notif = $pdo->prepare($sql_notificacoes);
$stmt_notif->execute([':professor_id' => $professor_id]);
$total_notificacoes_nao_lidas = $stmt_notif->fetch(PDO::FETCH_ASSOC)['total'];

// Array de ícones disponíveis
$icones_disponiveis = [
    'fas fa-layer-group' => 'Camadas',
    'fas fa-book-open' => 'Livro aberto',
    'fas fa-graduation-cap' => 'Graduação',
    'fas fa-language' => 'Idioma',
    'fas fa-globe' => 'Globo',
    'fas fa-users' => 'Usuários',
    'fas fa-chart-line' => 'Gráfico',
    'fas fa-business-time' => 'Negócios',
    'fas fa-comments' => 'Conversação',
    'fas fa-microphone' => 'Microfone',
    'fas fa-music' => 'Música',
    'fas fa-film' => 'Filme',
    'fas fa-newspaper' => 'Notícias',
    'fas fa-pencil-alt' => 'Escrita',
    'fas fa-spell-check' => 'Gramática',
    'fas fa-volume-up' => 'Pronúncia'
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Conteúdos - Professor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        body { background-color: #FAF9F6; overflow-x: hidden; }
        
        /* SIDEBAR ORIGINAL RESTAURADA */
        .sidebar { position: fixed; left: 0; top: 0; height: 100vh; width: 16.666667%; background-color: #081d40; color: #fff; z-index: 1000; overflow-y: auto; }
        .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px 15px; margin-bottom: 5px; border-radius: 5px; transition: 0.3s; }
        .sidebar a:hover { background-color: rgba(255, 255, 255, 0.1); transform: translateX(3px); }
        .sidebar .active { background-color: #c0392b; }
        
        .main-content { margin-left: 16.666667%; width: 83.333333%; min-height: 100vh; overflow-y: auto; padding: 30px; }
        
        /* Ajustes da Biblioteca e Alinhamento à Esquerda */
        .biblioteca-container { max-width: 1000px; margin-left: 0; }
        .card-header { background-color: #081d40; color: white; }
        .card-header-grupo { background-color: #f8f9fa; color: #081d40; border-bottom: 2px solid #081d40; cursor: pointer; }
        
        /* Ícone de pasta Azul Risenglish */
        .fa-folder, .fa-folder-open { color: #081d40 !important; } 
        
        /* Estilo customizado para os botões */
        .btn-custom-dark {
            background-color: #081d40;
            color: white;
            border: none;
        }
        .btn-custom-dark:hover {
            background-color: #0c2a5c;
            color: white;
        }
        
        .btn-custom-outline-dark {
            background-color: transparent;
            color: #081d40;
            border: 1px solid #081d40;
        }
        .btn-custom-outline-dark:hover {
            background-color: #081d40;
            color: white;
        }

        .list-group-item { padding: 0.6rem 1rem; cursor: pointer; transition: background-color 0.2s; }
        .list-group-item:hover { background-color: #f1f3f5; }
        
        .grupo-item {
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        
        .grupo-header {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s;
        }
        .grupo-header:hover { background-color: #e9ecef; }
        
        .grupo-titulo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .grupo-icone {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .grupo-conteudo {
            background-color: #ffffff;
            border-top: 1px solid #dee2e6;
            padding: 10px;
        }
        
        .badge-count {
            background-color: #6c757d;
            color: white;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 0.8em;
        }
        
        .sem-grupo-section {
            margin-top: 30px;
            opacity: 0.9;
        }
        
        #formNovoGrupo, #formNovoTema { display: none; }
        
        .rotate-icon {
            transition: transform 0.3s;
        }
        .rotate-icon.expanded {
            transform: rotate(90deg);
        }
        
        @media (max-width: 768px) { 
            .sidebar { position: relative; width: 100%; height: auto; } 
            .main-content { margin-left: 0; width: 100%; } 
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center"><h5 class="mt-4">Prof. <?= $_SESSION['user_nome'] ?></h5></div>
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="notificacoes.php" class="rounded position-relative">
                        <i class="fas fa-bell"></i>&nbsp;&nbsp;Notificações
                        <?php if ($total_notificacoes_nao_lidas > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $total_notificacoes_nao_lidas ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="dashboard.php"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="active"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>
                <div class="mt-auto"><a href="../logout.php" class="btn btn-outline-danger w-100 border-0"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></div>
            </div>

            <div class="col-md-10 main-content">
                <h2 class="mb-4 mt-3">Biblioteca de Conteúdos</h2>
                
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show"><?= $mensagem ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <!-- Botões de Ação -->
                <div class="mb-3 d-flex gap-2">
                    <button class="btn btn-custom-dark shadow-sm" onclick="toggleForm('grupo')">
                        <i class="fas fa-layer-group me-2"></i> Novo Grupo
                    </button>
                    <button class="btn btn-custom-outline-dark shadow-sm" onclick="toggleForm('tema')">
                        <i class="fas fa-folder-plus me-2"></i> Novo Tema
                    </button>
                </div>

                <!-- Formulário Novo Grupo -->
                <div id="formNovoGrupo" class="card rounded biblioteca-container mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <i class="fas fa-layer-group me-2"></i> Criar Novo Grupo
                    </div>
                    <div class="card-body">
                        <form action="gerenciar_conteudos.php" method="POST">
                            <input type="hidden" name="acao_grupo" value="cadastrar">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Nome do Grupo</label>
                                    <input type="text" class="form-control" name="nome_grupo" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Descrição</label>
                                    <input type="text" class="form-control" name="descricao_grupo" placeholder="Opcional">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Cor</label>
                                    <input type="color" class="form-control form-control-color" name="cor_grupo" value="#081d40">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Ícone</label>
                                    <select class="form-select" name="icone_grupo">
                                        <?php foreach ($icones_disponiveis as $icone => $desc): ?>
                                            <option value="<?= $icone ?>" <?= $icone == 'fas fa-layer-group' ? 'selected' : '' ?>>
                                                <?= $desc ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success">Criar Grupo</button>
                                    <button type="button" class="btn btn-secondary" onclick="toggleForm('grupo')">Cancelar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Formulário Novo Tema -->
                <div id="formNovoTema" class="card rounded biblioteca-container mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <i class="fas fa-folder-plus me-2"></i> Criar Novo Tema
                    </div>
                    <div class="card-body">
                        <form action="gerenciar_conteudos.php" method="POST">
                            <input type="hidden" name="acao_tema" value="cadastrar">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Título do Tema</label>
                                    <input type="text" class="form-control" name="titulo" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Descrição</label>
                                    <input type="text" class="form-control" name="descricao" placeholder="Opcional">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Grupo (opcional)</label>
                                    <select class="form-select" name="grupo_id">
                                        <option value="">-- Sem grupo --</option>
                                        <?php foreach ($grupos as $grupo): ?>
                                            <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success w-100">Criar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Grupos e Temas -->
                <div class="biblioteca-container">
                    <?php if (empty($grupos) && empty($temas_sem_grupo)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhum grupo ou tema cadastrado. Comece criando um grupo ou tema!
                        </div>
                    <?php else: ?>
                        
                        <!-- Grupos -->
                        <?php foreach ($grupos as $grupo): 
                            $temas_do_grupo = $temas_por_grupo[$grupo['id']] ?? [];
                            $total_temas = count($temas_do_grupo);
                        ?>
                            <div class="grupo-item shadow-sm">
                                <div class="grupo-header" onclick="toggleGrupo(<?= $grupo['id'] ?>)">
                                    <div class="grupo-titulo">
                                        <div class="grupo-icone" style="background-color: <?= $grupo['cor'] ?>">
                                            <i class="<?= $grupo['icone'] ?>"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0"><?= htmlspecialchars($grupo['nome']) ?></h5>
                                            <?php if ($grupo['descricao']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($grupo['descricao']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge-count"><?= $total_temas ?> temas</span>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-link text-primary" onclick="event.stopPropagation(); editarGrupo(<?= $grupo['id'] ?>, '<?= htmlspecialchars($grupo['nome']) ?>', '<?= htmlspecialchars($grupo['descricao']) ?>', '<?= $grupo['cor'] ?>', '<?= $grupo['icone'] ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-link text-danger" onclick="event.stopPropagation(); confirmarExclusaoGrupo(<?= $grupo['id'] ?>, '<?= htmlspecialchars($grupo['nome']) ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            <button class="btn btn-sm btn-link text-dark" onclick="event.stopPropagation();">
                                                <i class="fas fa-chevron-right rotate-icon" id="icone-grupo-<?= $grupo['id'] ?>"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="grupo-conteudo" id="grupo-<?= $grupo['id'] ?>" style="display: none;">
                                    <?php if (empty($temas_do_grupo)): ?>
                                        <p class="text-center text-muted py-3 mb-0">
                                            <i class="fas fa-folder-open me-2"></i>
                                            Nenhum tema neste grupo. 
                                            <a href="#" onclick="abrirFormTema(<?= $grupo['id'] ?>)">Adicionar tema</a>
                                        </p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush">
                                            <?php 
                                            $contador = 1;
                                            foreach ($temas_do_grupo as $tema): 
                                            ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="w-100" onclick="window.location.href='gerenciar_arquivos_tema.php?tema_id=<?= $tema['id'] ?>'">
                                                        <div>
                                                            <span class="text-muted fw-bold me-2"><?= $contador ?>.</span>
                                                            <strong class="text-dark"><i class="fas fa-folder me-2"></i> <?= htmlspecialchars($tema['titulo']) ?></strong>
                                                        </div>
                                                        <small class="text-muted ms-4"><?= htmlspecialchars($tema['descricao'] ?: 'Sem descrição.') ?></small>
                                                    </div>
                                                    <div class="actions d-flex">
                                                        <button class="btn btn-sm btn-link text-primary me-2" onclick="event.stopPropagation(); editarTema(<?= $tema['id'] ?>, '<?= htmlspecialchars($tema['titulo']) ?>', '<?= htmlspecialchars($tema['descricao']) ?>', <?= $tema['grupo_id'] ?: 'null' ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-link text-warning me-2" onclick="event.stopPropagation(); abrirModalMover(<?= $tema['id'] ?>, '<?= htmlspecialchars($tema['titulo']) ?>', <?= $tema['grupo_id'] ?: 'null' ?>)">
                                                            <i class="fas fa-arrows-alt"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-link text-danger" onclick="event.stopPropagation(); confirmarExclusaoTema(<?= $tema['id'] ?>, '<?= htmlspecialchars($tema['titulo']) ?>')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </li>
                                            <?php $contador++; endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Temas sem grupo -->
                        <?php if (!empty($temas_sem_grupo)): ?>
                            <div class="sem-grupo-section">
                                <div class="grupo-item shadow-sm opacity-75">
                                    <div class="grupo-header" onclick="toggleGrupo('sem-grupo')">
                                        <div class="grupo-titulo">
                                            <div class="grupo-icone" style="background-color: #6c757d">
                                                <i class="fas fa-folder"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-0">Sem Grupo</h5>
                                                <small class="text-muted">Temas não organizados</small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge-count"><?= count($temas_sem_grupo) ?> temas</span>
                                            <button class="btn btn-sm btn-link text-dark" onclick="event.stopPropagation();">
                                                <i class="fas fa-chevron-right rotate-icon" id="icone-grupo-sem-grupo"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grupo-conteudo" id="grupo-sem-grupo" style="display: none;">
                                        <ul class="list-group list-group-flush">
                                            <?php 
                                            $contador = 1;
                                            foreach ($temas_sem_grupo as $tema): 
                                            ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="w-100" onclick="window.location.href='gerenciar_arquivos_tema.php?tema_id=<?= $tema['id'] ?>'">
                                                        <div>
                                                            <span class="text-muted fw-bold me-2"><?= $contador ?>.</span>
                                                            <strong class="text-dark"><i class="fas fa-folder me-2"></i> <?= htmlspecialchars($tema['titulo']) ?></strong>
                                                        </div>
                                                        <small class="text-muted ms-4"><?= htmlspecialchars($tema['descricao'] ?: 'Sem descrição.') ?></small>
                                                    </div>
                                                    <div class="actions d-flex">
                                                        <button class="btn btn-sm btn-link text-primary me-2" onclick="event.stopPropagation(); editarTema(<?= $tema['id'] ?>, '<?= htmlspecialchars($tema['titulo']) ?>', '<?= htmlspecialchars($tema['descricao']) ?>', <?= $tema['grupo_id'] ?: 'null' ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-link text-warning me-2" onclick="event.stopPropagation(); abrirModalMover(<?= $tema['id'] ?>, '<?= htmlspecialchars($tema['titulo']) ?>', <?= $tema['grupo_id'] ?: 'null' ?>)">
                                                            <i class="fas fa-arrows-alt"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-link text-danger" onclick="event.stopPropagation(); confirmarExclusaoTema(<?= $tema['id'] ?>, '<?= htmlspecialchars($tema['titulo']) ?>')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </li>
                                            <?php $contador++; endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Grupo -->
    <div class="modal fade" id="modalEditarGrupo" tabindex="-1">
        <div class="modal-dialog">
            <form action="gerenciar_conteudos.php" method="POST" class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Editar Grupo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao_grupo" value="editar">
                    <input type="hidden" name="grupo_id" id="edit-grupo-id">
                    <div class="mb-3">
                        <label class="form-label">Nome do Grupo</label>
                        <input type="text" class="form-control" name="nome_grupo" id="edit-grupo-nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="descricao_grupo" id="edit-grupo-descricao">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cor</label>
                        <input type="color" class="form-control form-control-color" name="cor_grupo" id="edit-grupo-cor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ícone</label>
                        <select class="form-select" name="icone_grupo" id="edit-grupo-icone">
                            <?php foreach ($icones_disponiveis as $icone => $desc): ?>
                                <option value="<?= $icone ?>"><?= $desc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Tema -->
    <div class="modal fade" id="modalEditarTema" tabindex="-1">
        <div class="modal-dialog">
            <form action="gerenciar_conteudos.php" method="POST" class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Editar Tema</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao_tema" value="editar">
                    <input type="hidden" name="conteudo_id" id="edit-tema-id">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="titulo" id="edit-tema-titulo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="descricao" id="edit-tema-descricao">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grupo</label>
                        <select class="form-select" name="grupo_id" id="edit-tema-grupo">
                            <option value="">-- Sem grupo --</option>
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Mover Tema -->
    <div class="modal fade" id="modalMoverTema" tabindex="-1">
        <div class="modal-dialog">
            <form action="gerenciar_conteudos.php" method="POST" class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Mover Tema</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="mover_tema" value="1">
                    <input type="hidden" name="tema_id" id="mover-tema-id">
                    <p id="mover-tema-info" class="fw-bold"></p>
                    <div class="mb-3">
                        <label class="form-label">Selecione o grupo de destino</label>
                        <select class="form-select" name="grupo_destino" id="mover-grupo-destino">
                            <option value="">-- Sem grupo --</option>
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?= $grupo['id'] ?>"><?= htmlspecialchars($grupo['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Mover</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Estado dos grupos (expandido/recolhido)
        let gruposExpandidos = JSON.parse(localStorage.getItem('gruposExpandidos')) || {};
        
        function toggleForm(tipo) {
            if (tipo === 'grupo') {
                var form = document.getElementById('formNovoGrupo');
                form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
                document.getElementById('formNovoTema').style.display = 'none';
            } else {
                var form = document.getElementById('formNovoTema');
                form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
                document.getElementById('formNovoGrupo').style.display = 'none';
            }
        }

        function abrirFormTema(grupoId) {
            document.getElementById('formNovoTema').style.display = 'block';
            var select = document.querySelector('select[name="grupo_id"]');
            if (select) {
                select.value = grupoId;
            }
        }

        function toggleGrupo(grupoId) {
            var grupoDiv = document.getElementById('grupo-' + grupoId);
            var icone = document.getElementById('icone-grupo-' + grupoId);
            
            if (grupoDiv.style.display === 'none' || grupoDiv.style.display === '') {
                grupoDiv.style.display = 'block';
                if (icone) icone.classList.add('expanded');
                gruposExpandidos[grupoId] = true;
            } else {
                grupoDiv.style.display = 'none';
                if (icone) icone.classList.remove('expanded');
                gruposExpandidos[grupoId] = false;
            }
            
            localStorage.setItem('gruposExpandidos', JSON.stringify(gruposExpandidos));
        }

        // Restaurar estado dos grupos ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($grupos as $grupo): ?>
                if (gruposExpandidos[<?= $grupo['id'] ?>]) {
                    document.getElementById('grupo-<?= $grupo['id'] ?>').style.display = 'block';
                    var icone = document.getElementById('icone-grupo-<?= $grupo['id'] ?>');
                    if (icone) icone.classList.add('expanded');
                }
            <?php endforeach; ?>
            
            if (gruposExpandidos['sem-grupo']) {
                var grupoDiv = document.getElementById('grupo-sem-grupo');
                if (grupoDiv) {
                    grupoDiv.style.display = 'block';
                    var icone = document.getElementById('icone-grupo-sem-grupo');
                    if (icone) icone.classList.add('expanded');
                }
            }
        });

        // Funções para editar grupo
        function editarGrupo(id, nome, descricao, cor, icone) {
            document.getElementById('edit-grupo-id').value = id;
            document.getElementById('edit-grupo-nome').value = nome;
            document.getElementById('edit-grupo-descricao').value = descricao;
            document.getElementById('edit-grupo-cor').value = cor;
            
            var select = document.getElementById('edit-grupo-icone');
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === icone) {
                    select.options[i].selected = true;
                    break;
                }
            }
            
            new bootstrap.Modal(document.getElementById('modalEditarGrupo')).show();
        }

        // Funções para editar tema
        function editarTema(id, titulo, descricao, grupoId) {
            document.getElementById('edit-tema-id').value = id;
            document.getElementById('edit-tema-titulo').value = titulo;
            document.getElementById('edit-tema-descricao').value = descricao;
            
            var select = document.getElementById('edit-tema-grupo');
            if (grupoId) {
                select.value = grupoId;
            } else {
                select.value = '';
            }
            
            new bootstrap.Modal(document.getElementById('modalEditarTema')).show();
        }

        // Funções para mover tema
        function abrirModalMover(id, titulo, grupoAtual) {
            document.getElementById('mover-tema-id').value = id;
            document.getElementById('mover-tema-info').textContent = 'Movendo: ' + titulo;
            
            var select = document.getElementById('mover-grupo-destino');
            if (grupoAtual) {
                select.value = grupoAtual;
            } else {
                select.value = '';
            }
            
            new bootstrap.Modal(document.getElementById('modalMoverTema')).show();
        }

        // Funções de confirmação
        function confirmarExclusaoGrupo(id, nome) {
            if (confirm("Deseja realmente excluir o grupo '" + nome + "'?\nOs temas serão movidos para 'Sem Grupo'.")) {
                window.location.href = 'gerenciar_conteudos.php?excluir_grupo=' + id;
            }
        }

        function confirmarExclusaoTema(id, titulo) {
            if (confirm("Deseja realmente excluir o tema '" + titulo + "'?")) {
                window.location.href = 'gerenciar_conteudos.php?excluir_tema=' + id;
            }
        }

        // Inicializar tooltips do Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>