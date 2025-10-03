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

// --- 2. BUSCAR TODOS OS TEMAS (PASTAS) DO PROFESSOR E SEU STATUS DE PLANEJAMENTO PARA ESTA AULA ---
// Filtra apenas conteúdos principais (Temas) onde parent_id é NULL.
// Inclui uma subconsulta para contar o número de arquivos filhos dentro de cada tema.
$sql_todos_temas = "
    SELECT 
        c.id AS tema_id, 
        c.titulo, 
        c.descricao, 
        (SELECT COUNT(id) FROM conteudos WHERE parent_id = c.id AND professor_id = :professor_id_sub) AS total_arquivos, 
        COALESCE(ac.planejado, 0) AS planejado
    FROM 
        conteudos c
    LEFT JOIN 
        aulas_conteudos ac ON c.id = ac.conteudo_id AND ac.aula_id = :aula_id
    WHERE 
        c.professor_id = :professor_id 
        AND c.parent_id IS NULL -- Filtra apenas temas principais
    ORDER BY 
        c.titulo ASC
";
$stmt_temas = $pdo->prepare($sql_todos_temas);
$stmt_temas->execute([
    ':aula_id' => $aula_id, 
    ':professor_id' => $professor_id,
    ':professor_id_sub' => $professor_id // Usado para a subconsulta de contagem
]);
$todos_temas = $stmt_temas->fetchAll(PDO::FETCH_ASSOC);

// Contagem para exibição no filtro
$count_planejados = array_sum(array_column($todos_temas, 'planejado'));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Aula - <?= htmlspecialchars($detalhes_aula['titulo_aula']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Depende do seu CSS externo para o layout e cores -->
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
                        <p class="mb-1"><strong>Turma:</strong> <a href="detalhes_turma.php?turma_id=<?= $detalhes_aula['turma_id'] ?>"><?= htmlspecialchars($detalhes_aula['nome_turma']) ?></a></p>
                        <p class="mb-1"><strong>Data:</strong> <?= (new DateTime($detalhes_aula['data_aula']))->format('d/m/Y') ?></p>
                        <p class="mb-1"><strong>Horário:</strong> <?= substr($detalhes_aula['horario'], 0, 5) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTEÚDO DA AULA (TEMAS DO PROFESSOR) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <span>Conteúdo da Aula (Temas Cadastrados e Visibilidade para o Aluno)</span>
                
                <!-- FILTRO (CHECKBOX) -->
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="filtroPlanejadoSwitch">
                    <label class="form-check-label text-white" for="filtroPlanejadoSwitch">
                        Mostrar Apenas Temas Visíveis para o Aluno (<?= $count_planejados ?>)
                    </label>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (empty($todos_temas)): ?>
                    <p class="text-center text-muted">Você ainda não possui Temas (Pastas) cadastrados. Por favor, vá à seção "Conteúdos" para organizar seus materiais.</p>
                <?php else: ?>
                    
                    <!-- NOVOS CABEÇALHOS PARA TEMAS -->
                    <div class="row mb-3 align-items-center border-bottom pb-2 text-muted small">
                        <div class="col-1 text-center"><strong>Visível?</strong></div>
                        <div class="col-11"><strong>Tema / Pasta (Clique para Gerenciar Arquivos)</strong></div>
                    </div>
                    
                    <div id="lista-conteudos-container">
                        <?php foreach ($todos_temas as $t): ?>
                            <?php 
                                $is_planejado = $t['planejado'] == 1;
                                $planejado_class = $is_planejado ? 'planejado' : 'nao-planejado'; 
                            ?>
                            
                            <!-- Usa tema_id como data-conteudo-id para compatibilidade com o script AJAX -->
                            <div class="conteudo-item d-flex align-items-center <?= $planejado_class ?>" 
                                 data-conteudo-id="<?= $t['tema_id'] ?>" 
                                 data-planejado="<?= $t['planejado'] ?>">
                                
                                <!-- CHECKBOX VISIBILIDADE -->
                                <div class="col-1 text-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input planejado-switch" 
                                            type="checkbox" 
                                            role="switch" 
                                            id="switch_<?= $t['tema_id'] ?>" 
                                            data-aula-id="<?= $detalhes_aula['aula_id'] ?>"
                                            data-conteudo-id="<?= $t['tema_id'] ?>"
                                            <?= $is_planejado ? 'checked' : '' ?>>
                                        <label class="form-check-label small status-label" for="switch_<?= $t['tema_id'] ?>">
                                            <?= $is_planejado ? 'Sim' : 'Não' ?>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- TÍTULO E DESCRIÇÃO (COM CONTAGEM DE ARQUIVOS) -->
                                <div class="col-11">
                                    <a href="gerenciar_arquivos_tema.php?conteudo_id=<?= $t['tema_id'] ?>" class="link-tema">
                                        <i class="fas fa-folder me-2"></i> <?= htmlspecialchars($t['titulo']) ?> 
                                        <span class="badge bg-secondary ms-2"><?= $t['total_arquivos'] ?> Arquivo(s)</span>
                                    </a>
                                    
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
    
    setTimeout(() => {
        const alertElement = container.querySelector('.alert');
        if (alertElement) {
            // Usa o método 'hide' do Bootstrap para fechar o alerta
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
            bsAlert.close();
        }
    }, 5000); // 5 segundos
}

document.addEventListener('DOMContentLoaded', function() {
    const switches = document.querySelectorAll('.planejado-switch');
    const filtroSwitch = document.getElementById('filtroPlanejadoSwitch');
    const listaConteudosContainer = document.getElementById('lista-conteudos-container');

    // --- 1. Lógica do Filtro ---
    function aplicarFiltro() {
        const mostrarApenasPlanejados = filtroSwitch.checked;
        
        listaConteudosContainer.querySelectorAll('.conteudo-item').forEach(item => {
            // dataset.planejado é uma string '0' ou '1'
            const isPlanejado = item.dataset.planejado === '1';
            
            if (mostrarApenasPlanejados && !isPlanejado) {
                item.style.display = 'none'; // Esconde se for filtro e não planejado
            } else {
                item.style.display = 'flex'; // Mostra caso contrário
            }
        });
    }

    filtroSwitch.addEventListener('change', aplicarFiltro);


    // --- 2. Lógica do Toggle (Checkbox de Visibilidade) ---
    switches.forEach(function(switchElement) {
        switchElement.addEventListener('change', function() {
            const aulaId = this.dataset.aulaId;
            const conteudoId = this.dataset.conteudoId;
            const novoStatus = this.checked ? 1 : 0; // 1 (Visível) ou 0 (Não Visível)
            
            const statusLabel = this.closest('.form-switch').querySelector('.status-label');
            const conteudoItem = this.closest('.conteudo-item');
            
            // Cria o objeto FormData
            const formData = new FormData();
            formData.append('aula_id', aulaId);
            formData.append('conteudo_id', conteudoId);
            formData.append('status', novoStatus); // 0 ou 1
            
            // Desabilita o switch durante o AJAX
            this.disabled = true; 
            statusLabel.textContent = '...';

            // Executa a Requisição AJAX usando Fetch API
            fetch('ajax_toggle_conteudo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição: Status ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                this.disabled = false; // Habilita o switch de volta

                if (data.success) {
                    displayAlert(data.message, 'success');
                    
                    // Atualiza o data-atributo e a classe visual
                    conteudoItem.dataset.planejado = novoStatus;
                    
                    if (novoStatus === 1) {
                        statusLabel.textContent = 'Sim';
                        conteudoItem.classList.add('planejado');
                        conteudoItem.classList.remove('nao-planejado');
                    } else {
                        statusLabel.textContent = 'Não';
                        conteudoItem.classList.remove('planejado');
                        conteudoItem.classList.add('nao-planejado');
                    }

                    // Reavalia o filtro após a atualização do status (necessário para que a lista se ajuste)
                    aplicarFiltro();

                } else {
                    console.error('Erro:', data.message);
                    displayAlert('Erro ao atualizar status: ' + data.message, 'danger');
                    
                    // Reverte o estado do switch em caso de falha
                    this.checked = !this.checked; 
                    statusLabel.textContent = novoStatus === 1 ? 'Não' : 'Sim'; // Reverte o texto do label
                }
            })
            .catch(error => {
                this.disabled = false; // Habilita o switch de volta
                console.error('Erro de conexão ou servidor:', error);
                displayAlert('Erro de conexão ao servidor ou erro interno.', 'danger');
                
                // Reverte o estado do switch em caso de falha
                this.checked = !this.checked; 
                statusLabel.textContent = novoStatus === 1 ? 'Não' : 'Sim'; // Reverte o texto do label
            });
        });
    });
    
    // Dispara o filtro na carga para aplicar as classes visuais iniciais
    aplicarFiltro();
});
</script>
</body>
</html>
