<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Garante que apenas admin acessa esta página
if ($_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php?erro=acesso_negado");
    exit;
}

$mensagem = '';
$tipo_mensagem = '';
$turma_id = $_GET['turma_id'] ?? null;

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

// --- LÓGICA DE ASSOCIAÇÃO/DESASSOCIAÇÃO DE ALUNOS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'associar_alunos') {
    $alunos_selecionados = $_POST['alunos_selecionados'] ?? [];

    try {
        $pdo->beginTransaction();

        $sql_delete = "DELETE FROM alunos_turmas WHERE turma_id = :turma_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->bindParam(':turma_id', $turma_id);
        $stmt_delete->execute();

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
        $mensagem = "Alunos da turma <strong>{$nome_turma}</strong> atualizados com sucesso!";
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao associar alunos à turma: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- CONSULTAS ---
$sql_todos_alunos = "SELECT id, nome FROM usuarios WHERE tipo_usuario = 'aluno' ORDER BY nome";
$todos_alunos = $pdo->query($sql_todos_alunos)->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/admin/gerenciar_alunos_turmas.css">
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
             <a href="dashboard.php" class="rounded active"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
             <a href="leads.php" class="rounded"><i class="fas fa-user-tie"></i>&nbsp;&nbsp;Leads</a>
             <a href="personalizar_index.php" class="rounded"><i class="fas fa-paint-brush"></i>&nbsp;&nbsp;Personalizar Site</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="agendas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;Agendas</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
            <a href="acessos.php" class="rounded"><i class="fas fa-chart-line"></i>&nbsp;&nbsp;Relatório de Acessos</a>
        </div>

        <!-- Botão sair no rodapé -->
        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4">Gerenciando Alunos da Turma: <strong><?= htmlspecialchars($nome_turma) ?></strong></h1>
        
        <p><a href="gerenciar_turmas.php" style="color: var(--cor-secundaria); text-decoration: none;">
            <i class="fas fa-arrow-left me-2"></i> Voltar para Gerenciar Turmas
        </a></p>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <button class="btn btn-acao mb-4" data-bs-toggle="modal" data-bs-target="#modalAssociarAlunos">
            <i class="fas fa-user-plus"></i> Gerenciar Alunos na Turma
        </button>

        <h3>Alunos Atualmente Matriculados</h3>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Nome do Aluno</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alunos_associados)): ?>
                        <tr><td colspan="2" class="text-center">Nenhum aluno está associado a esta turma.</td></tr>
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
                <h5 class="modal-title" id="modalAssociarAlunosLabel">Gerenciar Alunos na Turma: <?= htmlspecialchars($nome_turma) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="gerenciar_alunos_turmas.php?turma_id=<?= $turma_id ?>">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="associar_alunos">
                    
                    <p>Selecione todos os alunos que devem estar nesta turma. Os alunos desmarcados serão removidos.</p>
                    
                    <div id="lista_alunos_checkbox">
                        <?php if (empty($todos_alunos)): ?>
                            <p class="text-danger">Nenhum aluno foi cadastrado. Cadastre alunos na seção Usuários.</p>
                        <?php else: ?>
                            <?php 
                            $ids_associados = array_column($alunos_associados, 'id');
                            
                            foreach ($todos_alunos as $aluno): ?>
                            <div class="form-check mb-2">
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