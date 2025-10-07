<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// --- LÓGICA DE CRIAÇÃO/EDIÇÃO DE TURMA (mantida) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && ($_POST['acao'] == 'add_turma' || $_POST['acao'] == 'editar_turma')) {
    $nome_turma = $_POST['nome_turma'];
    $professor_id = $_POST['professor_id']; 
    $turma_id = $_POST['turma_id'] ?? null;
    $acao = $_POST['acao'];
    
    try {
        if ($acao == 'add_turma') {
            $sql = "INSERT INTO turmas (nome_turma, professor_id) VALUES (:nome_turma, :professor_id)";
            $stmt = $pdo->prepare($sql);
            $mensagem = "Turma **{$nome_turma}** criada e associada com sucesso!";
        } else { // editar_turma
            $sql = "UPDATE turmas SET nome_turma = :nome_turma, professor_id = :professor_id WHERE id = :turma_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':turma_id', $turma_id);
            $mensagem = "Turma **{$nome_turma}** atualizada com sucesso!";
        }
        
        $stmt->bindParam(':nome_turma', $nome_turma);
        $stmt->bindParam(':professor_id', $professor_id);
        $stmt->execute();
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $mensagem = "Erro ao gerenciar turma: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA DE REMOÇÃO DE TURMA (mantida) ---
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


// --- Consultas para Listagem (CORRIGIDAS) ---

// 1. Listar Turmas (Com nome do professor)
$sql_turmas = "SELECT t.*, u.nome AS nome_professor
               FROM turmas t
               LEFT JOIN usuarios u ON t.professor_id = u.id
               ORDER BY t.nome_turma";
$turmas = $pdo->query($sql_turmas)->fetchAll(PDO::FETCH_ASSOC);

// 2. Listar Professores disponíveis para associação
// CORREÇÃO: Usando tipo_usuario
$sql_professores = "SELECT id, nome FROM usuarios WHERE tipo_usuario = 'professor' ORDER BY nome";
$professores = $pdo->query($sql_professores)->fetchAll(PDO::FETCH_ASSOC);

// 3. Listar Alunos disponíveis 
// CORREÇÃO: Usando tipo_usuario
$sql_alunos = "SELECT id, nome FROM usuarios WHERE tipo_usuario = 'aluno' ORDER BY nome";
$alunos = $pdo->query($sql_alunos)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Turmas - Admin Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/gerenciar_turmas.css">
    
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">ADMIN RISENGLISH</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
        <a href="gerenciar_turmas.php" style="background-color: #92171B;"><i class="fas fa-users-class me-2"></i> Turmas</a>
        <a href="gerenciar_usuarios.php"><i class="fas fa-user-friends me-2"></i> Usuários (Prof/Alunos)</a>
        <a href="gerenciar_uteiss.php"><i class="fas fa-book"></i> Recomendações</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciar Turmas</h1>
        
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
                <thead class="bg-light" style="color: var(--cor-primaria);">
                    <tr>
                        <th>ID</th>
                        <th>Nome da Turma</th>
                        <th>Professor Responsável</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turmas)): ?>
                        <tr><td colspan="4">Nenhuma turma cadastrada.</td></tr>
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
                                <a href="gerenciar_alunos_turmas.php?turma_id=<?= $turma['id'] ?>" class="btn btn-sm btn-outline-success me-2"><i class="fas fa-user-plus"></i> Alunos</a>
                                <button class="btn btn-sm btn-outline-primary me-2" 
                                        onclick="openEditTurmaModal(<?= $turma['id'] ?>, '<?= htmlspecialchars($turma['nome_turma'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['professor_id'] ?? '', ENT_QUOTES) ?>')">
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
        <h5 class="modal-title" id="modalAddTurmaLabel" style="color: var(--cor-primaria);">Criar Nova Turma</h5>
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
    // Funções JavaScript (mantidas)
    function resetForm() {
        document.getElementById('modalAddTurmaLabel').innerText = 'Criar Nova Turma';
        document.getElementById('turma_acao').value = 'add_turma';
        document.getElementById('turma_id').value = '';
        document.getElementById('nome_turma').value = '';
        document.getElementById('professor_id').value = '';
        document.getElementById('btn_salvar_turma').innerText = 'Salvar Turma';
    }

    function openEditTurmaModal(id, nome, professorId) {
        document.getElementById('modalAddTurmaLabel').innerText = `Editar Turma: ${nome}`;
        document.getElementById('turma_acao').value = 'editar_turma';
        document.getElementById('turma_id').value = id;
        document.getElementById('nome_turma').value = nome;
        document.getElementById('professor_id').value = professorId;
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