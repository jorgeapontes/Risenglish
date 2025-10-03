<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

// 📌 CORREÇÃO DE FUSO HORÁRIO: Garante que a data 'hoje' seja calculada corretamente,
// evitando que o servidor UTC (ou outro fuso) avance o dia antes do fuso local.
date_default_timezone_set('America/Sao_Paulo');

$professor_id = $_SESSION['user_id'];
$turma_id = $_GET['turma_id'] ?? null;

if (!$turma_id || !is_numeric($turma_id)) {
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
    header("Location: gerenciar_alunos.php");
    exit;
}

// --- LÓGICA DO CALENDÁRIO ---
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('n'));
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));
$primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
$dias_no_mes = date('t', $primeiro_dia);
$dia_inicio_semana = date('w', $primeiro_dia); // 0 (Dom) a 6 (Sáb)

// Mapeamento dos meses para exibição
$nomes_meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// Calcula o mês anterior e próximo
$mes_anterior = $mes - 1;
$ano_anterior = $ano;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $ano_anterior--;
}
$mes_proximo = $mes + 1;
$ano_proximo = $ano;
if ($mes_proximo > 12) {
    $mes_proximo = 1;
    $ano_proximo++;
}

// --- CONSULTA PARA AULAS NO MÊS DA TURMA ---
$data_inicio = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-01';
$data_fim = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dias_no_mes, 2, '0', STR_PAD_LEFT);

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
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center">
                    <h5 class="mt-4">Prof. <?= htmlspecialchars($_SESSION['user_nome'] ?? 'Professor') ?></h5>
                </div>
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded active"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>
            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <h2 class="mb-4 mt-3"><a id="back-link" href="gerenciar_alunos.php"> Gerenciamento de Turmas</a> > <strong><?= htmlspecialchars($turma_detalhes['nome_turma']) ?></strong></h2>
                <div class="row mb-4">
                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-info-circle me-2"></i> Informações da Turma
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
                                        <strong>Status:</strong> <span class="badge bg-success">Ativa</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <a href="gerenciar_aulas.php?turma_id=<?= $turma_id ?>" class="btn btn-agendar w-100 d-flex flex-column justify-content-center align-items-center text-center">
                            <i class="fas fa-calendar-plus mb-2 fs-4"></i>
                            <span>Agendar Nova Aula</span>
                            <small class="mt-1 opacity-75">Ir para agendamento</small>
                        </a>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= $mes_anterior ?>&ano=<?= $ano_anterior ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i> <?= $nomes_meses[$mes_anterior] ?>
                    </a>
                    <h3 class="mb-0"><?= $nomes_meses[$mes] ?> de <?= $ano ?></h3>
                    <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= $mes_proximo ?>&ano=<?= $ano_proximo ?>" class="btn btn-outline-secondary">
                        <?= $nomes_meses[$mes_proximo] ?> <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <div class="calendario-grid">
                    <!-- Cabeçalho dos dias da semana -->
                    <div class="calendario-header">DOM</div>
                    <div class="calendario-header">SEG</div>
                    <div class="calendario-header">TER</div>
                    <div class="calendario-header">QUA</div>
                    <div class="calendario-header">QUI</div>
                    <div class="calendario-header">SEX</div>
                    <div class="calendario-header">SÁB</div>
                    <!-- Dias vazios no início -->
                    <?php for ($i = 0; $i < $dia_inicio_semana; $i++): ?>
                        <div class="calendario-dia outro-mes"></div>
                    <?php endfor; ?>
                    <!-- Dias do mês -->
                    <?php for ($dia = 1; $dia <= $dias_no_mes; $dia++): 
                        $data_completa = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);
                        $is_hoje = (date('Y-m-d') == $data_completa);
                    ?>
                        <div class="calendario-dia <?= $is_hoje ? 'hoje' : '' ?>">
                            <span class="dia-numero"><?= $dia ?></span>
                            <?php if (isset($aulas_por_dia[$dia])): ?>
                                <?php foreach ($aulas_por_dia[$dia] as $aula): ?>
                                    <a href="detalhes_aula.php?aula_id=<?= $aula['id'] ?>" class="aula-item text-decoration-none" title="<?= htmlspecialchars($aula['titulo_aula']) ?>">
                                        <small>
                                            <i class="far fa-clock me-1"></i>
                                            <?= substr($aula['horario'], 0, 5) ?>
                                            <?= htmlspecialchars($aula['titulo_aula']) ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                    <!-- Dias vazios no final -->
                    <?php 
                    $total_celulas = $dia_inicio_semana + $dias_no_mes;
                    $celulas_faltantes = (7 - ($total_celulas % 7)) % 7;
                    for ($i = 0; $i < $celulas_faltantes; $i++): ?>
                        <div class="calendario-dia outro-mes"></div>
                    <?php endfor; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= date('n') ?>&ano=<?= date('Y') ?>" class="btn btn-secondary">
                        <i class="fas fa-calendar-day me-2"></i>Voltar para Mês Atual
                    </a>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
