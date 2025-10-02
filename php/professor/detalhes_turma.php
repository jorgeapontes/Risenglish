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

// Mapeamento dos meses para exibição
$nomes_meses = [
    '1' => 'Janeiro', '2' => 'Fevereiro', '3' => 'Março', '4' => 'Abril', 
    '5' => 'Maio', '6' => 'Junho', '7' => 'Julho', '8' => 'Agosto', 
    '9' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

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
            width: 16.666667%;
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
            margin-left: 16.666667%;
            width: 83.333333%;
            min-height: 100vh;
            overflow-y: auto;
            padding: 30px;
        }

        .card-header {
            background-color: #081d40;
            color: white;
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

        /* Estilos do calendário */
        .calendario {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            margin-top: 20px;
        }

        .dia-semana {
            background-color: #081d40;
            color: white;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            border-radius: 3px;
        }

        .celula-dia {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 120px;
            padding: 8px;
            position: relative;
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        .celula-dia.outros-meses {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .numero-dia {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 5px;
            display: block;
        }

        .bloco-aula {
            background-color: #081d40;
            color: white;
            padding: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
            font-size: 0.85em;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .bloco-aula:hover {
            background-color: #32475b;
        }

        .bloco-aula strong {
            display: block;
            font-size: 0.8em;
        }

        .bloco-aula span {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Estilos específicos para detalhes_turma */
        .info-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .btn-agendar {
            background-color: #c0392b;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            height: 100%;
        }

        .btn-agendar:hover {
            background-color: #a93226;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .aula-item {
            background-color: #e9ecef;
            border-left: 3px solid #c0392b;
            padding: 4px 6px;
            margin: 3px 0;
            border-radius: 4px;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .aula-item:hover {
            background-color: #dee2e6;
            transform: scale(1.02);
        }

        .aula-item a {
            color: #333;
            text-decoration: none;
        }

        .aula-item a:hover {
            color: #c0392b;
        }

        /* Calendário estilo dashboard */
        .calendario-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .calendario-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0px;
            background-color: #ddd;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .calendario-header {
            background-color: #081d40;
            color: white;
            text-align: center;
            padding: 12px;
            font-weight: bold;
        }

        .calendario-dia {
            background-color: white;
            min-height: 100px;
            padding: 8px;
            border: 1px solid #e9ecef;
        }

        .calendario-dia.hoje {
            background-color: #f5f5f5;
            border: 2px solid red;
        }

        .calendario-dia.outro-mes {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .dia-numero {
            font-weight: bold;
            margin-bottom: 5px;
            color: #081d40;
        }

        #back-link {
            text-decoration: none;
            color: #081d40
        }

        #back-link:hover {
            text-decoration: none;
            color: #384d90
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
                padding: 15px;
            }

            .calendario-dia {
                min-height: 80px;
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
                            $data_completa = "$ano-$mes-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
                            $is_hoje = (date('Y-m-d') == $data_completa);
                        ?>
                            <div class="calendario-dia <?= $is_hoje ? 'hoje' : '' ?>">
                                <span class="dia-numero"><?= $dia ?></span>
                                
                                <?php if (isset($aulas_por_dia[$dia])): ?>
                                    <?php foreach ($aulas_por_dia[$dia] as $aula): ?>
                                        <div class="aula-item" title="<?= htmlspecialchars($aula['titulo_aula']) ?>">
                                            <small>
                                                <i class="far fa-clock me-1"></i>
                                                <?= substr($aula['horario'], 0, 5) ?>
                                                <a href="gerenciar_aulas.php?editar=<?= $aula['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($aula['titulo_aula']) ?>
                                                </a>
                                            </small>
                                        </div>
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