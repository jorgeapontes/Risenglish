<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// --- LÓGICA DE CRIAÇÃO/EDIÇÃO DE TURMA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && ($_POST['acao'] == 'add_turma' || $_POST['acao'] == 'editar_turma')) {
    $nome_turma = $_POST['nome_turma'];
    $professor_id = $_POST['professor_id']; 
    $link_aula = $_POST['link_aula'] ?? null;
    $turma_id = $_POST['turma_id'] ?? null;
    $acao = $_POST['acao'];
    
    $inicio_turma = date('Y-m-d'); // Data atual como padrão
    
    try {
        if ($acao == 'add_turma') {
            $sql = "INSERT INTO turmas (nome_turma, professor_id, inicio_turma, link_aula) VALUES (:nome_turma, :professor_id, :inicio_turma, :link_aula)";
            $stmt = $pdo->prepare($sql);
            $mensagem = "Turma <strong>{$nome_turma}</strong> criada e associada com sucesso!";
        } else {
            $sql = "UPDATE turmas SET nome_turma = :nome_turma, professor_id = :professor_id, inicio_turma = :inicio_turma, link_aula = :link_aula WHERE id = :turma_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':turma_id', $turma_id);
            $mensagem = "Turma <strong>{$nome_turma}</strong> atualizada com sucesso!";
        }
        
        $stmt->bindParam(':nome_turma', $nome_turma);
        $stmt->bindParam(':professor_id', $professor_id);
        $stmt->bindParam(':inicio_turma', $inicio_turma);
        $stmt->bindParam(':link_aula', $link_aula);
        $stmt->execute();
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $mensagem = "Erro ao gerenciar turma: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA DE REMOÇÃO DE TURMA ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'remover_turma') {
    $id_turma = $_POST['id_turma'];

    try {
        $sql = "DELETE FROM turmas WHERE id = :id_turma";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_turma', $id_turma);
        $stmt->execute();
        $mensagem = "Turma removida com sucesso! (Alunos associados foram removidos da turma)";
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = "Erro ao remover turma: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- CONSULTAS ---
$sql_turmas = "SELECT t.*, u.nome AS nome_professor
               FROM turmas t
               LEFT JOIN usuarios u ON t.professor_id = u.id
               ORDER BY t.nome_turma";
$turmas = $pdo->query($sql_turmas)->fetchAll(PDO::FETCH_ASSOC);

$sql_professores = "SELECT id, nome FROM usuarios WHERE tipo_usuario = 'professor' ORDER BY nome";
$professores = $pdo->query($sql_professores)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Turmas - Admin Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        :root {
            --cor-primaria: #0A1931;
            --cor-secundaria: #c0392b;
            --cor-destaque: #c0392b;
            --cor-texto: #333;
            --cor-fundo: #f8f9fa;
            --cor-borda: #dee2e6;
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            margin: 0;
            padding: 0;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 16.666667%; /* Equivale a col-md-2 */
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
            margin-left: 280px;
            padding: 30px;
            background-color: white;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 16.666667%; /* Compensa a largura da sidebar fixa */
            width: 83.333333%;
            animation: fadeIn 0.5s ease;
        }

        h1, h2, h3, h4, h6 {
            color: var(--cor-primaria);
            font-weight: 600;
        }

        .btn-acao {
            background: var(--cor-secundaria);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-acao:hover {
            background: var(--cor-secundaria);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(146, 23, 27, 0.3);
            color: white;
        }

        .btn-outline-primary {
            border-color: var(--cor-primaria);
            color: var(--cor-primaria);
        }

        .btn-outline-primary:hover {
            background-color: var(--cor-primaria);
            border-color: var(--cor-primaria);
            color: white;
        }

        .btn-outline-success {
            border-color: #28a745;
            color: #28a745;
        }

        .btn-outline-success:hover {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: var(--cor-primaria);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: var(--cor-borda);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(26, 42, 58, 0.02);
        }

        .badge {
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .bg-primary {
            background: var(--cor-primaria) !important;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 0.2rem rgba(26, 42, 58, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--cor-primaria);
            margin-bottom: 8px;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .modal-header {
            background: var(--cor-primaria);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
            padding: 20px;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <!-- Nome do professor -->
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo $_SESSION['user_nome'] ?? 'Professor'; ?></h5>
        </div>

        <!-- Menu centralizado verticalmente -->
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" ><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="gerenciar_turmas.php" class="rounded active"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
        <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
        </div>

        <!-- Botão sair no rodapé -->
        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4">Gerenciar Turmas</h1>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <button class="btn btn-acao mb-4" data-bs-toggle="modal" data-bs-target="#modalAddTurma" onclick="resetForm()">
            <i class="fas fa-plus"></i> Criar Nova Turma
        </button>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome da Turma</th>
                        <th>Professor Responsável</th>
                        <th>Link da Aula</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turmas)): ?>
                        <tr><td colspan="5" class="text-center">Nenhuma turma cadastrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($turmas as $turma): ?>
                        <tr>
                            <td><?= htmlspecialchars($turma['id']) ?></td>
                            <td><?= htmlspecialchars($turma['nome_turma']) ?></td>
                            <td>
                                <span class="badge bg-primary">
                                    <?= htmlspecialchars($turma['nome_professor'] ?? 'Aguardando Associação') ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($turma['link_aula'])): ?>
                                    <a href="<?= htmlspecialchars($turma['link_aula']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-link"></i> Acessar Aula
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Nenhum link</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="gerenciar_alunos_turmas.php?turma_id=<?= $turma['id'] ?>" class="btn btn-sm btn-outline-success me-2">
                                    <i class="fas fa-user-plus"></i> Alunos
                                </a>
                                <button class="btn btn-sm btn-outline-primary me-2" 
                                        onclick="openEditTurmaModal(<?= $turma['id'] ?>, '<?= htmlspecialchars($turma['nome_turma'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['professor_id'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['link_aula'] ?? '', ENT_QUOTES) ?>')">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmRemove(<?= $turma['id'] ?>, '<?= htmlspecialchars($turma['nome_turma'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash-alt"></i> Remover
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddTurma" tabindex="-1" aria-labelledby="modalAddTurmaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddTurmaLabel">Criar Nova Turma</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="gerenciar_turmas.php">
        <div class="modal-body">
            <input type="hidden" name="acao" id="turma_acao" value="add_turma">
            <input type="hidden" name="turma_id" id="turma_id">
            
            <div class="mb-3">
                <label for="nome_turma" class="form-label">Nome da Turma</label>
                <input type="text" class="form-control" id="nome_turma" name="nome_turma" required>
            </div>
            
            <div class="mb-3">
                <label for="professor_id" class="form-label">Professor Responsável</label>
                <select class="form-select" id="professor_id" name="professor_id" required>
                    <option value="">Selecione um Professor</option>
                    <?php foreach ($professores as $professor): ?>
                        <option value="<?= $professor['id'] ?>"><?= htmlspecialchars($professor['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($professores)): ?>
                    <small class="text-danger">Nenhum professor cadastrado. Cadastre um na seção Usuários.</small>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="link_aula" class="form-label">Link da Aula (Opcional)</label>
                <input type="url" class="form-control" id="link_aula" name="link_aula" >
            </div>
            
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-acao" id="btn_salvar_turma">Salvar Turma</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="formRemover" method="POST" action="gerenciar_turmas.php">
    <input type="hidden" name="acao" value="remover_turma">
    <input type="hidden" name="id_turma" id="remover_id_turma">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function resetForm() {
        document.getElementById('modalAddTurmaLabel').innerText = 'Criar Nova Turma';
        document.getElementById('turma_acao').value = 'add_turma';
        document.getElementById('turma_id').value = '';
        document.getElementById('nome_turma').value = '';
        document.getElementById('professor_id').value = '';
        document.getElementById('link_aula').value = '';
        document.getElementById('btn_salvar_turma').innerText = 'Salvar Turma';
    }

    function openEditTurmaModal(id, nome, professorId, linkAula) {
        document.getElementById('modalAddTurmaLabel').innerText = `Editar Turma: ${nome}`;
        document.getElementById('turma_acao').value = 'editar_turma';
        document.getElementById('turma_id').value = id;
        document.getElementById('nome_turma').value = nome;
        document.getElementById('professor_id').value = professorId;
        document.getElementById('link_aula').value = linkAula;
        document.getElementById('btn_salvar_turma').innerText = 'Atualizar Turma';
        
        var myModal = new bootstrap.Modal(document.getElementById('modalAddTurma'));
        myModal.show();
    }
    
    function confirmRemove(id, nome) {
        if (confirm(`Tem certeza que deseja remover a turma "${nome}"? Esta ação removerá também os alunos associados a esta turma.`)) {
            document.getElementById('remover_id_turma').value = id;
            document.getElementById('formRemover').submit();
        }
    }
</script>
</body>
</html>