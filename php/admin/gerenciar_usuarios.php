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

// --- LÓGICA DE CRUD DE USUÁRIOS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && ($_POST['acao'] == 'add_usuario' || $_POST['acao'] == 'editar_usuario')) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo_usuario']; // NOVO: Campo Tipo
    $usuario_id = $_POST['usuario_id'] ?? null;
    $acao = $_POST['acao'];

    try {
        if (empty($nome) || empty($email) || empty($tipo)) throw new Exception("Todos os campos obrigatórios (Nome, Email, Tipo) devem ser preenchidos.");

        if ($acao == 'add_usuario') {
            if (empty($senha)) throw new Exception("A senha é obrigatória para novos cadastros.");

            // Verificação de Email duplicado
            $sql_check = "SELECT id FROM usuarios WHERE email = :email";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':email', $email);
            $stmt_check->execute();
            if ($stmt_check->rowCount() > 0) throw new Exception("O email já está cadastrado.");

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            // CORREÇÃO: Usando tipo_usuario
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario) VALUES (:nome, :email, :senha, :tipo_usuario)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':senha', $senha_hash);
            $mensagem = "Usuário **{$nome}** cadastrado como **{$tipo}** com sucesso!";

        } else { // editar_usuario
            // CORREÇÃO: Usando tipo_usuario
            $sql_parts = ["nome = :nome", "email = :email", "tipo_usuario = :tipo_usuario"];
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $sql_parts[] = "senha = :senha";
            }
            $sql = "UPDATE usuarios SET " . implode(', ', $sql_parts) . " WHERE id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id);
            if (!empty($senha)) $stmt->bindParam(':senha', $senha_hash);
            $mensagem = "Usuário **{$nome}** atualizado com sucesso!";
        }

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':tipo_usuario', $tipo); // BIND CORRIGIDO
        $stmt->execute();
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $mensagem = "Erro ao gerenciar usuário: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA DE REMOÇÃO DE USUÁRIO (mantida) ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'remover_usuario') {
    $id_usuario = $_POST['id_usuario'];

    try {
        $sql = "DELETE FROM usuarios WHERE id = :id_usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $mensagem = "Usuário removido com sucesso!";
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = "Erro ao remover usuário: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}


// --- Consultas para Listagem (CORRIGIDAS) ---

// 1. Listar Professores
$sql_professores = "SELECT id, nome, email, tipo_usuario FROM usuarios WHERE tipo_usuario = 'professor' ORDER BY nome";
$professores = $pdo->query($sql_professores)->fetchAll(PDO::FETCH_ASSOC);

// 2. Listar Alunos (Com turmas associadas)
$sql_alunos = "SELECT u.id, u.nome, u.email, u.tipo_usuario, GROUP_CONCAT(t.nome_turma SEPARATOR ', ') AS turmas_associadas
               FROM usuarios u
               LEFT JOIN alunos_turmas at ON u.id = at.aluno_id
               LEFT JOIN turmas t ON at.turma_id = t.id
               WHERE u.tipo_usuario = 'aluno'
               GROUP BY u.id
               ORDER BY u.nome";
$alunos = $pdo->query($sql_alunos)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/gerenciar_usuarios.css">
   
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">ADMIN RISENGLISH</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
        <a href="gerenciar_turmas.php"><i class="fas fa-user-friends me-2"></i> Turmas</a>
        <a href="gerenciar_usuarios.php" style="background-color: #92171B;"><i class="fas fa-user me-2"></i> Usuários (Prof/Alunos)</a>
        <a href="recomendacoes.php"><i class="fas fa-book"></i> Recomendações</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciar Usuários</h1>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <button class="btn btn-acao mb-4" data-bs-toggle="modal" data-bs-target="#modalAddUsuario" onclick="resetForm()">
            <i class="fas fa-plus"></i> Cadastrar Novo Usuário (Prof/Aluno)
        </button>

        <ul class="nav nav-tabs mb-4" id="usuarioTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="professores-tab" data-bs-toggle="tab" data-bs-target="#professores" type="button" role="tab" aria-controls="professores" aria-selected="true" style="color: var(--cor-primaria);">Professores</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="alunos-tab" data-bs-toggle="tab" data-bs-target="#alunos" type="button" role="tab" aria-controls="alunos" aria-selected="false" style="color: var(--cor-primaria);">Alunos</button>
            </li>
        </ul>

        <div class="tab-content" id="usuarioTabContent">

            <div class="tab-pane fade show active" id="professores" role="tabpanel" aria-labelledby="professores-tab">
                <h3 style="color: var(--cor-secundaria);">Lista de Professores</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="bg-light" style="color: var(--cor-primaria);">
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($professores)): ?>
                                <tr><td colspan="3">Nenhum professor cadastrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($professores as $professor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($professor['nome']) ?></td>
                                    <td><?= htmlspecialchars($professor['email']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="openEditUsuarioModal(<?= $professor['id'] ?>, '<?= htmlspecialchars($professor['nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($professor['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($professor['tipo_usuario'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmRemove(<?= $professor['id'] ?>, '<?= htmlspecialchars($professor['nome'], ENT_QUOTES) ?>')">
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

            <div class="tab-pane fade" id="alunos" role="tabpanel" aria-labelledby="alunos-tab">
                <h3 style="color: var(--cor-secundaria);">Lista de Alunos</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="bg-light" style="color: var(--cor-primaria);">
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Turmas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($alunos)): ?>
                                <tr><td colspan="4">Nenhum aluno cadastrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($alunos as $aluno): ?>
                                <tr>
                                    <td><?= htmlspecialchars($aluno['nome']) ?></td>
                                    <td><?= htmlspecialchars($aluno['email']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($aluno['turmas_associadas'] ?: 'Nenhuma') ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="openEditUsuarioModal(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($aluno['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($aluno['tipo_usuario'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmRemove(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome'], ENT_QUOTES) ?>')">
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

    </div>
</div>

<div class="modal fade" id="modalAddUsuario" tabindex="-1" aria-labelledby="modalAddUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddUsuarioLabel" style="color: var(--cor-primaria);">Cadastrar Novo Usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="gerenciar_usuarios.php">
        <div class="modal-body">
            <input type="hidden" name="acao" id="usuario_acao" value="add_usuario">
            <input type="hidden" name="usuario_id" id="usuario_id">
            
            <div class="mb-3">
                <label for="tipo_usuario" class="form-label">Tipo de Usuário</label>
                <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                    <option value="">Selecione o Tipo</option>
                    <option value="professor">Professor</option>
                    <option value="aluno">Aluno</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label" id="label_senha">Senha (Obrigatória para novo. Deixe vazio para manter a atual)</label>
                <input type="password" class="form-control" id="senha" name="senha">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-acao" id="btn_salvar_usuario">Salvar Usuário</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="formRemover" method="POST" action="gerenciar_usuarios.php">
    <input type="hidden" name="acao" value="remover_usuario">
    <input type="hidden" name="id_usuario" id="remover_id_usuario">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // As funções JavaScript continuam as mesmas, mas o parâmetro 'tipo' foi renomeado no PHP para 'tipo_usuario'
    function resetForm() {
        document.getElementById('modalAddUsuarioLabel').innerText = 'Cadastrar Novo Usuário';
        document.getElementById('usuario_acao').value = 'add_usuario';
        document.getElementById('usuario_id').value = '';
        document.getElementById('nome').value = '';
        document.getElementById('email').value = '';
        document.getElementById('tipo_usuario').value = ''; 
        document.getElementById('senha').value = '';
        document.getElementById('label_senha').innerText = 'Senha (Obrigatória para novo)';
        document.getElementById('btn_salvar_usuario').innerText = 'Salvar Usuário';
        document.getElementById('email').disabled = false;
        
        document.getElementById('senha').removeAttribute('required');
    }

    function openEditUsuarioModal(id, nome, email, tipo) { // O parâmetro 'tipo' aqui representa 'tipo_usuario'
        document.getElementById('modalAddUsuarioLabel').innerText = `Editar Usuário: ${nome}`;
        document.getElementById('usuario_acao').value = 'editar_usuario';
        document.getElementById('usuario_id').value = id;
        document.getElementById('nome').value = nome;
        document.getElementById('email').value = email;
        document.getElementById('tipo_usuario').value = tipo;
        document.getElementById('senha').value = '';
        document.getElementById('label_senha').innerText = 'Nova Senha (Deixe vazio para manter a atual)';
        document.getElementById('btn_salvar_usuario').innerText = 'Atualizar Usuário';
        document.getElementById('email').disabled = false;
        
        var myModal = new bootstrap.Modal(document.getElementById('modalAddUsuario'));
        myModal.show();
    }
    
    function confirmRemove(id, nome) {
        if (confirm(`Tem certeza que deseja remover o usuário "${nome}"? Esta ação é irreversível e removerá todas as associações (turmas/aulas).`)) {
            document.getElementById('remover_id_usuario').value = id;
            document.getElementById('formRemover').submit();
        }
    }
</script>
</body>
</html>