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
$turma_id = $_GET['turma_id'] ?? null;

// 1. Checa se o ID da turma é válido e pega o nome
if (empty($turma_id) || !is_numeric($turma_id)) {
    header("Location: gerenciar_turmas.php"); 
    exit;
}

$sql_turma = "SELECT nome_turma, professor_id FROM turmas WHERE id = :turma_id";
$stmt_turma = $pdo->prepare($sql_turma);
$stmt_turma->bindParam(':turma_id', $turma_id);
$stmt_turma->execute();
$turma_info = $stmt_turma->fetch(PDO::FETCH_ASSOC);

if (!$turma_info) {
    header("Location: gerenciar_turmas.php");
    exit;
}
$nome_turma = $turma_info['nome_turma'];

// --- LÓGICA DE ASSOCIAÇÃO/DESASSOCIAÇÃO DE ALUNOS (mantida) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'associar_alunos') {
    $alunos_selecionados = $_POST['alunos_selecionados'] ?? [];

    try {
        $pdo->beginTransaction();

        // 1. Remove todas as associações atuais da turma
        $sql_delete = "DELETE FROM alunos_turmas WHERE turma_id = :turma_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->bindParam(':turma_id', $turma_id);
        $stmt_delete->execute();

        // 2. Insere as novas associações
        if (!empty($alunos_selecionados)) {
            $sql_insert = "INSERT INTO alunos_turmas (aluno_id, turma_id) VALUES (:aluno_id, :turma_id)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->bindParam(':turma_id', $turma_id);
            
            foreach ($alunos_selecionados as $aluno_id) {
                if (is_numeric($aluno_id)) {
                    $stmt_insert->bindParam(':aluno_id', $aluno_id);
                    $stmt_insert->execute();
                }
            }
        }
        
        $pdo->commit();
        $mensagem = "Alunos da turma **{$nome_turma}** atualizados com sucesso!";
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao associar alunos à turma: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}


// --- Consultas para Listagem (CORRIGIDAS) ---

// 1. Lista de TODOS os alunos (para o modal de seleção)
// CORREÇÃO: Usando tipo_usuario
$sql_todos_alunos = "SELECT id, nome FROM usuarios WHERE tipo_usuario = 'aluno' ORDER BY nome";
$todos_alunos = $pdo->query($sql_todos_alunos)->fetchAll(PDO::FETCH_ASSOC);

// 2. Lista de alunos JÁ associados a esta turma (mantida, mas u.tipo_usuario já não era necessário aqui)
$sql_alunos_associados = "SELECT u.id, u.nome
                          FROM usuarios u
                          JOIN alunos_turmas at ON u.id = at.aluno_id
                          WHERE at.turma_id = :turma_id
                          ORDER BY u.nome";
$stmt_alunos_associados = $pdo->prepare($sql_alunos_associados);
$stmt_alunos_associados->bindParam(':turma_id', $turma_id);
$stmt_alunos_associados->execute();
$alunos_associados = $stmt_alunos_associados->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos - <?= htmlspecialchars($nome_turma) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/gerenciar_alunos_turmas.css">
    
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">ADMIN RISENGLISH</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
        <a href="gerenciar_turmas.php" style="background-color: #92171B;"><i class="fas fa-users-class me-2"></i> Turmas</a>
        <a href="gerenciar_usuarios.php"><i class="fas fa-user-friends me-2"></i> Usuários (Prof/Alunos)</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciando Alunos da Turma: **<?= htmlspecialchars($nome_turma) ?>**</h1>
        
        <p><a href="gerenciar_turmas.php" style="color: var(--cor-secundaria); text-decoration: none;"><i class="fas fa-arrow-left me-2"></i> Voltar para Gerenciar Turmas</a></p>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <button class="btn btn-acao mb-4" data-bs-toggle="modal" data-bs-target="#modalAssociarAlunos">
            <i class="fas fa-user-plus"></i> Gerenciar Alunos na Turma
        </button>

        <h3 style="color: var(--cor-primaria);">Alunos Atualmente Matriculados</h3>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="bg-light" style="color: var(--cor-primaria);">
                    <tr>
                        <th>Nome do Aluno</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alunos_associados)): ?>
                        <tr><td colspan="2">Nenhum aluno está associado a esta turma.</td></tr>
                    <?php else: ?>
                        <?php foreach ($alunos_associados as $aluno): ?>
                        <tr>
                            <td><?= htmlspecialchars($aluno['nome']) ?></td>
                            <td><span class="badge bg-success">Matriculado</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAssociarAlunos" tabindex="-1" aria-labelledby="modalAssociarAlunosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAssociarAlunosLabel" style="color: var(--cor-primaria);">Gerenciar Alunos na Turma: <?= htmlspecialchars($nome_turma) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="gerenciar_alunos_turmas.php?turma_id=<?= $turma_id ?>">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="associar_alunos">
                    
                    <p>Selecione todos os alunos que devem estar nesta turma. Os alunos desmarcados serão removidos.</p>
                    
                    <div id="lista_alunos_checkbox" style="max-height: 350px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                        <?php if (empty($todos_alunos)): ?>
                            <p class="text-danger">Nenhum aluno foi cadastrado. Cadastre alunos na seção Usuários.</p>
                        <?php else: ?>
                            <?php 
                            $ids_associados = array_column($alunos_associados, 'id');
                            
                            foreach ($todos_alunos as $aluno): ?>
                            <div class="form-check">
                                <input class="form-check-input aluno-checkbox" type="checkbox" name="alunos_selecionados[]" value="<?= $aluno['id'] ?>" id="aluno_<?= $aluno['id'] ?>"
                                    <?php if (in_array($aluno['id'], $ids_associados)): ?> checked <?php endif; ?>>
                                <label class="form-check-label" for="aluno_<?= $aluno['id'] ?>">
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-acao">Salvar Matrículas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>