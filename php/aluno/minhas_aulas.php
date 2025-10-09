<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-aluno
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login.php");
    exit;
}

$aluno_id = $_SESSION['user_id'];
$aluno_nome = $_SESSION['user_nome'] ?? 'Aluno';

// Consulta para obter todas as aulas do aluno
$sql = "
    SELECT 
        a.id AS aula_id,
        a.data_aula, 
        a.horario, 
        a.titulo_aula, 
        a.descricao,
        t.id AS turma_id,
        t.nome_turma,
        u.nome AS nome_professor
    FROM 
        aulas a
    JOIN 
        turmas t ON a.turma_id = t.id
    JOIN 
        alunos_turmas at ON t.id = at.turma_id
    JOIN 
        usuarios u ON a.professor_id = u.id
    WHERE 
        at.aluno_id = :aluno_id
    ORDER BY 
        a.data_aula ASC, a.horario ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':aluno_id' => $aluno_id]);
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar aulas passadas e futuras
$aulas_passadas = [];
$aulas_futuras = [];
$data_atual = new DateTime();

foreach ($aulas as $aula) {
    $data_aula = new DateTime($aula['data_aula']);
    if ($data_aula < $data_atual) {
        $aulas_passadas[] = $aula;
    } else {
        $aulas_futuras[] = $aula;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Aulas - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <style>
        .card-aula {
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #c0392b;
        }
        .card-aula:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .aula-passada {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <!-- Nome do aluno -->
                <div class="mb-4 text-center">
                    <h5 class="mt-4"><?php echo $aluno_nome; ?></h5>
                </div>

                <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded active"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Minhas Aulas</h3>
                </div>

                <!-- Aulas Futuras -->
                <?php if (count($aulas_futuras) > 0): ?>
                <div class="mb-5">
                    <h4 class="mb-3"><i class="fas fa-clock text-primary me-2"></i>Próximas Aulas</h4>
                    <div class="row">
                        <?php foreach ($aulas_futuras as $aula): 
                            $data_aula = new DateTime($aula['data_aula']);
                            $hoje = new DateTime();
                            $diferenca = $hoje->diff($data_aula);
                            $dias_restantes = $diferenca->days;
                            
                            if ($dias_restantes == 0) {
                                $texto_data = "Hoje";
                                $badge_class = "bg-warning";
                            } elseif ($dias_restantes == 1) {
                                $texto_data = "Amanhã";
                                $badge_class = "bg-info";
                            } else {
                                $texto_data = "Em " . $dias_restantes . " dias";
                                $badge_class = "bg-primary";
                            }
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card card-aula h-100" onclick="window.location.href='detalhes_aula.php?id=<?= $aula['aula_id'] ?>'">
                                <div class="card-body position-relative">
                                    <span class="badge <?= $badge_class ?> status-badge"><?= $texto_data ?></span>
                                    <h5 class="card-title"><?= htmlspecialchars($aula['titulo_aula']) ?></h5>
                                    <p class="card-text text-muted"><?= htmlspecialchars($aula['descricao'] ?: 'Sem descrição') ?></p>
                                    <div class="mt-auto">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i><?= $data_aula->format('d/m/Y') ?>
                                            <i class="fas fa-clock ms-2 me-1"></i><?= substr($aula['horario'], 0, 5) ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i><?= htmlspecialchars($aula['nome_turma']) ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>Prof. <?= htmlspecialchars($aula['nome_professor']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Aulas Passadas -->
                <?php if (count($aulas_passadas) > 0): ?>
                <div>
                    <h4 class="mb-3"><i class="fas fa-history text-secondary me-2"></i>Aulas Passadas</h4>
                    <div class="row">
                        <?php foreach (array_reverse($aulas_passadas) as $aula): 
                            $data_aula = new DateTime($aula['data_aula']);
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card card-aula aula-passada h-100" onclick="window.location.href='detalhes_aula.php?id=<?= $aula['aula_id'] ?>'">
                                <div class="card-body position-relative">
                                    <span class="badge bg-secondary status-badge">Realizada</span>
                                    <h5 class="card-title"><?= htmlspecialchars($aula['titulo_aula']) ?></h5>
                                    <p class="card-text text-muted"><?= htmlspecialchars($aula['descricao'] ?: 'Sem descrição') ?></p>
                                    <div class="mt-auto">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i><?= $data_aula->format('d/m/Y') ?>
                                            <i class="fas fa-clock ms-2 me-1"></i><?= substr($aula['horario'], 0, 5) ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i><?= htmlspecialchars($aula['nome_turma']) ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>Prof. <?= htmlspecialchars($aula['nome_professor']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Nenhuma aula encontrada</h4>
                        <p class="text-muted">Você não está matriculado em nenhuma aula.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>