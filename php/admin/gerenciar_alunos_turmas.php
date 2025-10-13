<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
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
    <style>
        :root {
            --cor-primaria: #0A1931;
            --cor-secundaria: #c0392b;
            --cor-destaque: #c0392b;
            --cor-texto: #333;
            --cor-fundo: #f8f9fa;
            --cor-borda: #dee2e6;
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

        .bg-success {
            background: #28a745 !important;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
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

        #lista_alunos_checkbox {
            max-height: 350px;
            overflow-y: auto;
            border: 1px solid var(--cor-borda);
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }

        .form-check-input:checked {
            background-color: var(--cor-secundaria);
            border-color: var(--cor-secundaria);
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
             <a href="dashboard.php" class="rounded active"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
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