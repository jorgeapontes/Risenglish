<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$mensagem = '';
$tipo_mensagem = '';
$termo_pesquisa = '';

// --- LÓGICA DE CRUD DE USUÁRIOS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && ($_POST['acao'] == 'add_usuario' || $_POST['acao'] == 'editar_usuario')) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo_usuario'];
    $informacoes = $_POST['informacoes'] ?? '';
    $usuario_id = $_POST['usuario_id'] ?? null;
    $acao = $_POST['acao'];

    try {
        if (empty($nome) || empty($email) || empty($tipo)) throw new Exception("Todos os campos obrigatórios (Nome, Email, Tipo) devem ser preenchidos.");

        if ($acao == 'add_usuario') {
            if (empty($senha)) throw new Exception("A senha é obrigatória para novos cadastros.");

            $sql_check = "SELECT id FROM usuarios WHERE email = :email";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':email', $email);
            $stmt_check->execute();
            if ($stmt_check->rowCount() > 0) throw new Exception("O email já está cadastrado.");

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, informacoes) VALUES (:nome, :email, :senha, :tipo_usuario, :informacoes)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':informacoes', $informacoes);
            $mensagem = "Usuário <strong>{$nome}</strong> cadastrado como <strong>{$tipo}</strong> com sucesso!";

        } else {
            $sql_parts = ["nome = :nome", "email = :email", "tipo_usuario = :tipo_usuario", "informacoes = :informacoes"];
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $sql_parts[] = "senha = :senha";
            }
            $sql = "UPDATE usuarios SET " . implode(', ', $sql_parts) . " WHERE id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':informacoes', $informacoes);
            if (!empty($senha)) $stmt->bindParam(':senha', $senha_hash);
            $mensagem = "Usuário <strong>{$nome}</strong> atualizado com sucesso!";
        }

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':tipo_usuario', $tipo);
        $stmt->execute();
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $mensagem = "Erro ao gerenciar usuário: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA DE REMOÇÃO DE USUÁRIO ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'remover_usuario') {
    $id_usuario = $_POST['id_usuario'];

    try {
        $sql = "DELETE FROM usuarios WHERE id = :id_usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $mensagem = "Usuário removido com sucesso!";
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = "Erro ao remover usuário: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA PARA ATIVAR / DESATIVAR USUÁRIO ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'toggle_status') {
    $id_usuario = $_POST['id_usuario_toggle'];

    try {
        $sql = "SELECT status FROM usuarios WHERE id = :id_usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $current = $stmt->fetchColumn();
        $novo = ($current === 'ativo') ? 'desativado' : 'ativo';

        $sql = "UPDATE usuarios SET status = :novo WHERE id = :id_usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':novo', $novo);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();

        $mensagem = "Usuário " . ($novo == 'ativo' ? 'ativado' : 'desativado') . " com sucesso!";
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = "Erro ao atualizar status: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- VERIFICAR SE HÁ PESQUISA ---
if (isset($_GET['pesquisa']) && !empty(trim($_GET['pesquisa']))) {
    $termo_pesquisa = trim($_GET['pesquisa']);
    $termo_like = "%" . $termo_pesquisa . "%";
    
    // --- CONSULTA PARA PESQUISAR PROFESSORES ---
    $sql_professores = "SELECT id, nome, email, tipo_usuario, informacoes, status 
                        FROM usuarios 
                        WHERE tipo_usuario = 'professor' 
                        AND (nome LIKE :termo OR email LIKE :termo OR informacoes LIKE :termo)
                        ORDER BY nome";
    $stmt_professores = $pdo->prepare($sql_professores);
    $stmt_professores->bindParam(':termo', $termo_like);
    $stmt_professores->execute();
    $professores = $stmt_professores->fetchAll(PDO::FETCH_ASSOC);
    
    // --- CONSULTA PARA PESQUISAR ALUNOS ---
    $sql_alunos = "SELECT u.id, u.nome, u.email, u.tipo_usuario, u.informacoes, u.status,
                          GROUP_CONCAT(t.nome_turma SEPARATOR ', ') AS turmas_associadas
                   FROM usuarios u
                   LEFT JOIN alunos_turmas at ON u.id = at.aluno_id
                   LEFT JOIN turmas t ON at.turma_id = t.id
                   WHERE u.tipo_usuario = 'aluno'
                   AND (u.nome LIKE :termo OR u.email LIKE :termo OR u.informacoes LIKE :termo)
                   GROUP BY u.id
                   ORDER BY u.nome";
    $stmt_alunos = $pdo->prepare($sql_alunos);
    $stmt_alunos->bindParam(':termo', $termo_like);
    $stmt_alunos->execute();
    $alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);
} else {
    // --- CONSULTAS SEM PESQUISA (TODOS OS USUÁRIOS) ---
    $sql_professores = "SELECT id, nome, email, tipo_usuario, informacoes, status FROM usuarios WHERE tipo_usuario = 'professor' ORDER BY nome";
    $professores = $pdo->query($sql_professores)->fetchAll(PDO::FETCH_ASSOC);

    $sql_alunos = "SELECT u.id, u.nome, u.email, u.tipo_usuario, u.informacoes, u.status, GROUP_CONCAT(t.nome_turma SEPARATOR ', ') AS turmas_associadas
                   FROM usuarios u
                   LEFT JOIN alunos_turmas at ON u.id = at.aluno_id
                   LEFT JOIN turmas t ON at.turma_id = t.id
                   WHERE u.tipo_usuario = 'aluno'
                   GROUP BY u.id
                   ORDER BY u.nome";
    $alunos = $pdo->query($sql_alunos)->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin Risenglish</title>
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

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 16.666667%; /* Equivale a col-md-2 */
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
            margin-left: 16.666667%; /* Compensa a largura da sidebar fixa */
            width: 83.333333%;
            animation: fadeIn 0.5s ease;
            padding: 30px;
        }

        h1, h2, h3, h4, h6 {
            color: var(--cor-primaria);
            font-weight: 600;
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

        .btn-outline-primary {
            border-color: var(--cor-primaria);
            color: var(--cor-primaria);
        }

        .btn-outline-primary:hover {
            background-color: var(--cor-primaria);
            border-color: var(--cor-primaria);
            color: white;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: var(--cor-primaria);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: var(--cor-borda);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(26, 42, 58, 0.02);
        }

        .badge {
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .form-control, .form-select, .form-textarea {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 0.2rem rgba(26, 42, 58, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--cor-primaria);
            margin-bottom: 8px;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .modal-header {
            background: var(--cor-primaria);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
            padding: 20px;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .nav-tabs {
            border-bottom: 2px solid var(--cor-borda);
        }

        .nav-tabs .nav-link {
            color: var(--cor-primaria);
            border: none;
            padding: 12px 25px;
            font-weight: 500;
            border-radius: 8px 8px 0 0;
            margin-right: 5px;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--cor-primaria);
            color: white;
            border: none;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            background-color: rgba(26, 42, 58, 0.1);
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

        .nav-link.active:hover {
            background-color: #081d40;
        }

        .informacoes-text {
            max-height: 100px;
            overflow-y: auto;
            font-size: 0.9em;
            line-height: 1.4;
        }
        
        /* Estilos para a barra de pesquisa */
        .search-container {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .search-input-group {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
        }
        
        .search-input {
            padding-left: 45px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            height: 45px;
        }
        
        .search-input:focus {
            border-color: #081d40;
            box-shadow: 0 0 0 0.2rem rgba(8, 29, 64, 0.25);
        }
        
        .search-results-info {
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .search-results-info .badge {
            background-color: #081d40 !important;
            color: white !important;
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        /* Estilo para destacar o termo de pesquisa */
        mark.bg-warning {
            background-color: #ffc107 !important;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        /* Estilos para o cabeçalho com pesquisa */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h1 {
            margin-bottom: 0;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <!-- Nome do admin -->
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo $_SESSION['user_nome'] ?? 'Admin'; ?></h5>
        </div>

        <!-- Menu centralizado verticalmente -->
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" ><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded active"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
        </div>

        <!-- Botão sair no rodapé -->
        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <div class="main-content flex-grow-1">
        <div class="page-header">
            <h1>Gerenciar Usuários</h1>
            
        </div>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        

        <button class="btn btn-acao mb-4" data-bs-toggle="modal" data-bs-target="#modalAddUsuario" onclick="resetForm()">
            <i class="fas fa-plus"></i> Cadastrar Novo Usuário (Prof/Aluno)
        </button>

        <!-- Barra de Pesquisa -->
        <div class="search-container">
            <form method="GET" action="" class="mb-0">
                <div class="search-input-group">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           name="pesquisa" 
                           class="form-control search-input" 
                           placeholder="Pesquisar por nome, email ou informações de usuários..." 
                           value="<?php echo htmlspecialchars($termo_pesquisa); ?>"
                           autocomplete="off">
                </div>
                
                <?php if (!empty($termo_pesquisa)): ?>
                    <div class="search-results-info mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Pesquisando por: <strong>"<?php echo htmlspecialchars($termo_pesquisa); ?>"</strong>
                        <span class="badge ms-2"><?php echo (count($professores) + count($alunos)); ?> usuário(s) encontrado(s)</span>
                          <?php if (!empty($termo_pesquisa)): ?>
                <a href="gerenciar_usuarios.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Limpar Pesquisa
                </a>
            <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <ul class="nav nav-tabs mb-4" id="usuarioTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="professores-tab" data-bs-toggle="tab" data-bs-target="#professores" type="button" role="tab" aria-controls="professores" aria-selected="false">
                    Professores <span class="badge bg-secondary ms-1"><?php echo count($professores); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="alunos-tab" data-bs-toggle="tab" data-bs-target="#alunos" type="button" role="tab" aria-controls="alunos" aria-selected="true">
                    Alunos <span class="badge bg-secondary ms-1"><?php echo count($alunos); ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="usuarioTabContent">
            <div class="tab-pane fade show active" id="professores" role="tabpanel" aria-labelledby="professores-tab">
                <h3>Professores <?php if (!empty($termo_pesquisa)): ?><small class="text-muted">(Resultados da pesquisa)</small><?php endif; ?></h3>
                
                <?php if (empty($professores) && !empty($termo_pesquisa)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h4 class="mt-3">Nenhum professor encontrado</h4>
                        <p class="text-muted">Não encontramos professores com o termo "<?php echo htmlspecialchars($termo_pesquisa); ?>"</p>
                    </div>
                <?php elseif (empty($professores)): ?>
                    <div class="alert alert-info">
                        Nenhum professor cadastrado.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Informações</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($professores as $professor): 
                                    // Destacar o termo de pesquisa nos resultados
                                    $nome_professor = htmlspecialchars($professor['nome']);
                                    $email_professor = htmlspecialchars($professor['email']);
                                    $informacoes_professor = htmlspecialchars($professor['informacoes'] ?: 'Sem informações adicionais');
                                    
                                    if (!empty($termo_pesquisa)) {
                                        $termo_highlight = preg_quote(htmlspecialchars($termo_pesquisa), '/');
                                        $nome_professor = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $nome_professor);
                                        $email_professor = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $email_professor);
                                        $informacoes_professor = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $informacoes_professor);
                                    }
                                ?>
                                <tr>
                                    <td><?= $nome_professor ?></td>
                                    <td><?= $email_professor ?></td>
                                    <td>
                                        <div class="informacoes-text">
                                            <?= $informacoes_professor ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="openEditUsuarioModal(<?= $professor['id'] ?>, '<?= htmlspecialchars($professor['nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($professor['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($professor['tipo_usuario'], ENT_QUOTES) ?>', '<?= htmlspecialchars($professor['informacoes'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>

                                        <button class="btn btn-sm btn-outline-dark me-2" 
                                                onclick="confirmToggle(<?= $professor['id'] ?>, '<?= htmlspecialchars($professor['nome'], ENT_QUOTES) ?>', '<?= $professor['status'] ?>')">
                                            <?php if ($professor['status'] == 'ativo'): ?>
                                                <i class="fas fa-user-slash"></i> Desativar
                                            <?php else: ?>
                                                <i class="fas fa-user-check"></i> Ativar
                                            <?php endif; ?>
                                        </button>

                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmRemove(<?= $professor['id'] ?>, '<?= htmlspecialchars($professor['nome'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash-alt"></i> Remover
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="alunos" role="tabpanel" aria-labelledby="alunos-tab">
                <h3>Alunos <?php if (!empty($termo_pesquisa)): ?><small class="text-muted">(Resultados da pesquisa)</small><?php endif; ?></h3>
                
                <?php if (empty($alunos) && !empty($termo_pesquisa)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h4 class="mt-3">Nenhum aluno encontrado</h4>
                        <p class="text-muted">Não encontramos alunos com o termo "<?php echo htmlspecialchars($termo_pesquisa); ?>"</p>
                    </div>
                <?php elseif (empty($alunos)): ?>
                    <div class="alert alert-info">
                        Nenhum aluno cadastrado.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Informações</th>
                                    <th>Turmas</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alunos as $aluno): 
                                    // Destacar o termo de pesquisa nos resultados
                                    $nome_aluno = htmlspecialchars($aluno['nome']);
                                    $email_aluno = htmlspecialchars($aluno['email']);
                                    $informacoes_aluno = htmlspecialchars($aluno['informacoes'] ?: 'Sem informações adicionais');
                                    $turmas_aluno = htmlspecialchars($aluno['turmas_associadas'] ?: 'Nenhuma');
                                    
                                    if (!empty($termo_pesquisa)) {
                                        $termo_highlight = preg_quote(htmlspecialchars($termo_pesquisa), '/');
                                        $nome_aluno = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $nome_aluno);
                                        $email_aluno = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $email_aluno);
                                        $informacoes_aluno = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $informacoes_aluno);
                                        $turmas_aluno = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $turmas_aluno);
                                    }
                                ?>
                                <tr>
                                    <td><?= $nome_aluno ?></td>
                                    <td><?= $email_aluno ?></td>
                                    <td>
                                        <div class="informacoes-text">
                                            <?= $informacoes_aluno ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $turmas_aluno ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="openEditUsuarioModal(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($aluno['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($aluno['tipo_usuario'], ENT_QUOTES) ?>', '<?= htmlspecialchars($aluno['informacoes'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>

                                        <button class="btn btn-sm btn-outline-dark me-2" 
                                                onclick="confirmToggle(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome'], ENT_QUOTES) ?>', '<?= $aluno['status'] ?>')">
                                            <?php if ($aluno['status'] == 'ativo'): ?>
                                                <i class="fas fa-user-slash"></i> Desativar
                                            <?php else: ?>
                                                <i class="fas fa-user-check"></i> Ativar
                                            <?php endif; ?>
                                        </button>

                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmRemove(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash-alt"></i> Remover
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

<div class="modal fade" id="modalAddUsuario" tabindex="-1" aria-labelledby="modalAddUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddUsuarioLabel">Cadastrar Novo Usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="gerenciar_usuarios.php">
        <div class="modal-body">
            <input type="hidden" name="acao" id="usuario_acao" value="add_usuario">
            <input type="hidden" name="usuario_id" id="usuario_id">
            
            <div class="mb-3">
                <label for="tipo_usuario" class="form-label">Tipo de Usuário</label>
                <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                    <option value="">Selecione o Tipo</option>
                    <option value="professor">Professor</option>
                    <option value="aluno">Aluno</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="informacoes" class="form-label">Informações Adicionais</label>
                <textarea class="form-control form-textarea" id="informacoes" name="informacoes" rows="3" placeholder="Adicione informações relevantes sobre o usuário (ex: nível de inglês, observações, etc.)"></textarea>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label" id="label_senha">Senha (Obrigatória para novo. Deixe vazio para manter a atual)</label>
                <input type="password" class="form-control" id="senha" name="senha">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-acao" id="btn_salvar_usuario">Salvar Usuário</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="formRemover" method="POST" action="gerenciar_usuarios.php">
    <input type="hidden" name="acao" value="remover_usuario">
    <input type="hidden" name="id_usuario" id="remover_id_usuario">
</form>

<form id="formToggleStatus" method="POST" action="gerenciar_usuarios.php">
    <input type="hidden" name="acao" value="toggle_status">
    <input type="hidden" name="id_usuario_toggle" id="id_usuario_toggle">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function resetForm() {
        document.getElementById('modalAddUsuarioLabel').innerText = 'Cadastrar Novo Usuário';
        document.getElementById('usuario_acao').value = 'add_usuario';
        document.getElementById('usuario_id').value = '';
        document.getElementById('nome').value = '';
        document.getElementById('email').value = '';
        document.getElementById('tipo_usuario').value = ''; 
        document.getElementById('informacoes').value = '';
        document.getElementById('senha').value = '';
        document.getElementById('label_senha').innerText = 'Senha (Obrigatória para novo)';
        document.getElementById('btn_salvar_usuario').innerText = 'Salvar Usuário';
        document.getElementById('email').disabled = false;
        
        document.getElementById('senha').removeAttribute('required');
    }

    function openEditUsuarioModal(id, nome, email, tipo, informacoes) {
        document.getElementById('modalAddUsuarioLabel').innerText = `Editar Usuário: ${nome}`;
        document.getElementById('usuario_acao').value = 'editar_usuario';
        document.getElementById('usuario_id').value = id;
        document.getElementById('nome').value = nome;
        document.getElementById('email').value = email;
        document.getElementById('tipo_usuario').value = tipo;
        document.getElementById('informacoes').value = informacoes || '';
        document.getElementById('senha').value = '';
        document.getElementById('label_senha').innerText = 'Nova Senha (Deixe vazio para manter a atual)';
        document.getElementById('btn_salvar_usuario').innerText = 'Atualizar Usuário';
        document.getElementById('email').disabled = false;
        
        var myModal = new bootstrap.Modal(document.getElementById('modalAddUsuario'));
        myModal.show();
    }
    
    function confirmRemove(id, nome) {
        if (confirm(`Tem certeza que deseja remover o usuário "${nome}"? Esta ação é irreversível e removerá todas as associações (turmas/aulas).`)) {
            document.getElementById('remover_id_usuario').value = id;
            document.getElementById('formRemover').submit();
        }
    }
    
    function confirmToggle(id, nome, status) {
        var acaoText = (status === 'ativo') ? 'desativar' : 'ativar';
        if (confirm(`Deseja realmente ${acaoText} o usuário "${nome}"?`)) {
            document.getElementById('id_usuario_toggle').value = id;
            document.getElementById('formToggleStatus').submit();
        }
    }
    
    // Auto-focus na barra de pesquisa se houver um termo de pesquisa
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="pesquisa"]');
        if (searchInput && searchInput.value) {
            searchInput.focus();
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }
    });
</script>
</body>
</html>