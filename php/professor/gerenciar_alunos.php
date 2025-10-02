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
    <style>
        body {
            background-color: #FAF9F6;
            overflow-x: hidden;
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
            margin-left: 16.666667%; /* Compensa a largura da sidebar fixa */
            width: 83.333333%; /* Equivale a col-md-10 */
            min-height: 100vh;
            overflow-y: auto;
        }

        .btn-danger {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        
        .btn-danger:hover {
            background-color: #a93226;
            border-color: #a93226;
        }
        
        .btn-outline-danger {
            color: #c0392b;
            border-color: #c0392b;
        }
        
        .btn-outline-danger:hover {
            background-color: #c0392b;
            color: white;
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        .accordion-header-custom {
            position: relative;
        }

        .btn-gerenciar {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            background-color: #c0392b;
            border-color: #c0392b;
            color: white;
        }

        .btn-gerenciar:hover {
            background-color: #a93226;
            border-color: #a93226;
            color: white;
        }

        .accordion-button {
            background-color: #081d40;
            color: white;
            font-weight: bold;
        }

        .accordion-button:not(.collapsed) {
            background-color: #0a2a5c;
            color: white;
        }

        .accordion-button:hover {
            background-color: #0a2a5c;
            color: white;
            font-weight: bold;
        }

        .accordion-item {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 3px;
        }

        .aluno-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .aluno-item:last-child {
            border-bottom: none;
        }

        .aluno-item:hover {
            background-color: #f8f9fa;
        }

        /* Badge personalizado */
        .badge.bg-light {
            background-color: white !important;
            color: #081d40 !important;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .accordion-header-custom {
                padding-right: 15px;
            }

            .btn-gerenciar {
                position: relative;
                right: auto;
                top: auto;
                transform: none;
                margin-top: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <!-- Nome do professor -->
                <div class="mb-4 text-center">
                    <h5 class="mt-4">Prof. <?php echo $_SESSION['user_nome'] ?? 'Professor'; ?></h5>
                </div>

                <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded active"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <h2 class="mb-4 mt-3">Gerenciamento de Turmas</h2>
                
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
                                    
                                    <button class="container-fluid accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" 
                                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="<?= $collapse_id ?>">
                                        
                                        <span class="me-3"><i class="fas fa-graduation-cap"></i></span>
                                        <div>
                                            <?= htmlspecialchars($turma['nome_turma']) ?> 
                                            
                                            <br>
                                            <small class="text-white opacity-75">
                                                Professor: <?= htmlspecialchars($turma['nome_professor']) ?> | Início: <?= $data_inicio_display ?>
                                            </small>
                                        </div>
                                        <div class="end">
                                            <span class="badge float-end bg-light text-dark ms-3"><?= $num_alunos ?> Aluno(s)</span>
                                        </div>
                                    </button>
                                    
                                    <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>" class="btn btn-sm btn-gerenciar" title="Gerenciar Turma e Aulas">
                                        <i class="fas fa-cog me-1"></i> Gerenciar
                                    </a>
                                </h2>
                                <div id="<?= $collapse_id ?>" class="accordion-collapse collapse" aria-labelledby="<?= $heading_id ?>" data-bs-parent="#accordionTurmas">
                                    <div class="accordion-body p-0">
                                        <?php if ($num_alunos > 0): ?>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($turma['alunos'] as $aluno): ?>
                                                    <li class="aluno-item d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-user-circle me-2" style="color: #c0392b;"></i> 
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

                <p class="mt-1 text-muted">
                    &nbsp;* Clique no nome da turma para ver a lista de alunos. Clique em "Gerenciar" para ver o calendário de aulas.
                </p>
                
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>