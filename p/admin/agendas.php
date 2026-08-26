<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Garante que apenas admin acessa esta página
if ($_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login?erro=acesso_negado");
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
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/admin/base.css">
    <link rel="stylesheet" href="../../css/admin/agendas.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php $paginaAtiva = 'agendas'; require '../includes/layout/admin_sidebar.php'; ?>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                    url: 'agendas',
                    method: 'GET',
                    extraParams: {
                        acao: 'buscar_eventos',
                        professor_id: '<?php echo $professor_id; ?>'
                    }
                },
                eventClick: function(info) {
                    window.location.href = '../professor/detalhes_aula?aula_id=' + info.event.id;
                }
            });
            calendar.render();

            var professorSelect = document.getElementById('professor_id');
            professorSelect.addEventListener('change', function() {
                calendar.setOption('events', {
                    url: 'agendas',
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
