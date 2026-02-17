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
        t.id AS turma_id,
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
            'turma_id' => $aula['turma_id'],
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

// ===== BUSCAR NOTIFICAÇÕES NÃO LIDAS =====
$sql_notificacoes = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :professor_id AND lida = 0";
$stmt_notif = $pdo->prepare($sql_notificacoes);
$stmt_notif->execute([':professor_id' => $professor_id]);
$total_notificacoes_nao_lidas = $stmt_notif->fetch(PDO::FETCH_ASSOC)['total'];
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
    <style>
        /* Estilos para o dropdown de notificações */
        .notificacoes-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .notificacoes-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #c0392b;
            color: white;
            border-radius: 50%;
            padding: 3px 6px;
            font-size: 10px;
            min-width: 18px;
            text-align: center;
        }
        
        .notificacoes-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 1050;
            display: none;
            margin-top: 10px;
        }
        
        .notificacoes-dropdown.show {
            display: block;
        }
        
        .notificacoes-header {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }
        
        .notificacoes-header h6 {
            margin: 0;
            font-weight: 600;
            color: #081d40;
        }
        
        .notificacoes-body {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notificacao-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .notificacao-item:hover {
            background-color: #f8f9fa;
        }
        
        .notificacao-item.nao-lida {
            background-color: #fff9f9;
        }
        
        .notificacao-item.nao-lida:hover {
            background-color: #fff0f0;
        }
        
        .notificacao-icone {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }
        
        .notificacao-titulo {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2px;
            color: #333;
        }
        
        .notificacao-mensagem {
            font-size: 0.8rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px;
        }
        
        .notificacao-data {
            font-size: 0.7rem;
            color: #adb5bd;
        }
        
        .notificacoes-footer {
            padding: 10px 15px;
            border-top: 1px solid #eee;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }
        
        .notificacoes-footer a {
            color: #c0392b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .notificacoes-footer a:hover {
            text-decoration: underline;
        }
        
        .notificacoes-vazias {
            padding: 30px;
            text-align: center;
            color: #adb5bd;
        }
        
        .notificacoes-vazias i {
            font-size: 3rem;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        
        .btn-notificacoes {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            position: relative;
            padding: 8px 12px;
            border-radius: 5px;
            transition: 0.3s;
        }
        
        .btn-notificacoes:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .btn-notificacoes .badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #c0392b;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
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
                    <a href="notificacoes.php" class="rounded position-relative">
                        <i class="fas fa-bell"></i>&nbsp;&nbsp;Notificações
                        <?php if ($total_notificacoes_nao_lidas > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $total_notificacoes_nao_lidas ?></span>
                        <?php endif; ?>
                    </a>
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
    </div>

    <!-- Dropdown de notificações -->
    <div class="notificacoes-dropdown" id="notificacoesDropdown">
        <div class="notificacoes-header">
            <h6>Notificações</h6>
            <small class="text-muted" id="notificacoes-total"></small>
        </div>
        <div class="notificacoes-body" id="notificacoesBody">
            <div class="notificacoes-vazias">
                <i class="fas fa-bell-slash"></i>
                <p>Carregando notificações...</p>
            </div>
        </div>
        <div class="notificacoes-footer">
            <a href="notificacoes.php">Ver todas as notificações</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek', 
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
                buttonText: {
                    today: 'Hoje', month: 'Mês', week: 'Semana', day: 'Dia'
                },
                
                editable: true, 
                droppable: true, 
                eventDurationEditable: false,
                
                eventDisplay: 'block',
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
                events: 'buscar_aulas.php',
                
                eventClick: function(info) {
                    window.location.href = 'detalhes_aula.php?aula_id=' + info.event.id;
                },

                eventDrop: function(info) {
                    fetch('atualizar_aula.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id: info.event.id,
                            novaData: info.event.startStr
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
            
            // ===== SISTEMA DE NOTIFICAÇÕES =====
            const btnNotificacoes = document.getElementById('btnNotificacoes');
            const dropdown = document.getElementById('notificacoesDropdown');
            const notificacoesBody = document.getElementById('notificacoesBody');
            const notificacoesTotal = document.getElementById('notificacoes-total');
            
            // Toggle dropdown
            btnNotificacoes.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
                if (dropdown.classList.contains('show')) {
                    carregarNotificacoes();
                }
            });
            
            // Fechar dropdown ao clicar fora
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target) && !btnNotificacoes.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Carregar notificações via AJAX
function carregarNotificacoes() {
    fetch('ajax_notificacoes.php?acao=buscar_nao_lidas')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.notificacoes.length > 0) {
                    let html = '';
                    data.notificacoes.forEach(notif => {
                        html += `
                            <a href="${notif.link}" class="notificacao-item nao-lida" onclick="marcarNotificacaoLida(${notif.id})">
                                <div class="d-flex">
                                    <div class="notificacao-icone me-2" style="background-color: ${notif.cor}">
                                        <i class="${notif.icone}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="notificacao-titulo">${notif.titulo}</div>
                                            <small class="notificacao-data">${notif.data_formatada}</small>
                                        </div>
                                        <div class="notificacao-mensagem">${notif.mensagem.substring(0, 80)}${notif.mensagem.length > 80 ? '...' : ''}</div>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    notificacoesBody.innerHTML = html;
                    notificacoesTotal.textContent = `${data.notificacoes.length} não lida${data.notificacoes.length > 1 ? 's' : ''}`;
                    
                    // Atualizar badge
                    atualizarBadgeNotificacoes(data.total_nao_lidas);
                } else {
                    notificacoesBody.innerHTML = `
                        <div class="notificacoes-vazias">
                            <i class="fas fa-bell-slash"></i>
                            <p class="mb-0">Nenhuma notificação</p>
                            <small class="text-muted">Você está em dia!</small>
                        </div>
                    `;
                    notificacoesTotal.textContent = '0 não lidas';
                }
            }
        })
        .catch(error => {
            console.error('Erro ao carregar notificações:', error);
            notificacoesBody.innerHTML = `
                <div class="notificacoes-vazias">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    <p>Erro ao carregar</p>
                </div>
            `;
        });
}
            
            // Função para marcar notificação como lida (chamada via AJAX antes de redirecionar)
            window.marcarNotificacaoLida = function(notificacaoId) {
                fetch('ajax_notificacoes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'acao=marcar_lida&notificacao_id=' + notificacaoId,
                    keepalive: true
                }).catch(error => console.error('Erro ao marcar notificação:', error));
            };
            
            // Função para atualizar apenas o contador do badge
            function carregarContadorNotificacoes() {
                fetch('ajax_notificacoes.php?acao=buscar_nao_lidas')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            atualizarBadgeNotificacoes(data.total_nao_lidas);
                        }
                    })
                    .catch(error => console.error('Erro ao atualizar contador:', error));
            }
            
            // Função para atualizar o badge na sidebar e no botão
            function atualizarBadgeNotificacoes(total) {
                // Atualizar badge na sidebar
                const sidebarBadge = document.querySelector('.sidebar a[href="notificacoes.php"] .badge');
                if (total > 0) {
                    if (sidebarBadge) {
                        sidebarBadge.textContent = total;
                    } else {
                        const linkNotificacoes = document.querySelector('.sidebar a[href="notificacoes.php"]');
                        if (linkNotificacoes) {
                            const span = document.createElement('span');
                            span.className = 'badge bg-danger ms-2';
                            span.textContent = total;
                            linkNotificacoes.appendChild(span);
                        }
                    }
                    
                    // Atualizar badge do botão
                    const btnBadge = btnNotificacoes.querySelector('.badge');
                    if (btnBadge) {
                        btnBadge.textContent = total;
                    } else {
                        const badge = document.createElement('span');
                        badge.className = 'badge';
                        badge.textContent = total;
                        btnNotificacoes.appendChild(badge);
                    }
                } else {
                    // Remover badges se total = 0
                    if (sidebarBadge) sidebarBadge.remove();
                    const btnBadge = btnNotificacoes.querySelector('.badge');
                    if (btnBadge) btnBadge.remove();
                }
            }
            
            // Atualizar contador a cada 30 segundos
            setInterval(carregarContadorNotificacoes, 30000);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>