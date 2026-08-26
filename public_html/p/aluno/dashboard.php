<?php

// aaa
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-aluno
if ($_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login");
    exit;
}
date_default_timezone_set('America/Sao_Paulo');

$aluno_id = $_SESSION['user_id'];
$aluno_nome = $_SESSION['user_nome'] ?? 'Aluno';

// ===== BUSCAR NOTIFICAÇÕES NÃO LIDAS =====
$sql_notificacoes = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :aluno_id AND lida = 0";
$stmt_notif = $pdo->prepare($sql_notificacoes);
$stmt_notif->execute([':aluno_id' => $aluno_id]);
$total_notificacoes_nao_lidas = $stmt_notif->fetch(PDO::FETCH_ASSOC)['total'];

$data_hoje = new DateTime();
$mes_atual = $data_hoje->format('m');
$ano_atual = $data_hoje->format('Y');
$dia_hoje = $data_hoje->format('j');

if (isset($_GET['ano']) && preg_match('/^\d{4}$/', $_GET['ano'])) {
    $ano_get = (int) $_GET['ano'];
    if ($ano_get >= 2000 && $ano_get <= 2100) {
        $ano_atual = (string) $ano_get;
    }
}

if (isset($_GET['mes']) && preg_match('/^\d{2}$/', $_GET['mes'])) {
    $mes_get = (int) $_GET['mes'];
    if ($mes_get >= 1 && $mes_get <= 12) {
        $mes_atual = str_pad((string) $mes_get, 2, '0', STR_PAD_LEFT);
    }
}

$mes = $mes_atual;
$ano = $ano_atual;

// Cria o objeto DateTime para o primeiro dia do mês
$primeiro_dia_mes = new DateTime("$ano-$mes-01");
$num_dias = $primeiro_dia_mes->format('t');

// Data para navegação
$data_prox = (clone $primeiro_dia_mes)->modify('+1 month');
$data_ant = (clone $primeiro_dia_mes)->modify('-1 month');

// Array para armazenar aulas por dia
$aulas_por_dia = [];

// --- CONSULTA SQL PARA AULAS DO ALUNO ---
$sql = "
    SELECT 
        a.id AS aula_id,
        a.data_aula, 
        a.horario, 
        a.titulo_aula, 
        t.id AS turma_id,
        t.nome_turma,
        u.nome AS nome_professor
    FROM 
        aulas a
    JOIN 
        turmas t ON a.turma_id = t.id
    JOIN 
        alunos_turmas at ON t.id = at.turma_id
    JOIN 
        usuarios u ON a.professor_id = u.id
    WHERE 
        at.aluno_id = :aluno_id
        AND YEAR(a.data_aula) = :ano 
        AND MONTH(a.data_aula) = :mes
    ORDER BY 
        a.data_aula ASC, a.horario ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':aluno_id' => $aluno_id,
    ':ano' => $ano,
    ':mes' => $mes
]);

$aulas_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiza as aulas por dia
foreach ($aulas_db as $aula) {
    $dia = (new DateTime($aula['data_aula']))->format('j');
    $hora_formatada = substr($aula['horario'], 0, 5); // Pega apenas HH:MM
    
    // Usa o ID da aula + o horário para criar uma chave única
    $chave_aula = $aula['aula_id'] . '_' . $hora_formatada;
    
    // Certifica-se de que cada aula aparece apenas uma vez por horário para o dia
    if (!isset($aulas_por_dia[$dia][$chave_aula])) {
        $aulas_por_dia[$dia][$chave_aula] = [
            'aula_id' => $aula['aula_id'],
            'hora' => $hora_formatada,
            'topico' => $aula['titulo_aula'],
            'turma' => $aula['nome_turma'], 
            'turma_id' => $aula['turma_id'],
            'professor' => $aula['nome_professor'],
            'data_aula' => $aula['data_aula']
        ];
    }
}

// Array para armazenar aulas de hoje (para exibição simplificada no mobile)
$aulas_hoje = $aulas_por_dia[$dia_hoje] ?? [];
if (!empty($aulas_hoje)) {
     // Garante que as aulas de hoje estejam ordenadas por horário
    uasort($aulas_hoje, function($a, $b) {
        return strcmp($a['hora'], $b['hora']);
    });
}

// Array de nomes dos dias da semana (Começando pelo DOMINGO)
$dias_semana = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];

// Mapeamento dos meses para exibição
$nomes_meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', 
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto', 
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

// Dias com aula marcada (para exibir só eles no mobile)
$dias_com_aula = [];
foreach ($aulas_por_dia as $dia => $aulas) {
    $dias_com_aula[] = [
        'dia' => $dia,
        'data' => "$ano-$mes-" . str_pad($dia, 2, '0', STR_PAD_LEFT),
        'aulas' => $aulas
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Minhas Aulas</title>
    <!-- Adiciona a fonte Inter para uma estética moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- O CSS customizado será carregado em um arquivo separado, conforme solicitado -->
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/aluno/dashboard_page.css">
</head>
<body>
    <div class="container-fluid p-0">
        
        <?php
        $paginaAtiva = 'dashboard';
        $tituloMobile = 'Dashboard';
        $comBotaoNotificacoes = true;
        require '../includes/layout/aluno_sidebar.php';
        ?>

        <div class="row g-0">
            <!--
                =================================================
                4. Conteúdo Principal
                =================================================
            -->
            <div class="col-12 col-md-10 main-content p-4">
                
                <!-- 
                    AULAS DE HOJE (Apenas para Mobile/Pequenas telas) 
                -->
                <div class="card shadow-sm mb-4 d-md-none">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-dot-circle me-2"></i>Aulas de Hoje (<?= $dia_hoje ?>/<?= $mes ?>)</h6>
                        <span class="badge bg-danger rounded-pill"><?= count($aulas_hoje) ?></span>
                    </div>
                    <div class="card-body p-2">
                        <?php if (empty($aulas_hoje)): ?>
                            <p class="text-center text-muted mb-0 py-2">Nenhuma aula agendada para hoje.</p>
                        <?php else: ?>
                            <?php foreach ($aulas_hoje as $aula): 
                                $url_redirecionamento = "detalhes_aula?id=" . $aula['aula_id'];
                                $cor_fundo_aula = '#1a2a3a';
                            ?>
                                <div class="bloco-aula-simples mb-2" 
                                    style="background-color: <?= $cor_fundo_aula ?>;"
                                    onclick="window.location.href='<?= $url_redirecionamento ?>';">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-truncate">
                                            <?= $aula['hora'] ?>
                                            <span class="d-block text-white"><?= htmlspecialchars($aula['turma']) ?></span>
                                            <small class="text-light-subtle"><?= htmlspecialchars($aula['topico']) ?></small>
                                        </div>
                                        <i class="fas fa-chevron-right text-white opacity-50 ms-2"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 
                    Controle de Navegação do Mês (Visível em todas as telas)
                -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <a href="dashboard?mes=<?= $data_ant->format('m') ?>&ano=<?= $data_ant->format('Y') ?>" class="btn btn-outline-secondary mb-2 mb-sm-0">
                        <i class="fas fa-chevron-left"></i> <?= $nomes_meses[$data_ant->format('m')] ?>
                    </a>
                    <h3 class="text-center fw-light flex-grow-1 mx-2">
                        <span class="fw-bold text-primary"><?= $nomes_meses[$primeiro_dia_mes->format('m')] ?></span> de <?= $primeiro_dia_mes->format('Y') ?>
                    </h3>
                    <a href="dashboard?mes=<?= $data_prox->format('m') ?>&ano=<?= $data_prox->format('Y') ?>" class="btn btn-outline-secondary">
                        <?= $nomes_meses[$data_prox->format('m')] ?> <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <!-- 
                    LISTA DE DIAS COM AULA (Mobile)
                -->
                <div class="dias-com-aula-mobile" style="display:none;">
                    <?php if (empty($dias_com_aula)): ?>
                        <div class="alert alert-light text-center mt-3">
                            Nenhuma aula marcada neste mês.
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($dias_com_aula as $dia_info): 
                                $data_completa = $dia_info['data'];
                                $is_hoje = ($data_hoje->format('Y-m-d') == $data_completa);
                                $aulas = $dia_info['aulas'];
                            ?>
                                <div class="col-12">
                                    <div class="card shadow-sm <?= $is_hoje ? 'border-danger' : '' ?>">
                                        <div class="card-header d-flex align-items-center <?= $is_hoje ? 'bg-danger text-white' : '' ?>">
                                            <span class="fw-bold me-2"><?= date('d/m', strtotime($data_completa)) ?></span>
                                            <?php if ($is_hoje): ?>
                                                <span class="badge bg-warning text-dark ms-2">Hoje</span>
                                            <?php endif; ?>
                                            <span class="ms-auto badge bg-primary"><?= count($aulas) ?> aula<?= count($aulas) > 1 ? 's' : '' ?></span>
                                        </div>
                                        <div class="card-body p-2">
                                            <?php 
                                            uasort($aulas, function($a, $b) {
                                                return strcmp($a['hora'], $b['hora']);
                                            });
                                            foreach ($aulas as $aula): 
                                                $url_redirecionamento = "detalhes_aula?id=" . $aula['aula_id'];
                                            ?>
                                                <div class="bloco-aula-simples mb-2" 
                                                    style="background-color: #1a2a3a;"
                                                    onclick="window.location.href='<?= $url_redirecionamento ?>';">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="text-truncate">
                                                            <?= $aula['hora'] ?>
                                                            
                                                            <small class="text-light-subtle"><?= htmlspecialchars($aula['topico']) ?></small>
                                                        </div>
                                                        <i class="fas fa-chevron-right text-white opacity-50 ms-2"></i>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 
                    CALENDÁRIO COMPLETO (Desktop)
                -->
                <div class="calendario card shadow-lg p-0">
                    <?php foreach ($dias_semana as $dia_nome): ?>
                        <div class="dia-semana"><?= $dia_nome ?></div>
                    <?php endforeach; ?>

                    <?php 
                    $offset = ($primeiro_dia_mes->format('w')); // 'w' retorna 0 (Dom) a 6 (Sáb)
                    
                    for ($i = 0; $i < $offset; $i++): ?>
                        <div class="celula-dia outros-meses"></div>
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $num_dias; $dia++): 
                        $data_completa = "$ano-$mes-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
                        $is_hoje = ($data_hoje->format('Y-m-d') == $data_completa);
                        $tem_aulas = isset($aulas_por_dia[$dia]);
                    ?>
                        <div class="celula-dia <?= $tem_aulas && count($aulas_por_dia[$dia]) > 3 ? 'muitas-aulas' : '' ?>" 
                            style="<?= $is_hoje ? 'border: 2px solid var(--cor-secundaria); background-color: var(--cor-fundo-hoje);' : '' ?>">
                            <span class="numero-dia <?= $is_hoje ? 'text-danger' : '' ?>"><?= $dia ?></span>
                            
                            <?php if ($tem_aulas): ?>
                                <div class="aulas-container">
                                    <?php 
                                    uasort($aulas_por_dia[$dia], function($a, $b) {
                                        return strcmp($a['hora'], $b['hora']);
                                    });
                                    
                                    foreach ($aulas_por_dia[$dia] as $aula): 
                                        $texto_exibido = $aula['turma']; 
                                        $url_redirecionamento = "detalhes_aula?id=" . $aula['aula_id'];
                                        
                                        // Define a cor de fundo com base no dia da semana 
                                        $dia_da_semana = (new DateTime($data_completa))->format('w');
                                        $cor_fundo_aula = '#1a2a3a';
                                    ?>
                                        <div class="bloco-aula" 
                                            title="Clique para ver detalhes da Aula: <?= htmlspecialchars($aula['topico']) ?>" 
                                            style="background-color: <?= $cor_fundo_aula ?>;"
                                            onclick="window.location.href='<?= $url_redirecionamento ?>';">
                                            <?= $aula['hora'] ?>
                                            
                                            <?= htmlspecialchars($aula['topico']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="aulas-container">
                                    <!-- Espaço vazio para manter o layout -->
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                    
                    <?php 
                    $total_celulas = $offset + $num_dias;
                    $celulas_faltantes = (7 - ($total_celulas % 7)) % 7;
                    
                    for ($i = 0; $i < $celulas_faltantes; $i++): ?>
                        <div class="celula-dia outros-meses"></div>
                    <?php endfor; ?>
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
            <a href="notificacoes">Ver todas as notificações</a>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== SISTEMA DE NOTIFICAÇÕES =====
    const btnNotificacoes = document.getElementById('btnNotificacoes');
    const btnNotificacoesMobile = document.getElementById('btnNotificacoesMobile');
    const dropdown = document.getElementById('notificacoesDropdown');
    const notificacoesBody = document.getElementById('notificacoesBody');
    const notificacoesTotal = document.getElementById('notificacoes-total');
    
    // Função para abrir dropdown (usada tanto pelo botão desktop quanto mobile)
    function abrirDropdown() {
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            carregarNotificacoes();
        }
    }
    
    // Adicionar eventos de clique
    if (btnNotificacoes) {
        btnNotificacoes.addEventListener('click', function(e) {
            e.stopPropagation();
            abrirDropdown();
        });
    }
    
    if (btnNotificacoesMobile) {
        btnNotificacoesMobile.addEventListener('click', function(e) {
            e.stopPropagation();
            abrirDropdown();
        });
    }
    
    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && 
            !(btnNotificacoes && btnNotificacoes.contains(e.target)) && 
            !(btnNotificacoesMobile && btnNotificacoesMobile.contains(e.target))) {
            dropdown.classList.remove('show');
        }
    });
    
    // Carregar notificações via AJAX
    function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function carregarNotificacoes() {
            fetch('ajax_notificacoes?acao=buscar_nao_lidas')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.notificacoes.length > 0) {
                            let html = '';
                            data.notificacoes.forEach(notif => {
                                const notifLink = escapeHtml(notif.link);
                                const notifCor = escapeHtml(notif.cor);
                                const notifIcone = escapeHtml(notif.icone);
                                const notifTitulo = escapeHtml(notif.titulo);
                                const notifData = escapeHtml(notif.data_formatada);
                                const notifMensagem = escapeHtml(notif.mensagem.substring(0, 80));
                                const notifId = Number(notif.id) || 0;

                                html += `
                                    <a href="${notifLink}" class="notificacao-item nao-lida" onclick="marcarNotificacaoLida(${notifId})">
                                        <div class="d-flex">
                                            <div class="notificacao-icone me-2" style="background-color: ${notifCor}">
                                                <i class="${notifIcone}"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="notificacao-titulo">${notifTitulo}</div>
                                                    <small class="notificacao-data">${notifData}</small>
                                                </div>
                                                <div class="notificacao-mensagem">${notifMensagem}${notif.mensagem.length > 80 ? '...' : ''}</div>
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
        fetch('ajax_notificacoes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'acao=marcar_lida&notificacao_id=' + notificacaoId,
            keepalive: true
        }).catch(error => console.error('Erro ao marcar notificação:', error));
    };
    
    // Função para atualizar apenas o contador do badge
    function carregarContadorNotificacoes() {
        fetch('ajax_notificacoes?acao=buscar_nao_lidas')
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
        const sidebarBadge = document.querySelector('.sidebar a[href="notificacoes"] .badge');
        if (total > 0) {
            if (sidebarBadge) {
                sidebarBadge.textContent = total;
            } else {
                const linkNotificacoes = document.querySelector('.sidebar a[href="notificacoes"]');
                if (linkNotificacoes) {
                    const span = document.createElement('span');
                    span.className = 'badge bg-danger ms-2';
                    span.textContent = total;
                    linkNotificacoes.appendChild(span);
                }
            }
            
            // Atualizar badge do botão desktop
            if (btnNotificacoes) {
                const btnBadge = btnNotificacoes.querySelector('.badge');
                if (btnBadge) {
                    btnBadge.textContent = total;
                } else {
                    const badge = document.createElement('span');
                    badge.className = 'badge';
                    badge.textContent = total;
                    btnNotificacoes.appendChild(badge);
                }
            }
            
            // Atualizar badge do botão mobile
            if (btnNotificacoesMobile) {
                const btnMobileBadge = btnNotificacoesMobile.querySelector('.badge');
                if (btnMobileBadge) {
                    btnMobileBadge.textContent = total;
                } else {
                    const badge = document.createElement('span');
                    badge.className = 'badge';
                    badge.textContent = total;
                    btnNotificacoesMobile.appendChild(badge);
                }
            }
        } else {
            // Remover badges se total = 0
            if (sidebarBadge) sidebarBadge.remove();
            
            if (btnNotificacoes) {
                const btnBadge = btnNotificacoes.querySelector('.badge');
                if (btnBadge) btnBadge.remove();
            }
            
            if (btnNotificacoesMobile) {
                const btnMobileBadge = btnNotificacoesMobile.querySelector('.badge');
                if (btnMobileBadge) btnMobileBadge.remove();
            }
        }
    }
    
    // Atualizar contador a cada 30 segundos
    setInterval(carregarContadorNotificacoes, 30000);
});
</script>
</body>
</html>