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

// --- NOVO: 1. TRATAMENTO DE MENSAGENS DE SESSÃO (PRG) ---
// Verifica se há mensagens de sucesso/erro armazenadas na sessão após um redirecionamento
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $sucesso = $_SESSION['sucesso'] ?? false;
    // Limpa as variáveis de sessão para que a mensagem não reapareça
    unset($_SESSION['mensagem']);
    unset($_SESSION['sucesso']);
}


// --- 2. VALIDAÇÃO DO TEMA (Mantido) ---
if (!isset($_GET['tema_id']) || !is_numeric($_GET['tema_id'])) {
    header("Location: gerenciar_conteudos.php");
    exit;
}

$tema_id = (int)$_GET['tema_id'];

// Busca as informações do TEMA para exibição e validação
$sql_tema = "SELECT id, titulo FROM conteudos WHERE id = :id AND professor_id = :professor_id AND parent_id IS NULL";
$stmt_tema = $pdo->prepare($sql_tema);
$stmt_tema->execute([':id' => $tema_id, ':professor_id' => $professor_id]);
$tema_info = $stmt_tema->fetch(PDO::FETCH_ASSOC);

if (!$tema_info) {
    // Mensagem é definida, mas o tema_id é zerado para evitar operações
    $mensagem = "Tema não encontrado ou você não tem permissão para gerenciá-lo.";
    $sucesso = false;
    $tema_id = 0; 
}


// --- NOVO: 3. LÓGICA UNIFICADA DE CADASTRO (ARQUIVO OU LINK) ---
if ($tema_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'upload_recurso') {
    
    $titulo_recurso = trim($_POST['titulo_recurso']);
    $link_url = trim($_POST['link_url'] ?? '');
    
    $is_file_upload = (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK);
    $is_link_provided = !empty($link_url);

    // Validação: Pelo menos o título e um recurso (arquivo ou link) devem ser fornecidos
    if (empty($titulo_recurso) || (!$is_file_upload && !$is_link_provided)) {
        $_SESSION['mensagem'] = "Por favor, preencha o Título e envie um Arquivo ou forneça um Link.";
        $_SESSION['sucesso'] = false;
        header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
        exit;
    }
    
    $caminho_arquivo_bd = null;
    $tipo_arquivo = null;

    if ($is_file_upload) {
        // --- Processamento de Arquivo ---
        $upload_dir = '../uploads/conteudos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $nome_arquivo_original = basename($_FILES['arquivo']['name']);
        $extensao = strtolower(pathinfo($nome_arquivo_original, PATHINFO_EXTENSION));
        $novo_nome = time() . '_' . uniqid() . '.' . $extensao;
        $caminho_destino = $upload_dir . $novo_nome;

        // Filtro de tipos permitidos: PDF, Imagens
        $allowed_mime_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
        $uploaded_mime_type = mime_content_type($_FILES['arquivo']['tmp_name']); // Usa função para obter MIME Type real

        if (!in_array($uploaded_mime_type, $allowed_mime_types)) {
            $_SESSION['mensagem'] = "Tipo de arquivo não permitido. Apenas PDF e Imagens (JPG, PNG, GIF).";
            $_SESSION['sucesso'] = false;
            header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
            exit;
        }

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_destino)) {
            $caminho_arquivo_bd = 'uploads/conteudos/' . $novo_nome; // Caminho relativo para o BD
            $tipo_arquivo = $uploaded_mime_type;
            $descricao_recurso = $titulo_recurso . ' (Arquivo: ' . $nome_arquivo_original . ')';
        } else {
            $_SESSION['mensagem'] = "Erro ao mover o arquivo. Tente novamente.";
            $_SESSION['sucesso'] = false;
            header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
            exit;
        }

    } elseif ($is_link_provided) {
        // --- Processamento de Link ---
        if (!filter_var($link_url, FILTER_VALIDATE_URL)) {
             $_SESSION['mensagem'] = "O link fornecido não é uma URL válida.";
             $_SESSION['sucesso'] = false;
             header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
             exit;
        }
        
        $caminho_arquivo_bd = $link_url;
        $tipo_arquivo = 'URL'; // Novo tipo para links
        $descricao_recurso = $titulo_recurso . ' (Link: ' . parse_url($link_url, PHP_URL_HOST) . ')';
    }


    try {
        // Insere o Recurso como filho do Tema (parent_id = tema_id)
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
            // SUCESSO! Armazena a mensagem na sessão e REDIRECIONA
            $tipo_msg = $is_file_upload ? "Arquivo" : "Link";
            $_SESSION['mensagem'] = $tipo_msg . " **" . htmlspecialchars($titulo_recurso) . "** enviado e vinculado ao tema com sucesso!";
            $_SESSION['sucesso'] = true;
        } else {
            // ERRO BD!
            $_SESSION['mensagem'] = "Erro ao vincular recurso ao banco de dados.";
            $_SESSION['sucesso'] = false;
            // Se foi um upload de arquivo, tenta reverter (excluir o arquivo que foi movido)
            if ($is_file_upload && file_exists('../' . $caminho_arquivo_bd)) {
                unlink('../' . $caminho_arquivo_bd);
            }
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = "Erro de BD: " . $e->getMessage();
        $_SESSION['sucesso'] = false;
    }
    
    // REDIRECIONAMENTO FINAL após qualquer tentativa (PRG)
    header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
    exit;
}


// --- 4. LÓGICA DE EXCLUSÃO DE RECURSO (Mantido com Ajustes) ---
if ($tema_id > 0 && isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $recurso_id = $_GET['excluir'];
    try {
        // Busca o caminho e o tipo do recurso para exclusão e garante permissão
        $sql_caminho = "SELECT caminho_arquivo, tipo_arquivo FROM conteudos 
                         WHERE id = :id AND professor_id = :professor_id AND parent_id = :parent_id";
        $stmt_caminho = $pdo->prepare($sql_caminho);
        $stmt_caminho->execute([':id' => $recurso_id, ':professor_id' => $professor_id, ':parent_id' => $tema_id]);
        $recurso_info = $stmt_caminho->fetch(PDO::FETCH_ASSOC);

        // Exclui o registro do conteúdo (recurso)
        $sql_delete = "DELETE FROM conteudos 
                        WHERE id = :id AND professor_id = :professor_id AND parent_id = :parent_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        
        if ($stmt_delete->execute([':id' => $recurso_id, ':professor_id' => $professor_id, ':parent_id' => $tema_id])) {
            
            // SE FOR ARQUIVO (NÃO FOR LINK), tenta excluir o arquivo físico
            if ($recurso_info && $recurso_info['caminho_arquivo'] && $recurso_info['tipo_arquivo'] !== 'URL') {
                $caminho_completo = '../' . $recurso_info['caminho_arquivo'];
                if (file_exists($caminho_completo) && !is_dir($caminho_completo)) {
                    unlink($caminho_completo);
                }
            }
            
            // SUCESSO! Armazena a mensagem na sessão e REDIRECIONA
            $_SESSION['mensagem'] = "Recurso excluído com sucesso!";
            $_SESSION['sucesso'] = true;
        } else {
            // ERRO BD!
            $_SESSION['mensagem'] = "Erro ao excluir recurso ou recurso não encontrado.";
            $_SESSION['sucesso'] = false;
        }
    } catch (PDOException $e) {
        // ERRO EXCEÇÃO!
        $_SESSION['mensagem'] = "Erro: O recurso pode estar sendo referenciado. (" . $e->getMessage() . ")";
        $_SESSION['sucesso'] = false;
    }
    
    // REDIRECIONAMENTO FINAL após a exclusão (PRG)
    header("Location: gerenciar_arquivos_tema.php?tema_id=" . $tema_id);
    exit;
}


// --- 5. BUSCAR RECURSOS DO TEMA (Mantido) ---
$recursos = [];
if ($tema_id > 0) {
    // Agora selecionamos o caminho_arquivo para URLs também
    $sql_recursos = "SELECT id, titulo, tipo_arquivo, caminho_arquivo, data_upload 
                     FROM conteudos 
                     WHERE parent_id = :tema_id AND professor_id = :professor_id 
                     ORDER BY data_upload DESC";
    $stmt_recursos = $pdo->prepare($sql_recursos);
    $stmt_recursos->execute([':tema_id' => $tema_id, ':professor_id' => $professor_id]);
    $recursos = $stmt_recursos->fetchAll(PDO::FETCH_ASSOC);
}

// Função auxiliar para mostrar o ícone correto
function get_file_icon($mime_type, $caminho_arquivo = null) {
    // 1. Tratamento para Links (URL)
    if ($mime_type === 'URL') {
        if ($caminho_arquivo && (strpos($caminho_arquivo, 'youtube.com') !== false || strpos($caminho_arquivo, 'youtu.be') !== false)) {
            return 'fab fa-youtube text-danger';
        }
        return 'fas fa-link text-info';
    }
    
    // 2. Tratamento para Arquivos
    if (strpos($mime_type, 'image/') !== false) return 'fas fa-image text-success';
    if (strpos($mime_type, 'pdf') !== false) return 'fas fa-file-pdf text-danger';
    
    // Tipos genéricos (mantidos por segurança, caso outros tipos sejam permitidos no futuro)
    if (strpos($mime_type, 'word') !== false || strpos($mime_type, 'document') !== false) return 'fas fa-file-word';
    if (strpos($mime_type, 'audio/') !== false) return 'fas fa-file-audio';
    if (strpos($mime_type, 'video/') !== false) return 'fas fa-file-video';
    
    return 'fas fa-file text-secondary';
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Arquivos: <?= htmlspecialchars($tema_info['titulo'] ?? 'Tema Inválido') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/professor/gerenciar_conteudos.css">
    <style>
        /* Mantém a paleta de cores original do gerenciar_conteudos.css (se existir) ou define padrões */
        :root {
            --cor-primaria: #0A1931; /* Marinho Escuro */
            --cor-secundaria: #B91D23; /* Vermelho Escuro */
            --cor-fundo: #F5F5DC; /* Creme/Bege */
        }
        .main-content { padding: 30px; }
        .card-header-custom {
            background-color: var(--cor-primaria);
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt me-2"></i>Aulas</a>
        <a href="gerenciar_conteudos.php" style="background-color: #92171B;"><i class="fas fa-book-open me-2"></i>Conteúdos</a>
        <a href="gerenciar_alunos.php"><i class="fas fa-users me-2"></i> Alunos/Turmas</a>
        <a href="../logout.php" class="link-sair"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciamento de Recursos</h1>
        
        <?php if ($tema_info): ?>
            <p class="lead">Recursos (Arquivos e Links) do Tema: <strong style="color: var(--cor-secundaria);"><?= htmlspecialchars($tema_info['titulo']) ?></strong></p>
            <a href="gerenciar_conteudos.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-1"></i> Voltar para Temas</a>
        <?php endif; ?>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($tema_info): ?>
            <div class="row">
                
                <div class="col-lg-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header card-header-custom">
                            <i class="fas fa-plus-circle me-2"></i> Adicionar Novo Recurso (Arquivo ou Link)
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" action="gerenciar_arquivos_tema.php?tema_id=<?= $tema_id ?>" id="formRecurso">
                                <input type="hidden" name="acao" value="upload_recurso">
                                
                                <div class="mb-3">
                                    <label for="titulo_recurso" class="form-label">Título do Recurso (Ex: Vídeo de Pronúncia, PDF de Vocabulário)</label>
                                    <input type="text" class="form-control" id="titulo_recurso" name="titulo_recurso" required>
                                </div>
                                
                                <div class="p-3 mb-3 border rounded">
                                    <h6 class="text-secondary"><i class="fas fa-file-upload me-1"></i> 1. Upload de Arquivo (Opcional)</h6>
                                    <div class="mb-2">
                                        <label for="arquivo" class="form-label">Selecione o Arquivo (PDF ou Imagem)</label>
                                        <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".pdf,image/jpeg,image/png,image/gif">
                                        <small class="text-muted">Tipos permitidos: **PDF**, **JPG**, **PNG**, **GIF**.</small>
                                    </div>
                                </div>

                                <div class="p-3 mb-3 border rounded">
                                    <h6 class="text-secondary"><i class="fas fa-link me-1"></i> 2. Link Externo / URL (Opcional)</h6>
                                    <div class="mb-2">
                                        <label for="link_url" class="form-label">Link (Ex: Vídeo do YouTube, Artigo)</label>
                                        <input type="url" class="form-control" id="link_url" name="link_url" placeholder="Ex: https://www.youtube.com/watch?v=...">
                                        <small class="text-muted">Se fornecido, este recurso será salvo como um link, e não como um arquivo.</small>
                                    </div>
                                </div>

                                <div class="alert alert-info small" role="alert">
                                    <i class="fas fa-info-circle me-1"></i> Você deve fornecer **um arquivo OU um link**, ou ambos. O campo **Título é obrigatório**.
                                </div>
                                
                                <button type="submit" class="btn text-white" style="background-color: var(--cor-secundaria);">
                                    <i class="fas fa-save me-2"></i> Salvar Recurso
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-header card-header-custom">
                            <i class="fas fa-list-ul me-2"></i> Recursos Vinculados (Total: <?= count($recursos) ?>)
                        </div>
                        <div class="card-body">
                            <?php if (empty($recursos)): ?>
                                <p class="text-center text-muted">Nenhum recurso (arquivo ou link) anexado a este tema ainda.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($recursos as $r): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="<?= get_file_icon($r['tipo_arquivo'], $r['caminho_arquivo']) ?> fa-lg me-2"></i> 
                                                <strong><?= htmlspecialchars($r['titulo']) ?></strong>
                                                <p class="text-muted mb-0" style="font-size: 0.8em;">
                                                    <i class="fas fa-tag"></i> Tipo: 
                                                    <?= ($r['tipo_arquivo'] === 'URL') 
                                                        ? 'Link Externo (' . htmlspecialchars(parse_url($r['caminho_arquivo'], PHP_URL_HOST) ?? 'URL Inválida') . ')'
                                                        : htmlspecialchars($r['tipo_arquivo']) ?> 
                                                    | Upload/Registro: <?= date('d/m/Y', strtotime($r['data_upload'])) ?>
                                                </p>
                                            </div>
                                            <div class="btn-group" role="group">
                                                <?php 
                                                    $href = ($r['tipo_arquivo'] === 'URL') 
                                                        ? htmlspecialchars($r['caminho_arquivo']) 
                                                        : '../' . htmlspecialchars($r['caminho_arquivo']);
                                                    $text_button = ($r['tipo_arquivo'] === 'URL') ? 'Acessar Link' : 'Visualizar Arquivo';
                                                ?>
                                                <?php if ($r['caminho_arquivo']): ?>
                                                    <a href="<?= $href ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="<?= $text_button ?>">
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
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        <?php else: ?>
             <div class="alert alert-danger">
                 Não foi possível carregar os recursos. O tema especificado não é válido.
             </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalExcluirRecurso" tabindex="-1" aria-labelledby="modalExcluirRecursoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalExcluirRecursoLabel">Confirmar Exclusão do Recurso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja **excluir permanentemente** o recurso: <strong id="recursoTituloModal"></strong>?</p>
                <p class="text-danger small">Esta ação é irreversível e removerá o registro (e o arquivo físico, se for um) do servidor.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="linkExcluirRecurso" class="btn btn-danger">Excluir Recurso</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalExcluir = document.getElementById('modalExcluirRecurso');
    var temaId = <?= $tema_id ?>; 
    
    // Script para preencher o ID e Título no modal de exclusão
    modalExcluir.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var recursoId = button.getAttribute('data-recurso-id');
        var recursoTitulo = button.getAttribute('data-recurso-titulo');
        
        var linkExcluir = modalExcluir.querySelector('#linkExcluirRecurso');
        var modalTitle = modalExcluir.querySelector('#recursoTituloModal');
        
        modalTitle.textContent = recursoTitulo;
        // Monta o link de exclusão com o ID do recurso e o tema_id
        linkExcluir.href = 'gerenciar_arquivos_tema.php?tema_id=' + temaId + '&excluir=' + recursoId;
    });

    // --- NOVO SCRIPT: Validação de Formulário (Garantir que Arquivo OU Link seja enviado) ---
    const form = document.getElementById('formRecurso');
    const arquivoInput = document.getElementById('arquivo');
    const linkUrlInput = document.getElementById('link_url');
    const tituloInput = document.getElementById('titulo_recurso');

    form.addEventListener('submit', function (e) {
        let isFileProvided = arquivoInput.files.length > 0;
        let isLinkProvided = linkUrlInput.value.trim() !== '';
        let isTituloProvided = tituloInput.value.trim() !== '';

        if (!isTituloProvided) {
            alert('O Título do Recurso é obrigatório.');
            e.preventDefault();
            return false;
        }

        if (!isFileProvided && !isLinkProvided) {
            alert('Você deve fornecer um Arquivo OU um Link Externo.');
            e.preventDefault(); // Impede o envio do formulário
            return false;
        }
        // Se a validação passar, o formulário será enviado
    });
});
</script>

</body>
</html>