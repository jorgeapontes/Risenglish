<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
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
        a.horario, /* NOVO CAMPO: HORÁRIO */
        a.titulo_aula, 
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
        a.professor_id = :professor_id /* AGORA COM professor_id GARANTIDO */
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
            'hora' => $hora_formatada,
            'topico' => $aula['titulo_aula'],
            'turma' => $aula['nome_turma'],
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
    <style>
        /* Paleta de Cores: Creme, Vermelho Escuro, Marinho Escuro */
        :root {
            --cor-primaria: #0A1931; /* Marinho Escuro */
            --cor-secundaria: #B91D23; /* Vermelho Escuro */
            --cor-fundo: #F5F5DC; /* Creme/Bege */
        }
        body { background-color: var(--cor-fundo); }
        .sidebar { background-color: var(--cor-primaria); color: white; min-height: 100vh; }
        .sidebar a { color: white; padding: 15px; text-decoration: none; display: block; }
        .sidebar a:hover { background-color: var(--cor-secundaria); }
        .main-content { padding: 30px; }
        
        /* Estilo do Calendário */
        .calendario {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border: 1px solid #ccc;
            background-color: white;
        }
        .dia-semana {
            background-color: var(--cor-primaria);
            color: white;
            padding: 10px 5px;
            font-weight: bold;
            text-align: center;
            border-right: 1px solid #ccc;
        }
        .celula-dia {
            border-top: 1px solid #ccc;
            border-right: 1px solid #eee;
            min-height: 120px;
            padding: 5px;
            position: relative;
            background-color: #f8f8f8; /* Fundo levemente mais escuro */
        }
        .celula-dia:nth-child(7n) { border-right: none; }
        
        .numero-dia {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--cor-secundaria);
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .outros-meses {
            background-color: #ddd;
            opacity: 0.5;
        }

        /* Estilo da Aula (Bloco no Calendário) */
        .bloco-aula {
            background-color: var(--cor-secundaria);
            color: white;
            border-radius: 4px;
            padding: 4px;
            margin-bottom: 3px;
            font-size: 0.8em;
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .bloco-aula strong {
            display: block;
            font-size: 1.1em;
        }
        .bloco-aula:hover {
            background-color: #92171B;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php" style="background-color: #92171B;"><i class="fas fa-home me-2"></i> **Dashboard (Agenda)**</a>
        <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt me-2"></i> Agendar/Gerenciar Aulas</a>
        <a href="gerenciar_conteudos.php"><i class="fas fa-book-open me-2"></i> Conteúdos (Biblioteca)</a>
        <a href="gerenciar_alunos.php"><i class="fas fa-users me-2"></i> Alunos/Turmas</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Agenda de Aulas</h1>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="dashboard.php?mes=<?= $data_ant->format('m') ?>&ano=<?= $data_ant->format('Y') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chevron-left"></i> <?= $nomes_meses[$data_ant->format('m')] ?>
            </a>
            <h2><?= $nomes_meses[$primeiro_dia_mes->format('m')] ?> de <?= $primeiro_dia_mes->format('Y') ?></h2>
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
            ?>
                <div class="celula-dia" style="<?= $is_hoje ? 'border: 2px solid var(--cor-secundaria); background-color: #f0f0f0;' : '' ?>">
                    <span class="numero-dia"><?= $dia ?></span>
                    
                    <?php if (isset($aulas_por_dia[$dia])): ?>
                        <?php 
                        uasort($aulas_por_dia[$dia], function($a, $b) {
                            return strcmp($a['hora'], $b['hora']);
                        });
                        
                        foreach ($aulas_por_dia[$dia] as $aula): 
                            $alunos_str = implode(', ', $aula['alunos']);
                            // Se não houver aluno associado (turma vazia), exibe o nome da turma
                            if (empty($alunos_str)) {
                                $alunos_str = $aula['turma'];
                            }

                            // Define a cor de fundo com base no dia da semana (simulando a referência)
                            $dia_da_semana = (new DateTime($data_completa))->format('w');
                            $cor_fundo_aula = $dia_da_semana == 0 || $dia_da_semana == 6 ? '#A0A0A0' : 'var(--cor-secundaria)'; // Cinza no Fim de Semana
                        ?>
                            <div class="bloco-aula" title="<?= htmlspecialchars($aula['topico']) ?> em <?= $aula['turma'] ?>" style="background-color: <?= $cor_fundo_aula ?>;"
                                onclick="alert('Aula: <?= htmlspecialchars($aula['topico']) ?>\nHorário: <?= $aula['hora'] ?>\nAlunos: <?= htmlspecialchars($alunos_str) ?>')">
                                <strong><?= $aula['hora'] ?></strong>
                                <span><?= htmlspecialchars($alunos_str) ?></span>
                            </div>
                        <?php endforeach; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>