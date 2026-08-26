<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Garante que apenas admin acessa esta página
if ($_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login?erro=acesso_negado");
    exit;
}

$nome_usuario = $_SESSION['user_nome'] ?? 'Administrador';

$busca       = $_GET['busca']       ?? '';
$tipo_filtro = $_GET['tipo_filtro'] ?? '';
$ordem       = ($_GET['ordem'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$ordemSQL    = $ordem === 'asc' ? 'ASC' : 'DESC';

// WHERE: exclui admin sempre; filtra por tipo e busca se necessário
$where = "WHERE u.tipo_usuario != 'admin' AND (u.nome LIKE :busca OR u.email LIKE :busca)";
if ($tipo_filtro === 'aluno' || $tipo_filtro === 'professor') {
    $where .= " AND u.tipo_usuario = :tipo_filtro";
}

// Sem LIMIT: todos na mesma página
$sql = "
    SELECT 
        u.id,
        u.nome, 
        u.email, 
        u.tipo_usuario, 
        MAX(l.data_acesso) AS ultimo_acesso,
        (
            SELECT COUNT(*) 
            FROM logs_acesso lm 
            WHERE lm.usuario_id = u.id 
              AND MONTH(lm.data_acesso) = MONTH(NOW())
              AND YEAR(lm.data_acesso)  = YEAR(NOW())
        ) AS acessos_mes
    FROM usuarios u
    LEFT JOIN logs_acesso l ON u.id = l.usuario_id
    $where
    GROUP BY u.id, u.nome, u.email, u.tipo_usuario
    ORDER BY MAX(l.data_acesso) $ordemSQL
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
if ($tipo_filtro === 'aluno' || $tipo_filtro === 'professor') {
    $stmt->bindValue(':tipo_filtro', $tipo_filtro, PDO::PARAM_STR);
}
$stmt->execute();
$relatorioAcessos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$urlBase = '?busca=' . urlencode($busca) . '&tipo_filtro=' . urlencode($tipo_filtro);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de Acessos - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/admin/base.css">
    <link rel="stylesheet" href="../../css/admin/acessos.css">
    <style>
        /* Valor dinâmico por requisição — não pode ir para o arquivo .css estático */
        .btn-limpar-busca {
            display: <?= $busca !== '' ? 'block' : 'none' ?>;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php $paginaAtiva = 'acessos'; require '../includes/layout/admin_sidebar.php'; ?>

    <!-- Conteúdo Principal -->
    <div class="main-content flex-grow-1">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-line me-2"></i>Relatório de Acessos
            </h1>
        </div>

        <!-- Formulário de Filtros -->
        <form method="GET" id="formFiltros" class="row g-3 mb-4 p-3 bg-light border rounded">
            <input type="hidden" name="ordem" id="ordemHidden" value="<?= htmlspecialchars($ordem) ?>">

            <!-- Campo de busca com botão X -->
            <div class="col-md-5">
                <label class="form-label"><i class="fas fa-search me-1"></i>Buscar Usuário</label>
                <div class="input-busca-wrapper">
                    <input type="text" class="form-control pe-4" id="inputBusca" name="busca"
                           value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou e-mail...">
                    <button type="button" class="btn-limpar-busca" id="btnLimpar" title="Limpar busca">&times;</button>
                </div>
            </div>

            <!-- Filtro de tipo: submete ao mudar -->
            <div class="col-md-4">
                <label class="form-label"><i class="fas fa-users me-1"></i>Tipo de Usuário</label>
                <select name="tipo_filtro" class="form-select" onchange="document.getElementById('formFiltros').submit()">
                    <option value=""          <?= $tipo_filtro === ''          ? 'selected' : '' ?>>Todos</option>
                    <option value="aluno"     <?= $tipo_filtro === 'aluno'     ? 'selected' : '' ?>>Alunos</option>
                    <option value="professor" <?= $tipo_filtro === 'professor' ? 'selected' : '' ?>>Professores</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-acao flex-grow-1">
                    <i class="fas fa-filter me-1"></i>Filtrar
                </button>
                
                <!-- Botões de ordenação -->
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-ordem <?= $ordem === 'desc' ? 'btn-ordem-desc' : 'btn-outline-primary' ?>" 
                            onclick="setOrdem('desc')" title="Mais recentes primeiro">
                        <i class="fas fa-arrow-down"></i> Recents
                    </button>
                    <button type="button" class="btn btn-ordem <?= $ordem === 'asc' ? 'btn-ordem-asc' : 'btn-outline-secondary' ?>" 
                            onclick="setOrdem('asc')" title="Mais antigos primeiro">
                        <i class="fas fa-arrow-up"></i> Antigos
                    </button>
                </div>
            </div>
        </form>

        <!-- Contagem -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted mb-0">
                <i class="fas fa-database me-1"></i><?= count($relatorioAcessos) ?> usuário(s) encontrado(s)
            </p>
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>Ordenado por: 
                <?= $ordem === 'desc' ? 'mais recentes primeiro' : 'mais antigos primeiro' ?>
            </small>
        </div>

        <!-- Tabela -->
        <div class="table-responsive">
            <table class="table table-striped table-hover border">
                <thead class="table-dark">
                    <tr>
                        <th><i class="fas fa-user me-1"></i>Nome</th>
                        <th><i class="fas fa-envelope me-1"></i>E-mail</th>
                        <th><i class="fas fa-tag me-1"></i>Tipo</th>
                        <th><i class="fas fa-calendar-alt me-1"></i>Neste Mês</th>
                        <th>
                            <i class="fas fa-clock me-1"></i>Último Acesso
                            <span class="ms-1 text-muted small">
                                <?= $ordem === 'desc' ? '↓' : '↑' ?>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($relatorioAcessos) > 0): ?>
                        <?php foreach ($relatorioAcessos as $usuario): ?>
                            <tr>
                                <td class="align-middle">
                                    <strong><?= htmlspecialchars($usuario['nome']) ?></strong>
                                </td>
                                <td class="align-middle"><?= htmlspecialchars($usuario['email']) ?></td>
                                <td class="align-middle">
                                    <?php if ($usuario['tipo_usuario'] === 'professor'): ?>
                                        <span class="badge bg-success badge-acesso">
                                            <i class="fas fa-chalkboard-teacher me-1"></i>Professor
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary badge-acesso">
                                            <i class="fas fa-graduation-cap me-1"></i>Aluno
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle">
                                    <?php if ($usuario['acessos_mes'] > 0): ?>
                                        <span><?= $usuario['acessos_mes'] ?> acessos</span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Sem acessos</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle">
                                    <?php if ($usuario['ultimo_acesso']): ?>
                                        <span title="<?= date('d/m/Y H:i:s', strtotime($usuario['ultimo_acesso'])) ?>">
                                            <i class="fas fa-history me-1 text-muted"></i>
                                            <?= date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">
                                            <i class="fas fa-ban me-1"></i>Nunca acessou
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-search fa-2x text-muted mb-2 d-block"></i>
                                <p class="text-muted mb-0">Nenhum registro encontrado para os filtros aplicados.</p>
                                <small class="text-muted">Tente alterar os critérios de busca.</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const inputBusca = document.getElementById('inputBusca');
    const btnLimpar  = document.getElementById('btnLimpar');
    const ordemHidden = document.getElementById('ordemHidden');
    
    // Função para definir a ordenação e submeter o formulário
    function setOrdem(ordem) {
        ordemHidden.value = ordem;
        document.getElementById('formFiltros').submit();
    }

    // Mostra/esconde o X enquanto o usuário digita
    inputBusca.addEventListener('input', () => {
        btnLimpar.style.display = inputBusca.value.length > 0 ? 'block' : 'none';
    });

    // Ao clicar no X: limpa o campo e submete o formulário
    btnLimpar.addEventListener('click', () => {
        inputBusca.value = '';
        btnLimpar.style.display = 'none';
        document.getElementById('formFiltros').submit();
    });
</script>
</body>
</html>