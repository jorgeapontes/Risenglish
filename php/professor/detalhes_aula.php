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
        c.id, c.titulo, c.descricao, c.caminho_arquivo, 
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

// --- 3. LÓGICA DE ATUALIZAÇÃO DO CHECKBOX 'PLANEJADO' ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_planejado') {
    $conteudo_id_post = $_POST['conteudo_id'];
    // O valor 'planejado' é 1 se o checkbox estava marcado, 0 caso contrário.
    $planejado_status = isset($_POST['planejado']) ? 1 : 0; 
    
    try {
        $sql_update = "
            UPDATE 
                aulas_conteudos 
            SET 
                planejado = :planejado 
            WHERE 
                aula_id = :aula_id AND conteudo_id = :conteudo_id
        ";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            ':planejado' => $planejado_status, 
            ':aula_id' => $aula_id, 
            ':conteudo_id' => $conteudo_id_post
        ]);

        // Redireciona para atualizar a página e evitar reenvio do POST
        header("Location: detalhes_aula.php?aula_id=" . $aula_id . "&msg=sucesso");
        exit;
        
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao atualizar status: " . $e->getMessage();
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
    <link rel="stylesheet" href="../../css/professor/detalhes_aula.css">
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard (Agenda)</a>
        <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt me-2"></i> Agendar/Gerenciar Aulas</a>
        <a href="gerenciar_conteudos.php"><i class="fas fa-book-open me-2"></i> Conteúdos (Biblioteca)</a>
        <a href="gerenciar_alunos.php"><i class="fas fa-users me-2"></i> Alunos/Turmas</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 style="color: var(--cor-primaria);">Detalhes da Aula</h1>
            <a href="gerenciar_aulas.php?editar=<?= $detalhes_aula['aula_id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i> Editar/Vincular Conteúdos
            </a>
        </div>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sucesso'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Status do conteúdo atualizado com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($mensagem_erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $mensagem_erro ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

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
                            <strong>Status</strong>
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
                        
                        <div class="conteudo-item d-flex align-items-center <?= $planejado_class ?>">
                            
                            <div class="col-1 text-center">
                                <form method="POST" action="detalhes_aula.php?aula_id=<?= $aula_id ?>" class="m-0">
                                    <input type="hidden" name="acao" value="atualizar_planejado">
                                    <input type="hidden" name="conteudo_id" value="<?= $c['id'] ?>">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               role="switch" 
                                               name="planejado" 
                                               id="planejado_<?= $c['id'] ?>" 
                                               value="1"
                                               onchange="this.form.submit()"
                                               <?= $c['planejado'] == 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="planejado_<?= $c['id'] ?>">
                                            <?= $c['planejado'] == 1 ? 'Planejado' : 'Não Usado' ?>
                                        </label>
                                    </div>
                                </form>
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

</body>
</html>