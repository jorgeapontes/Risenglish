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

// --- FUNÇÕES PARA AULAS RECORRENTES ---
function gerarDatasRecorrentes($data_inicio, $dia_semana, $quantidade_semanas = 4) {
    $datas = [];
    $data_atual = new DateTime($data_inicio);
    
    // Encontrar a primeira ocorrência do dia da semana
    $data_atual->modify('next ' . $dia_semana);
    
    for ($i = 0; $i < $quantidade_semanas; $i++) {
        $datas[] = $data_atual->format('Y-m-d');
        $data_atual->modify('+1 week');
    }
    
    return $datas;
}

function diaSemanaParaPortugues($dia_ingles) {
    $dias = [
        'monday' => 'Segunda-feira',
        'tuesday' => 'Terça-feira',
        'wednesday' => 'Quarta-feira',
        'thursday' => 'Quinta-feira',
        'friday' => 'Sexta-feira',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo'
    ];
    return $dias[strtolower($dia_ingles)] ?? $dia_ingles;
}

// --- LÓGICA DE PROCESSAMENTO DE FORMULÁRIOS ---
$mensagem = '';
$sucesso = false;

// 1. Lógica para aula única
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_unica') {
    $titulo_aula = trim($_POST['titulo_aula']);
    $descricao = trim($_POST['descricao']);
    $data_aula = $_POST['data_aula'];
    $horario = $_POST['horario'];
    $turma_id_post = $_POST['turma_id'];

    // Validação básica
    if (empty($titulo_aula) || empty($data_aula) || empty($horario) || empty($turma_id_post)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Insere a nova aula única
            $sql = "INSERT INTO aulas (professor_id, titulo_aula, descricao, data_aula, horario, turma_id, recorrente) 
                     VALUES (:professor_id, :titulo_aula, :descricao, :data_aula, :horario, :turma_id, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':professor_id' => $professor_id,
                ':titulo_aula' => $titulo_aula,
                ':descricao' => $descricao,
                ':data_aula' => $data_aula,
                ':horario' => $horario,
                ':turma_id' => $turma_id_post
            ]);
            
            $mensagem = "Aula única agendada com sucesso!";
            $sucesso = true;
            
            // Recarrega a página para mostrar a nova aula
            header("Location: detalhes_turma.php?turma_id=" . $turma_id . "&sucesso=" . ($sucesso ? '1' : '0') . "&mensagem=" . urlencode($mensagem));
            exit;
        } catch (PDOException $e) {
            $mensagem = "Erro ao agendar a aula: " . $e->getMessage();
        }
    }
}

// 2. Lógica para aulas recorrentes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_recorrente') {
    $turma_id_post = $_POST['turma_id'];
    $descricao = trim($_POST['descricao']);
    $dia_semana = $_POST['dia_semana'];
    $horario = $_POST['horario'];
    $quantidade_semanas = $_POST['quantidade_semanas'];
    
    // Busca o nome da turma para o título automático
    $sql_turma = "SELECT nome_turma FROM turmas WHERE id = :turma_id AND professor_id = :professor_id";
    $stmt_turma = $pdo->prepare($sql_turma);
    $stmt_turma->execute([':turma_id' => $turma_id_post, ':professor_id' => $professor_id]);
    $turma = $stmt_turma->fetch(PDO::FETCH_ASSOC);
    
    $titulo_aula = "Aulas " . ($turma['nome_turma'] ?? 'da Turma');

    // Validação básica
    if (empty($turma_id_post) || empty($dia_semana) || empty($horario)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Gera as datas recorrentes
            $data_inicio = date('Y-m-d');
            $datas_aulas = gerarDatasRecorrentes($data_inicio, $dia_semana, $quantidade_semanas);
            $aulas_criadas = 0;
            
            foreach ($datas_aulas as $data_aula) {
                $sql = "INSERT INTO aulas (professor_id, titulo_aula, descricao, data_aula, horario, turma_id, recorrente, dia_semana) 
                         VALUES (:professor_id, :titulo_aula, :descricao, :data_aula, :horario, :turma_id, 1, :dia_semana)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':professor_id' => $professor_id,
                    ':titulo_aula' => $titulo_aula,
                    ':descricao' => $descricao,
                    ':data_aula' => $data_aula,
                    ':horario' => $horario,
                    ':turma_id' => $turma_id_post,
                    ':dia_semana' => $dia_semana
                ]);
                $aulas_criadas++;
            }
            
            $mensagem = "{$aulas_criadas} aulas recorrentes agendadas com sucesso para as {$quantidade_semanas} próximas " . diaSemanaParaPortugues($dia_semana) . "s!";
            $sucesso = true;
            
            // Recarrega a página para mostrar as novas aulas
            header("Location: detalhes_turma.php?turma_id=" . $turma_id . "&sucesso=" . ($sucesso ? '1' : '0') . "&mensagem=" . urlencode($mensagem));
            exit;
        } catch (PDOException $e) {
            $mensagem = "Erro ao agendar as aulas recorrentes: " . $e->getMessage();
        }
    }
}

// Verificar se há mensagem na URL (após redirecionamento)
if (isset($_GET['sucesso']) && isset($_GET['mensagem'])) {
    $sucesso = $_GET['sucesso'] == '1';
    $mensagem = urldecode($_GET['mensagem']);
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

// --- CONSULTA PARA INFORMAÇÕES DOS ALUNOS DA TURMA ---
$sql_alunos = "
    SELECT 
        u.id, u.nome, u.email, u.informacoes
    FROM 
        usuarios u
    JOIN 
        alunos_turmas at ON u.id = at.aluno_id
    WHERE 
        at.turma_id = :turma_id
    ORDER BY 
        u.nome ASC
";
$stmt_alunos = $pdo->prepare($sql_alunos);
$stmt_alunos->execute([':turma_id' => $turma_id]);
$alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);

// --- LÓGICA DO CALENDÁRIO ---
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('n'));
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));
$primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
$dias_no_mes = date('t', $primeiro_dia);
$dia_inicio_semana = date('w', $primeiro_dia); // 0 (Dom) a 6 (Sáb)

// Datas para consultas do mês atual
$data_inicio_mes = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-01';
$data_fim_mes = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dias_no_mes, 2, '0', STR_PAD_LEFT);

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

// --- CONSULTA PARA AULAS NO MÊS DA TURMA (para o calendário) ---
$sql_aulas_calendario = "
    SELECT id, titulo_aula, data_aula, horario
    FROM aulas 
    WHERE turma_id = :turma_id 
      AND data_aula BETWEEN :data_inicio AND :data_fim
    ORDER BY data_aula, horario ASC
";
$stmt_aulas_calendario = $pdo->prepare($sql_aulas_calendario);
$stmt_aulas_calendario->execute([':turma_id' => $turma_id, ':data_inicio' => $data_inicio_mes, ':data_fim' => $data_fim_mes]);
$aulas_por_dia = [];
$aulas_lista = [];

while ($aula = $stmt_aulas_calendario->fetch(PDO::FETCH_ASSOC)) {
    $dia = date('j', strtotime($aula['data_aula'])); // Dia do mês (1 a 31)
    if (!isset($aulas_por_dia[$dia])) {
        $aulas_por_dia[$dia] = [];
    }
    $aulas_por_dia[$dia][] = $aula;
    $aulas_lista[] = $aula; // Armazenar lista completa de aulas para referência
}



// --- CONSULTA PARA ESTATÍSTICAS DE PRESENÇA DOS ALUNOS (APENAS DO MÊS ATUAL) ---
$sql_presenca_stats_mes = "
    SELECT 
        u.id AS aluno_id,
        u.nome AS aluno_nome,
        COUNT(DISTINCT a.id) AS total_aulas_mes,
        COUNT(DISTINCT CASE WHEN COALESCE(pa.presente, 1) = 1 THEN a.id END) AS total_presencas_mes,
        COUNT(DISTINCT CASE WHEN pa.presente = 0 THEN a.id END) AS total_faltas_mes,
        COUNT(DISTINCT CASE WHEN pa.id IS NULL AND a.data_aula <= CURDATE() THEN a.id END) AS total_aulas_sem_registro_mes
    FROM 
        usuarios u
    JOIN 
        alunos_turmas at ON u.id = at.aluno_id
    LEFT JOIN 
        aulas a ON at.turma_id = a.turma_id 
            AND a.data_aula BETWEEN :data_inicio AND :data_fim
    LEFT JOIN 
        presenca_aula pa ON a.id = pa.aula_id AND pa.aluno_id = u.id
    WHERE 
        at.turma_id = :turma_id
        AND u.tipo_usuario = 'aluno'
    GROUP BY 
        u.id, u.nome
    ORDER BY 
        u.nome ASC
";
$stmt_presenca_mes = $pdo->prepare($sql_presenca_stats_mes);
$stmt_presenca_mes->execute([
    ':turma_id' => $turma_id,
    ':data_inicio' => $data_inicio_mes,
    ':data_fim' => $data_fim_mes
]);
$presenca_stats_mes = $stmt_presenca_mes->fetchAll(PDO::FETCH_ASSOC);

// --- CONSULTA PARA ESTATÍSTICAS GERAIS DA TURMA (APENAS DO MÊS ATUAL) ---
$sql_estatisticas_gerais_mes = "
    SELECT 
        COUNT(DISTINCT a.id) AS total_aulas_mes,
        SUM(CASE WHEN COALESCE(pa.presente, 1) = 1 THEN 1 ELSE 0 END) AS total_presencas_geral_mes,
        SUM(CASE WHEN pa.presente = 0 THEN 1 ELSE 0 END) AS total_faltas_geral_mes
    FROM 
        aulas a
    LEFT JOIN 
        presenca_aula pa ON a.id = pa.aula_id
    WHERE 
        a.turma_id = :turma_id
        AND a.data_aula BETWEEN :data_inicio AND :data_fim
";
$stmt_estatisticas_mes = $pdo->prepare($sql_estatisticas_gerais_mes);
$stmt_estatisticas_mes->execute([
    ':turma_id' => $turma_id,
    ':data_inicio' => $data_inicio_mes,
    ':data_fim' => $data_fim_mes
]);
$estatisticas_gerais_mes = $stmt_estatisticas_mes->fetch(PDO::FETCH_ASSOC);

$total_aulas_mes = $estatisticas_gerais_mes['total_aulas_mes'] ?? 0;
$total_presencas_geral_mes = $estatisticas_gerais_mes['total_presencas_geral_mes'] ?? 0;
$total_faltas_geral_mes = $estatisticas_gerais_mes['total_faltas_geral_mes'] ?? 0;

// Calcular frequência média da turma no mês
$total_alunos = count($alunos);
$frequencia_media_mes = 0;

if ($total_aulas_mes > 0 && $total_alunos > 0) {
    // Primeiro, vamos calcular a soma das frequências individuais do mês
    $soma_frequencias_mes = 0;
    $alunos_com_aulas = 0;
    
    foreach ($presenca_stats_mes as $stats) {
        $presencas = $stats['total_presencas_mes'] ?? 0;
        $aulas_aluno = $stats['total_aulas_mes'] ?? 0;
        
        if ($aulas_aluno > 0) {
            $frequencia_aluno = ($presencas / $aulas_aluno) * 100;
            $soma_frequencias_mes += $frequencia_aluno;
            $alunos_com_aulas++;
        }
    }
    
    if ($alunos_com_aulas > 0) {
        $frequencia_media_mes = round($soma_frequencias_mes / $alunos_com_aulas, 1);
    }
}

// Calcular total máximo possível de presenças no mês
$total_maximo_presencas_mes = $total_aulas_mes * $total_alunos;

// Função para renderizar o seletor de horário
function renderTimePicker($id_prefix, $currentTime = '09:00') {
    $parts = explode(':', $currentTime);
    $h_sel = $parts[0] ?? '09';
    $m_sel = $parts[1] ?? '00';
    ?>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select time-hour" data-prefix="<?= $id_prefix ?>" style="width: 80px;">
            <?php for($i=0; $i<24; $i++) { 
                $v = sprintf("%02d", $i); 
                echo "<option value='$v' ".($v==$h_sel?'selected':'').">$v</option>"; 
            } ?>
        </select>
        <strong>:</strong>
        <select class="form-select time-minute" data-prefix="<?= $id_prefix ?>" style="width: 80px;">
            <?php for($i=0; $i<60; $i+=5) { 
                $v = sprintf("%02d", $i); 
                echo "<option value='$v' ".($v==$m_sel?'selected':'').">$v</option>"; 
            } ?>
        </select>
        <input type="hidden" name="horario" id="real_time_<?= $id_prefix ?>" value="<?= $currentTime ?>">
    </div>
    <?php
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
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <style>
        .informacoes-alunos {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .aluno-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .aluno-info:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .aluno-nome {
            font-weight: bold;
            color: #081d40;
            margin-bottom: 5px;
        }
        .aluno-informacoes {
            font-size: 0.9em;
            color: #495057;
            line-height: 1.4;
        }
        .sem-informacoes {
            font-style: italic;
            color: #6c757d;
        }

        .agendar {
            max-height: 200px;
        }
        
        .btn-agendar-modal {
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #081d40 0%, #1a365d 100%);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-agendar-modal:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background: linear-gradient(135deg, #081d40 0%, #1a365d 100%);
            color: white;
        }
        
        .modal-header-custom {
            background-color: #081d40;
            color: white;
        }
        
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
        /* Estilos para a seção de presença */
        .presenca-card {
            margin-top: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .presenca-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .presenca-body {
            padding: 15px;
        }
        
        .presenca-row {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .presenca-row:hover {
            background-color: #f8f9fa;
        }
        
        .presenca-row:last-child {
            border-bottom: none;
        }
        
        .presenca-nome {
            flex: 3;
            font-weight: 500;
        }
        
        .presenca-stats {
            flex: 2;
            text-align: center;
        }
        
        .presenca-frequencia {
            flex: 2;
            text-align: center;
        }
        
        .presenca-progresso {
            flex: 4;
            padding: 0 15px;
        }
        
        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .progress-label {
            min-width: 40px;
            text-align: right;
            font-weight: 500;
        }
        
        .progress-bar-custom {
            flex: 1;
            height: 12px;
            background-color: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #28a745;
            border-radius: 6px;
            transition: width 0.3s ease;
        }
        
        .progress-fill.baixa {
            background-color: #dc3545;
        }
        
        .progress-fill.media {
            background-color: #ffc107;
        }
        
        .badge-frequencia {
            font-size: 0.85em;
            padding: 4px 8px;
        }
        
        .stats-summary {
            display: flex;
            justify-content: space-around;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .stat-value.presencas {
            color: #28a745;
        }
        
        .stat-value.faltas {
            color: #dc3545;
        }
        
        .stat-value.frequencia {
            color: #007bff;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        /* Nova cor para o card de presenças */
        .card-presenca-turma {
            border: 1px solid #081d40;
            background: linear-gradient(135deg, #081d40 0%, #1a3b6e 100%);
            color: white;
        }
        
        .card-presenca-turma .card-header {
            background-color: #081d40;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card-presenca-turma .card-footer {
            background-color: rgba(8, 29, 64, 0.8);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card-presenca-turma .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        @media (max-width: 768px) {
            .presenca-row {
                flex-wrap: wrap;
                padding: 15px 0;
            }
            
            .presenca-nome {
                flex: 100%;
                margin-bottom: 10px;
            }
            
            .presenca-stats,
            .presenca-frequencia,
            .presenca-progresso {
                flex: 1 0 33.333%;
                margin-bottom: 10px;
            }
            
            .progress-container {
                flex-direction: column;
                gap: 5px;
            }
            
            .progress-label {
                text-align: center;
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
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= $mensagem ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <h2 class="mb-4 mt-3"><a id="back-link" href="gerenciar_alunos.php"> Gerenciamento de Turmas</a> > <strong><?= htmlspecialchars($turma_detalhes['nome_turma']) ?></strong></h2>
                
                <div class="row mb-4">
                    <!-- Coluna da esquerda - Informações da Turma (agora com 8 colunas) -->
                    <div class="col-md-8">
                        <div class="card h-100">
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
                                
                                <!-- Nova seção para Informações dos Alunos -->
                                <?php if (!empty($alunos)): ?>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h6><i class="fas fa-user-graduate me-2"></i>Informações dos Alunos:</h6>
                                        <div class="informacoes-alunos">
                                            <?php foreach ($alunos as $aluno): ?>
                                            <div class="aluno-info">
                                                <div class="aluno-nome">
                                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($aluno['nome']) ?>
                                                </div>
                                                <div class="aluno-informacoes">
                                                    <?php if (!empty($aluno['informacoes'])): ?>
                                                        <?= nl2br(htmlspecialchars($aluno['informacoes'])) ?>
                                                    <?php else: ?>
                                                        <span class="sem-informacoes">Sem informações adicionais</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Coluna da direita - Botão de Agendar e Estatísticas (agora com 4 colunas e empilhados) -->
                    <div class="col-md-4">
                        <!-- Botão para abrir modal de agendamento -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-agendar-modal w-100" data-bs-toggle="modal" data-bs-target="#modalAgendarAula">
                                <div class="d-flex flex-column justify-content-center align-items-center text-center py-4">
                                    <i class="fas fa-calendar-plus mb-3 fs-2"></i>
                                    <span class="fw-bold fs-5">Agendar Nova Aula</span>
                                    <small class="mt-2 opacity-75">Clique para agendar</small>
                                </div>
                            </button>
                        </div>
                        
                        <!-- Container de Estatísticas de Presença (com nova cor) -->
                        <div class="card card-presenca-turma h-auto">
                            <div class="card-header">
                                <i class="fas fa-clipboard-check me-2"></i> Presenças da Turma - <?= $nomes_meses[$mes] ?>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                                <?php if ($total_aulas_mes > 0): ?>
                                    <div class="display-4 fw-bold text-white mb-2">
                                        <?= $total_presencas_geral_mes ?> de <?= $total_maximo_presencas_mes ?>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-success fs-6"><?= $total_presencas_geral_mes ?> presenças</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-danger fs-6"><?= $total_faltas_geral_mes ?> faltas</span>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-white">
                                            <i class="fas fa-chart-line me-1"></i>
                                            Frequência média: <?= $frequencia_media_mes ?>%
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="py-4">
                                        <i class="fas fa-calendar-times fa-3x text-white-50 mb-3"></i>
                                        <p class="mb-0 text-white">Nenhuma aula em <?= $nomes_meses[$mes] ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <small class="text-white">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <?= $total_aulas_mes ?> aulas × <?= $total_alunos ?> alunos
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seção de Controle de Presença (APENAS DO MÊS ATUAL) -->
                <div class="presenca-card mt-4">
                    <div class="presenca-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-user-check me-2"></i> Acompanhamento Individual de Presença - <?= $nomes_meses[$mes] ?></h5>
                                <p class="mb-0 text-muted small">Frequência detalhada de cada aluno da turma no mês atual</p>
                            </div>
                            <div>
                                <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= $mes_anterior ?>&ano=<?= $mes_anterior == 12 ? $ano - 1 : $ano ?>" class="btn btn-sm btn-light me-2" title="Mês anterior">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= date('n') ?>&ano=<?= date('Y') ?>" class="btn btn-sm btn-light me-2" title="Mês atual">
                                    <i class="fas fa-calendar-day"></i>
                                </a>
                                <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= $mes_proximo ?>&ano=<?= $mes_proximo == 1 ? $ano + 1 : $ano ?>" class="btn btn-sm btn-light" title="Próximo mês">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="presenca-body">
                        <?php if (empty($presenca_stats_mes)): ?>
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h5>Nenhum aluno na turma ou nenhuma aula no mês</h5>
                                <p>Adicione alunos à turma ou agende aulas para <?= $nomes_meses[$mes] ?> para começar o acompanhamento.</p>
                            </div>
                        <?php else: ?>
                            <!-- Cabeçalho da tabela -->
                            <div class="presenca-row" style="background-color: #f8f9fa; font-weight: bold;">
                                <div class="presenca-nome">Aluno</div>
                                <div class="presenca-stats">Presenças / Aulas</div>
                                <div class="presenca-frequencia">Frequência</div>
                                <div class="presenca-progresso">Porcentagem</div>
                            </div>
                            
                            <?php foreach ($presenca_stats_mes as $stats): 
                                $aluno_id = $stats['aluno_id'];
                                $aluno_nome = $stats['aluno_nome'];
                                $total_aulas_aluno_mes = $stats['total_aulas_mes'] ?? 0;
                                $presencas_mes = $stats['total_presencas_mes'] ?? 0;
                                $faltas_mes = $stats['total_faltas_mes'] ?? 0;
                                $sem_registro_mes = $stats['total_aulas_sem_registro_mes'] ?? 0;
                                
                                // Calcular frequência do mês
                                $frequencia_mes = 0;
                                if ($total_aulas_aluno_mes > 0) {
                                    $frequencia_mes = round(($presencas_mes / $total_aulas_aluno_mes) * 100, 1);
                                }
                                
                                // Determinar cor do progresso baseado na frequência
                                $progress_class = '';
                                if ($frequencia_mes >= 80) {
                                    $progress_class = '';
                                } elseif ($frequencia_mes >= 60) {
                                    $progress_class = 'media';
                                } else {
                                    $progress_class = 'baixa';
                                }
                            ?>
                                <div class="presenca-row">
                                    <div class="presenca-nome">
                                        <strong><?= htmlspecialchars($aluno_nome) ?></strong>
                                    </div>
                                    <div class="presenca-stats">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <span class="badge bg-success fs-6"><?= $presencas_mes ?></span>
                                            <span class="fw-bold">/</span>
                                            <span class="badge bg-secondary fs-6"><?= $total_aulas_aluno_mes ?></span>
                                        </div>
                                        <?php if ($sem_registro_mes > 0): ?>
                                            <small class="text-muted d-block mt-1"><?= $sem_registro_mes ?> sem reg.</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="presenca-frequencia">
                                        <span class="badge badge-frequencia <?= $frequencia_mes >= 80 ? 'bg-success' : ($frequencia_mes >= 60 ? 'bg-warning' : 'bg-danger') ?>">
                                            <?= $frequencia_mes ?>%
                                        </span>
                                    </div>
                                    <div class="presenca-progresso">
                                        <div class="progress-container">
                                            <div class="progress-label"><?= $frequencia_mes ?>%</div>
                                            <div class="progress-bar-custom">
                                                <div class="progress-fill <?= $progress_class ?>" 
                                                     style="width: <?= min($frequencia_mes, 100) ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="espacamento">
                    
                </div>
                <!-- Calendário de Aulas -->
                <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark-blue text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Agenda da Turma</h5>
        <small>Arraste para reagendar</small>
    </div>
    <div class="card-body p-3">
        <div id='calendar-turma'></div>
    </div>
</div>
                <div class="text-center mt-3">
                    <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>&mes=<?= date('n') ?>&ano=<?= date('Y') ?>" class="btn btn-secondary">
                        <i class="fas fa-calendar-day me-2"></i>Voltar para Mês Atual
                    </a>
                </div>
            </div>
        </div>
    </div>

<!-- Modal Principal para Escolha do Tipo de Aula -->
<div class="modal fade" id="modalAgendarAula" tabindex="-1" aria-labelledby="modalAgendarAulaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="modalAgendarAulaLabel">Agendar Nova Aula</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-3">
                                    <i class="fas fa-calendar-day fa-3x text-danger mb-3"></i>
                                    <h5 class="card-title">Aula Única</h5>
                                    <p class="card-text">Para agendar uma aula em data específica.</p>
                                </div>
                                <button type="button" class="btn btn-danger mt-auto" data-bs-toggle="modal" data-bs-target="#modalAulaUnica" data-bs-dismiss="modal">
                                    <i class="fas fa-calendar-plus me-2"></i>Agendar Aula Única
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-3">
                                    <i class="fas fa-calendar-week fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Aula Recorrente</h5>
                                    <p class="card-text">Para agendar a mesma aula em múltiplas semanas.</p>
                                </div>
                                <button type="button" class="btn btn-success mt-auto" data-bs-toggle="modal" data-bs-target="#modalAulaRecorrente" data-bs-dismiss="modal">
                                    <i class="fas fa-calendar-plus me-2"></i>Agendar Aula Recorrente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aula Única -->
<div class="modal fade" id="modalAulaUnica" tabindex="-1" aria-labelledby="modalAulaUnicaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="modalAulaUnicaLabel">Agendar Aula Única</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="detalhes_turma.php?turma_id=<?= $turma_id ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar_unica">
                    <input type="hidden" name="turma_id" value="<?= $turma_id ?>">

                    <div class="mb-3">
                        <label for="titulo_aula_unica" class="form-label">Título da Aula <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titulo_aula_unica" name="titulo_aula" required placeholder="Ex: Aula de Gramática - Present Perfect">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_aula_unica" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="data_aula_unica" name="data_aula" required 
                                    value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Horário <span class="text-danger">*</span></label>
                            <?php renderTimePicker('unica', '09:00'); ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao_unica" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao_unica" name="descricao" rows="3" placeholder="Descreva o conteúdo que será ministrado..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-calendar-plus me-1"></i> Agendar Aula Única
                    </button>
                </div>
            </form>
        </div>
</div>
</div>

<!-- Modal Aula Recorrente -->
<div class="modal fade" id="modalAulaRecorrente" tabindex="-1" aria-labelledby="modalAulaRecorrenteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="modalAulaRecorrenteLabel">Agendar Aulas Recorrentes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="detalhes_turma.php?turma_id=<?= $turma_id ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar_recorrente">
                    <input type="hidden" name="turma_id" value="<?= $turma_id ?>">

                    <div class="mb-3">
                        <label class="form-label">Título das Aulas</label>
                        <input type="text" class="form-control" id="titulo_aula_recorrente" value="Aulas <?= htmlspecialchars($turma_detalhes['nome_turma']) ?>" readonly style="background-color: #f8f9fa;">
                        <small class="form-text text-muted">O título será automaticamente gerado como "Aulas [Nome da Turma]"</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dia_semana" class="form-label">Dia da Semana <span class="text-danger">*</span></label>
                            <select class="form-select" id="dia_semana" name="dia_semana" required>
                                <option value="monday">Segunda-feira</option>
                                <option value="tuesday">Terça-feira</option>
                                <option value="wednesday">Quarta-feira</option>
                                <option value="thursday">Quinta-feira</option>
                                <option value="friday">Sexta-feira</option>
                                <option value="saturday">Sábado</option>
                                <option value="sunday">Domingo</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Horário <span class="text-danger">*</span></label>
                            <?php renderTimePicker('recorrente', '09:00'); ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="quantidade_semanas" class="form-label">Número de Semanas <span class="text-danger">*</span></label>
                            <select class="form-select" id="quantidade_semanas" name="quantidade_semanas" required>
                                <option value="2">2 semanas</option>
                                <option value="4" selected>4 semanas (1 mês)</option>
                                <option value="8">8 semanas (2 meses)</option>
                                <option value="12">12 semanas (3 meses)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Próximas Datas</label>
                            <div id="preview_datas" class="form-control" style="background-color: #f8f9fa; height: auto; min-height: 38px; font-size: 0.9em;">
                                Selecione o dia da semana para visualizar as datas
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao_recorrente" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao_recorrente" name="descricao" rows="3" placeholder="Descreva o conteúdo que será ministrado nas aulas..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Serão criadas <span id="quantidade_aulas_span">4</span> aulas automaticamente para as próximas semanas no mesmo dia e horário.
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-calendar-plus me-1"></i> Agendar Aulas Recorrentes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Atualizar preview das datas quando mudar dia da semana ou quantidade
    $('#dia_semana, #quantidade_semanas').change(function() {
        atualizarPreviewDatas();
    });

    function atualizarPreviewDatas() {
        var diaSemana = $('#dia_semana').val();
        var quantidadeSemanas = $('#quantidade_semanas').val();
        
        if (!diaSemana) {
            $('#preview_datas').html('Selecione o dia da semana para visualizar as datas');
            return;
        }

        var dias = {
            'monday': 'Segunda',
            'tuesday': 'Terça', 
            'wednesday': 'Quarta',
            'thursday': 'Quinta',
            'friday': 'Sexta',
            'saturday': 'Sábado',
            'sunday': 'Domingo'
        };
        
        var hoje = new Date();
        var datasPreview = [];
        
        // Encontrar a próxima ocorrência do dia da semana
        var dataAtual = new Date(hoje);
        var diaSemanaNum = {'monday':1,'tuesday':2,'wednesday':3,'thursday':4,'friday':5,'saturday':6,'sunday':0}[diaSemana];
        
        // Ir para o próximo dia da semana
        var diff = (diaSemanaNum + 7 - dataAtual.getDay()) % 7;
        if (diff === 0) diff = 7; // Se for hoje, ir para próxima semana
        dataAtual.setDate(dataAtual.getDate() + diff);
        
        // Gerar preview das datas
        for (var i = 0; i < quantidadeSemanas; i++) {
            var dataStr = dataAtual.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
            datasPreview.push(dataStr);
            dataAtual.setDate(dataAtual.getDate() + 7);
        }
        
        $('#preview_datas').html('<small><strong>Datas:</strong> ' + datasPreview.join(', ') + '</small>');
    }

    // Atualizar quantidade de aulas no texto informativo
    $('#quantidade_semanas').change(function() {
        $('#quantidade_aulas_span').text($(this).val());
    });

    // Sincroniza os selects de Hora/Minuto com o input oculto que o PHP lê
    $('.time-hour, .time-minute').on('change', function() {
        var prefix = $(this).data('prefix');
        var h = $('.time-hour[data-prefix="'+prefix+'"]').val();
        var m = $('.time-minute[data-prefix="'+prefix+'"]').val();
        $('#real_time_' + prefix).val(h + ':' + m);
    });

    // Inicializar preview das datas
    atualizarPreviewDatas();
    
    // Configurar data mínima para hoje nos inputs de data
    var hoje = new Date().toISOString().split('T')[0];
    $('#data_aula_unica').attr('min', hoje);
    
    // Fechar alertas automaticamente após 5 segundos
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar-turma');
    var turmaId = <?= $turma_id ?>; // Pega o ID da turma via PHP

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth', // Alterado para MÊS por padrão como solicitado
        locale: 'pt-br',
        timeZone: 'local',
        firstDay: 1, 
        slotMinTime: '06:00:00',
        slotMaxTime: '24:00:00',
        allDaySlot: false,
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: { today: 'Hoje', month: 'Mês', week: 'Semana', day: 'Dia' },
        editable: true, 
        droppable: true,
        eventDisplay: 'block',
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
        
        // Carrega apenas aulas desta turma específica
        events: 'buscar_aulas_turma.php?turma_id=' + turmaId,
        
        eventClick: function(info) {
            window.location.href = 'detalhes_aula.php?aula_id=' + info.event.id;
        },

        eventDrop: function(info) {
            fetch('atualizar_aula.php', { // Reutiliza o arquivo que já funciona na dashboard
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: info.event.id,
                    novaData: info.event.startStr
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'success') {
                    alert("Erro ao atualizar: " + data.message);
                    info.revert();
                }
            })
            .catch(error => {
                alert("Erro de conexão.");
                info.revert();
            });
        }
    });
    calendar.render();
});
</script>
</body>
</html>