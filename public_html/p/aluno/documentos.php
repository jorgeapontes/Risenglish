<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Garante que apenas aluno acessa esta página
if ($_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login?erro=acesso_negado");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/aluno/documentos.css">
</head>
<body>

<div class="container-fluid p-0">
    
    <?php
    $paginaAtiva = 'documentos';
    $tituloMobile = 'Documentos';
    require '../includes/layout/aluno_sidebar.php';
    ?>

    <div class="row g-0">
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
                        $ext = pathinfo($doc['nome_arquivo'], PATHINFO_EXTENSION);
                        $icon = 'fa-file-alt';
                        if(in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
                        if(in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                        if(in_array($ext, ['jpg', 'png', 'jpeg'])) $icon = 'fa-file-image';
                        if(in_array($ext, ['txt'])) $icon = 'fa-file-alt';
                        if(in_array($ext, ['xls', 'xlsx'])) $icon = 'fa-file-excel';
                        if(in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
                        if(in_array($ext, ['zip', 'rar'])) $icon = 'fa-file-archive';
                    ?>
                        <a href="download?id=<?= $doc['id'] ?>" class="doc-item">
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
                &copy; Risenglish
            </footer>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>