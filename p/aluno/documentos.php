<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-aluno
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login.php");
    exit;
}
date_default_timezone_set('America/Sao_Paulo');

$aluno_id = $_SESSION['user_id'];
$aluno_nome = $_SESSION['user_nome'] ?? 'Aluno';

// ===== BUSCAR NOTIFICAÇÕES NÃO LIDAS =====
$sql_notificacoes = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :aluno_id AND lida = 0";
$stmt_notif = $pdo->prepare($sql_notificacoes);
$stmt_notif->execute([':aluno_id' => $aluno_id]);
$total_notificacoes_nao_lidas = $stmt_notif->fetch(PDO::FETCH_ASSOC)['total'];

// ===== BUSCAR DOCUMENTOS DO ALUNO =====
try {
    $sql = "SELECT id, nome_arquivo, caminho_arquivo, DATE_FORMAT(data_upload, '%d/%m/%Y %H:%i') as data_formatada 
            FROM usuarios_anexos 
            WHERE usuario_id = :aluno_id 
            ORDER BY data_upload DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':aluno_id', $aluno_id, PDO::PARAM_INT);
    $stmt->execute();
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $documentos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Documentos - Risenglish</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">

    <style>
        /* Melhorias no Design do Conteúdo */
        .doc-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .doc-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
            text-decoration: none !important;
            color: inherit;
        }

        .doc-item:last-child { border-bottom: none; }

        .doc-item:hover {
            background-color: #fcfcfc;
            transform: translateX(5px);
        }

        .file-icon-wrapper {
            width: 50px;
            height: 50px;
            background-color: #fdf2f1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: var(--cor-secundaria);
            font-size: 1.5rem;
            transition: 0.3s;
        }

        .doc-item:hover .file-icon-wrapper {
            background-color: var(--cor-secundaria);
            color: white;
        }

        .doc-info { flex-grow: 1; }

        .doc-title {
            display: block;
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--cor-primaria);
            margin-bottom: 2px;
        }

        .doc-meta {
            font-size: 0.85rem;
            color: #888;
        }

        .btn-view {
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 2px solid #eee;
            color: #555;
            transition: 0.3s;
        }

        .doc-item:hover .btn-view {
            background-color: var(--cor-secundaria);
            border-color: var(--cor-secundaria);
            color: white;
        }

        .empty-state {
            padding: 80px 20px;
            text-align: center;
        }

        .empty-icon {
            font-size: 4rem;
            color: #eee;
            margin-bottom: 20px;
        }

        /* Ajustes Mobile */
        @media (max-width: 768px) {
            .doc-item { flex-direction: column; text-align: center; }
            .file-icon-wrapper { margin-right: 0; margin-bottom: 15px; }
            .btn-view { width: 100%; margin-top: 15px; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    
    <header class="d-flex d-md-none mobile-navbar-custom border-bottom shadow-sm p-3 align-items-center sticky-top">
        <button class="btn btn-outline-primary me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
            <i class="fas fa-bars"></i>
        </button>
        <h5 class="mb-0 fw-bold">Documentos</h5>
    </header>

    <div class="offcanvas offcanvas-top text-white mobile-offcanvas" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title fw-bold"><?php echo $aluno_nome; ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <nav class="d-flex flex-column gap-2">
                <a href="dashboard.php"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                <a href="minhas_aulas.php"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;Minhas Aulas</a>
                <a href="documentos.php" class="active"><i class="fas fa-file-alt"></i>&nbsp;&nbsp;Meus Documentos</a>
                <a href="financeiro.php"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Financeiro</a>
            </nav>
            <div class="mt-auto">
                <a href="../logout.php" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
            </div>
        </div>
    </div>

    <div class="row g-0">
        <div class="col-md-2 d-none d-md-flex flex-column sidebar p-3">
            <div class="mb-4 text-center">
                <h5 class="mt-4 fw-bold text-white"><?php echo $aluno_nome; ?></h5>
            </div>
            
            <div class="d-flex flex-column flex-grow-1">
                <a href="dashboard.php"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                <a href="minhas_aulas.php"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;Minhas Aulas</a>
                <a href="documentos.php" class="active"><i class="fas fa-file-alt"></i>&nbsp;&nbsp;Meus Documentos</a>
                <a href="financeiro.php"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Financeiro</a>
            </div>

            <div class="mt-auto">
                <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100 border-0">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </div>
        </div>

        <div class="col-12 col-md-10 main-content p-4">
            <div class="mb-5">
                <h2 class="fw-bold mb-1" style="color: var(--cor-primaria);">Material de Apoio</h2>
                <p class="text-muted">Acesse abaixo os arquivos e documentos compartilhados com você.</p>
            </div>

            <div class="doc-container">
                <?php if (empty($documentos)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                        <h5 class="text-dark fw-bold">Nenhum arquivo por aqui</h5>
                        <p class="text-muted">Quando seus professores enviarem materiais, eles aparecerão nesta lista.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($documentos as $doc): 
                        // Lógica simples para mudar o ícone conforme a extensão
                        $ext = pathinfo($doc['nome_arquivo'], PATHINFO_EXTENSION);
                        $icon = 'fa-file-alt';
                        if(in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
                        if(in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                        if(in_array($ext, ['jpg', 'png', 'jpeg'])) $icon = 'fa-file-image';
                    ?>
                        <a href="../<?= $doc['caminho_arquivo'] ?>" target="_blank" class="doc-item">
                            <div class="file-icon-wrapper">
                                <i class="far <?= $icon ?>"></i>
                            </div>
                            <div class="doc-info">
                                <span class="doc-title"><?= htmlspecialchars($doc['nome_arquivo']) ?></span>
                                <span class="doc-meta">
                                    <i class="far fa-calendar-alt me-1"></i> Enviado em <?= $doc['data_formatada'] ?>
                                </span>
                            </div>
                            <div class="btn-view">
                                <i class="fas fa-download me-2"></i> Acessar
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <footer class="mt-5 text-center text-muted small">
                &copy; <?= date('Y') ?> Risenglish - Learning Management System
            </footer>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>