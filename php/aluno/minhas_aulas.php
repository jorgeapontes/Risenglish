<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-aluno
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login.php");
    exit;
}

date_default_timezone_set('America/Sao_Paulo');

$aluno_id = $_SESSION['user_id'];
$aluno_nome = $_SESSION['user_nome'] ?? 'Aluno';

// Consulta para obter todas as aulas do aluno
$sql = "
    SELECT 
        a.id AS aula_id,
        a.data_aula, 
        a.horario, 
        a.titulo_aula, 
        a.descricao,
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
    ORDER BY 
        a.data_aula DESC, a.horario DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':aluno_id' => $aluno_id]);
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar aulas por mês/ano
$aulas_por_mes = [];
$total_aulas_passadas = 0; // Variável para contar total de aulas passadas
$agora = new DateTime();
$mes_atual = $agora->format('Y-m'); // Formato: 2024-01

foreach ($aulas as $aula) {
    $data_hora_aula = new DateTime($aula['data_aula'] . ' ' . $aula['horario']);
    $mes_ano = $data_hora_aula->format('Y-m'); // Formato: 2024-01
    $mes_nome = $data_hora_aula->format('F'); // Nome completo do mês
    $ano = $data_hora_aula->format('Y');
    
    // Determinar se é aula futura ou passada
    $status = $data_hora_aula < $agora ? 'passada' : 'futura';
    
    if (!isset($aulas_por_mes[$mes_ano])) {
        $aulas_por_mes[$mes_ano] = [
            'mes_ano' => $mes_ano,
            'mes_nome' => $mes_nome,
            'ano' => $ano,
            'total_aulas' => 0,
            'aulas_passadas' => 0,
            'aulas_futuras' => 0,
            'aulas' => []
        ];
    }
    
    $aulas_por_mes[$mes_ano]['total_aulas']++;
    if ($status === 'passada') {
        $aulas_por_mes[$mes_ano]['aulas_passadas']++;
        $total_aulas_passadas++; // Incrementar o total de aulas passadas
    } else {
        $aulas_por_mes[$mes_ano]['aulas_futuras']++;
    }
    
    $aula['status'] = $status;
    $aula['data_hora_obj'] = $data_hora_aula;
    $aulas_por_mes[$mes_ano]['aulas'][] = $aula;
}

// Separar meses com aulas futuras e passadas para exibição
$meses_com_futuras = [];
$meses_com_passadas = [];

foreach ($aulas_por_mes as $mes_ano => $dados) {
    if ($dados['aulas_futuras'] > 0) {
        $meses_com_futuras[$mes_ano] = $dados;
    }
    if ($dados['aulas_passadas'] > 0) {
        $meses_com_passadas[$mes_ano] = $dados;
    }
}

// Ordenar meses com aulas futuras do mais próximo para o mais distante
uksort($meses_com_futuras, function($a, $b) {
    return strtotime($a) - strtotime($b);
});

// Ordenar meses com aulas passadas do mais recente para o mais antigo
krsort($meses_com_passadas);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Aulas - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        body {
            background-color: #FAF9F6;
            overflow-x: hidden;
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

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        .card-aula {
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #c0392b;
        }
        .card-aula:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .aula-passada {
            opacity: 0.8;
        }
        .toggle-section {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .toggle-section:hover {
            background-color: #f8f9fa;
        }
        .aulas-passadas-container {
            transition: all 0.3s ease;
        }
        .collapse-icon {
            transition: transform 0.3s ease;
        }
        .collapsed .collapse-icon {
            transform: rotate(-90deg);
        }
        
        /* Estilos para o cabeçalho do mês */
        .mes-header {
            background: linear-gradient(135deg, #081d40 0%, #0a2a5c 100%);
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .mes-titulo {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .mes-estatisticas {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .badge-presenca {
            background-color: #28a745;
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .badge-futuras {
            background-color: #17a2b8;
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .badge-total {
            background-color: #6c757d;
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .badge-mes-atual {
            background-color: #ffc107;
            color: black;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .mes-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
        }
        
        .mes-toggle:hover {
            background-color: #f8f9fa;
        }
        
        .mes-toggle .collapse-icon {
            transition: transform 0.3s ease;
        }
        
        .mes-toggle.collapsed .collapse-icon {
            transform: rotate(-90deg);
        }

        /* Menu Mobile */
        @media (max-width: 991px) {
            .sidebar {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .mobile-navbar-custom {
                background: #081d40 !important;
                color: #fff !important;
            }
            .mobile-navbar-custom h5 {
                color: #fff !important;
            }
            .mobile-navbar-custom .btn-outline-primary {
                color: #fff !important;
                border-color: #fff !important;
            }
            .mobile-navbar-custom .btn-outline-primary:active,
            .mobile-navbar-custom .btn-outline-primary:focus,
            .mobile-navbar-custom .btn-outline-primary:hover {
                background: #0a2a5c !important;
                color: #fff !important;
                border-color: #fff !important;
            }
            
            .mes-header {
                padding: 12px 15px;
                margin-bottom: 15px;
            }
            
            .mes-titulo {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        
        <!-- Menu Mobile (Hamburger & Header) -->
        <header class="d-flex d-md-none mobile-navbar-custom border-bottom shadow-sm p-3 align-items-center sticky-top">
            <button class="btn btn-outline-primary me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Abrir Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0 fw-bold">Minhas Aulas</h5>
        </header>

        <!-- Sidebar Offcanvas (Menu para Mobile) -->
        <div class="offcanvas offcanvas-top text-white mobile-offcanvas" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="background-color: #081d40; height: 50vh;">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title fw-bold" id="sidebarOffcanvasLabel"><?php echo $aluno_nome; ?></h5>
                <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                 <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded active"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>
        </div>

        <div class="row g-0">
            <!-- Sidebar Desktop (Visível apenas em md e acima) -->
            <div class="col-md-2 d-none d-md-flex flex-column sidebar p-3">
                <!-- Nome do aluno -->
                <div class="mb-4 text-center">
                    <h5 class="mt-4"><?php echo $aluno_nome; ?></h5>
                </div>

                <!-- Menu -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded active"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo Principal -->
            <div class="col-12 col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Minhas Aulas</h3>
                </div>

                <!-- Aulas Futuras Agrupadas por Mês -->
                <?php if (!empty($meses_com_futuras)): ?>
                <div class="mb-5">
                    <h4 class="mb-3"><i class="fas fa-clock text-primary me-2"></i>Próximas Aulas</h4>
                    
                    <?php foreach ($meses_com_futuras as $mes_ano => $dados_mes): 
                        $mes_ptbr = '';
                        switch ($dados_mes['mes_nome']) {
                            case 'January': $mes_ptbr = 'Janeiro'; break;
                            case 'February': $mes_ptbr = 'Fevereiro'; break;
                            case 'March': $mes_ptbr = 'Março'; break;
                            case 'April': $mes_ptbr = 'Abril'; break;
                            case 'May': $mes_ptbr = 'Maio'; break;
                            case 'June': $mes_ptbr = 'Junho'; break;
                            case 'July': $mes_ptbr = 'Julho'; break;
                            case 'August': $mes_ptbr = 'Agosto'; break;
                            case 'September': $mes_ptbr = 'Setembro'; break;
                            case 'October': $mes_ptbr = 'Outubro'; break;
                            case 'November': $mes_ptbr = 'Novembro'; break;
                            case 'December': $mes_ptbr = 'Dezembro'; break;
                        }
                        
                        // Verificar se é o mês atual
                        $is_mes_atual = ($mes_ano === $mes_atual);
                    ?>
                    <div class="mes-toggle mb-3 p-3 border rounded <?php echo !$is_mes_atual ? 'collapsed' : ''; ?>" 
                         data-bs-toggle="collapse" 
                         data-bs-target="#mesFuturo<?= str_replace('-', '', $mes_ano) ?>" 
                         aria-expanded="<?php echo $is_mes_atual ? 'true' : 'false'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                <?= $mes_ptbr . ' ' . $dados_mes['ano'] ?>
                                <!-- <?php if ($is_mes_atual): ?>
                                <span class="badge badge-mes-atual ms-2">Mês Atual</span>
                                <?php endif; ?> -->
                                <span class="badge badge-futuras ms-2"><?= $dados_mes['aulas_futuras'] ?> futura(s)</span>
                                <span class="badge badge-total ms-1"><?= $dados_mes['total_aulas'] ?> total</span>
                            </h5>
                            <i class="fas fa-chevron-down collapse-icon"></i>
                        </div>
                    </div>
                    
                    <div class="collapse <?php echo $is_mes_atual ? 'show' : ''; ?>" id="mesFuturo<?= str_replace('-', '', $mes_ano) ?>">
                        <div class="row mb-4">
                            <?php 
                            // Filtrar apenas aulas futuras deste mês
                            $aulas_futuras_mes = array_filter($dados_mes['aulas'], function($aula) {
                                return $aula['status'] === 'futura';
                            });
                            
                            // Ordenar por data (mais próximas primeiro)
                            usort($aulas_futuras_mes, function($a, $b) {
                                return $a['data_hora_obj'] <=> $b['data_hora_obj'];
                            });
                            
                            foreach ($aulas_futuras_mes as $aula): 
                                $data_hora_aula = $aula['data_hora_obj'];
                                $agora = new DateTime();
                                
                                // Cálculo para o badge
                                $hoje = new DateTime('today');
                                $amanha = new DateTime('tomorrow');
                                $data_aula_sem_hora = new DateTime($aula['data_aula']);
                                
                                if ($data_aula_sem_hora == $hoje) {
                                    $texto_data = "Hoje";
                                    $badge_class = "bg-warning";
                                } elseif ($data_aula_sem_hora == $amanha) {
                                    $texto_data = "Amanhã";
                                    $badge_class = "bg-info";
                                } else {
                                    $diferenca = $hoje->diff($data_aula_sem_hora);
                                    $diferenca_dias = $diferenca->days;
                                    $texto_data = "Em " . $diferenca_dias . " dias";
                                    $badge_class = "bg-primary";
                                }
                            ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-aula h-100" onclick="window.location.href='detalhes_aula.php?id=<?= $aula['aula_id'] ?>'">
                                    <div class="card-body position-relative">
                                        <span class="badge <?= $badge_class ?> status-badge"><?= $texto_data ?></span>
                                        <h5 class="card-title"><?= htmlspecialchars($aula['titulo_aula']) ?></h5>
                                        <p class="card-text text-muted"><?= htmlspecialchars($aula['descricao'] ?: 'Sem descrição') ?></p>
                                        <div class="mt-auto">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i><?= $data_hora_aula->format('d/m/Y') ?>
                                                <i class="fas fa-clock ms-2 me-1"></i><?= substr($aula['horario'], 0, 5) ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i><?= htmlspecialchars($aula['nome_turma']) ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>Prof. <?= htmlspecialchars($aula['nome_professor']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Aulas Passadas Agrupadas por Mês -->
                <?php if (!empty($meses_com_passadas)): ?>
                <div class="aulas-passadas-container">
                    <div class="toggle-section mb-3 p-3 border rounded" data-bs-toggle="collapse" data-bs-target="#aulasPassadas" aria-expanded="false">
                        <h4 class="mb-0 d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-history text-secondary me-2"></i>Aulas Passadas
                                <span class="badge bg-secondary ms-2"><?= $total_aulas_passadas ?></span>
                            </span>
                            <i class="fas fa-chevron-down collapse-icon"></i>
                        </h4>
                    </div>
                    
                    <div class="collapse" id="aulasPassadas">
                        <?php foreach ($meses_com_passadas as $mes_ano => $dados_mes): 
                            $mes_ptbr = '';
                            switch ($dados_mes['mes_nome']) {
                                case 'January': $mes_ptbr = 'Janeiro'; break;
                                case 'February': $mes_ptbr = 'Fevereiro'; break;
                                case 'March': $mes_ptbr = 'Março'; break;
                                case 'April': $mes_ptbr = 'Abril'; break;
                                case 'May': $mes_ptbr = 'Maio'; break;
                                case 'June': $mes_ptbr = 'Junho'; break;
                                case 'July': $mes_ptbr = 'Julho'; break;
                                case 'August': $mes_ptbr = 'Agosto'; break;
                                case 'September': $mes_ptbr = 'Setembro'; break;
                                case 'October': $mes_ptbr = 'Outubro'; break;
                                case 'November': $mes_ptbr = 'Novembro'; break;
                                case 'December': $mes_ptbr = 'Dezembro'; break;
                            }
                        ?>
                        <div class="mes-toggle mb-3 p-3 border rounded collapsed" data-bs-toggle="collapse" data-bs-target="#mesPassado<?= str_replace('-', '', $mes_ano) ?>" aria-expanded="false">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-check text-success me-2"></i>
                                    <?= $mes_ptbr . ' ' . $dados_mes['ano'] ?>
                                    <span class="badge badge-presenca ms-2"><?= $dados_mes['aulas_passadas'] ?> realizada(s)</span>
                                    <span class="badge badge-total ms-1"><?= $dados_mes['total_aulas'] ?> total</span>
                                </h5>
                                <i class="fas fa-chevron-down collapse-icon"></i>
                            </div>
                        </div>
                        
                        <div class="collapse" id="mesPassado<?= str_replace('-', '', $mes_ano) ?>">
                            <div class="row mb-4">
                                <?php 
                                // Filtrar apenas aulas passadas deste mês
                                $aulas_passadas_mes = array_filter($dados_mes['aulas'], function($aula) {
                                    return $aula['status'] === 'passada';
                                });
                                
                                // Ordenar por data (mais recentes primeiro)
                                usort($aulas_passadas_mes, function($a, $b) {
                                    return $b['data_hora_obj'] <=> $a['data_hora_obj'];
                                });
                                
                                foreach ($aulas_passadas_mes as $aula): 
                                    $data_aula = $aula['data_hora_obj'];
                                ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card card-aula aula-passada h-100" onclick="window.location.href='detalhes_aula.php?id=<?= $aula['aula_id'] ?>'">
                                        <div class="card-body position-relative">
                                            <span class="badge bg-secondary status-badge">Realizada</span>
                                            <h5 class="card-title"><?= htmlspecialchars($aula['titulo_aula']) ?></h5>
                                            <p class="card-text text-muted"><?= htmlspecialchars($aula['descricao'] ?: 'Sem descrição') ?></p>
                                            <div class="mt-auto">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i><?= $data_aula->format('d/m/Y') ?>
                                                    <i class="fas fa-clock ms-2 me-1"></i><?= substr($aula['horario'], 0, 5) ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i><?= htmlspecialchars($aula['nome_turma']) ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i>Prof. <?= htmlspecialchars($aula['nome_professor']) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Mensagem quando não há aulas -->
                <?php if (empty($aulas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Nenhuma aula encontrada</h4>
                    <p class="text-muted">Você não está matriculado em nenhuma turma com aulas no momento.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar o collapse das aulas passadas como fechado
        document.addEventListener('DOMContentLoaded', function() {
            var aulasPassadas = document.getElementById('aulasPassadas');
            if (aulasPassadas) {
                var collapse = new bootstrap.Collapse(aulasPassadas, {
                    toggle: false
                });
            }
            
            // Adicionar funcionalidade de toggle para cada mês
            document.querySelectorAll('.mes-toggle').forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    // Se clicar no ícone, não fazer nada extra
                    if (e.target.classList.contains('collapse-icon') || 
                        e.target.parentElement.classList.contains('collapse-icon')) {
                        return;
                    }
                    
                    // Alternar a classe collapsed
                    this.classList.toggle('collapsed');
                });
            });
        });
    </script>
</body>
</html>