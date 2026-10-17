<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';
require_once '../includes/avatar.php';

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
        u.foto_perfil,
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
    GROUP BY u.id, u.nome, u.foto_perfil, u.email, u.tipo_usuario
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
            <h1 class="page-title">Relatório de Acessos</h1>
        </div>

        <!-- Formulário de Filtros -->
        <form method="GET" id="formFiltros" class="filtros-acessos row g-3 mb-4">
            <input type="hidden" name="ordem" id="ordemHidden" value="<?= htmlspecialchars($ordem) ?>">

            <!-- Campo de busca com botão X -->
            <div class="col-md-6">
                <label class="form-label" for="inputBusca">Buscar usuário</label>
                <div class="input-busca-wrapper">
                    <input type="text" class="form-control pe-4" id="inputBusca" name="busca"
                           value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou e-mail...">
                    <button type="button" class="btn-limpar-busca" id="btnLimpar" title="Limpar busca">&times;</button>
                </div>
            </div>

            <!-- Filtro de tipo: submete ao mudar -->
            <div class="col-md-4">
                <label class="form-label" for="tipoFiltro">Tipo de usuário</label>
                <select name="tipo_filtro" id="tipoFiltro" class="form-select" onchange="document.getElementById('formFiltros').submit()">
                    <option value=""          <?= $tipo_filtro === ''          ? 'selected' : '' ?>>Todos</option>
                    <option value="aluno"     <?= $tipo_filtro === 'aluno'     ? 'selected' : '' ?>>Alunos</option>
                    <option value="professor" <?= $tipo_filtro === 'professor' ? 'selected' : '' ?>>Professores</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-filtrar w-100">Filtrar</button>
            </div>
        </form>

        <p class="contagem-acessos">
            <?= count($relatorioAcessos) ?> usuário(s) · ordenado pelo último acesso,
            <?= $ordem === 'desc' ? 'mais recentes primeiro' : 'mais antigos primeiro' ?>
        </p>

        <!-- Tabela -->
        <?php $proximaOrdem = $ordem === 'desc' ? 'asc' : 'desc'; ?>
        <div class="table-responsive">
            <table class="table tabela-acessos">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Tipo</th>
                        <th>Neste mês</th>
                        <th aria-sort="<?= $ordem === 'desc' ? 'descending' : 'ascending' ?>">
                            <!-- Clicar no cabeçalho inverte a ordenação -->
                            <a class="ordenar-coluna" href="<?= htmlspecialchars($urlBase . '&ordem=' . $proximaOrdem) ?>"
                               title="<?= $ordem === 'desc' ? 'Mostrar mais antigos primeiro' : 'Mostrar mais recentes primeiro' ?>">
                                Último acesso <span aria-hidden="true"><?= $ordem === 'desc' ? '↓' : '↑' ?></span>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($relatorioAcessos) > 0): ?>
                        <?php foreach ($relatorioAcessos as $usuario): ?>
                            <tr>
                                <td><?= avatar_com_nome($usuario['nome'], $usuario['foto_perfil'], 28) ?></td>
                                <td class="texto-secundario"><?= htmlspecialchars($usuario['email']) ?></td>
                                <td class="texto-secundario"><?= $usuario['tipo_usuario'] === 'professor' ? 'Professor' : 'Aluno' ?></td>
                                <td>
                                    <?php if ($usuario['acessos_mes'] > 0): ?>
                                        <?= (int) $usuario['acessos_mes'] ?> <?= (int) $usuario['acessos_mes'] === 1 ? 'acesso' : 'acessos' ?>
                                    <?php else: ?>
                                        <span class="texto-vazio">Nenhum</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($usuario['ultimo_acesso']): ?>
                                        <span title="<?= date('d/m/Y H:i:s', strtotime($usuario['ultimo_acesso'])) ?>">
                                            <?= date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="texto-vazio">Nunca acessou</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 texto-vazio">
                                Nenhum usuário encontrado para esses filtros.
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