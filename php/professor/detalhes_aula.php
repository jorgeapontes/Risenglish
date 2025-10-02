<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$aula_id = $_GET['aula_id'] ?? null;

if (!$aula_id || !is_numeric($aula_id)) {
    header("Location: dashboard.php");
    exit;
}

// --- 1. BUSCAR DETALHES DA AULA E DA TURMA RELACIONADA ---
$sql_detalhes = "
    SELECT 
        a.id AS aula_id, a.titulo_aula, a.data_aula, a.horario, a.descricao AS desc_aula,
        t.id AS turma_id, t.nome_turma,
        p.nome AS nome_professor
    FROM 
        aulas a
    JOIN 
        turmas t ON a.turma_id = t.id
    JOIN 
        usuarios p ON a.professor_id = p.id
    WHERE 
        a.id = :aula_id AND a.professor_id = :professor_id
";
$stmt_detalhes = $pdo->prepare($sql_detalhes);
$stmt_detalhes->execute([':aula_id' => $aula_id, ':professor_id' => $professor_id]);
$detalhes_aula = $stmt_detalhes->fetch(PDO::FETCH_ASSOC);

if (!$detalhes_aula) {
    header("Location: dashboard.php");
    exit;
}

// --- 2. BUSCAR CONTEÚDOS VINCULADOS A ESTA AULA ---
$sql_conteudos = "
    SELECT 
        c.id AS conteudo_id, c.titulo, c.descricao, c.caminho_arquivo, 
        ac.planejado 
    FROM 
        conteudos c
    JOIN 
        aulas_conteudos ac ON c.id = ac.conteudo_id
    WHERE 
        ac.aula_id = :aula_id
    ORDER BY 
        c.titulo ASC
";
$stmt_conteudos = $pdo->prepare($sql_conteudos);
$stmt_conteudos->execute([':aula_id' => $aula_id]);
$conteudos_vinculados = $stmt_conteudos->fetchAll(PDO::FETCH_ASSOC);

// Mensagens de sucesso ou erro
// (O AJAX fará a própria notificação para o switch de planejado).
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Aula - <?= htmlspecialchars($detalhes_aula['titulo_aula']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/professor/detalhes_aula.css">
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard </a>
        <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt me-2"></i>Aulas</a>
        <a href="gerenciar_conteudos.php"><i class="fas fa-book-open me-2"></i>Conteúdos</a>
        <a href="gerenciar_alunos.php"><i class="fas fa-users me-2"></i>Alunos/Turmas</a>
        <a href="../logout.php" class="link-sair"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 style="color: var(--cor-primaria);">Detalhes da Aula</h1>
            <a href="gerenciar_aulas.php?editar=<?= $detalhes_aula['aula_id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i> Editar/Vincular Conteúdos
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
                        <p class="mb-1"><strong>Turma:</strong> <a href="detalhes_turma.php?turma_id=<?= $detalhes_aula['turma_id'] ?>"><?= htmlspecialchars($detalhes_aula['nome_turma']) ?></a></p>
                        <p class="mb-1"><strong>Data:</strong> <?= (new DateTime($detalhes_aula['data_aula']))->format('d/m/Y') ?></p>
                        <p class="mb-1"><strong>Horário:</strong> <?= substr($detalhes_aula['horario'], 0, 5) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-custom">
                Conteúdo da Aula (Materiais Vinculados)
            </div>
            <div class="card-body">
                <?php if (empty($conteudos_vinculados)): ?>
                    <p class="text-center text-muted">Nenhum conteúdo vinculado a esta aula. Use o botão "Editar" acima para vincular.</p>
                <?php else: ?>
                    <div class="row mb-3 align-items-center border-bottom pb-2">
                        <div class="col-1 text-center">
                            <strong>Planejado</strong>
                        </div>
                        <div class="col-10">
                            <strong>Título do Conteúdo</strong>
                        </div>
                        <div class="col-1 text-center">
                            <strong>Ação</strong>
                        </div>
                    </div>
                    
                    <?php foreach ($conteudos_vinculados as $c): ?>
                        <?php $planejado_class = $c['planejado'] == 1 ? 'planejado' : ''; ?>
                        
                        <div class="conteudo-item d-flex align-items-center <?= $planejado_class ?>" data-conteudo-id="<?= $c['conteudo_id'] ?>">
                            
                            <div class="col-1 text-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input planejado-switch" 
                                        type="checkbox" 
                                        role="switch" 
                                        id="switch_<?= $c['conteudo_id'] ?>" 
                                        data-aula-id="<?= $detalhes_aula['aula_id'] ?>"
                                        data-conteudo-id="<?= $c['conteudo_id'] ?>"
                                        <?= $c['planejado'] == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label small status-label" for="switch_<?= $c['conteudo_id'] ?>">
                                        <?= $c['planejado'] == 1 ? 'Planejado' : 'Não Usado' ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-10">
                                <strong><?= htmlspecialchars($c['titulo']) ?></strong>
                                <p class="text-muted mb-0 small"><?= htmlspecialchars($c['descricao'] ?? 'Sem descrição.') ?></p>
                            </div>

                            <div class="col-1 text-center">
                                <?php if ($c['caminho_arquivo']): ?>
                                    <a href="../<?= htmlspecialchars($c['caminho_arquivo']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Visualizar Material">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">N/A</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
/**
 * Função para exibir mensagens de notificação (sucesso ou erro)
 * @param {string} message - A mensagem a ser exibida.
 * @param {string} type - O tipo de alerta ('success' ou 'danger').
 */
function displayAlert(message, type) {
    const container = document.getElementById('ajax-message-container');
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    container.innerHTML = alertHtml;
    
    // Opcional: Remover o alerta automaticamente após X segundos
    setTimeout(() => {
        const alertElement = container.querySelector('.alert');
        if (alertElement) {
            new bootstrap.Alert(alertElement).close();
        }
    }, 5000); // 5 segundos
}


document.addEventListener('DOMContentLoaded', function() {
    // 1. Captura todos os switches com a classe 'planejado-switch'
    const switches = document.querySelectorAll('.planejado-switch');

    switches.forEach(function(switchElement) {
        switchElement.addEventListener('change', function() {
            const aulaId = this.dataset.aulaId;
            const conteudoId = this.dataset.conteudoId;
            const novoStatus = this.checked ? 1 : 0; // Se marcado = 1, se desmarcado = 0
            
            // Elementos visuais para atualização
            const statusLabel = this.closest('.form-switch').querySelector('.status-label');
            const conteudoItem = this.closest('.conteudo-item');
            
            // 2. Cria o objeto FormData com os dados a serem enviados
            const formData = new FormData();
            formData.append('aula_id', aulaId);
            formData.append('conteudo_id', conteudoId);
            formData.append('status', novoStatus);
            
            // 3. Executa a Requisição AJAX usando Fetch API
            fetch('ajax_update_planejado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Checa se o status HTTP é de sucesso (200-299)
                if (!response.ok) {
                    throw new Error('Erro na requisição: Status ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayAlert('Status do conteúdo atualizado para: ' + (novoStatus === 1 ? 'Planejado' : 'Não Usado'), 'success');
                    
                    // Atualiza o texto e a classe visual
                    statusLabel.textContent = novoStatus === 1 ? 'Planejado' : 'Não Usado';
                    if (novoStatus === 1) {
                        conteudoItem.classList.add('planejado');
                    } else {
                        conteudoItem.classList.remove('planejado');
                    }
                    
                } else {
                    console.error('Erro:', data.message);
                    displayAlert('Erro ao atualizar status: ' + data.message, 'danger');
                    
                    // Reverte o estado do switch em caso de falha
                    this.checked = !this.checked; 
                }
            })
            .catch(error => {
                console.error('Erro de conexão ou servidor:', error);
                displayAlert('Erro de conexão ao servidor ou erro interno.', 'danger');
                
                // Reverte o estado do switch em caso de falha
                this.checked = !this.checked; 
            });
        });
    });
});
</script>
</body>
</html>