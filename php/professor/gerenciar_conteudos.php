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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $acao = $_POST['acao'];
    $conteudo_id = $_POST['conteudo_id'] ?? null;
    
    if ($acao === 'cadastrar') {
        $sql = "INSERT INTO conteudos (professor_id, parent_id, titulo, descricao, tipo_arquivo, caminho_arquivo, data_upload) 
                VALUES (:professor_id, NULL, :titulo, :descricao, 'TEMA', '', NOW())";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([':professor_id' => $professor_id, ':titulo' => $titulo, ':descricao' => $descricao])) {
            $mensagem = "Tema cadastrado com sucesso!";
            $sucesso = true;
        }
    } elseif ($acao === 'editar' && $conteudo_id) {
        $sql = "UPDATE conteudos SET titulo = :titulo, descricao = :descricao WHERE id = :id AND professor_id = :professor_id";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([':titulo' => $titulo, ':descricao' => $descricao, ':id' => $conteudo_id, ':professor_id' => $professor_id])) {
            $mensagem = "Tema atualizado com sucesso!";
            $sucesso = true;
        }
    }
}

// --- LÓGICA DE EXCLUSÃO ---
if (isset($_GET['excluir'])) {
    $id_excluir = $_GET['excluir'];
    $sql = "DELETE FROM conteudos WHERE id = :id AND professor_id = :professor_id";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([':id' => $id_excluir, ':professor_id' => $professor_id])) {
        $mensagem = "Tema excluído com sucesso!";
        $sucesso = true;
    }
}

// --- CONSULTA DOS TEMAS ---
$sql_temas = "SELECT c.*, u.nome as nome_professor FROM conteudos c JOIN usuarios u ON c.professor_id = u.id WHERE c.parent_id IS NULL AND c.tipo_arquivo = 'TEMA' ORDER BY c.titulo ASC";
$temas = $pdo->query($sql_temas)->fetchAll(PDO::FETCH_ASSOC);
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
        .biblioteca-container { max-width: 900px; margin-left: 0; }
        .card-header { background-color: #081d40; color: white; }
        
        /* Ícone de pasta Azul Risenglish */
        .fa-folder { color: #081d40 !important; } 
        
        /* Estilo customizado para o botão de adicionar */
        .btn-custom-dark {
            background-color: #081d40;
            color: white;
            border: none;
        }
        .btn-custom-dark:hover {
            background-color: #0c2a5c;
            color: white;
        }

        .list-group-item { padding: 0.6rem 1rem; cursor: pointer; transition: background-color 0.2s; }
        .list-group-item:hover { background-color: #f1f3f5; }
        
        #formNovoConteudo { display: none; } /* Oculto por padrão */
        
        @media (max-width: 768px) { .sidebar { position: relative; width: 100%; height: auto; } .main-content { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center"><h5 class="mt-4">Prof. <?= $_SESSION['user_nome'] ?></h5></div>
                <div class="d-flex flex-column flex-grow-1 mb-5">
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

                <button class="btn btn-custom-dark mb-3 shadow-sm" onclick="toggleForm()">
                    <i class="fas fa-folder-plus me-2"></i> Adicionar Novo Conteúdo
                </button>

                <div id="formNovoConteudo" class="card rounded biblioteca-container mb-4 shadow-sm">
                    <div class="card-body">
                        <form action="gerenciar_conteudos.php" method="POST">
                            <input type="hidden" name="acao" value="cadastrar">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Título do Tema</label>
                                    <input type="text" class="form-control" name="titulo" required >
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Descrição</label>
                                    <input type="text" class="form-control" name="descricao" placeholder="Opcional">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success w-100">Criar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card rounded biblioteca-container shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Lista de Conteúdos</span>
                        <span class="badge bg-light text-dark"><?= count($temas) ?> itens</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($temas)): ?>
                            <p class="p-4 text-center text-muted">Nenhum tema cadastrado.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php 
                                $contador = 1;
                                foreach ($temas as $tema): 
                                    $is_owner = ($tema['professor_id'] == $professor_id);
                                ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="w-100" onclick="window.location.href='gerenciar_arquivos_tema.php?tema_id=<?= $tema['id'] ?>'">
                                            <div>
                                                <span class="text-muted fw-bold me-2"><?= $contador ?>.</span>
                                                <strong class="text-dark"><i class="fas fa-folder me-2"></i> <?= htmlspecialchars($tema['titulo']) ?></strong>
                                                <span class="badge bg-secondary ms-2" style="font-size: 0.7em;"><?= htmlspecialchars($tema['nome_professor']) ?></span>
                                            </div>
                                            <small class="text-muted ms-4"><?= htmlspecialchars($tema['descricao'] ?: 'Sem descrição.') ?></small>
                                        </div>
                                        <div class="actions d-flex">
                                            <?php if ($is_owner): ?>
                                                <button class="btn btn-sm btn-link text-primary me-2" data-bs-toggle="modal" data-bs-target="#modalEditarTema" 
                                                        data-id="<?= $tema['id'] ?>" data-titulo="<?= htmlspecialchars($tema['titulo']) ?>" data-descricao="<?= htmlspecialchars($tema['descricao']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-link text-danger" onclick="confirmarExclusao(<?= $tema['id'] ?>, '<?= htmlspecialchars($tema['titulo']) ?>')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php $contador++; endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarTema" tabindex="-1">
        <div class="modal-dialog">
            <form action="gerenciar_conteudos.php" method="POST" class="modal-content">
                <div class="modal-header bg-dark text-white"><h5 class="modal-title">Editar Tema</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="conteudo_id" id="edit-conteudo-id">
                    <div class="mb-3"><label class="form-label">Título</label><input type="text" class="form-control" name="titulo" id="edit-titulo" required></div>
                    <div class="mb-3"><label class="form-label">Descrição</label><input type="text" class="form-control" name="descricao" id="edit-descricao"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-danger">Salvar Alterações</button></div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleForm() {
            var form = document.getElementById('formNovoConteudo');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }

        function confirmarExclusao(id, titulo) {
            if (confirm("Deseja realmente excluir o tema '" + titulo + "'?")) {
                window.location.href = 'gerenciar_conteudos.php?excluir=' + id;
            }
        }

        var modalEditar = document.getElementById('modalEditarTema');
        modalEditar.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('edit-conteudo-id').value = button.getAttribute('data-id');
            document.getElementById('edit-titulo').value = button.getAttribute('data-titulo');
            document.getElementById('edit-descricao').value = button.getAttribute('data-descricao');
        });
    </script>
</body>
</html>