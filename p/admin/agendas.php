<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$nome_usuario = $_SESSION['user_nome'];

date_default_timezone_set('America/Sao_Paulo');

$professor_id = isset($_GET['professor_id']) ? intval($_GET['professor_id']) : null;

$stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'professor' AND status = 'ativo' ORDER BY nome");
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$professor_id && !empty($professores)) {
    $professor_id = $professores[0]['id'];
}

if (isset($_GET['acao']) && $_GET['acao'] === 'buscar_eventos') {
    header('Content-Type: application/json; charset=utf-8');

    $professor_id_ajax = isset($_GET['professor_id']) ? intval($_GET['professor_id']) : 0;
    $start = isset($_GET['start']) ? date('Y-m-d', strtotime($_GET['start'])) : null;
    $end = isset($_GET['end']) ? date('Y-m-d', strtotime($_GET['end'])) : null;

    if (!$professor_id_ajax || !$start || !$end) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT a.id, a.titulo_aula, a.data_aula, a.horario, t.nome_turma
         FROM aulas a
         LEFT JOIN turmas t ON t.id = a.turma_id
         WHERE a.professor_id = ?
           AND a.data_aula BETWEEN ? AND ?
         ORDER BY a.data_aula, a.horario"
    );
    $stmt->execute([$professor_id_ajax, $start, $end]);
    $aulas_ajax = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventos_ajax = [];
    foreach ($aulas_ajax as $aula) {
        $eventos_ajax[] = [
            'id' => $aula['id'],
            'title' => $aula['titulo_aula'],
            'start' => $aula['data_aula'] . 'T' . $aula['horario'],
            'backgroundColor' => '#081d40',
            'borderColor' => '#081d40',
            'allDay' => false
        ];
    }

    echo json_encode($eventos_ajax);
    exit;
}

$agenda = [];
$professor_nome = '';

$hoje = new DateTime('today');
$dia_semana = (int) $hoje->format('w');
$inicio_semana = (clone $hoje)->modify('-' . $dia_semana . ' days');
$fim_semana = (clone $inicio_semana)->modify('+6 days');

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
    $stmt->execute([$professor_id, $inicio_semana->format('Y-m-d'), $fim_semana->format('Y-m-d')]);
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($aulas as $aula) {
        $agenda[$aula['data_aula']][] = $aula;
    }
}

$weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$weekDates = [];
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = (clone $inicio_semana)->modify("+{$i} days");
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendas - Admin Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/professor/dashboard.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        body {
            background-color: #FAF9F6;
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

        .calendar-header h2 {
            margin-bottom: 0;
            color: #081d40;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
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

            <div class="col-md-10 main-content p-4">

                <div class="main-content-container">
                    <div class="main-content-container p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="h3 mb-1 fw-bold" style="color: #081d40;">Agenda do Professor</h1>
                            </div>
                            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3 mt-3">
                                <label for="professor_id" class="mb-0 fw-semibold" style="color: #081d40;">Selecionar professor:</label>
                                <select id="professor_id" class="form-select" style="min-width: 240px; max-width: 360px;">
                                    <?php foreach ($professores as $professor): ?>
                                        <option value="<?php echo $professor['id']; ?>" <?php echo ($professor['id'] == $professor_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($professor['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="calendar-card shadow-sm border-0">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                initialDate: '<?php echo $hoje->format('Y-m-d'); ?>',
                locale: 'pt-br',
                timeZone: 'local',
                firstDay: 0,
                slotMinTime: '06:00:00',
                slotMaxTime: '24:00:00',
                allDaySlot: false,
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoje', month: 'Mês', week: 'Semana', day: 'Dia'
                },
                editable: false,
                droppable: false,
                eventDurationEditable: false,
                eventDisplay: 'block',
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
                events: {
                    url: 'agendas.php',
                    method: 'GET',
                    extraParams: {
                        acao: 'buscar_eventos',
                        professor_id: '<?php echo $professor_id; ?>'
                    }
                },
                eventClick: function(info) {
                    window.location.href = 'detalhes_aula.php?aula_id=' + info.event.id;
                }
            });
            calendar.render();

            var professorSelect = document.getElementById('professor_id');
            professorSelect.addEventListener('change', function() {
                calendar.setOption('events', {
                    url: 'agendas.php',
                    method: 'GET',
                    extraParams: {
                        acao: 'buscar_eventos',
                        professor_id: this.value
                    }
                });
                calendar.refetchEvents();
            });
        });
    </script>
</body>
</html>
