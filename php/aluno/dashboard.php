<?php
// aaa
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

$data_hoje = new DateTime();
$mes_atual = $data_hoje->format('m');
$ano_atual = $data_hoje->format('Y');
$dia_hoje = $data_hoje->format('j');

// Opcional: Permitir que o aluno navegue por mês (se houver parâmetro na URL)
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Minhas Aulas</title>
    <!-- Adiciona a fonte Inter para uma estética moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- O CSS customizado será carregado em um arquivo separado, conforme solicitado -->
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
</head>
<body>
    <div class="container-fluid p-0">
        
        <!-- 
            =================================================
            1. Menu Mobile (Hamburger & Header)
            =================================================
        -->
        <header class="d-flex d-md-none bg-white border-bottom shadow-sm p-3 align-items-center sticky-top">
            <button class="btn btn-outline-primary me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Abrir Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0 text-primary fw-bold">Minhas Aulas</h5>
        </header>

        <!-- 
            =================================================
            2. Sidebar Offcanvas (Menu para Mobile)
            =================================================
        -->
        <div class="offcanvas offcanvas-start bg-primary text-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title fw-bold" id="sidebarOffcanvasLabel"><?php echo $aluno_nome; ?></h5>
                <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                 <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded active"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>
        </div>

        <div class="row g-0">
            <!-- 
                =================================================
                3. Sidebar Desktop (Visível apenas em md e acima)
                =================================================
            -->
            <div class="col-md-2 d-none d-md-flex flex-column sidebar p-3">
                <!-- Nome do aluno -->
                <div class="mb-4 text-center">
                    <h5 class="mt-4"><?php echo $aluno_nome; ?></h5>
                </div>

                <!-- Menu -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded active"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

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
                                $url_redirecionamento = "detalhes_aula.php?id=" . $aula['aula_id'];
                                $cor_fundo_aula = '#1a2a3a'; 
                            ?>
                                <div class="bloco-aula-simples mb-2" 
                                    style="background-color: <?= $cor_fundo_aula ?>;"
                                    onclick="window.location.href='<?= $url_redirecionamento ?>';">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-truncate">
                                            <strong class="text-warning"><?= $aula['hora'] ?></strong>
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
                    <a href="dashboard.php?mes=<?= $data_ant->format('m') ?>&ano=<?= $data_ant->format('Y') ?>" class="btn btn-outline-secondary mb-2 mb-sm-0">
                        <i class="fas fa-chevron-left"></i> <?= $nomes_meses[$data_ant->format('m')] ?>
                    </a>
                    <h3 class="text-center fw-light flex-grow-1 mx-2">
                        <span class="fw-bold text-primary"><?= $nomes_meses[$primeiro_dia_mes->format('m')] ?></span> de <?= $primeiro_dia_mes->format('Y') ?>
                    </h3>
                    <a href="dashboard.php?mes=<?= $data_prox->format('m') ?>&ano=<?= $data_prox->format('Y') ?>" class="btn btn-outline-secondary">
                        <?= $nomes_meses[$data_prox->format('m')] ?> <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <!-- 
                    CALENDÁRIO COMPLETO (Se torna uma lista vertical no mobile)
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
                                        $url_redirecionamento = "detalhes_aula.php?id=" . $aula['aula_id'];
                                        
                                        // Define a cor de fundo com base no dia da semana 
                                        $dia_da_semana = (new DateTime($data_completa))->format('w');
                                        $cor_fundo_aula = $dia_da_semana == 0 || $dia_da_semana == 6 ? '#A0A0A0' : '#1a2a3a'; // Cinza no Fim de Semana
                                    ?>
                                        <div class="bloco-aula" 
                                            title="Clique para ver detalhes da Aula: <?= htmlspecialchars($aula['topico']) ?>" 
                                            style="background-color: <?= $cor_fundo_aula ?>;"
                                            onclick="window.location.href='<?= $url_redirecionamento ?>';">
                                            <strong><?= $aula['hora'] ?></strong>
                                            <span><?= htmlspecialchars($texto_exibido) ?></span>
                                            <small><?= htmlspecialchars($aula['topico']) ?></small>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
