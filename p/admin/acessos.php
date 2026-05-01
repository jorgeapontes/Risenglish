<?php
session_start();
require_once '../includes/conexao.php';

// Verificação básica de segurança para garantir que apenas admins acessem
if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$busca = $_GET['busca'] ?? '';
$tipo_filtro = $_GET['tipo_filtro'] ?? ''; // '' = todos, 'aluno' = alunos, 'professor' = professores
$pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

// Constrói a cláusula WHERE dinamicamente
$where = "WHERE (u.nome LIKE :busca OR u.email LIKE :busca)";
if ($tipo_filtro === 'aluno' || $tipo_filtro === 'professor') {
    $where .= " AND u.tipo_usuario = :tipo_filtro";
}

// Consulta com JOIN e agrupamento
$sql = "SELECT u.nome, u.email, u.tipo_usuario, MAX(l.data_acesso) as ultimo_acesso 
        FROM usuarios u 
        LEFT JOIN logs_acesso l ON u.id = l.usuario_id 
        $where
        GROUP BY u.id 
        ORDER BY ultimo_acesso DESC 
        LIMIT :offset, :limite";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);

if ($tipo_filtro === 'aluno' || $tipo_filtro === 'professor') {
    $stmt->bindValue(':tipo_filtro', $tipo_filtro, PDO::PARAM_STR);
}

$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->execute();
$relatorioAcessos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de Acessos - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Relatório de Acessos</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Voltar ao Dashboard</a>
    </div>

    <!-- Formulário de Filtros -->
    <form method="GET" class="row g-3 mb-4 p-3 bg-light border rounded">
        <div class="col-md-5">
            <label class="form-label">Buscar Usuário</label>
            <input type="text" class="form-control" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou e-mail...">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tipo de Usuário</label>
            <select name="tipo_filtro" class="form-select">
                <option value="" <?= $tipo_filtro === '' ? 'selected' : '' ?>>Todos</option>
                <option value="aluno" <?= $tipo_filtro === 'aluno' ? 'selected' : '' ?>>Alunos</option>
                <option value="professor" <?= $tipo_filtro === 'professor' ? 'selected' : '' ?>>Professores</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <!-- Tabela de Acessos -->
    <div class="table-responsive">
        <table class="table table-striped table-hover border">
            <thead class="table-dark">
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Tipo</th>
                    <th>Última Vez Visto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($relatorioAcessos) > 0): ?>
                    <?php foreach ($relatorioAcessos as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['nome']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td>
                            <?php if ($usuario['tipo_usuario'] === 'professor'): ?>
                                <span class="badge bg-success">Professor</span>
                            <?php elseif ($usuario['tipo_usuario'] === 'aluno'): ?>
                                <span class="badge bg-primary">Aluno</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($usuario['tipo_usuario'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $usuario['ultimo_acesso'] 
                                ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) 
                                : '<span class="text-muted fst-italic">Nunca acessou</span>' 
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">Nenhum registro encontrado para os filtros aplicados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?p=<?= $pagina - 1 ?>&busca=<?= urlencode($busca) ?>&tipo_filtro=<?= urlencode($tipo_filtro) ?>">Anterior</a>
                </li>
            <?php endif; ?>
            
            <?php if(count($relatorioAcessos) === $limite): ?>
                <li class="page-item">
                    <a class="page-link" href="?p=<?= $pagina + 1 ?>&busca=<?= urlencode($busca) ?>&tipo_filtro=<?= urlencode($tipo_filtro) ?>">Próxima</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>