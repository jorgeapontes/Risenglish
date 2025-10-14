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
        .subpastas-container.show {
            display: block;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
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
                    <a href="../logout.php" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 style="color: #081d40;">Detalhes da Aula</h1>
                    <a href="gerenciar_aulas.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Voltar para Aulas
                    </a>
                </div>
                
                <div id="ajax-message-container"></div>
                
                <!-- DETALHES DA AULA -->
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

                <!-- CONTEÚDO DA AULA (TEMAS E SUBPASTAS) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Conteúdo da Aula - Controle de Visibilidade</span>
                        
                        <!-- FILTRO -->
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
                            
                            <!-- CABEÇALHOS -->
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
                                    
                                    <!-- TEMA PRINCIPAL -->
                                    <div class="conteudo-item tema-header row mx-0 align-items-center <?= $planejado_class ?>" 
                                        data-conteudo-id="<?= $tema['tema_id'] ?>" 
                                        data-planejado="<?= $tema['planejado'] ?>"
                                        data-tipo="tema">
                                        
                                        <!-- CHECKBOX VISIBILIDADE -->
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
                                        
                                        <!-- TÍTULO E DESCRIÇÃO -->
                                        <div class="col-5">
                                            <div class="d-flex align-items-center">
                                                <?php if ($tem_subpastas): ?>
                                                    <i class="fas fa-chevron-right subpasta-toggle me-2" data-tema-id="<?= $tema['tema_id'] ?>" style="cursor: pointer;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-folder me-2 text-warning"></i>
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

                                        <!-- AUTOR -->
                                        <div class="col-4">
                                            <span class="badge bg-info text-dark">
                                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($tema['autor_tema']) ?>
                                            </span>
                                        </div>
                                        
                                        <!-- CONTAGEM DE SUBPASTAS -->
                                        <div class="col-2 text-center">
                                            <span class="badge bg-secondary arquivo-count">
                                                <?= $total_subpastas ?> subpasta(s)
                                            </span>
                                        </div>
                                    </div>

                                    <!-- SUBPASTAS -->
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
                                                    
                                                    <!-- CHECKBOX VISIBILIDADE SUBPASTA -->
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
                                                    
                                                    <!-- TÍTULO DA SUBPASTA -->
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

                                                    <!-- TIPO -->
                                                    <div class="col-4">
                                                        <span class="badge bg-light text-dark">
                                                            <i class="fas fa-folder-open me-1"></i> Subpasta
                                                        </span>
                                                    </div>
                                                    
                                                    <!-- CONTAGEM DE ARQUIVOS -->
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
        const switches = document.querySelectorAll('.planejado-switch');
        const filtroSwitch = document.getElementById('filtroPlanejadoSwitch');
        const listaConteudosContainer = document.getElementById('lista-conteudos-container');
        const filtroLabel = document.getElementById('filtroPlanejadoLabel');

        // Toggle das subpastas
        document.querySelectorAll('.subpasta-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const temaId = this.getAttribute('data-tema-id');
                const subpastasContainer = document.getElementById(`subpastas-${temaId}`);
                this.classList.toggle('rotated');
                subpastasContainer.classList.toggle('show');
            });
        });

        // Atualizar contagem de itens visíveis
        function atualizarContagemPlanejados() {
            const count = listaConteudosContainer.querySelectorAll('.conteudo-item[data-planejado="1"]').length;
            filtroLabel.innerHTML = `Mostrar Apenas Itens Visíveis (${count})`;
        }

        // Lógica do Filtro
        function aplicarFiltro() {
            const mostrarApenasPlanejados = filtroSwitch.checked;
            
            listaConteudosContainer.querySelectorAll('.conteudo-item').forEach(item => {
                const isPlanejado = item.dataset.planejado === '1';
                
                if (mostrarApenasPlanejados && !isPlanejado) {
                    item.style.setProperty('display', 'none', 'important');
                } else {
                    item.style.display = 'flex';
                }
            });

            // Mostrar/ocultar containers de subpastas baseado no filtro
            document.querySelectorAll('.subpastas-container').forEach(container => {
                const hasVisibleItems = container.querySelector('.conteudo-item[data-planejado="1"]');
                if (mostrarApenasPlanejados && !hasVisibleItems) {
                    container.style.display = 'none';
                } else {
                    container.style.display = 'block';
                }
            });
        }

        filtroSwitch.addEventListener('change', aplicarFiltro);

        // Lógica do Toggle (Checkbox de Visibilidade)
        switches.forEach(function(switchElement) {
            switchElement.addEventListener('change', function() {
                const aulaId = this.dataset.aulaId;
                const conteudoId = this.dataset.conteudoId;
                const tipo = this.dataset.tipo;
                const novoStatus = this.checked ? 1 : 0;
                
                const statusLabel = this.closest('.form-switch').querySelector('.status-label');
                const conteudoItem = this.closest('.conteudo-item');
                
                const formData = new FormData();
                formData.append('aula_id', aulaId);
                formData.append('conteudo_id', conteudoId);
                formData.append('status', novoStatus);
                
                const estadoAnterior = novoStatus === 1 ? 0 : 1;

                this.disabled = true;
                statusLabel.textContent = '...';

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

                    if (data.success) {
                        displayAlert(data.message, 'success');
                        
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

                        aplicarFiltro();
                        atualizarContagemPlanejados();

                    } else {
                        console.error('Erro:', data.message);
                        displayAlert('Erro ao atualizar status: ' + data.message, 'danger');
                        this.checked = !this.checked;
                        statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
                    }
                })
                .catch(error => {
                    this.disabled = false;
                    console.error('Erro de conexão:', error);
                    displayAlert('Erro de comunicação. O item não foi atualizado.', 'danger');
                    this.checked = !this.checked;
                    statusLabel.textContent = estadoAnterior === 1 ? 'Sim' : 'Não';
                });
            });
        });
        
        aplicarFiltro();
        atualizarContagemPlanejados();
    });
    </script>
</body>
</html>