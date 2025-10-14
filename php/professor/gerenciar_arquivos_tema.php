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

// --- 4. LÓGICA UNIFICADA DE CADASTRO (SUBPASTA, ARQUIVO OU LINK) ---
if ($tema_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'criar_subpasta') {
        $titulo_subpasta = trim($_POST['titulo_subpasta']);
        $descricao_subpasta = trim($_POST['descricao_subpasta'] ?? '');

        if (empty($titulo_subpasta)) {
            $_SESSION['mensagem'] = "Por favor, informe o título da subpasta.";
            $_SESSION['sucesso'] = false;
            header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
            exit;
        }

        try {
            $sql = "INSERT INTO conteudos (professor_id, parent_id, titulo, descricao, tipo_arquivo, caminho_arquivo, data_upload, eh_subpasta) 
                     VALUES (:professor_id, :parent_id, :titulo, :descricao, 'SUBPASTA', '', NOW(), 1)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([
                ':professor_id' => $professor_id,
                ':parent_id' => $tema_id,
                ':titulo' => $titulo_subpasta,
                ':descricao' => $descricao_subpasta
            ])) {
                $_SESSION['mensagem'] = "Subpasta <strong>" . htmlspecialchars($titulo_subpasta) . "</strong> criada com sucesso!";
                $_SESSION['sucesso'] = true;
            } else {
                $_SESSION['mensagem'] = "Erro ao criar subpasta.";
                $_SESSION['sucesso'] = false;
            }
        } catch (PDOException $e) {
            $_SESSION['mensagem'] = "Erro de BD: " . $e->getMessage();
            $_SESSION['sucesso'] = false;
        }

        header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
        exit;

    } elseif ($_POST['acao'] === 'upload_recurso') {
        $titulo_recurso = trim($_POST['titulo_recurso']);
        $link_url = trim($_POST['link_url'] ?? '');
        $subpasta_id = isset($_POST['subpasta_id']) && !empty($_POST['subpasta_id']) ? (int)$_POST['subpasta_id'] : null;

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
            $parent_id = $subpasta_id ? $subpasta_id : $tema_id;

            $sql = "INSERT INTO conteudos (professor_id, parent_id, titulo, descricao, tipo_arquivo, caminho_arquivo, data_upload, eh_subpasta) 
                     VALUES (:professor_id, :parent_id, :titulo, :descricao, :tipo_arquivo, :caminho_arquivo, NOW(), 0)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([
                ':professor_id' => $professor_id,
                ':parent_id' => $parent_id,
                ':titulo' => $titulo_recurso,
                ':descricao' => $descricao_recurso,
                ':tipo_arquivo' => $tipo_arquivo,
                ':caminho_arquivo' => $caminho_arquivo_bd
            ])) {
                $tipo_msg = $is_file_upload ? "Arquivo" : "Link";
                $_SESSION['mensagem'] = $tipo_msg . " <strong>" . htmlspecialchars($titulo_recurso) . "</strong> enviado com sucesso!";
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
}

// --- 5. LÓGICA DE EXCLUSÃO DE RECURSO OU SUBPASTA ---
if ($tema_id > 0 && isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $recurso_id = $_GET['excluir'];
    try {
        $sql_caminho = "SELECT caminho_arquivo, tipo_arquivo, eh_subpasta FROM conteudos 
                         WHERE id = :id AND professor_id = :professor_id AND (parent_id = :tema_id OR parent_id IN (SELECT id FROM conteudos WHERE parent_id = :tema_id AND eh_subpasta = 1))";
        $stmt_caminho = $pdo->prepare($sql_caminho);
        $stmt_caminho->execute([':id' => $recurso_id, ':professor_id' => $professor_id, ':tema_id' => $tema_id]);
        $recurso_info = $stmt_caminho->fetch(PDO::FETCH_ASSOC);

        if (!$recurso_info) {
            $_SESSION['mensagem'] = "Recurso não encontrado ou você não tem permissão para excluí-lo.";
            $_SESSION['sucesso'] = false;
            header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
            exit;
        }

        // Se for subpasta, verificar se tem conteúdo antes de excluir
        if ($recurso_info['eh_subpasta'] == 1) {
            $sql_verifica_conteudo = "SELECT COUNT(*) as total FROM conteudos WHERE parent_id = :subpasta_id";
            $stmt_verifica = $pdo->prepare($sql_verifica_conteudo);
            $stmt_verifica->execute([':subpasta_id' => $recurso_id]);
            $resultado = $stmt_verifica->fetch(PDO::FETCH_ASSOC);

            if ($resultado['total'] > 0) {
                $_SESSION['mensagem'] = "Não é possível excluir a subpasta porque ela contém arquivos. Exclua os arquivos primeiro.";
                $_SESSION['sucesso'] = false;
                header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
                exit;
            }
        }

        $sql_delete = "DELETE FROM conteudos 
                        WHERE id = :id AND professor_id = :professor_id";
        $stmt_delete = $pdo->prepare($sql_delete);

        if ($stmt_delete->execute([':id' => $recurso_id, ':professor_id' => $professor_id])) {
            if ($recurso_info && $recurso_info['caminho_arquivo'] && $recurso_info['tipo_arquivo'] !== 'URL') {
                $caminho_completo = '../' . $recurso_info['caminho_arquivo'];
                if (file_exists($caminho_completo) && !is_dir($caminho_completo)) {
                    unlink($caminho_completo);
                }
            }
            $_SESSION['mensagem'] = "Item excluído com sucesso!";
            $_SESSION['sucesso'] = true;
        } else {
            $_SESSION['mensagem'] = "Erro ao excluir item.";
            $_SESSION['sucesso'] = false;
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = "Erro: O item pode estar sendo referenciado. (" . $e->getMessage() . ")";
        $_SESSION['sucesso'] = false;
    }

    header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
    exit;
}

// --- 6. BUSCAR SUBPASTAS E RECURSOS DO TEMA ---
$subpastas = [];
$recursos = [];
$arquivos_por_subpasta = [];
if ($tema_id > 0) {
    // Buscar subpastas
    $sql_subpastas = "SELECT 
                        c.id, 
                        c.titulo, 
                        c.descricao,
                        c.data_upload
                     FROM conteudos c
                     WHERE c.parent_id = :tema_id AND c.eh_subpasta = 1
                     ORDER BY c.titulo ASC";
    $stmt_subpastas = $pdo->prepare($sql_subpastas);
    $stmt_subpastas->execute([':tema_id' => $tema_id]);
    $subpastas = $stmt_subpastas->fetchAll(PDO::FETCH_ASSOC);

    // Buscar recursos diretos (não em subpastas)
    $sql_recursos = "SELECT 
                        c.id, 
                        c.titulo, 
                        c.tipo_arquivo, 
                        c.caminho_arquivo, 
                        c.data_upload
                     FROM conteudos c
                     WHERE c.parent_id = :tema_id AND c.eh_subpasta = 0
                     ORDER BY c.data_upload DESC";
    $stmt_recursos = $pdo->prepare($sql_recursos);
    $stmt_recursos->execute([':tema_id' => $tema_id]);
    $recursos = $stmt_recursos->fetchAll(PDO::FETCH_ASSOC);

    // Buscar arquivos de cada subpasta
    if (!empty($subpastas)) {
        $subpasta_ids = array_column($subpastas, 'id');
        $in = str_repeat('?,', count($subpasta_ids) - 1) . '?';
        $sql_arquivos_subpasta = "SELECT * FROM conteudos WHERE parent_id IN ($in) AND eh_subpasta = 0 ORDER BY data_upload DESC";
        $stmt_arquivos_subpasta = $pdo->prepare($sql_arquivos_subpasta);
        $stmt_arquivos_subpasta->execute($subpasta_ids);
        $arquivos = $stmt_arquivos_subpasta->fetchAll(PDO::FETCH_ASSOC);
        foreach ($arquivos as $arq) {
            $arquivos_por_subpasta[$arq['parent_id']][] = $arq;
        }
    }
}

function get_file_icon($mime_type, $caminho_arquivo = null, $eh_subpasta = false) {
    if ($eh_subpasta) {
        return 'fas fa-folder text-primary';
    }
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
        .table-responsive {
            border-radius: 0 0 5px 5px;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            border-radius: .2rem;
        }
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
        .recurso-item {
            transition: all 0.2s ease;
        }
        .recurso-item:hover {
            background-color: #f8f9fa;
        }
        .subpasta-item {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            cursor: pointer;
        }
        .subpasta-badge {
            background-color: #0d6efd;
            color: #fff;
        }
        .arquivos-subpasta {
            background: #f4f8ff;
        }
        .arquivo-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .arquivo-item:last-child {
            margin-bottom: 0;
        }
        .arquivo-nome {
            font-weight: 500;
            color: #081d40;
            margin-right: 10px;
        }
        .arquivo-actions a {
            margin-left: 8px;
        }
        #back-link {
            text-decoration: none;
            color: #081d40
        }
        #back-link:hover {
            text-decoration: none;
            color: #384d90
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
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center">
                    <h5 class="mt-4">Prof. <?php echo $_SESSION['user_nome'] ?? 'Professor'; ?></h5>
                </div>
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded active"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>
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
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalCriarSubpasta">
                                <i class="fas fa-folder-plus me-2"></i> Nova Subpasta
                            </button>
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

                <!-- Lista de Subpastas -->
                <?php if (!empty($subpastas)): ?>
                <div class="card rounded mb-4">
                    <div class="card-header text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-folder me-2"></i> Subpastas (<?= count($subpastas) ?>)</span>
                    </div>
                    <div class="card-body p-0 rounded">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0 rounded">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Nome da Subpasta</th>
                                        <th>Descrição</th>
                                        <th>Arquivos</th>
                                        <th>Data de Criação</th>
                                        <th style="width: 150px;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subpastas as $subpasta): ?>
                                        <tr class="recurso-item subpasta-item tema-toggle" data-subpasta-id="<?= $subpasta['id'] ?>">
                                            <td>
                                                <i class="<?= get_file_icon('', '', true) ?> fa-lg"></i>
                                                <small class="d-block text-muted">Subpasta</small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($subpasta['titulo']) ?></strong>
                                            </td>
                                            <td>
                                                <?= !empty($subpasta['descricao']) ? htmlspecialchars($subpasta['descricao']) : '<span class="text-muted">Sem descrição</span>' ?>
                                            </td>
                                            <td>
                                                <span class="badge subpasta-badge">
                                                    <?= isset($arquivos_por_subpasta[$subpasta['id']]) ? count($arquivos_por_subpasta[$subpasta['id']]) : 0 ?> arquivo(s)
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($subpasta['data_upload'])) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalAdicionarRecursoSubpasta"
                                                        data-subpasta-id="<?= $subpasta['id'] ?>"
                                                        data-subpasta-titulo="<?= htmlspecialchars($subpasta['titulo']) ?>"
                                                        title="Adicionar arquivo nesta subpasta">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalExcluirRecurso" 
                                                        data-recurso-titulo="<?= htmlspecialchars($subpasta['titulo']) ?>" 
                                                        data-recurso-id="<?= $subpasta['id'] ?>" 
                                                        title="Excluir Subpasta">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="arquivos-subpasta" id="arquivos-subpasta-<?= $subpasta['id'] ?>" style="display:none;">
                                            <td colspan="6">
                                                <?php if (!empty($arquivos_por_subpasta[$subpasta['id']])): ?>
                                                    <?php foreach ($arquivos_por_subpasta[$subpasta['id']] as $arq): ?>
                                                        <div class="arquivo-item mb-2">
                                                            <i class="<?= get_file_icon($arq['tipo_arquivo'], $arq['caminho_arquivo']) ?> me-2"></i>
                                                            <span class="arquivo-nome"><?= htmlspecialchars($arq['titulo']) ?></span>
                                                            <?php if (!empty($arq['caminho_arquivo'])): ?>
                                                                <a href="../<?= htmlspecialchars($arq['caminho_arquivo']) ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2" title="Visualizar/Download">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <span class="ms-2 text-muted" style="font-size:0.85em;">
                                                                <?= htmlspecialchars($arq['descricao'] ?? '') ?>
                                                            </span>
                                                            <button class="btn btn-sm btn-outline-danger ms-2" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#modalExcluirRecurso" 
                                                                data-recurso-titulo="<?= htmlspecialchars($arq['titulo']) ?>" 
                                                                data-recurso-id="<?= $arq['id'] ?>" 
                                                                title="Excluir Recurso">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Nenhum arquivo nesta subpasta.</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lista de Recursos Diretos -->
                <div class="card rounded">
                    <div class="card-header text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list-ul me-2"></i> Recursos Diretos (Total: <?= count($recursos) ?>)</span>
                    </div>
                    <div class="card-body p-0 rounded">
                        <?php if (empty($recursos) && empty($subpastas)): ?>
                            <p class="p-4 text-center text-muted">Nenhum recurso ou subpasta criada ainda.</p>
                        <?php elseif (empty($recursos)): ?>
                            <p class="p-4 text-center text-muted">Nenhum recurso direto adicionado ao tema.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0 rounded">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Título do Recurso</th>
                                            <th>Data de Upload</th>
                                            <th style="width: 120px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recursos as $r): ?>
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
                                                <td><?= date('d/m/Y', strtotime($r['data_upload'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($r['caminho_arquivo']): ?>
                                                            <a href="<?= ($r['tipo_arquivo'] === 'URL') ? htmlspecialchars($r['caminho_arquivo']) : '../' . htmlspecialchars($r['caminho_arquivo']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Visualizar">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
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

<!-- Modal para Criar Subpasta -->
<div class="modal fade" id="modalCriarSubpasta" tabindex="-1" aria-labelledby="modalCriarSubpastaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                <h5 class="modal-title" id="modalCriarSubpastaLabel">Criar Nova Subpasta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="gerenciar_arquivos_tema.php?tema_id=<?= $tema_id ?>">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="criar_subpasta">
                    <div class="mb-3">
                        <label for="titulo_subpasta" class="form-label">Nome da Subpasta *</label>
                        <input type="text" class="form-control" id="titulo_subpasta" name="titulo_subpasta" required 
                               placeholder="Ex: Básico, Intermediário, Avançado">
                    </div>
                    <div class="mb-3">
                        <label for="descricao_subpasta" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao_subpasta" name="descricao_subpasta" rows="3" 
                                  placeholder="Descreva o conteúdo desta subpasta..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-folder-plus me-1"></i> Criar Subpasta
                    </button>
                </div>
            </form>
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

<!-- Modal para Adicionar Recurso em Subpasta -->
<div class="modal fade" id="modalAdicionarRecursoSubpasta" tabindex="-1" aria-labelledby="modalAdicionarRecursoSubpastaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                <h5 class="modal-title" id="modalAdicionarRecursoSubpastaLabel">Adicionar Recurso na Subpasta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="gerenciar_arquivos_tema.php?tema_id=<?= $tema_id ?>" id="formRecursoSubpasta">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="upload_recurso">
                    <input type="hidden" name="subpasta_id" id="subpasta_id">
                    <div class="alert alert-info">
                        <i class="fas fa-folder me-1"></i>
                        Adicionando recurso na subpasta: <strong id="subpasta_nome"></strong>
                    </div>
                    <div class="mb-3">
                        <label for="titulo_recurso_subpasta" class="form-label">Título do Recurso *</label>
                        <input type="text" class="form-control" id="titulo_recurso_subpasta" name="titulo_recurso" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 mb-3 border rounded">
                                <h6 class="text-secondary"><i class="fas fa-file-upload me-1"></i> Upload de Arquivo</h6>
                                <div class="mb-2">
                                    <label for="arquivo_subpasta" class="form-label">Selecione o Arquivo</label>
                                    <input type="file" class="form-control" id="arquivo_subpasta" name="arquivo" accept=".pdf,image/jpeg,image/png,image/gif">
                                    <small class="text-muted">Tipos permitidos: PDF, JPG, PNG, GIF</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 mb-3 border rounded">
                                <h6 class="text-secondary"><i class="fas fa-link me-1"></i> Link Externo</h6>
                                <div class="mb-2">
                                    <label for="link_url_subpasta" class="form-label">URL</label>
                                    <input type="url" class="form-control" id="link_url_subpasta" name="link_url" placeholder="Ex: https://www.youtube.com/watch?v=...">
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
                <p>Tem certeza de que deseja <strong>excluir permanentemente</strong> o item: <strong id="recursoTituloModal"></strong>?</p>
                <p class="text-danger small" id="mensagemExclusao">Esta ação é irreversível e removerá o registro (e o arquivo físico, se for um) do servidor.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="linkExcluirRecurso" class="btn btn-danger">Excluir Item</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Modal de exclusão
    $('#modalExcluirRecurso').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var recursoId = button.data('recurso-id');
        var recursoTitulo = button.data('recurso-titulo');
        var modal = $(this);
        modal.find('#recursoTituloModal').text(recursoTitulo);
        modal.find('#linkExcluirRecurso').attr('href', 'gerenciar_arquivos_tema.php?tema_id=<?= $tema_id ?>&excluir=' + recursoId);
    });

    // Modal para adicionar recurso em subpasta
    $('#modalAdicionarRecursoSubpasta').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var subpastaId = button.data('subpasta-id');
        var subpastaTitulo = button.data('subpasta-titulo');
        var modal = $(this);
        modal.find('#subpasta_id').val(subpastaId);
        modal.find('#subpasta_nome').text(subpastaTitulo);
    });

    // Toggle subpasta para mostrar/ocultar arquivos
    $('.tema-toggle').on('click', function(e) {
        if ($(e.target).closest('button').length > 0) return;
        var subpastaId = $(this).data('subpasta-id');
        var $linha = $('#arquivos-subpasta-' + subpastaId);
        $linha.toggle();
    });

    // Validação de Formulário (Arquivo OU Link)
    $('#formRecurso, #formRecursoSubpasta').on('submit', function (e) {
        var form = $(this);
        var arquivoInput = form.find('input[type="file"]')[0];
        var linkUrlInput = form.find('input[type="url"]').val().trim();
        var tituloInput = form.find('input[name="titulo_recurso"]').val().trim();

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
});
</script>
</body>
</html>