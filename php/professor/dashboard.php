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

                <div class="calendario">
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
                                style="<?= $is_hoje ? 'border: 2px solid #c0392b; background-color: #f0f0f0;' : '' ?>">
                            <span class="numero-dia"><?= $dia ?></span>
                            
                            <?php if ($tem_aulas): ?>
                                <div class="aulas-container">
                                    <?php 
                                    uasort($aulas_por_dia[$dia], function($a, $b) {
                                        return strcmp($a['hora'], $b['hora']);
                                    });
                                    
                                    foreach ($aulas_por_dia[$dia] as $aula): 
                                        $texto_exibido = $aula['turma']; 
                                        
                                        $url_redirecionamento = "detalhes_aula.php?aula_id=" . $aula['aula_id'];
                                        
                                        // Define a cor de fundo com base no dia da semana 
                                        $dia_da_semana = (new DateTime($data_completa))->format('w');
                                        $cor_fundo_aula = $dia_da_semana == 0 || $dia_da_semana == 6 ? '#A0A0A0' : '#1a2a3a'; // Cinza no Fim de Semana
                                    ?>
                                        <div class="bloco-aula" 
                                            title="Clique para ver detalhes da Turma <?= htmlspecialchars($aula['turma']) ?>" 
                                            style="background-color: <?= $cor_fundo_aula ?>;"
                                            onclick="window.location.href='<?= $url_redirecionamento ?>';">
                                            <?= $aula['hora'] ?>
                                            <span><?= htmlspecialchars($texto_exibido) ?></span>
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
