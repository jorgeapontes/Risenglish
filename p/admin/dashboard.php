<?php
session_start();
require_once '../includes/conexao.php';

// Verifica se a conexão PDO existe
if (!isset($pdo)) {
    die("Erro: Conexão com o banco de dados não estabelecida.");
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$nome_usuario = $_SESSION['user_nome'];

// Estatísticas do dashboard
$mes_atual = date('Y-m');
$inicio_mes = date('Y-m-01');
$fim_mes = date('Y-m-t');

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'aluno' AND status = 'ativo'");
    $alunos_ativos = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE status = 'matriculado' AND DATE_FORMAT(data_criacao, '%Y-%m') = ?");
    $stmt->execute([$mes_atual]);
    $novos_alunos = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'professor' AND status = 'ativo'");
    $professores = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aulas WHERE data_aula BETWEEN ? AND ?");
    $stmt->execute([$inicio_mes, $fim_mes]);
    $aulas_mes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT turma_id) as total FROM aulas WHERE data_aula BETWEEN ? AND ?");
    $stmt->execute([$inicio_mes, $fim_mes]);
    $turmas_ativas = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->prepare("SELECT IFNULL(SUM(valor), 0) as total FROM pagamentos WHERE data_pagamento BETWEEN ? AND ?");
    $stmt->execute([$inicio_mes, $fim_mes]);
    $receita_liquida = (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Buscar pagamentos diários
    $stmt = $pdo->prepare("SELECT data_pagamento, SUM(valor) as total_valor FROM pagamentos WHERE data_pagamento BETWEEN ? AND ? GROUP BY data_pagamento ORDER BY data_pagamento");
    $stmt->execute([$inicio_mes, $fim_mes]);
    $pagamentos_diarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Criar array completo com todos os dias do mês
    $receita_diaria = [];
    $data_inicio = new DateTime($inicio_mes);
    $data_fim = new DateTime($fim_mes);
    $data_atual = clone $data_inicio;

    // Mapear pagamentos existentes por data
    $pagamentos_map = [];
    foreach ($pagamentos_diarios as $pagamento) {
        $pagamentos_map[$pagamento['data_pagamento']] = (float) $pagamento['total_valor'];
    }

    // Preencher todos os dias do mês
    while ($data_atual <= $data_fim) {
        $data_formatada = $data_atual->format('Y-m-d');
        $receita_diaria[] = [
            'data_pagamento' => $data_formatada,
            'total_valor' => $pagamentos_map[$data_formatada] ?? 0
        ];
        $data_atual->modify('+1 day');
    }
} catch (PDOException $e) {
    $alunos_ativos = $novos_alunos = $professores = $aulas_mes = $turmas_ativas = 0;
    $receita_liquida = 0;
    $receita_diaria = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads - Admin Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        :root {
            --cor-primaria: #0A1931;
            --cor-secundaria: #c0392b;
            --cor-destaque: #c0392b;
            --cor-texto: #333;
            --cor-fundo: #f8f9fa;
            --cor-borda: #dee2e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
        }

        /* Sidebar */
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

        .sidebar .active:hover {
            background-color: #c0392b;
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        /* Main content */
        .main-content {
            margin-left: 16.666667%;
            width: 83.333333%;
            padding: 30px;
            min-height: 100vh;
        }

        h1 {
            color: var(--cor-primaria);
            font-weight: 600;
        }

        .dashboard-summary {
            margin-bottom: 24px;
        }

        .summary-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 16px;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        .summary-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: #2c3e50;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0d6efd;
        }

        .summary-card small {
            display: block;
            margin-top: 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .summary-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: white;
            margin-bottom: 16px;
        }

        .summary-card .icon-ativos { background-color: #198754; }
        .summary-card .icon-novos { background-color: #0d6efd; }
        .summary-card .icon-professores { background-color: #6610f2; }
        .summary-card .icon-aulas { background-color: #fd7e14; }
        .summary-card .icon-turmas { background-color: #20c997; }
        .summary-card .icon-receita { background-color: #dc3545; }

        .chart-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .chart-card-header {
            padding: 20px;
            border-bottom: 1px solid #f1f3f5;
            background: #f8f9fb;
        }

        .chart-card-header h2 {
            margin: 0;
            font-size: 1.1rem;
            color: #1f2a37;
            font-weight: 600;
        }

        .chart-card-body {
            padding: 20px;
        }

        .chart-card-body canvas {
            width: 100% !important;
            min-height: 320px;
        }

        .chart-empty {
            text-align: center;
            color: #6c757d;
            padding: 40px 0;
        }

        /* Scrollbar */
        .kanban-cards::-webkit-scrollbar {
            width: 6px;
        }

        .kanban-cards::-webkit-scrollbar-track {
            background: #e9ecef;
            border-radius: 10px;
        }

        .kanban-cards::-webkit-scrollbar-thumb {
            background: #c0392b;
            border-radius: 10px;
        }

        .dashboard-summary {
            margin-bottom: 24px;
        }

        .summary-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 16px;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        .summary-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: #2c3e50;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0d6efd;
        }

        .summary-card small {
            display: block;
            margin-top: 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .summary-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: white;
            margin-bottom: 16px;
        }

        .summary-card .icon-ativos { background-color: #198754; }
        .summary-card .icon-novos { background-color: #0d6efd; }
        .summary-card .icon-professores { background-color: #6610f2; }
        .summary-card .icon-aulas { background-color: #fd7e14; }
        .summary-card .icon-turmas { background-color: #20c997; }
        .summary-card .icon-receita { background-color: #dc3545; }

        .chart-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .chart-card-header {
            padding: 20px;
            border-bottom: 1px solid #f1f3f5;
            background: #f8f9fb;
        }

        .chart-card-header h2 {
            margin: 0;
            font-size: 1.1rem;
            color: #1f2a37;
            font-weight: 600;
        }

        .chart-card-body {
            padding: 20px;
        }

        .chart-card-body canvas {
            width: 100% !important;
            min-height: 320px;
        }

        .chart-empty {
            text-align: center;
            color: #6c757d;
            padding: 40px 0;
        }

        /* MELHORIA 2: Área de clique maior no menu de 3 pontos */
        .lead-menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            color: #6c757d;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
            margin: -6px -4px -6px 4px;
        }

        .lead-menu-btn:hover {
            background-color: #e9ecef;
            color: #333;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .kanban-board {
                flex-direction: column;
                overflow-x: unset;
            }
            .kanban-column {
                width: 100%;
                min-width: auto;
                max-height: none;
            }
            .btn-floating {
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Admin'); ?></h5>
        </div>

        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" class="rounded active"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="leads.php" class="rounded"><i class="fas fa-user-tie"></i>&nbsp;&nbsp;Leads</a>
            <a href="personalizar_index.php" class="rounded"><i class="fas fa-paint-brush"></i>&nbsp;&nbsp;Personalizar Site</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="agendas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;Agendas</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
            <a href="acessos.php" class="rounded"><i class="fas fa-chart-line"></i>&nbsp;&nbsp;Relatório de Acessos</a>
        </div>

        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <div class="row dashboard-summary g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <div class="summary-card">
                    <div>
                        <div class="summary-icon icon-ativos"><i class="fas fa-user-check"></i></div>
                        <h3>Alunos Ativos</h3>
                        <div class="summary-value"><?php echo number_format($alunos_ativos, 0, ',', '.'); ?></div>
                    </div>
                    <small>Total de alunos ativos no sistema</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="summary-card">
                    <div>
                        <div class="summary-icon icon-novos"><i class="fas fa-user-plus"></i></div>
                        <h3>Novos Alunos</h3>
                        <div class="summary-value"><?php echo number_format($novos_alunos, 0, ',', '.'); ?></div>
                    </div>
                    <small>Novos alunos matriculados este mês</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="summary-card">
                    <div>
                        <div class="summary-icon icon-professores"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h3>Professores</h3>
                        <div class="summary-value"><?php echo number_format($professores, 0, ',', '.'); ?></div>
                    </div>
                    <small>Professores ativos no sistema</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="summary-card">
                    <div>
                        <div class="summary-icon icon-aulas"><i class="fas fa-calendar-day"></i></div>
                        <h3>Total de Aulas no Mês</h3>
                        <div class="summary-value"><?php echo number_format($aulas_mes, 0, ',', '.'); ?></div>
                    </div>
                    <small>Aulas agendadas para o mês atual</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="summary-card">
                    <div>
                        <div class="summary-icon icon-turmas"><i class="fas fa-school"></i></div>
                        <h3>Turmas Ativas</h3>
                        <div class="summary-value"><?php echo number_format($turmas_ativas, 0, ',', '.'); ?></div>
                    </div>
                    <small>Turmas com aulas neste mês</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="summary-card">
                    <div>
                        <div class="summary-icon icon-receita"><i class="fas fa-dollar-sign"></i></div>
                        <h3>Receita Total Líquida</h3>
                        <div class="summary-value">R$ <?php echo number_format($receita_liquida, 2, ',', '.'); ?></div>
                    </div>
                    <small>Recebimentos efetivados no mês</small>
                </div>
            </div>
        </div>

        <div class="chart-card mt-4">
            <div class="chart-card-header">
                <h2>Receita recebida por dia</h2>
            </div>
            <div class="chart-card-body">
                <?php if (!empty($receita_diaria)): ?>
                    <canvas id="dailyRevenueChart" height="120"></canvas>
                <?php else: ?>
                    <div class="chart-empty">
                        <i class="fas fa-chart-line fa-2x mb-3"></i>
                        <p class="mb-0">Nenhum pagamento registrado neste mês.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const chartCanvas = document.getElementById('dailyRevenueChart');
    if (!chartCanvas) return;

    const revenueLabels = <?php echo json_encode(array_map(function($item) {
        return date('d/m', strtotime($item['data_pagamento']));
    }, $receita_diaria)); ?>;
    const revenueValues = <?php echo json_encode(array_map(function($item) {
        return (float) $item['total_valor'];
    }, $receita_diaria)); ?>;

    new Chart(chartCanvas, {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Receita diária',
                data: revenueValues,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.18)',
                tension: 0.35,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#0d6efd',
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#495057' }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#495057',
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            }
        }
    });
})();
</script>
</body>
</html>