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

// --- LÓGICA DE CADASTRO/EDIÇÃO DE CONTEÚDO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $acao = $_POST['acao'];
    $conteudo_id = $_POST['conteudo_id'] ?? null;
    
    // Processamento do arquivo (se houver um novo upload)
    $caminho_arquivo = '';
    $tipo_arquivo = '';
    
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/conteudos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $nome_arquivo = basename($_FILES['arquivo']['name']);
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        $novo_nome = time() . '_' . uniqid() . '.' . $extensao;
        $caminho_destino = $upload_dir . $novo_nome;

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_destino)) {
            $caminho_arquivo = 'uploads/conteudos/' . $novo_nome; // Caminho relativo para o BD
            $tipo_arquivo = $_FILES['arquivo']['type'];
        } else {
            $mensagem = "Erro ao mover o arquivo. Tente novamente.";
            $sucesso = false;
        }
    }

    if ($acao === 'cadastrar' && $sucesso !== false) {
        $sql = "INSERT INTO conteudos (professor_id, titulo, descricao, tipo_arquivo, caminho_arquivo, data_upload) 
                VALUES (:professor_id, :titulo, :descricao, :tipo_arquivo, :caminho_arquivo, NOW())";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([
            ':professor_id' => $professor_id,
            ':titulo' => $titulo,
            ':descricao' => $descricao,
            ':tipo_arquivo' => $tipo_arquivo,
            ':caminho_arquivo' => $caminho_arquivo
        ])) {
            $mensagem = "Conteúdo **" . htmlspecialchars($titulo) . "** cadastrado com sucesso!";
            $sucesso = true;
        } else {
            $mensagem = "Erro ao cadastrar conteúdo.";
        }
    } elseif ($acao === 'editar' && $conteudo_id && $sucesso !== false) {
        // Lógica de edição
        $sql = "UPDATE conteudos SET titulo = :titulo, descricao = :descricao";
        $params = [':titulo' => $titulo, ':descricao' => $descricao, ':conteudo_id' => $conteudo_id];
        
        if ($caminho_arquivo) {
            // Se um novo arquivo foi enviado, atualiza o caminho.
            $sql .= ", tipo_arquivo = :tipo_arquivo, caminho_arquivo = :caminho_arquivo";
            $params[':tipo_arquivo'] = $tipo_arquivo;
            $params[':caminho_arquivo'] = $caminho_arquivo;
            
            // Opcional: Adicionar lógica para apagar o arquivo antigo aqui, se necessário.
        }
        
        $sql .= " WHERE id = :conteudo_id AND professor_id = :professor_id";
        $params[':professor_id'] = $professor_id;
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $mensagem = "Conteúdo atualizado com sucesso!";
            $sucesso = true;
        } else {
            $mensagem = "Erro ao atualizar conteúdo.";
        }
    }
}

// --- LÓGICA DE EXCLUSÃO ---
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $conteudo_id = $_GET['excluir'];
    try {
        // Remove a associação primeiro (CASCADE deveria cuidar disso se FKs estiverem corretas)
        $sql_delete_assoc = "DELETE FROM aulas_conteudos WHERE conteudo_id = :id";
        $stmt_assoc = $pdo->prepare($sql_delete_assoc);
        $stmt_assoc->execute([':id' => $conteudo_id]);

        // Busca o caminho do arquivo para exclusão
        $sql_caminho = "SELECT caminho_arquivo FROM conteudos WHERE id = :id AND professor_id = :professor_id";
        $stmt_caminho = $pdo->prepare($sql_caminho);
        $stmt_caminho->execute([':id' => $conteudo_id, ':professor_id' => $professor_id]);
        $conteudo_info = $stmt_caminho->fetch(PDO::FETCH_ASSOC);

        // Exclui o registro do conteúdo
        $sql_delete = "DELETE FROM conteudos WHERE id = :id AND professor_id = :professor_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        
        if ($stmt_delete->execute([':id' => $conteudo_id, ':professor_id' => $professor_id])) {
            // Se a exclusão no BD foi OK, tenta excluir o arquivo físico
            if ($conteudo_info && $conteudo_info['caminho_arquivo']) {
                $caminho_completo = '../' . $conteudo_info['caminho_arquivo'];
                if (file_exists($caminho_completo) && !is_dir($caminho_completo)) {
                    unlink($caminho_completo);
                }
            }
            $mensagem = "Conteúdo excluído com sucesso!";
            $sucesso = true;
        } else {
            $mensagem = "Erro ao excluir conteúdo.";
            $sucesso = false;
        }
    } catch (PDOException $e) {
        $mensagem = "Erro: O conteúdo pode estar associado a uma aula ativa. Exclua a aula primeiro. (" . $e->getMessage() . ")";
        $sucesso = false;
    }
}

// --- LÓGICA PARA CARREGAR CONTEÚDO PARA EDIÇÃO ---
$conteudo_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $conteudo_id = $_GET['editar'];
    $sql_edit = "SELECT * FROM conteudos WHERE id = :id AND professor_id = :professor_id";
    $stmt_edit = $pdo->prepare($sql_edit);
    $stmt_edit->execute([':id' => $conteudo_id, ':professor_id' => $professor_id]);
    $conteudo_editar = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    if (!$conteudo_editar) {
        $mensagem = "Conteúdo não encontrado ou você não tem permissão para editar.";
    }
}


// --- BUSCAR TODOS OS CONTEÚDOS DO PROFESSOR ---
$sql_conteudos = "SELECT * FROM conteudos WHERE professor_id = :professor_id ORDER BY data_upload DESC";
$stmt_conteudos = $pdo->prepare($sql_conteudos);
$stmt_conteudos->execute([':professor_id' => $professor_id]);
$conteudos = $stmt_conteudos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Conteúdos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/professor/gerenciar_conteudos.css">
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard (Agenda)</a>
        <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt me-2"></i> Agendar/Gerenciar Aulas</a>
        <a href="gerenciar_conteudos.php" style="background-color: #92171B;"><i class="fas fa-book-open me-2"></i> **Conteúdos (Biblioteca)**</a>
        <a href="gerenciar_alunos.php"><i class="fas fa-users me-2"></i> Alunos/Turmas</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciamento de Conteúdos</h1>
        <p class="lead">Aqui você pode adicionar, visualizar e gerenciar os materiais que serão vinculados às suas aulas.</p>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            
            <div class="col-lg-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header card-header-custom">
                        <?= $conteudo_editar ? 'Editar Conteúdo: ' . htmlspecialchars($conteudo_editar['titulo']) : 'Adicionar Novo Conteúdo' ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" action="gerenciar_conteudos.php">
                            <?php if ($conteudo_editar): ?>
                                <input type="hidden" name="acao" value="editar">
                                <input type="hidden" name="conteudo_id" value="<?= $conteudo_editar['id'] ?>">
                            <?php else: ?>
                                <input type="hidden" name="acao" value="cadastrar">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título do Conteúdo</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($conteudo_editar['titulo'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição (Opcional)</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="2"><?= htmlspecialchars($conteudo_editar['descricao'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="arquivo" class="form-label">Arquivo (.pdf, .ppt, .doc, etc.) ou Link</label>
                                <input type="file" class="form-control" id="arquivo" name="arquivo" <?= $conteudo_editar ? '' : 'required' ?>>
                                <?php if ($conteudo_editar && $conteudo_editar['caminho_arquivo']): ?>
                                    <small class="text-muted mt-2 d-block">Arquivo atual: <a href="../<?= htmlspecialchars($conteudo_editar['caminho_arquivo']) ?>" target="_blank">Abrir Arquivo</a>. Envie um novo para substituir.</small>
                                <?php elseif ($conteudo_editar): ?>
                                    <small class="text-muted mt-2 d-block">Nenhum arquivo anexado. Envie agora.</small>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas <?= $conteudo_editar ? 'fa-save' : 'fa-plus-circle' ?> me-2"></i> 
                                <?= $conteudo_editar ? 'Salvar Edição' : 'Adicionar Conteúdo' ?>
                            </button>
                            <?php if ($conteudo_editar): ?>
                                <a href="gerenciar_conteudos.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar Edição
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header card-header-custom">
                        Biblioteca de Materiais (Total: <?= count($conteudos) ?>)
                    </div>
                    <div class="card-body">
                        <?php if (empty($conteudos)): ?>
                            <p class="text-center text-muted">Nenhum conteúdo cadastrado ainda.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($conteudos as $c): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($c['titulo']) ?></strong>
                                            <p class="text-muted mb-0" style="font-size: 0.9em;">
                                                <i class="fas fa-file-alt me-1"></i> Tipo: <?= htmlspecialchars(empty($c['tipo_arquivo']) ? 'Link/Não especificado' : $c['tipo_arquivo']) ?>
                                            </p>
                                        </div>
                                        <div class="btn-group" role="group">
                                            <?php if ($c['caminho_arquivo']): ?>
                                                <a href="../<?= htmlspecialchars($c['caminho_arquivo']) ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Visualizar Arquivo">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?editar=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar Conteúdo">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirConteudo" data-conteudo-titulo="<?= htmlspecialchars($c['titulo']) ?>" data-conteudo-id="<?= $c['id'] ?>" title="Excluir Conteúdo">
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
    </div>
</div>

<div class="modal fade" id="modalExcluirConteudo" tabindex="-1" aria-labelledby="modalExcluirConteudoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalExcluirConteudoLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja **excluir permanentemente** o conteúdo: <strong id="conteudoTituloModal"></strong>?</p>
                <p class="text-danger small">Esta ação é irreversível e removerá o arquivo físico.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="linkExcluir" class="btn btn-danger">Excluir Conteúdo</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Script para preencher o ID e Título no modal de exclusão
document.addEventListener('DOMContentLoaded', function () {
    var modalExcluir = document.getElementById('modalExcluirConteudo');
    modalExcluir.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var conteudoId = button.getAttribute('data-conteudo-id');
        var conteudoTitulo = button.getAttribute('data-conteudo-titulo');
        
        var linkExcluir = modalExcluir.querySelector('#linkExcluir');
        var modalTitle = modalExcluir.querySelector('#conteudoTituloModal');
        
        modalTitle.textContent = conteudoTitulo;
        linkExcluir.href = 'gerenciar_conteudos.php?excluir=' + conteudoId;
    });
});
</script>

</body>
</html>