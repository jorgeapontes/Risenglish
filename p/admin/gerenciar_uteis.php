<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$nome_usuario = $_SESSION['user_nome'];

if (!isset($pdo)) {
    die('<div class="alert alert-danger">Erro de Conexão: Variável PDO não definida. Verifique seu arquivo `conexao.php`.</div>');
}

// ===================================
// 1. Lógica de Processamento CRUD
// ===================================

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $titulo = trim($_POST['titulo'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $resource_id = $_POST['resource_id'] ?? null;

    // Sanitização básica: tenta garantir que o link comece com http/https
    if (!empty($link) && !preg_match('#^https?://#i', $link)) {
         $link = "http://" . $link;
    }

    try {
        if ($action === 'adicionar') {
            if (empty($titulo) || empty($link)) {
                throw new Exception("Título e Link são obrigatórios.");
            }

            $stmt = $pdo->prepare("INSERT INTO recursos_uteis (titulo, link, descricao) VALUES (?, ?, ?)");
            $stmt->execute([$titulo, $link, $descricao]);
            $mensagem = "Recurso <strong>{$titulo}</strong> adicionado com sucesso!";
            $tipo_mensagem = 'success';

        } elseif ($action === 'editar') {
            if (empty($titulo) || empty($link) || empty($resource_id)) {
                throw new Exception("Título, Link e ID são obrigatórios para edição.");
            }

            $stmt = $pdo->prepare("UPDATE recursos_uteis SET titulo = ?, link = ?, descricao = ? WHERE id = ?");
            $stmt->execute([$titulo, $link, $descricao, $resource_id]);
            $mensagem = "Recurso <strong>{$titulo}</strong> atualizado com sucesso!";
            $tipo_mensagem = 'success';

        } elseif ($action === 'apagar') {
            if (empty($resource_id)) {
                throw new Exception("ID do recurso é obrigatório para exclusão.");
            }

            $stmt = $pdo->prepare("DELETE FROM recursos_uteis WHERE id = ?");
            $stmt->execute([$resource_id]);
            $mensagem = "Recurso excluído com sucesso!";
            $tipo_mensagem = 'success';
        }

    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// ===================================
// 2. Lógica de Busca (Leitura)
// ===================================

$recursos = [];
$erro_sql = '';
try {
    $stmt_recursos = $pdo->query("SELECT id, titulo, link, descricao, data_criacao FROM recursos_uteis ORDER BY data_criacao DESC");
    $recursos = $stmt_recursos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro_sql = "Erro ao carregar recursos do banco de dados. Verifique se a tabela 'recursos_uteis' existe. Erro: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Recomendações - Admin Risenglish</title>
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

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
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
            margin-left: 280px;
            padding: 30px;
            background-color: white;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 16.666667%; /* Compensa a largura da sidebar fixa */
            width: 83.333333%;
            animation: fadeIn 0.5s ease;
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

        .btn-outline-success {
            border-color: #28a745;
            color: #28a745;
        }

        .btn-outline-success:hover {
            background-color: #28a745;
            border-color: #28a745;
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

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
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
    </style>
</head>
<body>

<div class="d-flex">
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <!-- Nome do professor -->
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo $_SESSION['user_nome'] ?? 'Professor'; ?></h5>
        </div>

        <!-- Menu centralizado verticalmente -->
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" ><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="personalizar_index.php" class="rounded"><i class="fas fa-paint-brush"></i>&nbsp;&nbsp;Personalizar Site</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded active"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
        </div>

        <!-- Botão sair no rodapé -->
        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4">Gerenciar Recomendações Úteis</h1>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro_sql)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro_sql) ?></div>
        <?php endif; ?>

        <button class="btn btn-acao mb-4" data-bs-toggle="modal" data-bs-target="#modalAddRecurso" onclick="resetForm()">
            <i class="fas fa-plus"></i> Adicionar Novo Recurso
        </button>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Título</th>
                        <th>Descrição</th>
                        <th>Link</th>
                        <th>Criado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recursos)): ?>
                        <tr><td colspan="5" class="text-center">Nenhuma recomendação útil cadastrada ainda.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recursos as $recurso): ?>
                        <tr>
                            <td><?= htmlspecialchars($recurso['titulo']) ?></td>
                            <td><?= htmlspecialchars($recurso['descricao']) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($recurso['link']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-eye"></i> Abrir
                                </a>
                            </td>
                            <td><?= (new DateTime($recurso['data_criacao']))->format('d/m/Y') ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-2" 
                                        onclick="openEditRecursoModal(<?= $recurso['id'] ?>, '<?= htmlspecialchars($recurso['titulo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($recurso['link'], ENT_QUOTES) ?>', '<?= htmlspecialchars($recurso['descricao'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmDelete(<?= $recurso['id'] ?>, '<?= htmlspecialchars($recurso['titulo'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash-alt"></i> Excluir
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar Recurso -->
<div class="modal fade" id="modalAddRecurso" tabindex="-1" aria-labelledby="modalAddRecursoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddRecursoLabel">Adicionar Novo Recurso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="gerenciar_uteis.php">
        <div class="modal-body">
            <input type="hidden" name="action" id="recurso_acao" value="adicionar">
            <input type="hidden" name="resource_id" id="resource_id">
            
            <div class="mb-3">
                <label for="titulo" class="form-label">Título do Recurso</label>
                <input type="text" class="form-control" id="titulo" name="titulo" required>
            </div>
            
            <div class="mb-3">
                <label for="link" class="form-label">Link (URL)</label>
                <input type="text" class="form-control" id="link" name="link" required placeholder="Ex: https://www.linguee.com.br/">
            </div>
            
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-acao" id="btn_salvar_recurso">Adicionar Recurso</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Formulário oculto para exclusão -->
<form id="formDelete" method="POST" action="gerenciar_uteis.php">
    <input type="hidden" name="action" value="apagar">
    <input type="hidden" name="resource_id" id="delete_resource_id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function resetForm() {
        document.getElementById('modalAddRecursoLabel').innerText = 'Adicionar Novo Recurso';
        document.getElementById('recurso_acao').value = 'adicionar';
        document.getElementById('resource_id').value = '';
        document.getElementById('titulo').value = '';
        document.getElementById('link').value = '';
        document.getElementById('descricao').value = '';
        document.getElementById('btn_salvar_recurso').innerText = 'Adicionar Recurso';
    }

    function openEditRecursoModal(id, titulo, link, descricao) {
        document.getElementById('modalAddRecursoLabel').innerText = `Editar Recurso: ${titulo}`;
        document.getElementById('recurso_acao').value = 'editar';
        document.getElementById('resource_id').value = id;
        document.getElementById('titulo').value = titulo;
        document.getElementById('link').value = link;
        document.getElementById('descricao').value = descricao;
        document.getElementById('btn_salvar_recurso').innerText = 'Salvar Alterações';
        
        var myModal = new bootstrap.Modal(document.getElementById('modalAddRecurso'));
        myModal.show();
    }
    
    function confirmDelete(id, titulo) {
        if (confirm(`Tem certeza que deseja excluir o recurso "${titulo}"? Esta ação é irreversível.`)) {
            document.getElementById('delete_resource_id').value = id;
            document.getElementById('formDelete').submit();
        }
    }
</script>
</body>
</html>