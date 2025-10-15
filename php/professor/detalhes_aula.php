<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$professor_nome = $_SESSION['user_nome'] ?? 'Professor';

$aula_id = $_GET['aula_id'] ?? null;

// Verificação do ID da aula
if (!$aula_id || !is_numeric($aula_id)) {
    header("Location: gerenciar_aulas.php");
    exit;
}

// Buscar detalhes da aula
$sql_detalhes = "SELECT
    a.id AS aula_id, a.titulo_aula, a.data_aula, a.horario, a.descricao AS desc_aula,
    t.id AS turma_id, t.nome_turma,
    p.nome AS nome_professor
    FROM aulas a
    JOIN turmas t ON a.turma_id = t.id
    JOIN usuarios p ON a.professor_id = p.id
    WHERE a.id = :aula_id AND a.professor_id = :professor_id";

$stmt_detalhes = $pdo->prepare($sql_detalhes);
$stmt_detalhes->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);

$detalhes_aula = $stmt_detalhes->fetch(PDO::FETCH_ASSOC);

if(!$detalhes_aula) {
    header("Location: gerenciar_aulas.php");
    exit;
}

// Buscar todos os temas e suas subpastas
$sql_temas = "
    SELECT 
        c.id AS tema_id, 
        c.titulo, 
        c.descricao, 
        u.nome AS autor_tema,
        COALESCE(ac.planejado, 0) AS planejado 
    FROM 
        conteudos c
    JOIN
        usuarios u ON c.professor_id = u.id 
    LEFT JOIN 
        aulas_conteudos ac ON c.id = ac.conteudo_id AND ac.aula_id = :aula_id
    WHERE 
        c.parent_id IS NULL
        AND c.professor_id = :professor_id
    ORDER BY 
        c.titulo ASC
";

$stmt_temas = $pdo->prepare($sql_temas);
$stmt_temas->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
$temas = $stmt_temas->fetchAll(PDO::FETCH_ASSOC);

// Buscar subpastas para cada tema
$subpastas_por_tema = [];
foreach ($temas as $tema) {
    $sql_subpastas = "
        SELECT 
            c.id AS subpasta_id,
            c.titulo AS subpasta_titulo,
            c.descricao AS subpasta_descricao,
            COALESCE(ac.planejado, 0) AS planejado,
            (SELECT COUNT(*) FROM conteudos WHERE parent_id = c.id AND eh_subpasta = 0) AS total_arquivos
        FROM 
            conteudos c
        LEFT JOIN 
            aulas_conteudos ac ON c.id = ac.conteudo_id AND ac.aula_id = :aula_id
        WHERE 
            c.parent_id = :tema_id 
            AND c.eh_subpasta = 1
        ORDER BY 
            c.titulo ASC
    ";
    
    $stmt_subpastas = $pdo->prepare($sql_subpastas);
    $stmt_subpastas->execute([
        ':aula_id' => $aula_id,
        ':tema_id' => $tema['tema_id']
    ]);
    $subpastas_por_tema[$tema['tema_id']] = $stmt_subpastas->fetchAll(PDO::FETCH_ASSOC);
}

// Contar total de itens planejados
$count_planejados = 0;
foreach ($temas as $tema) {
    if ($tema['planejado'] == 1) $count_planejados++;
    if (isset($subpastas_por_tema[$tema['tema_id']])) {
        foreach ($subpastas_por_tema[$tema['tema_id']] as $subpasta) {
            if ($subpasta['planejado'] == 1) $count_planejados++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Aula - <?= htmlspecialchars($detalhes_aula['titulo_aula']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #FAF9F6;
            overflow-x: hidden;
        }
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
        .sidebar .active:hover{
            background-color: #c0392b;
        }
        .main-content {
            margin-left: 16.666667%;
            width: 83.333333%;
            min-height: 100vh;
            overflow-y: auto;
        }
        .card-header {
            background-color: #081d40;
            color: white;
        }
        .btn-danger {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        .btn-danger:hover {
            background-color: #a93226;
            border-color: #a93226;
        }
        .conteudo-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .conteudo-item:last-child {
            border-bottom: none;
        }
        .link-tema {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .link-tema:hover {
            text-decoration: underline;
        }
        .planejado {
            background-color: #f0fff0;
        }
        .nao-planejado {
            background-color: #fff;
            opacity: 0.8;
        }
        .status-label {
            font-weight: bold;
        }
        .subpasta-item {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            margin-left: 20px;
        }
        .subpasta-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .subpasta-toggle.rotated {
            transform: rotate(90deg);
        }
        .subpastas-container {
            display: none;
        }
        .badge-subpasta {
            background-color: #6c757d;
        }
        .tema-header {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .tema-header:hover {
            background-color: #f8f9fa;
        }
        .arquivo-count {
            font-size: 0.85em;
        }
        .presenca-item {
            transition: background-color 0.3s ease;
        }

        .presenca-item.presente {
            background-color: #f0fff0;
        }

        .presenca-item.ausente {
            background-color: #fff0f0;
        }

        .presenca-item:hover {
            background-color: #f8f9fa;
        }
        #botao-sair {
            border: none;
        }
        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }
        .switch-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .loading {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center">
                    <h5 class="mt-4">Prof. <?= htmlspecialchars($professor_nome) ?></h5>
                </div>
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 style="color: #081d40;">Detalhes da Aula</h1>
                    <a href="gerenciar_aulas.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Voltar para Aulas
                    </a>
                </div>
                
                <div id="ajax-message-container"></div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>ID da Aula:</strong> <?= $detalhes_aula['aula_id'] ?></p>
                                <p class="mb-1"><strong>Professor:</strong> <?= htmlspecialchars($detalhes_aula['nome_professor']) ?></p>
                                <p class="mb-1"><strong>Tópico:</strong> <?= htmlspecialchars($detalhes_aula['titulo_aula']) ?></p>
                                <p class="mb-1"><strong>Descrição:</strong> <?= htmlspecialchars($detalhes_aula['desc_aula'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Turma:</strong> <?= htmlspecialchars($detalhes_aula['nome_turma']) ?></p>
                                <p class="mb-1"><strong>Data:</strong> <?= (new DateTime($detalhes_aula['data_aula']))->format('d/m/Y') ?></p>
                                <p class="mb-1"><strong>Horário:</strong> <?= substr($detalhes_aula['horario'], 0, 5) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <span>Controle de Presença</span>
                    </div>
                    
                    <div class="card-body">
                        <?php
                        // Buscar alunos da turma
                        $sql_alunos = "SELECT 
                            u.id AS aluno_id, 
                            u.nome AS aluno_nome,
                            COALESCE(p.presente, 1) AS presente
                        FROM usuarios u
                        INNER JOIN alunos_turmas at ON u.id = at.aluno_id
                        LEFT JOIN presenca_aula p ON u.id = p.aluno_id AND p.aula_id = :aula_id
                        WHERE at.turma_id = :turma_id
                        AND u.tipo_usuario = 'aluno'
                        ORDER BY u.nome ASC";
                        
                        $stmt_alunos = $pdo->prepare($sql_alunos);
                        $stmt_alunos->execute([
                            ':aula_id' => $aula_id,
                            ':turma_id' => $detalhes_aula['turma_id']
                        ]);
                        $alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if (empty($alunos)): ?>
                            <p class="text-center text-muted">Não há alunos matriculados nesta turma.</p>
                        <?php else: ?>
                            <div class="row mb-3 align-items-center border-bottom pb-2 text-muted small">
                                <div class="col-1 text-center"><strong>Presente?</strong></div>
                                <div class="col-8"><strong>Aluno</strong></div>
                                <div class="col-3 text-center"><strong>Status</strong></div>
                            </div>
                            
                            <div id="lista-presenca-container">
                                <?php foreach ($alunos as $aluno): ?>
                                    <?php 
                                        $is_presente = $aluno['presente'] == 1;
                                        $presenca_class = $is_presente ? 'presente' : 'ausente';
                                        $status_text = $is_presente ? 'Presente' : 'Faltou';
                                        $status_class = $is_presente ? 'bg-success' : 'bg-danger';
                                    ?>
                                    
                                    <div class="presenca-item row mx-0 align-items-center mb-2 py-2 border-bottom <?= $presenca_class ?>" 
                                        data-aluno-id="<?= $aluno['aluno_id'] ?>" 
                                        data-presente="<?= $aluno['presente'] ?>">
                                        
                                        <div class="col-1 text-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input presenca-switch" 
                                                    type="checkbox" 
                                                    role="switch" 
                                                    id="presenca_<?= $aluno['aluno_id'] ?>" 
                                                    data-aula-id="<?= $detalhes_aula['aula_id'] ?>"
                                                    data-aluno-id="<?= $aluno['aluno_id'] ?>"
                                                    <?= $is_presente ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="presenca_<?= $aluno['aluno_id'] ?>">
                                                    <?= $is_presente ? 'Sim' : 'Não' ?>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-8">
                                            <strong><?= htmlspecialchars($aluno['aluno_nome']) ?></strong>
                                        </div>
                                        
                                        <div class="col-3 text-center">
                                            <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php
                            $total_alunos = count($alunos);
                            $presentes = array_filter($alunos, function($aluno) {
                                return $aluno['presente'] == 1;
                            });
                            $total_presentes = count($presentes);
                            $total_faltas = $total_alunos - $total_presentes;
                            ?>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <h4 class="mb-0"><?= $total_alunos ?></h4>
                                        <small class="text-muted">Total de Alunos</small>
                                    </div>
                                    <div class="col-md-4">
                                        <h4 class="mb-0 text-success"><?= $total_presentes ?></h4>
                                        <small class="text-muted">Presentes</small>
                                    </div>
                                    <div class="col-md-4">
                                        <h4 class="mb-0 text-danger"><?= $total_faltas ?></h4>
                                        <small class="text-muted">Faltas</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Conteúdo da Aula - Controle de Visibilidade</span>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="filtroPlanejadoSwitch">
                            <label class="form-check-label text-white" for="filtroPlanejadoSwitch" id="filtroPlanejadoLabel">
                                Mostrar Apenas Itens Visíveis (<?= $count_planejados ?>)
                            </label>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($temas)): ?>
                            <p class="text-center text-muted">Não há temas cadastrados.</p>
                        <?php else: ?>
                            
                            <div class="row mb-3 align-items-center border-bottom pb-2 text-muted small">
                                <div class="col-1 text-center"><strong>Visível?</strong></div>
                                <div class="col-5"><strong>Item</strong></div>
                                <div class="col-4"><strong>Detalhes</strong></div>
                                <div class="col-2 text-center"><strong>Conteúdo</strong></div>
                            </div>
                            
                            <div id="lista-conteudos-container">
                                <?php foreach ($temas as $tema): ?>
                                    <?php 
                                        $is_planejado = $tema['planejado'] == 1;
                                        $planejado_class = $is_planejado ? 'planejado' : 'nao-planejado';
                                        $tem_subpastas = !empty($subpastas_por_tema[$tema['tema_id']]);
                                        $total_subpastas = count($subpastas_por_tema[$tema['tema_id']] ?? []);
                                    ?>
                                    
                                    <div class="conteudo-item tema-header row mx-0 align-items-center <?= $planejado_class ?>" 
                                        data-conteudo-id="<?= $tema['tema_id'] ?>" 
                                        data-planejado="<?= $tema['planejado'] ?>"
                                        data-tipo="tema">
                                        
                                        <div class="col-1 text-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input planejado-switch" 
                                                    type="checkbox" 
                                                    role="switch" 
                                                    id="switch_<?= $tema['tema_id'] ?>" 
                                                    data-aula-id="<?= $detalhes_aula['aula_id'] ?>"
                                                    data-conteudo-id="<?= $tema['tema_id'] ?>"
                                                    data-tipo="tema"
                                                    <?= $is_planejado ? 'checked' : '' ?>>
                                                <label class="form-check-label small status-label" for="switch_<?= $tema['tema_id'] ?>">
                                                    <?= $is_planejado ? 'Sim' : 'Não' ?>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-5">
                                            <div class="d-flex align-items-center">
                                                <?php if ($tem_subpastas): ?>
                                                    <i class="fas fa-chevron-right subpasta-toggle me-2" data-tema-id="<?= $tema['tema_id'] ?>" style="cursor: pointer;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-folder me-2 text-primary"></i>
                                                <?php endif; ?>
                                                <a href="gerenciar_arquivos_tema.php?tema_id=<?= $tema['tema_id'] ?>" class="link-tema">
                                                    <strong><?= htmlspecialchars($tema['titulo']) ?></strong>
                                                </a>
                                            </div>
                                            <?php if (!empty($tema['descricao'])): ?>
                                                <small class="text-muted d-block ms-4">
                                                    <?= htmlspecialchars($tema['descricao']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-4">
                                            <span class="badge bg-primary ms-2" title="Criado por">
                                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($tema['autor_tema']) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="col-2 text-center">
                                            <span class="badge bg-secondary arquivo-count">
                                                <?= $total_subpastas ?> subpasta(s)
                                            </span>
                                        </div>
                                    </div>

                                    <?php if ($tem_subpastas): ?>
                                        <div class="subpastas-container" id="subpastas-<?= $tema['tema_id'] ?>">
                                            <?php foreach ($subpastas_por_tema[$tema['tema_id']] as $subpasta): ?>
                                                <?php 
                                                    $is_subpasta_planejada = $subpasta['planejado'] == 1;
                                                    $subpasta_planejado_class = $is_subpasta_planejada ? 'planejado' : 'nao-planejado';
                                                ?>
                                                <div class="conteudo-item subpasta-item row mx-0 align-items-center <?= $subpasta_planejado_class ?>" 
                                                    data-conteudo-id="<?= $subpasta['subpasta_id'] ?>" 
                                                    data-planejado="<?= $subpasta['planejado'] ?>"
                                                    data-tipo="subpasta">
                                                    
                                                    <div class="col-1 text-center">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input planejado-switch" 
                                                                type="checkbox" 
                                                                role="switch" 
                                                                id="switch_<?= $subpasta['subpasta_id'] ?>" 
                                                                data-aula-id="<?= $detalhes_aula['aula_id'] ?>"
                                                                data-conteudo-id="<?= $subpasta['subpasta_id'] ?>"
                                                                data-tipo="subpasta"
                                                                <?= $is_subpasta_planejada ? 'checked' : '' ?>>
                                                            <label class="form-check-label small status-label" for="switch_<?= $subpasta['subpasta_id'] ?>">
                                                                <?= $is_subpasta_planejada ? 'Sim' : 'Não' ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-5">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-folder me-2 text-primary" style="margin-left: 20px;"></i>
                                                            <span><strong><?= htmlspecialchars($subpasta['subpasta_titulo']) ?></strong></span>
                                                        </div>
                                                        <?php if (!empty($subpasta['subpasta_descricao'])): ?>
                                                            <small class="text-muted d-block" style="margin-left: 40px;">
                                                                <?= htmlspecialchars($subpasta['subpasta_descricao']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="col-4">
                                                        <span class="badge bg-light text-dark">
                                                            <i class="fas fa-folder-open me-1"></i> Subpasta
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="col-2 text-center">
                                                        <span class="badge badge-subpasta arquivo-count">
                                                            <?= $subpasta['total_arquivos'] ?> arquivo(s)
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function displayAlert(message, type) {
    const container = document.getElementById('ajax-message-container');
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    container.innerHTML = alertHtml;
    
    setTimeout(() => {
        const alertElement = container.querySelector('.alert');
        if (alertElement) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
            bsAlert.close();
        }
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    const listaConteudosContainer = document.getElementById('lista-conteudos-container');
    const filtroSwitch = document.getElementById('filtroPlanejadoSwitch');
    const filtroLabel = document.getElementById('filtroPlanejadoLabel');

    // INICIALIZAÇÃO: Fechar todas as subpastas
    document.querySelectorAll('.subpastas-container').forEach(container => {
        container.style.display = 'none';
    });

    // TOGGLES DE SUBPASTAS - SIMPLES E FUNCIONAL
    document.querySelectorAll('.subpasta-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const temaId = this.getAttribute('data-tema-id');
            const subpastasContainer = document.getElementById(`subpastas-${temaId}`);

            if (subpastasContainer) {
                const isHidden = subpastasContainer.style.display === 'none' || 
                               subpastasContainer.style.display === '';
                
                if (isHidden) {
                    subpastasContainer.style.display = 'block';
                    this.classList.add('rotated');
                } else {
                    subpastasContainer.style.display = 'none';
                    this.classList.remove('rotated');
                }
            }
        });
    });

    // Atualizar contagem de itens visíveis
    function atualizarContagemPlanejados() {
        const count = listaConteudosContainer.querySelectorAll('.conteudo-item[data-planejado="1"]').length;
        filtroLabel.innerHTML = `Mostrar Apenas Itens Visíveis (${count})`;
        return count;
    }

    // Lógica do Filtro
    function aplicarFiltro() {
        const mostrarApenasPlanejados = filtroSwitch.checked;
        
        listaConteudosContainer.querySelectorAll('.conteudo-item').forEach(item => {
            const isPlanejado = item.dataset.planejado === '1';
            
            if (mostrarApenasPlanejados && !isPlanejado) {
                item.style.display = 'none';
            } else {
                item.style.display = 'flex';
            }
        });

        // Gerencia a exibição dos containers de subpastas baseado no filtro
        document.querySelectorAll('.subpastas-container').forEach(container => {
            const temaId = container.id.replace('subpastas-', '');
            const toggle = document.querySelector(`.subpasta-toggle[data-tema-id="${temaId}"]`);
            const temaPai = document.querySelector(`.conteudo-item[data-conteudo-id="${temaId}"]`);

            // Se o tema pai está escondido, esconde as subpastas também
            if (temaPai && temaPai.style.display === 'none') {
                container.style.display = 'none';
                if (toggle) toggle.classList.remove('rotated');
                return;
            }

            // Se estamos filtrando, mostra apenas containers que têm itens visíveis
            if (mostrarApenasPlanejados) {
                const hasVisibleItems = container.querySelector('.conteudo-item[data-planejado="1"]') !== null;
                if (hasVisibleItems) {
                    container.style.display = 'block';
                    if (toggle) toggle.classList.add('rotated');
                } else {
                    container.style.display = 'none';
                    if (toggle) toggle.classList.remove('rotated');
                }
            }
        });
    }

    filtroSwitch.addEventListener('change', aplicarFiltro);

    // LÓGICA AJAX PARA OS SWITCHES DE CONTEÚDO
    document.querySelectorAll('.planejado-switch').forEach(function(switchElement) {
        switchElement.addEventListener('change', function() {
            const aulaId = this.dataset.aulaId;
            const conteudoId = this.dataset.conteudoId;
            const tipo = this.dataset.tipo;
            const novoStatus = this.checked ? 1 : 0;
            
            const statusLabel = this.closest('.form-switch').querySelector('.form-check-label');
            const conteudoItem = this.closest('.conteudo-item');
            
            const formData = new FormData();
            formData.append('aula_id', aulaId);
            formData.append('conteudo_id', conteudoId);
            formData.append('status', novoStatus);
            
            const estadoAnterior = this.checked ? 0 : 1;

            // Feedback visual
            this.disabled = true;
            this.classList.add('switch-disabled');
            statusLabel.textContent = '...';
            statusLabel.classList.add('loading');

            fetch('ajax_toggle_conteudo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na comunicação com o servidor. Status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                this.disabled = false;
                this.classList.remove('switch-disabled');
                statusLabel.classList.remove('loading');

                if (data.success) {
                    displayAlert(data.message, 'success');
                    
                    // Atualiza o estado no DOM
                    conteudoItem.dataset.planejado = String(novoStatus);
                    
                    if (novoStatus === 1) {
                        statusLabel.textContent = 'Sim';
                        conteudoItem.classList.add('planejado');
                        conteudoItem.classList.remove('nao-planejado');
                    } else {
                        statusLabel.textContent = 'Não';
                        conteudoItem.classList.remove('planejado');
                        conteudoItem.classList.add('nao-planejado');
                    }

                    // Atualiza a contagem e aplica filtro se necessário
                    atualizarContagemPlanejados();
                    if (filtroSwitch.checked) {
                        aplicarFiltro();
                    }

                } else {
                    console.error('Erro:', data.message);
                    displayAlert('Erro ao atualizar visibilidade: ' + data.message, 'danger');
                    // Reverte o estado do switch
                    this.checked = !this.checked;
                    statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
                }
            })
            .catch(error => {
                this.disabled = false;
                this.classList.remove('switch-disabled');
                statusLabel.classList.remove('loading');
                console.error('Erro de conexão:', error);
                displayAlert('Erro de comunicação. A visibilidade não foi atualizada.', 'danger');
                // Reverte o estado do switch
                this.checked = !this.checked;
                statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
            });
        });
    });

    // Lógica do Controle de Presença (se necessário)
    document.querySelectorAll('.presenca-switch').forEach(function(switchElement) {
        switchElement.addEventListener('change', function() {
            const aulaId = this.dataset.aulaId;
            const alunoId = this.dataset.alunoId;
            const novoStatus = this.checked ? 1 : 0;
            
            const statusLabel = this.closest('.form-switch').querySelector('.form-check-label');
            const presencaItem = this.closest('.presenca-item');
            const statusBadge = presencaItem.querySelector('.badge');
            
            const formData = new FormData();
            formData.append('aula_id', aulaId);
            formData.append('aluno_id', alunoId);
            formData.append('presente', novoStatus);
            
            const estadoAnterior = this.checked ? 0 : 1;

            this.disabled = true;
            statusLabel.textContent = '...';

            fetch('ajax_controle_presenca.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na comunicação com o servidor. Status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                this.disabled = false;

                if (data.success) {
                    displayAlert(data.message, 'success');
                    
                    presencaItem.dataset.presente = String(novoStatus);
                    
                    if (novoStatus === 1) {
                        statusLabel.textContent = 'Sim';
                        presencaItem.classList.add('presente');
                        presencaItem.classList.remove('ausente');
                        statusBadge.textContent = 'Presente';
                        statusBadge.classList.remove('bg-danger');
                        statusBadge.classList.add('bg-success');
                    } else {
                        statusLabel.textContent = 'Não';
                        presencaItem.classList.remove('presente');
                        presencaItem.classList.add('ausente');
                        statusBadge.textContent = 'Faltou';
                        statusBadge.classList.remove('bg-success');
                        statusBadge.classList.add('bg-danger');
                    }

                    // Atualizar contadores
                    location.reload();

                } else {
                    console.error('Erro:', data.message);
                    displayAlert('Erro ao atualizar presença: ' + data.message, 'danger');
                    this.checked = !this.checked;
                    statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
                }
            })
            .catch(error => {
                this.disabled = false;
                console.error('Erro de conexão:', error);
                displayAlert('Erro de comunicação. A presença não foi atualizada.', 'danger');
                this.checked = !this.checked;
                statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
            });
        });
    });

    // Inicializar
    atualizarContagemPlanejados();
});
</script>
</body>
</html>