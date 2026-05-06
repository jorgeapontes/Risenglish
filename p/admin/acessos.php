<?php
session_start();
require_once '../includes/conexao.php';

// Verificação de segurança: apenas admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
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
    <style>
        :root {
            --cor-primaria: #0A1931;
            --cor-secundaria: #c0392b;
            --cor-destaque: #c0392b;
            --cor-texto: #333;
            --cor-fundo: #f8f9fa;
            --cor-borda: #dee2e6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            margin: 0;
            padding: 0;
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
            margin-left: 280px;
            padding: 30px;
            background-color: white;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h6 {
            color: var(--cor-primaria);
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .card-body {
            padding: 25px;
        }

        .btn-acao {
            background: var(--cor-secundaria);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-acao:hover {
            background: var(--cor-secundaria);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(146, 23, 27, 0.3);
            color: white;
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        .main-content {
            margin-left: 16.666667%;
            width: 83.333333%;
        }

        /* Estilos específicos da página de acessos */
        .input-busca-wrapper {
            position: relative;
        }
        .btn-limpar-busca {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            font-size: 1.2rem;
            line-height: 1;
            padding: 0;
            cursor: pointer;
            display: <?= $busca !== '' ? 'block' : 'none' ?>;
        }
        .btn-limpar-busca:hover { color: #212529; }
        
        .btn-ordem {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 0.85rem;
            border-radius: 20px;
            transition: all 0.2s ease;
        }
        
        .btn-ordem i {
            font-size: 0.8rem;
        }
        
        .btn-ordem-desc {
            background-color: #0d6efd;
            color: white;
            border: none;
        }
        
        .btn-ordem-asc {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        
        .btn-ordem-desc:hover, .btn-ordem-asc:hover {
            opacity: 0.85;
            color: white;
        }
        
        .badge-acesso {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        
        .table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--cor-borda);
            padding-bottom: 15px;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--cor-primaria);
            margin: 0;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <!-- Nome do administrador -->
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo $nome_usuario; ?></h5>
        </div>

        <!-- Menu centralizado verticalmente -->
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="personalizar_index.php" class="rounded"><i class="fas fa-paint-brush"></i>&nbsp;&nbsp;Personalizar Site</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="acessos.php" class="rounded active"><i class="fas fa-chart-line"></i>&nbsp;&nbsp;Relatório de Acessos</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
        </div>

        <!-- Botão sair no rodapé -->
        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

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