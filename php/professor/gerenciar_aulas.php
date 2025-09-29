<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$mensagem = '';
$sucesso = false;

// --- FUNÇÕES DE ASSOCIAÇÃO ---

// Função para buscar os conteúdos associados a uma aula
function getConteudoAssociado($pdo, $aula_id) {
    $sql = "SELECT ac.conteudo_id, c.titulo FROM aulas_conteudos ac 
            JOIN conteudos c ON ac.conteudo_id = c.id
            WHERE ac.aula_id = :aula_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':aula_id' => $aula_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para salvar a associação de conteúdos
function saveConteudoAssociacao($pdo, $aula_id, $conteudo_ids) {
    // 1. Deleta associações antigas para evitar duplicidade
    $sql_delete = "DELETE FROM aulas_conteudos WHERE aula_id = :aula_id";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([':aula_id' => $aula_id]);

    // 2. Insere as novas associações
    if (!empty($conteudo_ids)) {
        $sql_insert = "INSERT INTO aulas_conteudos (aula_id, conteudo_id) VALUES (:aula_id, :conteudo_id)";
        $stmt_insert = $pdo->prepare($sql_insert);
        
        foreach ($conteudo_ids as $conteudo_id) {
            // Garante que o ID é um número inteiro válido antes de executar
            if (is_numeric($conteudo_id)) {
                $stmt_insert->execute([':aula_id' => $aula_id, ':conteudo_id' => (int)$conteudo_id]);
            }
        }
    }
}


// --- 1. LÓGICA DE CRIAÇÃO/EDIÇÃO DE AULA (CREATE/UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['acao']) && ($_POST['acao'] === 'adicionar' || $_POST['acao'] === 'editar'))) {
    
    $titulo_aula = trim($_POST['titulo_aula']);
    $descricao = trim($_POST['descricao']);
    $data_aula = $_POST['data_aula'];
    $horario = $_POST['horario'];
    $turma_id = $_POST['turma_id'];
    // Garante que conteudo_ids é um array (mesmo que vazio) para evitar erro
    $conteudo_ids = isset($_POST['conteudo_ids']) && is_array($_POST['conteudo_ids']) ? $_POST['conteudo_ids'] : [];
    $aula_id = $_POST['aula_id'] ?? null;
    $acao = $_POST['acao'];

    // Validação básica
    if (empty($titulo_aula) || empty($data_aula) || empty($horario) || empty($turma_id)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios (Título, Data, Horário e Turma).";
    } else {
        try {
            if ($acao === 'adicionar') {
                // Insere a nova aula
                $sql = "INSERT INTO aulas (professor_id, titulo_aula, descricao, data_aula, horario, turma_id) 
                        VALUES (:professor_id, :titulo_aula, :descricao, :data_aula, :horario, :turma_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':professor_id' => $professor_id,
                    ':titulo_aula' => $titulo_aula,
                    ':descricao' => $descricao,
                    ':data_aula' => $data_aula,
                    ':horario' => $horario,
                    ':turma_id' => $turma_id
                ]);
                $nova_aula_id = $pdo->lastInsertId();
                
                // Associa os conteúdos
                saveConteudoAssociacao($pdo, $nova_aula_id, $conteudo_ids);
                
                $mensagem = "Aula agendada com sucesso!";
            } else { // Editar
                // Atualiza a aula existente
                $sql = "UPDATE aulas SET titulo_aula = :titulo_aula, descricao = :descricao, data_aula = :data_aula, horario = :horario, turma_id = :turma_id 
                        WHERE id = :id AND professor_id = :professor_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $aula_id,
                    ':professor_id' => $professor_id,
                    ':titulo_aula' => $titulo_aula,
                    ':descricao' => $descricao,
                    ':data_aula' => $data_aula,
                    ':horario' => $horario,
                    ':turma_id' => $turma_id
                ]);
                
                // Atualiza a associação de conteúdos
                saveConteudoAssociacao($pdo, $aula_id, $conteudo_ids);
                
                $mensagem = "Aula atualizada com sucesso!";
            }
            $sucesso = true;
        } catch (PDOException $e) {
            $mensagem = "Erro ao processar a aula: " . $e->getMessage();
        }
    }
}

// --- 2. LÓGICA DE EXCLUSÃO DE AULA (DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $aula_id = $_POST['aula_id'];

    try {
        // 1. Deleta associações de conteúdo primeiro (IMPORTANTE)
        $sql_delete_ac = "DELETE FROM aulas_conteudos WHERE aula_id = :aula_id";
        $pdo->prepare($sql_delete_ac)->execute([':aula_id' => $aula_id]);
        
        // 2. Deleta a aula
        $sql_delete = "DELETE FROM aulas WHERE id = :id AND professor_id = :professor_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([':id' => $aula_id, ':professor_id' => $professor_id]);

        $mensagem = "Aula excluída com sucesso!";
        $sucesso = true;
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir a aula: " . $e->getMessage();
    }
}


// --- 3. CONSULTAS DE LEITURA (READ) ---

// A. Lista de Aulas
$sql_aulas = "
    SELECT 
        a.id, a.titulo_aula, a.data_aula, a.horario, a.descricao, t.nome_turma 
    FROM 
        aulas a
    JOIN 
        turmas t ON a.turma_id = t.id
    WHERE 
        a.professor_id = :professor_id
    ORDER BY 
        a.data_aula DESC, a.horario DESC
";
$stmt_aulas = $pdo->prepare($sql_aulas);
$stmt_aulas->bindParam(':professor_id', $professor_id);
$stmt_aulas->execute();
$lista_aulas = $stmt_aulas->fetchAll(PDO::FETCH_ASSOC);

// B. Lista de Turmas (para o SELECT do formulário)
$sql_turmas = "SELECT id, nome_turma FROM turmas WHERE professor_id = :professor_id ORDER BY nome_turma ASC";
$stmt_turmas = $pdo->prepare($sql_turmas);
$stmt_turmas->bindParam(':professor_id', $professor_id);
$stmt_turmas->execute();
$lista_turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);

// C. Lista de Conteúdos (para o SELECT do formulário)
$sql_conteudos = "SELECT id, titulo, tipo_arquivo FROM conteudos WHERE professor_id = :professor_id ORDER BY titulo ASC";
$stmt_conteudos = $pdo->prepare($sql_conteudos);
$stmt_conteudos->bindParam(':professor_id', $professor_id);
$stmt_conteudos->execute();
$lista_conteudos = $stmt_conteudos->fetchAll(PDO::FETCH_ASSOC);

// --- 4. CONFIGURAÇÃO DE EDIÇÃO (PREENCHER MODAL) / PRÉ-SELEÇÃO DE TURMA ---
$aula_para_editar = null;
$turma_id_preselecionada = $_GET['turma_id'] ?? null; // Pega o ID da turma se vier da URL (detalhes_turma.php)
$abrir_modal = false; 

if (isset($_GET['editar'])) {
    $aula_id_editar = $_GET['editar'];
    $sql_edit = "SELECT * FROM aulas WHERE id = :id AND professor_id = :professor_id";
    $stmt_edit = $pdo->prepare($sql_edit);
    $stmt_edit->execute([':id' => $aula_id_editar, ':professor_id' => $professor_id]);
    $aula_para_editar = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    
    if ($aula_para_editar) {
        $conteudos_selecionados = getConteudoAssociado($pdo, $aula_id_editar);
        // Cria um array simples de IDs de conteúdo para pré-seleção no formulário
        $aula_para_editar['conteudo_ids'] = array_column($conteudos_selecionados, 'conteudo_id'); 
        $turma_id_preselecionada = $aula_para_editar['turma_id']; // Sobrescreve a pré-seleção se estiver editando
        $abrir_modal = true;
    }
} elseif ($turma_id_preselecionada) {
    // Se não está editando, mas tem um turma_id na URL, abrimos o modal
    $abrir_modal = true;
}

// Script para abrir o modal automaticamente
if ($abrir_modal) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var modalElement = document.getElementById('modalAdicionarEditar');
            var modal = new bootstrap.Modal(modalElement);
            
            // Corrige o bug de fundo estático do modal
            modalElement.addEventListener('hidden.bs.modal', function () {
                document.body.style.overflow = ''; 
                document.body.style.paddingRight = '';
            });

            modal.show();
        });
    </script>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Aulas - Professor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../css/professor/gerenciar_aulas.css">
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard (Agenda)</a>
        <a href="gerenciar_aulas.php" style="background-color: #92171B;"><i class="fas fa-calendar-alt me-2"></i> **Agendar/Gerenciar Aulas**</a>
        <a href="gerenciar_conteudos.php"><i class="fas fa-book-open me-2"></i> Conteúdos (Biblioteca)</a>
        <a href="gerenciar_alunos.php"><i class="fas fa-users me-2"></i> Alunos/Turmas</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Agendamento e Gerenciamento de Aulas</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <button class="btn text-white mb-3" data-bs-toggle="modal" data-bs-target="#modalAdicionarEditar" style="background-color: var(--cor-secundaria);">
            <i class="fas fa-plus me-2"></i> Agendar Nova Aula
        </button>

        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                Próximas Aulas Agendadas
            </div>
            <div class="card-body p-0">
                <?php if (empty($lista_aulas)): ?>
                    <p class="p-4 text-center text-muted">Nenhuma aula agendada ainda.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Data / Horário</th>
                                    <th>Título da Aula</th>
                                    <th>Turma</th>
                                    <th>Conteúdos Associados</th>
                                    <th style="width: 150px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_aulas as $aula): 
                                    // Puxa os conteúdos associados para exibição na tabela
                                    $conteudos_aula = getConteudoAssociado($pdo, $aula['id']);
                                ?>
                                    <tr>
                                        <td>
                                            <?= date('d/m/Y', strtotime($aula['data_aula'])) ?> às <?= substr($aula['horario'], 0, 5) ?>
                                        </td>
                                        <td><?= htmlspecialchars($aula['titulo_aula']) ?></td>
                                        <td><?= htmlspecialchars($aula['nome_turma']) ?></td>
                                        <td>
                                            <?php if (!empty($conteudos_aula)): ?>
                                                <?php foreach ($conteudos_aula as $cont): ?>
                                                    <span class="badge rounded-pill bg-info text-dark me-1"><?= htmlspecialchars($cont['titulo']) ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <small class="text-muted">Nenhum</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?editar=<?= $aula['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar Aula">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluir" data-aula-id="<?= $aula['id'] ?>" title="Excluir Aula">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAdicionarEditar" tabindex="-1" aria-labelledby="modalAdicionarEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: var(--cor-primaria); color: white;">
                <h5 class="modal-title" id="modalAdicionarEditarLabel"><?= $aula_para_editar ? 'Editar' : 'Agendar Nova' ?> Aula</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="gerenciar_aulas.php" method="POST" id="formAula">
                <div class="modal-body">
                    
                    <input type="hidden" name="acao" id="acao" value="<?= $aula_para_editar ? 'editar' : 'adicionar' ?>">
                    <input type="hidden" name="aula_id" value="<?= $aula_para_editar['id'] ?? '' ?>">

                    <div class="mb-3">
                        <label for="turma_id" class="form-label">Turma</label>
                        <select class="form-select" id="turma_id" name="turma_id" required>
                            <option value="">Selecione a Turma</option>
                            <?php foreach ($lista_turmas as $turma): ?>
                                <option value="<?= $turma['id'] ?>" 
                                    <?= ((isset($turma_id_preselecionada) && (string)$turma_id_preselecionada === (string)$turma['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($turma['nome_turma']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="titulo_aula" class="form-label">Título da Aula</label>
                        <input type="text" class="form-control" id="titulo_aula" name="titulo_aula" required 
                               value="<?= htmlspecialchars($aula_para_editar['titulo_aula'] ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_aula" class="form-label">Data</label>
                            <input type="date" class="form-control" id="data_aula" name="data_aula" required 
                                   value="<?= htmlspecialchars($aula_para_editar['data_aula'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="horario" class="form-label">Horário</label>
                            <input type="time" class="form-control" id="horario" name="horario" required 
                                   value="<?= htmlspecialchars(substr($aula_para_editar['horario'] ?? '00:00:00', 0, 5)) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($aula_para_editar['descricao'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="conteudo_ids" class="form-label">Conteúdos a serem usados nesta aula</label>
                        <select class="form-select select2-enable" id="conteudo_ids" name="conteudo_ids[]" multiple="multiple" style="width: 100%;">
                            <?php foreach ($lista_conteudos as $conteudo): ?>
                                <option value="<?= $conteudo['id'] ?>" 
                                    <?= (isset($aula_para_editar['conteudo_ids']) && in_array($conteudo['id'], $aula_para_editar['conteudo_ids'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($conteudo['titulo']) ?> (<?= strtoupper($conteudo['tipo_arquivo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Selecione um ou mais conteúdos disponíveis na sua biblioteca.</small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn text-white" style="background-color: var(--cor-secundaria);">
                        <i class="fas fa-save me-1"></i> Salvar Aula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExcluir" tabindex="-1" aria-labelledby="modalExcluirLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalExcluirLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="gerenciar_aulas.php" method="POST">
                <div class="modal-body">
                    <p>Tem certeza de que deseja **excluir permanentemente** esta aula?</p>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="aula_id" id="aula_id_excluir">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Inicializa o Select2
    $('.select2-enable').select2({
        placeholder: "Selecione os conteúdos...",
        allowClear: true
    });

    // Função para preencher o ID no modal de exclusão
    $('#modalExcluir').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Botão que acionou o modal
        var aulaId = button.data('aula-id'); // Extrai o ID da aula do atributo data-aula-id
        var modal = $(this);
        modal.find('.modal-body #aula_id_excluir').val(aulaId);
    });

    // Função para limpar o modal ao fechar, preparando para um novo agendamento
    $('#modalAdicionarEditar').on('hidden.bs.modal', function () {
        // Redireciona para a página limpa (sem parâmetros de edição/pré-seleção)
        if (window.location.search) {
            window.location.href = 'gerenciar_aulas.php';
        }
    });
});
</script>

</body>
</html>