<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$turma_id = $_GET['turma_id'] ?? null;

if (!$turma_id || !is_numeric($turma_id)) {
    // Redireciona se o ID for inválido
    header("Location: gerenciar_alunos.php");
    exit;
}

// --- CONSULTA PARA DETALHES DA TURMA ---
$sql_turma = "
    SELECT 
        t.id, t.nome_turma, t.inicio_turma, u.nome AS nome_professor, COUNT(at.aluno_id) AS total_alunos
    FROM 
        turmas t
    JOIN 
        usuarios u ON t.professor_id = u.id
    LEFT JOIN
        alunos_turmas at ON t.id = at.turma_id
    WHERE 
        t.id = :turma_id AND t.professor_id = :professor_id
    GROUP BY
        t.id, t.nome_turma, t.inicio_turma, u.nome
";
$stmt_turma = $pdo->prepare($sql_turma);
$stmt_turma->execute([':turma_id' => $turma_id, ':professor_id' => $professor_id]);
$turma_detalhes = $stmt_turma->fetch(PDO::FETCH_ASSOC);

if (!$turma_detalhes) {
    // Redireciona se a turma não for encontrada ou não pertencer ao professor
    header("Location: gerenciar_alunos.php");
    exit;
}

// --- LÓGICA DO CALENDÁRIO ---
$mes = $_GET['mes'] ?? date('n');
$ano = $_GET['ano'] ?? date('Y');
$primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
$dias_no_mes = date('t', $primeiro_dia);
$dia_inicio_semana = date('w', $primeiro_dia); // 0 (Dom) a 6 (Sáb)
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese'); 
$nome_mes = strftime('%B de %Y', $primeiro_dia);


// Calcula o mês anterior e próximo
$mes_anterior = date('n', mktime(0, 0, 0, $mes - 1, 1, $ano));
$ano_anterior = date('Y', mktime(0, 0, 0, $mes - 1, 1, $ano));
$mes_proximo = date('n', mktime(0, 0, 0, $mes + 1, 1, $ano));
$ano_proximo = date('Y', mktime(0, 0, 0, $mes + 1, 1, $ano));

// --- CONSULTA PARA AULAS NO MÊS DA TURMA ---
$data_inicio = date('Y-m-d', $primeiro_dia);
$data_fim = date('Y-m-d', mktime(0, 0, 0, $mes, $dias_no_mes, $ano));

$sql_aulas = "
    SELECT id, titulo_aula, data_aula, horario
    FROM aulas 
    WHERE turma_id = :turma_id 
      AND data_aula BETWEEN :data_inicio AND :data_fim
    ORDER BY data_aula, horario ASC
";
$stmt_aulas = $pdo->prepare($sql_aulas);
$stmt_aulas->execute([':turma_id' => $turma_id, ':data_inicio' => $data_inicio, ':data_fim' => $data_fim]);
$aulas_por_dia = [];

while ($aula = $stmt_aulas->fetch(PDO::FETCH_ASSOC)) {
    $dia = date('j', strtotime($aula['data_aula'])); // Dia do mês (1 a 31)
    if (!isset($aulas_por_dia[$dia])) {
        $aulas_por_dia[$dia] = [];
    }
    $aulas_por_dia[$dia][] = $aula;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Turma <?= htmlspecialchars($turma_detalhes['nome_turma']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/professor/detalhes_turma.css">
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt me-2"></i>Aulas</a>
        <a href="gerenciar_conteudos.php"><i class="fas fa-book-open me-2"></i>Conteúdos</a>
        <a href="gerenciar_alunos.php" style="background-color: #92171B;"><i class="fas fa-users me-2"></i>Alunos/Turmas</a>
        <a href="../logout.php" class="link-sair"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciar Turma: **<?= htmlspecialchars($turma_detalhes['nome_turma']) ?>**</h1>
        
        <div class="row mb-4">
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header card-header-turma">
                        <i class="fas fa-info-circle me-2"></i> Informações Principais
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>ID:</strong> <?= $turma_id ?><br>
                                <strong>Professor Responsável:</strong> <?= htmlspecialchars($turma_detalhes['nome_professor']) ?><br>
                                <strong>Total de Alunos:</strong> <?= $turma_detalhes['total_alunos'] ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Início da Turma:</strong> <?= $turma_detalhes['inicio_turma'] ? date('d/m/Y', strtotime($turma_detalhes['inicio_turma'])) : 'N/D' ?><br>
                                <strong>Status:</strong> Ativa (Hardcoded - Ajustar no BD se necessário)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex flex-column">
                <a href="gerenciar_aulas.php?turma_id=<?= $turma_id ?>" class="btn text-white h-100" style="background-color: var(--cor-primaria);">
                    <i class="fas fa-calendar-plus me-2"></i> Agendar Nova Aula
                    <small class="d-block">(Redireciona para tela de agendamento)</small>
                </a>
            </div>
        </div>

        <h3 class="mt-4 mb-3" style="color: var(--cor-primaria);">Calendário de Aulas - <?= ucwords($nome_mes) ?></h3>

        <div class="d-flex justify-content-between mb-3">
            <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= $mes_anterior ?>&ano=<?= $ano_anterior ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chevron-left"></i> Mês Anterior
            </a>
            <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= date('n') ?>&ano=<?= date('Y') ?>" class="btn btn-secondary">Mês Atual</a>
            <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= $mes_proximo ?>&ano=<?= $ano_proximo ?>" class="btn btn-outline-secondary">
                Próximo Mês <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <div class="calendario-container">
            <div class="row g-0 semana-header text-center">
                <div class="col py-2">DOMINGO</div>
                <div class="col py-2">SEGUNDA</div>
                <div class="col py-2">TERÇA</div>
                <div class="col py-2">QUARTA</div>
                <div class="col py-2">QUINTA</div>
                <div class="col py-2">SEXTA</div>
                <div class="col py-2">SÁBADO</div>
            </div>
            
            <div class="row g-0">
                <?php 
                $dia_contador = 1;
                // Preenche células vazias no início do mês
                for ($i = 0; $i < $dia_inicio_semana; $i++): ?>
                    <div class="col dia-celula" style="background-color: #f8f9fa;"></div>
                <?php endfor; ?>

                <?php while ($dia_contador <= $dias_no_mes): ?>
                    <?php 
                    $data_atual_timestamp = mktime(0, 0, 0, $mes, $dia_contador, $ano);
                    $hoje = (date('Y-m-d', $data_atual_timestamp) == date('Y-m-d'));
                    ?>

                    <div class="col dia-celula <?= $hoje ? 'bg-info bg-opacity-25' : '' ?>">
                        <span class="dia-num"><?= $dia_contador ?></span>
                        
                        <?php if (isset($aulas_por_dia[$dia_contador])): ?>
                            <?php foreach ($aulas_por_dia[$dia_contador] as $aula): ?>
                                <div class="aula-item" title="<?= htmlspecialchars($aula['titulo_aula']) ?>">
                                    <small>
                                        <i class="far fa-clock me-1"></i>
                                        <?= substr($aula['horario'], 0, 5) ?>
                                        <a href="gerenciar_aulas.php?editar=<?= $aula['id'] ?>" class="text-decoration-none text-dark fw-bold">
                                            <?= htmlspecialchars($aula['titulo_aula']) ?>
                                        </a>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php 
                    $dia_contador++;
                    // Inicia nova linha da tabela se for sábado (6) e não for o último dia
                    if (($dia_inicio_semana + $dia_contador - 1) % 7 == 0 && $dia_contador <= $dias_no_mes): ?>
                        </div><div class="row g-0">
                    <?php endif; ?>

                <?php endwhile; ?>

                <?php
                // Preenche células vazias no final do mês
                $dias_restantes = 7 - (($dia_inicio_semana + $dias_no_mes) % 7);
                if ($dias_restantes < 7):
                    for ($i = 0; $i < $dias_restantes; $i++): ?>
                        <div class="col dia-celula" style="background-color: #f8f9fa;"></div>
                    <?php endfor; 
                endif;
                ?>
            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>