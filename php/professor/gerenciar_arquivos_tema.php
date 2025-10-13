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

// --- 1. TRATAMENTO DE MENSAGENS DE SESSÃO (PRG) ---
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $sucesso = $_SESSION['sucesso'] ?? false;
    unset($_SESSION['mensagem']);
    unset($_SESSION['sucesso']);
}

// --- 2. VALIDAÇÃO DO TEMA ---
if (!isset($_GET['tema_id']) || !is_numeric($_GET['tema_id'])) {
    header("Location: gerenciar_conteudos.php");
    exit;
}

$tema_id = (int)$_GET['tema_id'];

// Buscar dados do TEMA
$sql_tema = "SELECT c.titulo, c.professor_id, u.nome AS nome_professor
             FROM conteudos AS c
             JOIN usuarios AS u ON c.professor_id = u.id
             WHERE c.id = :tema_id AND c.parent_id IS NULL";
$stmt_tema = $pdo->prepare($sql_tema);
$stmt_tema->execute([':tema_id' => $tema_id]);
$tema = $stmt_tema->fetch(PDO::FETCH_ASSOC);

if (!$tema) {
    header("Location: gerenciar_conteudos.php");
    exit;
}

// --- 3. BUSCAR AULAS DO PROFESSOR PARA VISIBILIDADE ---
$sql_aulas = "SELECT a.id, a.titulo_aula, a.data_aula, t.nome_turma
              FROM aulas a
              JOIN turmas t ON a.turma_id = t.id
              WHERE a.professor_id = :professor_id
              ORDER BY a.data_aula DESC, a.horario DESC";
$stmt_aulas = $pdo->prepare($sql_aulas);
$stmt_aulas->execute([':professor_id' => $professor_id]);
$aulas = $stmt_aulas->fetchAll(PDO::FETCH_ASSOC);

// --- 4. LÓGICA UNIFICADA DE CADASTRO (ARQUIVO OU LINK) ---
if ($tema_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'upload_recurso') {
    
    $titulo_recurso = trim($_POST['titulo_recurso']);
    $link_url = trim($_POST['link_url'] ?? '');
    
    $is_file_upload = (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK);
    $is_link_provided = !empty($link_url);

    if (empty($titulo_recurso) || (!$is_file_upload && !$is_link_provided)) {
        $_SESSION['mensagem'] = "Por favor, preencha o Título e envie um Arquivo ou forneça um Link.";
        $_SESSION['sucesso'] = false;
        header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
        exit;
    }
    
    $caminho_arquivo_bd = null;
    $tipo_arquivo = null;

    if ($is_file_upload) {
        $upload_dir = '../uploads/conteudos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $nome_arquivo_original = basename($_FILES['arquivo']['name']);
        $extensao = strtolower(pathinfo($nome_arquivo_original, PATHINFO_EXTENSION));
        $novo_nome = time() . '_' . uniqid() . '.' . $extensao;
        $caminho_destino = $upload_dir . $novo_nome;

        $allowed_mime_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
        $uploaded_mime_type = mime_content_type($_FILES['arquivo']['tmp_name']);

        if (!in_array($uploaded_mime_type, $allowed_mime_types)) {
            $_SESSION['mensagem'] = "Tipo de arquivo não permitido. Apenas PDF e Imagens (JPG, PNG, GIF).";
            $_SESSION['sucesso'] = false;
            header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
            exit;
        }

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_destino)) {
            $caminho_arquivo_bd = 'uploads/conteudos/' . $novo_nome;
            $tipo_arquivo = $uploaded_mime_type;
            $descricao_recurso = $titulo_recurso . ' (Arquivo: ' . $nome_arquivo_original . ')';
        } else {
            $_SESSION['mensagem'] = "Erro ao mover o arquivo. Tente novamente.";
            $_SESSION['sucesso'] = false;
            header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
            exit;
        }

    } elseif ($is_link_provided) {
        if (!filter_var($link_url, FILTER_VALIDATE_URL)) {
             $_SESSION['mensagem'] = "O link fornecido não é uma URL válida.";
             $_SESSION['sucesso'] = false;
             header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
             exit;
        }
        
        $caminho_arquivo_bd = $link_url;
        $tipo_arquivo = 'URL';
        $descricao_recurso = $titulo_recurso . ' (Link: ' . parse_url($link_url, PHP_URL_HOST) . ')';
    }

    try {
        $sql = "INSERT INTO conteudos (professor_id, parent_id, titulo, descricao, tipo_arquivo, caminho_arquivo, data_upload) 
                 VALUES (:professor_id, :parent_id, :titulo, :descricao, :tipo_arquivo, :caminho_arquivo, NOW())";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([
            ':professor_id' => $professor_id,
            ':parent_id' => $tema_id,
            ':titulo' => $titulo_recurso,
            ':descricao' => $descricao_recurso,
            ':tipo_arquivo' => $tipo_arquivo,
            ':caminho_arquivo' => $caminho_arquivo_bd
        ])) {
            $tipo_msg = $is_file_upload ? "Arquivo" : "Link";
            $_SESSION['mensagem'] = $tipo_msg . " **" . htmlspecialchars($titulo_recurso) . "** enviado e vinculado ao tema com sucesso!";
            $_SESSION['sucesso'] = true;
        } else {
            $_SESSION['mensagem'] = "Erro ao vincular recurso ao banco de dados.";
            $_SESSION['sucesso'] = false;
            if ($is_file_upload && file_exists('../' . $caminho_arquivo_bd)) {
                unlink('../' . $caminho_arquivo_bd);
            }
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = "Erro de BD: " . $e->getMessage();
        $_SESSION['sucesso'] = false;
    }
    
    header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
    exit;
}

// --- 5. LÓGICA DE EXCLUSÃO DE RECURSO ---
if ($tema_id > 0 && isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $recurso_id = $_GET['excluir'];
    try {
        $sql_caminho = "SELECT caminho_arquivo, tipo_arquivo FROM conteudos 
                         WHERE id = :id AND professor_id = :professor_id AND parent_id = :parent_id";
        $stmt_caminho = $pdo->prepare($sql_caminho);
        $stmt_caminho->execute([':id' => $recurso_id, ':professor_id' => $professor_id, ':parent_id' => $tema_id]);
        $recurso_info = $stmt_caminho->fetch(PDO::FETCH_ASSOC);

        $sql_delete = "DELETE FROM conteudos 
                        WHERE id = :id AND professor_id = :professor_id AND parent_id = :parent_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        
        if ($stmt_delete->execute([':id' => $recurso_id, ':professor_id' => $professor_id, ':parent_id' => $tema_id])) {
            // Remove também os registros de visibilidade
            $sql_delete_visibilidade = "DELETE FROM arquivos_visiveis WHERE conteudo_id = :conteudo_id";
            $stmt_delete_visibilidade = $pdo->prepare($sql_delete_visibilidade);
            $stmt_delete_visibilidade->execute([':conteudo_id' => $recurso_id]);
            
            if ($recurso_info && $recurso_info['caminho_arquivo'] && $recurso_info['tipo_arquivo'] !== 'URL') {
                $caminho_completo = '../' . $recurso_info['caminho_arquivo'];
                if (file_exists($caminho_completo) && !is_dir($caminho_completo)) {
                    unlink($caminho_completo);
                }
            }
            $_SESSION['mensagem'] = "Recurso excluído com sucesso!";
            $_SESSION['sucesso'] = true;
        } else {
            $_SESSION['mensagem'] = "Erro ao excluir recurso ou recurso não encontrado.";
            $_SESSION['sucesso'] = false;
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = "Erro: O recurso pode estar sendo referenciado. (" . $e->getMessage() . ")";
        $_SESSION['sucesso'] = false;
    }
    
    header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
    exit;
}

// --- 6. BUSCAR RECURSOS DO TEMA COM STATUS DE VISIBILIDADE ---
$recursos = [];
if ($tema_id > 0) {
    $sql_recursos = "SELECT 
                        c.id, 
                        c.titulo, 
                        c.tipo_arquivo, 
                        c.caminho_arquivo, 
                        c.data_upload,
                        (SELECT COUNT(*) FROM arquivos_visiveis av WHERE av.conteudo_id = c.id AND av.visivel = 1) as aulas_visiveis,
                        (SELECT COUNT(*) FROM aulas WHERE professor_id = :professor_id) as total_aulas
                     FROM conteudos c
                     WHERE c.parent_id = :tema_id 
                     ORDER BY c.data_upload DESC";
    $stmt_recursos = $pdo->prepare($sql_recursos);
    $stmt_recursos->execute([':tema_id' => $tema_id, ':professor_id' => $professor_id]);
    $recursos = $stmt_recursos->fetchAll(PDO::FETCH_ASSOC);
}

// --- 7. BUSCAR DETALHES DE VISIBILIDADE POR AULA ---
$visibilidade_por_aula = [];
if (!empty($recursos)) {
    $recurso_ids = array_column($recursos, 'id');
    $placeholders = str_repeat('?,', count($recurso_ids) - 1) . '?';
    
    $sql_visibilidade = "SELECT conteudo_id, aula_id, visivel 
                         FROM arquivos_visiveis 
                         WHERE conteudo_id IN ($placeholders)";
    $stmt_visibilidade = $pdo->prepare($sql_visibilidade);
    $stmt_visibilidade->execute($recurso_ids);
    
    while ($row = $stmt_visibilidade->fetch(PDO::FETCH_ASSOC)) {
        $visibilidade_por_aula[$row['conteudo_id']][$row['aula_id']] = $row['visivel'];
    }
}

function get_file_icon($mime_type, $caminho_arquivo = null) {
    if ($mime_type === 'URL') {
        if ($caminho_arquivo && (strpos($caminho_arquivo, 'youtube.com') !== false || strpos($caminho_arquivo, 'youtu.be') !== false)) {
            return 'fab fa-youtube text-danger';
        }
        return 'fas fa-link text-info';
    }
    if (strpos($mime_type, 'image/') !== false) return 'fas fa-image text-success';
    if (strpos($mime_type, 'pdf') !== false) return 'fas fa-file-pdf text-danger';
    if (strpos($mime_type, 'word') !== false || strpos($mime_type, 'document') !== false) return 'fas fa-file-word';
    if (strpos($mime_type, 'audio/') !== false) return 'fas fa-file-audio';
    if (strpos($mime_type, 'video/') !== false) return 'fas fa-file-video';
    return 'fas fa-file text-secondary';
}

// Função para extrair ID do YouTube
function get_youtube_id($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Arquivos: <?= htmlspecialchars($tema['titulo'] ?? 'Tema Inválido') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        
        .btn-outline-danger {
            color: #c0392b;
            border-color: #c0392b;
        }
        
        .btn-outline-danger:hover {
            background-color: #c0392b;
            color: white;
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        /* Estilos para a tabela */
        .table-responsive {
            border-radius: 0 0 5px 5px;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        /* Ajuste para o grupo de botões de ação */
        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            border-radius: .2rem;
        }
        
        /* Estilo para o botão de visualizar */
        .btn-info-detalhes {
            background-color: #099410ff;
            border-color: #099410ff;
            color: white;
        }
        
        .btn-info-detalhes:hover {
            background-color: #087e0eff;
            border-color: #087e0eff;
            color: white;
        }

        /* Estilo para recursos da lista */
        .recurso-item {
            transition: all 0.2s ease;
        }
        
        .recurso-item:hover {
            background-color: #f8f9fa;
        }

        /* Modal YouTube personalizado */
        .modal-youtube .modal-dialog {
            max-width: 70%;
            max-height: 70vh;
        }
        
        .modal-youtube .modal-content {
            background: #081d40;
            border: none;
            padding: 10px;
        }
        
        .modal-youtube .modal-header {
            border-bottom: none;
            padding-bottom: 25px;
        }
        
        .modal-youtube .btn-close {
            background-color: white;
            opacity: 1;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #back-link {
            text-decoration: none;
            color: #081d40
        }

        #back-link:hover {
            text-decoration: none;
            color: #384d90
        }

        /* Estilos para visibilidade */
        .visibilidade-switch {
            transform: scale(0.8);
        }
        
        .visibilidade-status {
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .visivel {
            color: #28a745;
        }
        
        .nao-visivel {
            color: #dc3545;
        }
        
        .aula-selector {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            background-color: #f8f9fa;
        }
        
        .aula-item {
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .aula-item:last-child {
            border-bottom: none;
        }

        /* Responsividade */
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
            
            .modal-youtube .modal-dialog {
                max-width: 95%;
                margin: 10px auto;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <!-- Nome do professor -->
                <div class="mb-4 text-center">
                    <h5 class="mt-4">Prof. <?php echo $_SESSION['user_nome'] ?? 'Professor'; ?></h5>
                </div>

                <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded active"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mt-3"><a id="back-link" href="gerenciar_conteudos.php"> Gerenciamento de Temas</a> > <strong><?= htmlspecialchars($tema['titulo'] ?? 'Tema') ?></strong></h2>
                    <div>
                        <div class="mt-4">
                            <a href="gerenciar_conteudos.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i> Voltar para Temas
                            </a>
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalAdicionarRecurso">
                                <i class="fas fa-plus me-2"></i> Adicionar Recurso
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= $mensagem ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Lista de Recursos -->
                <div class="card rounded">
                    <div class="card-header text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list-ul me-2"></i> Recursos Vinculados (Total: <?= count($recursos) ?>)</span>
                    </div>
                    <div class="card-body p-0 rounded">
                        <?php if (empty($recursos)): ?>
                            <p class="p-4 text-center text-muted">Nenhum recurso (arquivo ou link) anexado a este tema ainda.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0 rounded">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Título do Recurso</th>
                                            <th>Visibilidade</th>
                                            <th>Data de Upload</th>
                                            <th style="width: 200px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recursos as $r): 
                                            $is_youtube = ($r['tipo_arquivo'] === 'URL') && (strpos($r['caminho_arquivo'], 'youtube.com') !== false || strpos($r['caminho_arquivo'], 'youtu.be') !== false);
                                            $youtube_id = $is_youtube ? get_youtube_id($r['caminho_arquivo']) : null;
                                            $percentual_visivel = $r['total_aulas'] > 0 ? round(($r['aulas_visiveis'] / $r['total_aulas']) * 100) : 0;
                                        ?>
                                            <tr class="recurso-item" data-conteudo-id="<?= $r['id'] ?>">
                                                <td>
                                                    <i class="<?= get_file_icon($r['tipo_arquivo'], $r['caminho_arquivo']) ?> fa-lg"></i>
                                                    <small class="d-block text-muted">
                                                        <?= ($r['tipo_arquivo'] === 'URL') 
                                                            ? 'Link Externo' 
                                                            : htmlspecialchars($r['tipo_arquivo']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($r['titulo']) ?></strong>
                                                    <?php if ($r['tipo_arquivo'] === 'URL'): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars(parse_url($r['caminho_arquivo'], PHP_URL_HOST) ?? 'URL Inválida') ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="visibilidade-info">
                                                        <small class="visibilidade-status <?= $percentual_visivel > 0 ? 'visivel' : 'nao-visivel' ?>">
                                                            <?= $r['aulas_visiveis'] ?> de <?= $r['total_aulas'] ?> aulas (<?= $percentual_visivel ?>%)
                                                        </small>
                                                        <br>
                                                        <button class="btn btn-sm btn-outline-primary mt-1" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#modalVisibilidade"
                                                                data-conteudo-id="<?= $r['id'] ?>"
                                                                data-conteudo-titulo="<?= htmlspecialchars($r['titulo']) ?>">
                                                            <i class="fas fa-eye me-1"></i> Gerenciar
                                                        </button>
                                                    </div>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($r['data_upload'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($r['caminho_arquivo']): ?>
                                                            <?php if ($is_youtube && $youtube_id): ?>
                                                                <!-- Botão para abrir YouTube em Modal -->
                                                                <button class="btn btn-sm btn-outline-primary" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#modalYouTube"
                                                                        data-video-id="<?= $youtube_id ?>"
                                                                        title="Assistir Vídeo">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <!-- Link normal para outros recursos -->
                                                                <?php 
                                                                    $href = ($r['tipo_arquivo'] === 'URL') 
                                                                        ? htmlspecialchars($r['caminho_arquivo']) 
                                                                        : '../' . htmlspecialchars($r['caminho_arquivo']);
                                                                    $text_button = ($r['tipo_arquivo'] === 'URL') ? 'Acessar Link' : 'Visualizar Arquivo';
                                                                ?>
                                                                <a href="<?= $href ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="<?= $text_button ?>">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalExcluirRecurso" 
                                                            data-recurso-titulo="<?= htmlspecialchars($r['titulo']) ?>" 
                                                            data-recurso-id="<?= $r['id'] ?>" 
                                                            title="Excluir Recurso">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Modal para Adicionar Recurso -->
<div class="modal fade" id="modalAdicionarRecurso" tabindex="-1" aria-labelledby="modalAdicionarRecursoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                <h5 class="modal-title" id="modalAdicionarRecursoLabel">Adicionar Novo Recurso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="gerenciar_arquivos_tema.php?tema_id=<?= $tema_id ?>" id="formRecurso">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="upload_recurso">
                    
                    <div class="mb-3">
                        <label for="titulo_recurso" class="form-label">Título do Recurso *</label>
                        <input type="text" class="form-control" id="titulo_recurso" name="titulo_recurso" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 mb-3 border rounded">
                                <h6 class="text-secondary"><i class="fas fa-file-upload me-1"></i> Upload de Arquivo</h6>
                                <div class="mb-2">
                                    <label for="arquivo" class="form-label">Selecione o Arquivo</label>
                                    <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".pdf,image/jpeg,image/png,image/gif">
                                    <small class="text-muted">Tipos permitidos: PDF, JPG, PNG, GIF</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 mb-3 border rounded">
                                <h6 class="text-secondary"><i class="fas fa-link me-1"></i> Link Externo</h6>
                                <div class="mb-2">
                                    <label for="link_url" class="form-label">URL</label>
                                    <input type="url" class="form-control" id="link_url" name="link_url" placeholder="Ex: https://www.youtube.com/watch?v=...">
                                    <small class="text-muted">Para vídeos do YouTube, links externos, etc.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info small" role="alert">
                        <i class="fas fa-info-circle me-1"></i> 
                        Você deve fornecer um arquivo <strong>OU</strong> um link. O campo Título é obrigatório.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save me-1"></i> Salvar Recurso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modalExcluirRecurso" tabindex="-1" aria-labelledby="modalExcluirRecursoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalExcluirRecursoLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja <strong>excluir permanentemente</strong> o recurso: <strong id="recursoTituloModal"></strong>?</p>
                <p class="text-danger small">Esta ação é irreversível e removerá o registro (e o arquivo físico, se for um) do servidor.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="linkExcluirRecurso" class="btn btn-danger">Excluir Recurso</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Gerenciar Visibilidade -->
<div class="modal fade" id="modalVisibilidade" tabindex="-1" aria-labelledby="modalVisibilidadeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                <h5 class="modal-title" id="modalVisibilidadeLabel">Gerenciar Visibilidade do Recurso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Recurso: <strong id="recursoTituloVisibilidade"></strong></p>
                <div class="aula-selector" id="aulaSelector">
                    <!-- As aulas serão carregadas via JavaScript -->
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnMarcarTodos">
                        <i class="fas fa-check-double me-1"></i> Marcar Todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDesmarcarTodos">
                        <i class="fas fa-times me-1"></i> Desmarcar Todos
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarVisibilidade">
                    <i class="fas fa-save me-1"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para YouTube -->
<div class="modal fade modal-youtube" id="modalYouTube" tabindex="-1" aria-labelledby="modalYouTubeLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="color: white;" id="header-title"></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="youtubePlayer" src="" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    let conteudoIdAtual = null;
    const aulas = <?= json_encode($aulas) ?>;
    const visibilidadePorAula = <?= json_encode($visibilidade_por_aula) ?>;

    // Função para preencher o modal de exclusão
    $('#modalExcluirRecurso').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var recursoId = button.data('recurso-id');
        var recursoTitulo = button.data('recurso-titulo');
        
        var modal = $(this);
        modal.find('#recursoTituloModal').text(recursoTitulo);
        modal.find('#linkExcluirRecurso').attr('href', 'gerenciar_arquivos_tema.php?tema_id=<?= $tema_id ?>&excluir=' + recursoId);
    });

    // Função para o modal do YouTube
    $('#modalYouTube').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var videoId = button.data('video-id');
        
        var iframe = $('#youtubePlayer');
        iframe.attr('src', 'https://www.youtube.com/embed/' + videoId + '?autoplay=1');
    });

    // Função para fechar o modal do YouTube e parar o vídeo
    $('#modalYouTube').on('hidden.bs.modal', function () {
        var iframe = $('#youtubePlayer');
        iframe.attr('src', '');
    });

    // Validação de Formulário (Arquivo OU Link)
    $('#formRecurso').on('submit', function (e) {
        var arquivoInput = $('#arquivo')[0];
        var linkUrlInput = $('#link_url').val().trim();
        var tituloInput = $('#titulo_recurso').val().trim();

        var isFileProvided = arquivoInput.files.length > 0;
        var isLinkProvided = linkUrlInput !== '';
        var isTituloProvided = tituloInput !== '';

        if (!isTituloProvided) {
            alert('O Título do Recurso é obrigatório.');
            e.preventDefault();
            return false;
        }

        if (!isFileProvided && !isLinkProvided) {
            alert('Você deve fornecer um Arquivo OU um Link Externo.');
            e.preventDefault();
            return false;
        }

        if (isFileProvided && isLinkProvided) {
            alert('Forneça apenas um Arquivo OU um Link Externo, não ambos.');
            e.preventDefault();
            return false;
        }
    });

    // Modal de Visibilidade
    $('#modalVisibilidade').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        conteudoIdAtual = button.data('conteudo-id');
        var recursoTitulo = button.data('conteudo-titulo');
        
        var modal = $(this);
        modal.find('#recursoTituloVisibilidade').text(recursoTitulo);
        
        // Carrega as aulas no seletor
        carregarAulasNoSeletor(conteudoIdAtual);
    });

    function carregarAulasNoSeletor(conteudoId) {
        const container = $('#aulaSelector');
        container.empty();
        
        if (aulas.length === 0) {
            container.html('<p class="text-muted">Nenhuma aula encontrada.</p>');
            return;
        }
        
        aulas.forEach(aula => {
            const isVisivel = visibilidadePorAula[conteudoId] && visibilidadePorAula[conteudoId][aula.id] === 1;
            const dataFormatada = new Date(aula.data_aula).toLocaleDateString('pt-BR');
            
            const aulaHtml = `
                <div class="aula-item">
                    <div class="form-check">
                        <input class="form-check-input aula-checkbox" type="checkbox" 
                               data-aula-id="${aula.id}" 
                               id="aula_${aula.id}" 
                               ${isVisivel ? 'checked' : ''}>
                        <label class="form-check-label" for="aula_${aula.id}">
                            <strong>${aula.titulo_aula}</strong> - ${aula.nome_turma} (${dataFormatada})
                        </label>
                    </div>
                </div>
            `;
            container.append(aulaHtml);
        });
    }

    // Botões para marcar/desmarcar todos
    $('#btnMarcarTodos').on('click', function() {
        $('.aula-checkbox').prop('checked', true);
    });

    $('#btnDesmarcarTodos').on('click', function() {
        $('.aula-checkbox').prop('checked', false);
    });

    // Salvar visibilidade
    $('#btnSalvarVisibilidade').on('click', function() {
        if (!conteudoIdAtual) return;
        
        const visibilidades = [];
        $('.aula-checkbox').each(function() {
            const aulaId = $(this).data('aula-id');
            const visivel = $(this).is(':checked') ? 1 : 0;
            visibilidades.push({ aula_id: aulaId, visivel: visivel });
        });

        // Envia via AJAX
        $.ajax({
            url: 'ajax_salvar_visibilidade.php',
            method: 'POST',
            data: {
                conteudo_id: conteudoIdAtual,
                visibilidades: visibilidades
            },
            success: function(response) {
                if (response.success) {
                    alert('Visibilidade atualizada com sucesso!');
                    $('#modalVisibilidade').modal('hide');
                    location.reload(); // Recarrega para atualizar os contadores
                } else {
                    alert('Erro ao salvar: ' + response.message);
                }
            },
            error: function() {
                alert('Erro de comunicação com o servidor.');
            }
        });
    });
});
</script>

</body>
</html>