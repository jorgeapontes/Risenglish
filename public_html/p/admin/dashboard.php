<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Verifica se a conexão PDO existe
if (!isset($pdo)) {
    die("Erro: Conexão com o banco de dados não estabelecida.");
}

if ($_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login");
    exit;
}

$nome_usuario = $_SESSION['user_nome'];

// Seleção do mês para visualização
$mes_atual = date('Y-m');
if (isset($_GET['mes']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['mes'])) {
    $mes_atual = $_GET['mes'];
}
$inicio_mes = $mes_atual . '-01';
$fim_mes = date('Y-m-t', strtotime($inicio_mes));

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

    $stmt = $pdo->prepare("SELECT IFNULL(SUM(valor), 0) as total FROM pagamentos WHERE mes_referencia = ?");
    $stmt->execute([$inicio_mes]);
    $receita_liquida = (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Buscar pagamentos diários (agrupados pela data em que foram pagos, mas filtrados pelo mês de referência)
    $stmt = $pdo->prepare("SELECT data_pagamento, SUM(valor) as total_valor FROM pagamentos WHERE mes_referencia = ? GROUP BY data_pagamento ORDER BY data_pagamento");
    $stmt->execute([$inicio_mes]);
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
    <link rel="stylesheet" href="../../css/admin/base.css">
    <link rel="stylesheet" href="../../css/admin/dashboard.css">
</head>
<body>

<div class="d-flex">
    <?php $paginaAtiva = 'dashboard'; require '../includes/layout/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <div class="d-flex flex-column flex-md-row align-items-start justify-content-between mb-4 gap-3">
            <div>
                <h1>Dashboard</h1>
                <p class="text-muted mb-0">Mês selecionado: <?php echo date('m/Y', strtotime($inicio_mes)); ?></p>
            </div>
            <form method="get" class="d-flex align-items-center gap-2">
                <label for="mes" class="visually-hidden">Mês</label>
                <input id="mes" name="mes" type="month" class="form-control" value="<?php echo htmlspecialchars($mes_atual); ?>" onchange="this.form.submit()" />
            </form>
        </div>

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