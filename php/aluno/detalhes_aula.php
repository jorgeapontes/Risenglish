<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-aluno
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login.php");
    exit;
}

// Verificar se o ID da aula foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: minhas_aulas.php");
    exit;
}

$aluno_id = $_SESSION['user_id'];
$aluno_nome = $_SESSION['user_nome'] ?? 'Aluno';
$aula_id = $_GET['id'];

// Consulta para obter os detalhes da aula
$sql_aula = "
    SELECT 
        a.id AS aula_id,
        a.data_aula, 
        a.horario, 
        a.titulo_aula, 
        a.descricao,
        t.id AS turma_id,
        t.nome_turma,
        u.nome AS nome_professor,
        u.email AS email_professor
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
        AND a.id = :aula_id
";
$stmt_aula = $pdo->prepare($sql_aula);
$stmt_aula->execute([
    ':aluno_id' => $aluno_id,
    ':aula_id' => $aula_id
]);
$aula = $stmt_aula->fetch(PDO::FETCH_ASSOC);

// Verificar se a aula existe e pertence ao aluno
if (!$aula) {
    header("Location: minhas_aulas.php");
    exit;
}

// Consulta para obter os conteúdos da aula
$sql_conteudos = "
    SELECT 
        c.id,
        c.titulo,
        c.descricao,
        c.tipo_arquivo,
        c.caminho_arquivo,
        ac.planejado
    FROM 
        aulas_conteudos ac
    JOIN 
        conteudos c ON ac.conteudo_id = c.id
    WHERE 
        ac.aula_id = :aula_id
    ORDER BY 
        ac.planejado DESC, c.titulo ASC
";
$stmt_conteudos = $pdo->prepare($sql_conteudos);
$stmt_conteudos->execute([':aula_id' => $aula_id]);
$conteudos = $stmt_conteudos->fetchAll(PDO::FETCH_ASSOC);

// Verificar se a aula já aconteceu
$data_aula = new DateTime($aula['data_aula']);
$data_atual = new DateTime();
$aula_passada = $data_aula < $data_atual;

// Formatar data e hora
$data_formatada = $data_aula->format('d/m/Y');
$hora_formatada = substr($aula['horario'], 0, 5);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($aula['titulo_aula']) ?> - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <style>
        .conteudo-card {
            transition: all 0.3s ease;
            border-left: 4px solid #081d40;
        }
        .conteudo-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .badge-planejado {
            background-color: #28a745;
        }
        .badge-adicionado {
            background-color: #17a2b8;
        }
        .info-card {
            background-color: #f8f9fa;
            border-radius: 8px;
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
                    <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;Minhas Turmas</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <a href="minhas_aulas.php" class="btn btn-outline-secondary mb-2">
                            <i class="fas fa-arrow-left me-2"></i>Voltar para Minhas Aulas
                        </a>
                        <h3 class="mb-0"><?= htmlspecialchars($aula['titulo_aula']) ?></h3>
                    </div>
                    <span class="badge <?= $aula_passada ? 'bg-secondary' : 'bg-primary' ?> fs-6">
                        <?= $aula_passada ? 'Aula Realizada' : 'Próxima Aula' ?>
                    </span>
                </div>

                <div class="row">
                    <!-- Informações da Aula -->
                    <div class="col-md-4 mb-4">
                        <div class="card info-card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações da Aula</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong><i class="fas fa-calendar me-2 text-primary"></i>Data:</strong>
                                    <p class="mb-0"><?= $data_formatada ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-clock me-2 text-primary"></i>Horário:</strong>
                                    <p class="mb-0"><?= $hora_formatada ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-users me-2 text-primary"></i>Turma:</strong>
                                    <p class="mb-0"><?= htmlspecialchars($aula['nome_turma']) ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-user me-2 text-primary"></i>Professor:</strong>
                                    <p class="mb-0"><?= htmlspecialchars($aula['nome_professor']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($aula['email_professor']) ?></small>
                                </div>
                                <?php if (!empty($aula['descricao'])): ?>
                                <div>
                                    <strong><i class="fas fa-file-alt me-2 text-primary"></i>Descrição:</strong>
                                    <p class="mb-0"><?= htmlspecialchars($aula['descricao']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Conteúdos da Aula -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Conteúdos da Aula</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($conteudos) > 0): ?>
                                    <div class="row">
                                        <?php foreach ($conteudos as $conteudo): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card conteudo-card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="card-title mb-0"><?= htmlspecialchars($conteudo['titulo']) ?></h6>
                                                            <span class="badge <?= $conteudo['planejado'] ? 'badge-planejado' : 'badge-adicionado' ?>">
                                                                <?= $conteudo['planejado'] ? 'Planejado' : 'Adicionado' ?>
                                                            </span>
                                                        </div>
                                                        <?php if (!empty($conteudo['descricao'])): ?>
                                                            <p class="card-text text-muted small"><?= htmlspecialchars($conteudo['descricao']) ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($conteudo['tipo_arquivo'] === 'URL'): ?>
                                                            <a href="<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-2">
                                                                <i class="fas fa-external-link-alt me-1"></i>Acessar Link
                                                            </a>
                                                        <?php elseif ($conteudo['tipo_arquivo'] !== 'TEMA'): ?>
                                                            <a href="<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-2">
                                                                <i class="fas fa-download me-1"></i>Baixar Arquivo
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-folder-open fa-2x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">Nenhum conteúdo disponível para esta aula.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>