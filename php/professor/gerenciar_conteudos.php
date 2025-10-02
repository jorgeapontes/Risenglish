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

// --- LÓGICA DE CADASTRO/EDIÇÃO DE TEMA ---
// Esta lógica trata o envio do formulário, seja o de "Criar Novo Tema" ou o do Modal de "Editar Tema".
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $acao = $_POST['acao'];
    $conteudo_id = $_POST['conteudo_id'] ?? null;
    
    // Tema não tem arquivo, parent_id é NULL
    
    if ($acao === 'cadastrar') {
        // CORRIGIDO NOVAMENTE: tipo_arquivo foi definido como 'TEMA'.
        // CORRIGIDO NOVAMENTE: caminho_arquivo não pode ser NULL, foi definido como string vazia ''.
        $sql = "INSERT INTO conteudos (professor_id, parent_id, titulo, descricao, tipo_arquivo, caminho_arquivo, data_upload) 
                 VALUES (:professor_id, NULL, :titulo, :descricao, 'TEMA', '', NOW())";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([
            ':professor_id' => $professor_id,
            ':titulo' => $titulo,
            ':descricao' => $descricao
        ])) {
            $mensagem = "Tema **" . htmlspecialchars($titulo) . "** cadastrado com sucesso! Agora adicione arquivos a ele.";
            $sucesso = true;
        } else {
            $mensagem = "Erro ao cadastrar tema.";
        }
    } elseif ($acao === 'editar' && $conteudo_id) {
        // Lógica de edição para o Tema (garantindo que parent_id seja NULL)
        $sql = "UPDATE conteudos SET titulo = :titulo, descricao = :descricao WHERE id = :conteudo_id AND professor_id = :professor_id AND parent_id IS NULL";
        $params = [':titulo' => $titulo, ':descricao' => $descricao, ':conteudo_id' => $conteudo_id, ':professor_id' => $professor_id];
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $mensagem = "Tema atualizado com sucesso!";
            $sucesso = true;
        } else {
            $mensagem = "Erro ao atualizar tema. Verifique se é um Tema e se você tem permissão.";
        }
    }
}

// --- LÓGICA DE EXCLUSÃO (Deleta Tema, todos os Recursos e arquivos físicos) ---
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $conteudo_id = $_GET['excluir'];
    try {
        // 1. Encontra e exclui os arquivos físicos dos RECURSOS (filhos do tema)
        $sql_filhos = "SELECT caminho_arquivo FROM conteudos WHERE parent_id = :id AND professor_id = :professor_id";
        $stmt_filhos = $pdo->prepare($sql_filhos);
        $stmt_filhos->execute([':id' => $conteudo_id, ':professor_id' => $professor_id]);
        $recursos_filhos = $stmt_filhos->fetchAll(PDO::FETCH_COLUMN);

        foreach ($recursos_filhos as $caminho) {
            if (!empty($caminho)) {
                $caminho_completo = '../' . $caminho;
                if (file_exists($caminho_completo) && !is_dir($caminho_completo)) {
                    unlink($caminho_completo);
                }
            }
        }
        
        // 2. Remove os registros dos RECURSOS (filhos)
        $sql_delete_filhos = "DELETE FROM conteudos WHERE parent_id = :id AND professor_id = :professor_id";
        $stmt_delete_filhos = $pdo->prepare($sql_delete_filhos);
        $stmt_delete_filhos->execute([':id' => $conteudo_id, ':professor_id' => $professor_id]);


        // 3. Remove a associação do TEMA com AULAS (na tabela aulas_conteudos)
        $sql_delete_assoc = "DELETE FROM aulas_conteudos WHERE conteudo_id = :id";
        $stmt_assoc = $pdo->prepare($sql_delete_assoc);
        $stmt_assoc->execute([':id' => $conteudo_id]);

        // 4. Exclui o registro do TEMA principal
        $sql_delete = "DELETE FROM conteudos WHERE id = :id AND professor_id = :professor_id AND parent_id IS NULL"; 
        $stmt_delete = $pdo->prepare($sql_delete);
        
        if ($stmt_delete->execute([':id' => $conteudo_id, ':professor_id' => $professor_id])) {
            $mensagem = "Tema e todos os seus arquivos associados foram excluídos com sucesso!";
            $sucesso = true;
        } else {
            $mensagem = "Erro ao excluir tema.";
            $sucesso = false;
        }
        
    } catch (PDOException $e) {
        $mensagem = "Erro: O tema pode estar associado a uma aula ativa. Exclua a aula primeiro. (" . $e->getMessage() . ")";
        $sucesso = false;
    }
}


// --- LÓGICA PARA CARREGAR TEMA PARA EDIÇÃO (REMOVIDA) ---
// O carregamento do tema para edição via GET foi removido, pois usaremos o modal.


// --- BUSCAR APENAS OS TEMAS PRINCIPAIS DO PROFESSOR (parent_id IS NULL) ---
$sql_temas = "SELECT id, titulo, descricao, data_upload, 
             (SELECT COUNT(id) FROM conteudos AS c2 WHERE c2.parent_id = c1.id) AS total_recursos
             FROM conteudos AS c1
             WHERE professor_id = :professor_id AND parent_id IS NULL 
             ORDER BY data_upload DESC";
$stmt_temas = $pdo->prepare($sql_temas);
$stmt_temas->execute([':professor_id' => $professor_id]);
$temas = $stmt_temas->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Temas e Conteúdos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/professor/gerenciar_conteudos.css">
    <style>
        /* CSS Adicional para os novos botões */
        :root {
              --cor-primaria: #0A1931; /* Marinho Escuro */
              --cor-secundaria: #B91D23; /* Vermelho Escuro */
              --cor-fundo: #F5F5DC; /* Creme/Bege */
        }
        .btn-gerenciar-arquivos {
            background-color: var(--cor-secundaria);
            color: white;
            border: none;
        }
        .btn-gerenciar-arquivos:hover {
            background-color: #92171B;
            color: white;
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
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciamento de Temas</h1>
        <p class="lead">Crie temas principais e organize seus materiais (arquivos) dentro deles.</p>
        
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
                        Adicionar Novo Tema
                    </div>
                    <div class="card-body">
                        <form method="POST" action="gerenciar_conteudos.php">
                            <input type="hidden" name="acao" value="cadastrar">

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título do Tema</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição do Tema (Opcional)</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i> Criar Novo Tema
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header card-header-custom">
                        Biblioteca de Temas (Total: <?= count($temas) ?>)
                    </div>
                    <div class="card-body">
                        <?php if (empty($temas)): ?>
                            <p class="text-center text-muted">Nenhum tema cadastrado ainda.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($temas as $tema): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><i class="fas fa-folder me-2"></i> <?= htmlspecialchars($tema['titulo']) ?></strong>
                                            <p class="text-muted mb-0" style="font-size: 0.9em;">
                                                <?= htmlspecialchars($tema['descricao'] ?? 'Sem descrição.') ?>
                                            </p>
                                            <span class="badge bg-secondary"><?= $tema['total_recursos'] ?> Arquivo(s)</span>
                                        </div>
                                        <div class="btn-group" role="group">
                                            <a href="gerenciar_arquivos_tema.php?tema_id=<?= $tema['id'] ?>" class="btn btn-sm btn-gerenciar-arquivos" title="Gerenciar Arquivos do Tema">
                                                <i class="fas fa-file-upload me-1"></i> Gerenciar Arquivos
                                            </a>
                                            
                                            <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarTema" 
                                                data-id="<?= $tema['id'] ?>" 
                                                data-titulo="<?= htmlspecialchars($tema['titulo']) ?>" 
                                                data-descricao="<?= htmlspecialchars($tema['descricao']) ?>"
                                                title="Editar Tema">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirConteudo" data-conteudo-titulo="<?= htmlspecialchars($tema['titulo']) ?>" data-conteudo-id="<?= $tema['id'] ?>" title="Excluir Tema (e seus arquivos)">
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

<div class="modal fade" id="modalEditarTema" tabindex="-1" aria-labelledby="modalEditarTemaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="gerenciar_conteudos.php">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalEditarTemaLabel">Editar Tema</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="conteudo_id" id="edit-conteudo-id">
                    
                    <div class="mb-3">
                        <label for="edit-titulo" class="form-label">Título do Tema</label>
                        <input type="text" class="form-control" id="edit-titulo" name="titulo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-descricao" class="form-label">Descrição do Tema (Opcional)</label>
                        <textarea class="form-control" id="edit-descricao" name="descricao" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExcluirConteudo" tabindex="-1" aria-labelledby="modalExcluirConteudoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalExcluirConteudoLabel">Confirmar Exclusão do Tema</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja **excluir permanentemente** o tema: <strong id="conteudoTituloModal"></strong>?</p>
                <p class="text-danger small">Esta ação é irreversível e removerá todos os arquivos e o próprio tema do sistema!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="linkExcluir" class="btn btn-danger">Excluir Tema</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Script para preencher o ID e Título no modal de EXCLUSÃO
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

    // 2. Script para preencher os dados no modal de EDIÇÃO
    var modalEditar = document.getElementById('modalEditarTema');
    modalEditar.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        // Pega os dados armazenados nos data-atributos do botão
        var temaId = button.getAttribute('data-id');
        var temaTitulo = button.getAttribute('data-titulo');
        var temaDescricao = button.getAttribute('data-descricao');
        
        // Seleciona e preenche os campos do formulário no modal
        var inputId = modalEditar.querySelector('#edit-conteudo-id');
        var inputTitulo = modalEditar.querySelector('#edit-titulo');
        var inputDescricao = modalEditar.querySelector('#edit-descricao');
        
        inputId.value = temaId;
        inputTitulo.value = temaTitulo;
        inputDescricao.value = temaDescricao;
        
        // Opcional: Atualiza o título do modal
        var modalLabel = modalEditar.querySelector('#modalEditarTemaLabel');
        modalLabel.textContent = 'Editar Tema: ' + temaTitulo;
    });
});
</script>

</body>
</html>