<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$nome_usuario = $_SESSION['user_nome'];

$mes_selecionado = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mes_selecionado)) {
    $mes_selecionado = date('Y-m');
}

$professor_id = isset($_GET['professor_id']) ? intval($_GET['professor_id']) : null;

$stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'professor' AND status = 'ativo' ORDER BY nome");
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$professor_id && !empty($professores)) {
    $professor_id = $professores[0]['id'];
}

$agenda = [];
$professor_nome = '';
$inicio_mes = $mes_selecionado . '-01';
$fim_mes = date('Y-m-t', strtotime($inicio_mes));

if ($professor_id) {
    foreach ($professores as $prof) {
        if ($prof['id'] == $professor_id) {
            $professor_nome = $prof['nome'];
            break;
        }
    }

    $stmt = $pdo->prepare(
        "SELECT a.id, a.titulo_aula, a.descricao, a.data_aula, a.horario, t.nome_turma
         FROM aulas a
         LEFT JOIN turmas t ON t.id = a.turma_id
         WHERE a.professor_id = ?
           AND a.data_aula BETWEEN ? AND ?
         ORDER BY a.data_aula, a.horario"
    );
    $stmt->execute([$professor_id, $inicio_mes, $fim_mes]);
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($aulas as $aula) {
        $agenda[$aula['data_aula']][] = $aula;
    }
}

$dia_semana_inicial = (int) date('w', strtotime($inicio_mes));
$dias_no_mes = (int) date('t', strtotime($inicio_mes));
$days = [];
for ($d = 1; $d <= $dias_no_mes; $d++) {
    $days[] = $d;
}

$weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

function buildCalendarRows(array $days, int $startWeekday): array {
    $rows = [];
    $row = array_fill(0, 7, null);
    $dayIndex = 0;

    for ($cell = 0; $cell < $startWeekday; $cell++) {
        $row[$cell] = null;
    }

    $weekday = $startWeekday;

    while ($dayIndex < count($days)) {
        $row[$weekday] = $days[$dayIndex];
        $dayIndex++;
        $weekday++;

        if ($weekday === 7) {
            $rows[] = $row;
            $row = array_fill(0, 7, null);
            $weekday = 0;
        }
    }

    if ($weekday !== 0) {
        $rows[] = $row;
    }

    return $rows;
}

$calendarRows = buildCalendarRows($days, $dia_semana_inicial);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendas - Admin Risenglish</title>
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

        .page-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .calendar-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .calendar-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #f1f3f5;
        }

        .calendar-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar-grid th,
        .calendar-grid td {
            border: 1px solid #e9ecef;
            vertical-align: top;
            padding: 12px;
            min-height: 120px;
        }

        .calendar-grid td {
            padding: 8px;
        }

        .cell-content {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 6px;
        }

        .cell-content::-webkit-scrollbar {
            width: 6px;
        }

        .cell-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .cell-content::-webkit-scrollbar-thumb {
            background: rgba(13, 110, 253, 0.35);
            border-radius: 10px;
        }

        .calendar-grid th {
            background: #f8f9fb;
            color: #495057;
            text-align: center;
            font-weight: 600;
        }

        .day-number {
            font-weight: 700;
            margin-bottom: 8px;
            display: inline-block;
        }

        .day-cell-empty {
            color: #adb5bd;
        }

        .event-item {
            display: block;
            padding: 8px 10px;
            margin-bottom: 8px;
            border-radius: 10px;
            background: #eef4ff;
            color: #0d6efd;
            font-size: 0.9rem;
        }

        .event-time {
            font-weight: 700;
            margin-right: 6px;
        }

        .event-turma {
            display: block;
            margin-top: 3px;
            font-size: 0.82rem;
            color: #495057;
        }

        .calendar-footer {
            padding: 20px;
            color: #6c757d;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar {
                position: relative;
                height: auto;
                width: 100%;
            }
            .calendar-grid th, .calendar-grid td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Admin'); ?></h5>
        </div>

        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="leads.php" class="rounded"><i class="fas fa-user-tie"></i>&nbsp;&nbsp;Leads</a>
            <a href="personalizar_index.php" class="rounded"><i class="fas fa-paint-brush"></i>&nbsp;&nbsp;Personalizar Site</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="agendas.php" class="rounded active"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;Agendas</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
            <a href="acessos.php" class="rounded"><i class="fas fa-chart-line"></i>&nbsp;&nbsp;Relatório de Acessos</a>
        </div>

        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <div class="main-content flex-grow-1">
        <div class="page-header">
            <div>
                <h1>Agendas dos Professores</h1>
            </div>
        </div>

        <div class="calendar-card">
            <?php if (empty($professores)): ?>
                <div class="calendar-footer">Nenhum professor ativo encontrado.</div>
            <?php else: ?>
                <form method="get">
                    <div class="calendar-controls">
                        <div style="display: flex; gap: 15px; align-items: center">
                            <strong>Professor:</strong> 
                            <select id="professor_id" name="professor_id" class="form-select" style="min-width: 220px;" onchange="this.form.submit()">
                            <?php foreach ($professores as $professor): ?>
                                <option value="<?php echo $professor['id']; ?>" <?php echo ($professor['id'] == $professor_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($professor['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display: flex; gap: 15px; align-items: center">
                            <strong>Mês:</strong>
                            <input id="mes" name="mes" type="month" class="form-control" value="<?php echo htmlspecialchars($mes_selecionado); ?>" onchange="this.form.submit()">
                        </div>
                    </div>
                </form>
                <table class="calendar-grid">
                    <thead>
                        <tr>
                            <?php foreach ($weekDays as $weekday): ?>
                                <th><?php echo $weekday; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calendarRows as $row): ?>
                            <tr>
                                <?php foreach ($row as $day): ?>
                                    <td>
                                        <?php if ($day === null): ?>
                                            <div class="day-cell-empty">&nbsp;</div>
                                        <?php else: ?>
                                            <?php $dateKey = sprintf('%s-%02d', $mes_selecionado, $day); ?>
                                            <div class="cell-content">
                                                <div class="day-number"><?php echo $day; ?></div>
                                                <?php if (!empty($agenda[$dateKey])): ?>
                                                    <?php foreach ($agenda[$dateKey] as $evento): ?>
                                                        <div class="event-item">
                                                            <span class="event-time"><?php echo htmlspecialchars(date('H:i', strtotime($evento['horario']))); ?></span>
                                                            <?php echo htmlspecialchars($evento['titulo_aula']); ?>
                                                            <span class="event-turma"><?php echo htmlspecialchars($evento['nome_turma'] ?: 'Turma não informada'); ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="text-muted small">Sem aulas</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="calendar-footer">Exibindo agenda do professor selecionado para o mês escolhido.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
