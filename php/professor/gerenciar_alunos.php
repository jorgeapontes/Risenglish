<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];

// --- CONSULTA PARA LISTAR TURMAS E ALUNOS ---

// 1. Puxa todas as turmas que o professor gerencia E O NOME DO PROFESSOR
$sql_turmas = "
    SELECT 
        t.id, t.nome_turma, t.inicio_turma, u.nome AS nome_professor
    FROM 
        turmas t
    JOIN 
        usuarios u ON t.professor_id = u.id
    WHERE 
        t.professor_id = :professor_id 
    ORDER BY 
        t.nome_turma ASC
";
$stmt_turmas = $pdo->prepare($sql_turmas);
$stmt_turmas->bindParam(':professor_id', $professor_id);
$stmt_turmas->execute();
$turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);

// 2. Para cada turma, busca os alunos associados
$turmas_com_alunos = [];

foreach ($turmas as $turma) {
    $turma['alunos'] = [];
    
    // Consulta para buscar os alunos na turma
    $sql_alunos = "
        SELECT 
            u.id AS aluno_id, 
            u.nome AS nome_aluno,
            u.email AS email_aluno
        FROM 
            alunos_turmas at
        JOIN 
            usuarios u ON at.aluno_id = u.id
        WHERE 
            at.turma_id = :turma_id
            AND u.tipo_usuario = 'aluno'
        ORDER BY
            u.nome ASC
    ";
    $stmt_alunos = $pdo->prepare($sql_alunos);
    $stmt_alunos->bindParam(':turma_id', $turma['id']);
    $stmt_alunos->execute();
    $turma['alunos'] = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);

    $turmas_com_alunos[] = $turma;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos/Turmas - Professor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/professor/gerenciar_alunos.css">
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a>
        <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt me-2"></i>Aulas</a>
        <a href="gerenciar_conteudos.php"><i class="fas fa-book-open me-2"></i> Conteúdos</a>
        <a href="gerenciar_alunos.php" style="background-color: #92171B;"><i class="fas fa-users me-2"></i>Alunos/Turmas</a>
        <a href="../logout.php" class="link-sair"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciamento de Alunos e Turmas</h1>
        
        <?php if (empty($turmas_com_alunos)): ?>
            <div class="alert alert-info">
                Você ainda não possui turmas cadastradas ou associadas ao seu perfil.
            </div>
        <?php else: ?>
            <div class="accordion" id="accordionTurmas">
                <?php foreach ($turmas_com_alunos as $index => $turma): 
                    $turma_id = $turma['id'];
                    $collapse_id = "collapse" . $turma_id;
                    $heading_id = "heading" . $turma_id;
                    $num_alunos = count($turma['alunos']);
                    $data_inicio_display = isset($turma['inicio_turma']) && $turma['inicio_turma'] ? 
                                           date('d/m/Y', strtotime($turma['inicio_turma'])) : 
                                           'N/D';
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header accordion-header-custom" id="<?= $heading_id ?>">
                            
                            <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" 
                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="<?= $collapse_id ?>">
                                
                                <span class="me-3"><i class="fas fa-graduation-cap"></i></span>
                                <div>
                                    <?= htmlspecialchars($turma['nome_turma']) ?> 
                                    <span class="badge bg-light text-dark ms-3"><?= $num_alunos ?> Aluno(s)</span>
                                    <br>
                                    <small class="text-white opacity-75">
                                        Professor: **<?= htmlspecialchars($turma['nome_professor']) ?>** | Início: <?= $data_inicio_display ?>
                                    </small>
                                </div>
                            </button>
                            
                            <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>" class="btn btn-sm btn-light btn-gerenciar" title="Gerenciar Turma e Aulas">
                                <i class="fas fa-cog me-1"></i> Gerenciar
                            </a>

                        </h2>
                        <div id="<?= $collapse_id ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="<?= $heading_id ?>" data-bs-parent="#accordionTurmas">
                            <div class="accordion-body p-0">
                                <?php if ($num_alunos > 0): ?>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($turma['alunos'] as $aluno): ?>
                                            <li class="aluno-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-user-circle me-2" style="color: var(--cor-secundaria);"></i> 
                                                    <strong><?= htmlspecialchars($aluno['nome_aluno']) ?></strong>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($aluno['email_aluno']) ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="p-3 text-center text-muted m-0">
                                        <i class="fas fa-exclamation-circle me-2"></i> Esta turma ainda não tem alunos associados.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="mt-4 text-muted">
            * Clique no nome da turma para ver a lista de alunos. Clique em "Gerenciar" para ver o calendário de aulas.
        </p>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>