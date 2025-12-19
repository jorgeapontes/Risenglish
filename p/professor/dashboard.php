<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}
date_default_timezone_set('America/Sao_Paulo');

$professor_id = $_SESSION['user_id'];
// NOVO: Puxa o nome do professor da sessão. Se não existir, usa 'Professor'
$professor_nome = $_SESSION['user_nome'] ?? 'Professor';

// DEBUG: Se o nome não estiver aparecendo, remova os comentários da linha abaixo
// print_r("DEBUG: O nome do professor é: " . $professor_nome); 


$data_hoje = new DateTime();
$mes_atual = $data_hoje->format('m');
$ano_atual = $data_hoje->format('Y');

// Opcional: Permitir que o professor navegue por mês (se houver parâmetro na URL)
$mes = $_GET['mes'] ?? $mes_atual;
$ano = $_GET['ano'] ?? $ano_atual;

// Cria o objeto DateTime para o primeiro dia do mês
$primeiro_dia_mes = new DateTime("$ano-$mes-01");
$num_dias = $primeiro_dia_mes->format('t');

// Data para navegação
$data_prox = (clone $primeiro_dia_mes)->modify('+1 month');
$data_ant = (clone $primeiro_dia_mes)->modify('-1 month');

// Array para armazenar aulas por dia
$aulas_por_dia = [];

// --- CONSULTA SQL PARA AULAS ---
$sql = "
    SELECT 
        a.id AS aula_id,
        a.data_aula, 
        a.horario, 
        a.titulo_aula, 
        t.id AS turma_id, /* Adiciona o ID da Turma aqui */
        t.nome_turma,
        u.nome AS nome_aluno
    FROM 
        aulas a
    JOIN 
        turmas t ON a.turma_id = t.id
    LEFT JOIN 
        alunos_turmas at ON t.id = at.turma_id
    LEFT JOIN 
        usuarios u ON at.aluno_id = u.id AND u.tipo_usuario = 'aluno'
    WHERE 
        a.professor_id = :professor_id 
        AND YEAR(a.data_aula) = :ano 
        AND MONTH(a.data_aula) = :mes
    ORDER BY 
        a.data_aula ASC, a.horario ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':professor_id' => $professor_id,
    ':ano' => $ano,
    ':mes' => $mes
]);

$aulas_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiza as aulas por dia e agrupa os alunos por aula 
foreach ($aulas_db as $aula) {
    $dia = (new DateTime($aula['data_aula']))->format('j');
    $hora_formatada = substr($aula['horario'], 0, 5); // Pega apenas HH:MM
    
    // Usa o ID da aula + o horário para criar uma chave única
    $chave_aula = $aula['aula_id'] . '_' . $hora_formatada;
    
    if (!isset($aulas_por_dia[$dia][$chave_aula])) {
        $aulas_por_dia[$dia][$chave_aula] = [
            'aula_id' => $aula['aula_id'],
            'hora' => $hora_formatada,
            'topico' => $aula['titulo_aula'],
            'turma' => $aula['nome_turma'], 
            'turma_id' => $aula['turma_id'], // Novo dado crucial
            'alunos' => []
        ];
    }
    
    // Adiciona o nome do aluno, se existir
    if ($aula['nome_aluno']) {
        $aulas_por_dia[$dia][$chave_aula]['alunos'][] = $aula['nome_aluno'];
    }
}

// Array de nomes dos dias da semana (Começando pelo DOMINGO)
$dias_semana = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];

// Mapeamento dos meses para exibição
$nomes_meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', 
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto', 
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Agenda de Aulas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/professor/dashboard.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
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
                    <a href="dashboard.php" class="rounded active"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="dashboard.php?mes=<?= $data_ant->format('m') ?>&ano=<?= $data_ant->format('Y') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i> <?= $nomes_meses[$data_ant->format('m')] ?>
                    </a>
                    <h3>Agenda de Aulas - <?= $nomes_meses[$primeiro_dia_mes->format('m')] ?> de <?= $primeiro_dia_mes->format('Y') ?></h3>
                    <a href="dashboard.php?mes=<?= $data_prox->format('m') ?>&ano=<?= $data_prox->format('Y') ?>" class="btn btn-outline-secondary">
                        <?= $nomes_meses[$data_prox->format('m')] ?> <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <div class="main-content-container p-4">
    <div class="main-content-container p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold" style="color: #081d40;">Sua Agenda</h1>
            <p class="text-muted small">Arraste as aulas para reagendar horários instantaneamente</p>
        </div>
        <button class="btn btn-primary-modern shadow-sm" onclick="window.location.href='gerenciar_aulas.php'">
            <i class="fas fa-plus me-2"></i>Nova Aula
        </button>
    </div>

    <div class="calendar-card shadow-sm border-0">
        <div id='calendar'></div>
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
        locale: 'pt-br',
        timeZone: 'local', // Importante para sincronizar o arrasto com o horário do PC
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
        buttonText: {
            today: 'Hoje', month: 'Mês', week: 'Semana', day: 'Dia'
        },
        
        // Habilita arrastar e redimensionar em qualquer direção
        editable: true, 
        droppable: true, 
        eventDurationEditable: false, // Impede esticar a aula (opcional)
        
        eventDisplay: 'block',
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
        events: 'buscar_aulas.php',
        
        eventClick: function(info) {
            window.location.href = 'detalhes_aula.php?aula_id=' + info.event.id;
        },

        // Função disparada ao soltar em um novo dia ou horário
        eventDrop: function(info) {
            // O FullCalendar já entende se mudou o dia ou a hora aqui
            fetch('atualizar_aula.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: info.event.id,
                    novaData: info.event.startStr // Envia a string completa (Ex: 2025-12-20T10:00:00)
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status !== 'success') {
                    alert('Erro ao salvar alteração.');
                    info.revert();
                }
            })
            .catch(err => {
                console.error(err);
                info.revert();
            });
        }
    });
    calendar.render();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
