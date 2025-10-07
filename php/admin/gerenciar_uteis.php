<?php
    session_start();
    // Inclui a conexão com o banco de dados e o cabeçalho/navbar do admin
    // Assume que a estrutura é: php/admin/ -> ../../includes/conexao.php
    include_once '../includes/conexao.php';
    

    // Verifica se a conexão PDO ($pdo) foi estabelecida
    if (!isset($pdo)) {
        // Se $pdo não existe, a conexão falhou. Exibe erro e interrompe.
        die('<div class="alert alert-danger">Erro de Conexão: Variável PDO não definida. Verifique seu arquivo `conexao.php`.</div>');
    }

    // ===================================
    // 1. Lógica de Processamento CRUD
    // ===================================

    $mensagem_status = '';
    $erro = false;

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
                $mensagem_status = "Recurso '{$titulo}' adicionado com sucesso!";

            } elseif ($action === 'editar') {
                if (empty($titulo) || empty($link) || empty($resource_id)) {
                    throw new Exception("Título, Link e ID são obrigatórios para edição.");
                }

                $stmt = $pdo->prepare("UPDATE recursos_uteis SET titulo = ?, link = ?, descricao = ? WHERE id = ?");
                $stmt->execute([$titulo, $link, $descricao, $resource_id]);
                $mensagem_status = "Recurso '{$titulo}' atualizado com sucesso!";

            } elseif ($action === 'apagar') {
                if (empty($resource_id)) {
                    throw new Exception("ID do recurso é obrigatório para exclusão.");
                }

                $stmt = $pdo->prepare("DELETE FROM recursos_uteis WHERE id = ?");
                $stmt->execute([$resource_id]);
                $mensagem_status = "Recurso excluído com sucesso!";
            }
            // Redireciona para evitar re-submit do formulário
            header("Location: gerenciar_uteis.php?status=" . urlencode($mensagem_status) . "&erro=" . ($erro ? '1' : '0'));
            exit();

        } catch (Exception $e) {
            $erro = true;
            $mensagem_status = "Erro: " . $e->getMessage();
        }
    }

    // Lógica para exibir mensagens após o redirecionamento
    if (isset($_GET['status'])) {
        $mensagem_status = urldecode($_GET['status']);
        $erro = $_GET['erro'] === '1';
    }


    // ===================================
    // 2. Lógica de Busca (Leitura)
    // ===================================

    $recursos = [];
    $erro_sql = '';
    try {
        // Assume que a tabela 'recursos_uteis' já foi criada
        $stmt_recursos = $pdo->query("SELECT id, titulo, link, descricao, data_criacao FROM recursos_uteis ORDER BY data_criacao DESC");
        $recursos = $stmt_recursos->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro_sql = "Erro ao carregar recursos do banco de dados. Verifique se a tabela 'recursos_uteis' existe. Erro: " . $e->getMessage();
    }
?>
    <!-- Adiciona os estilos específicos desta página -->
     <!DOCTYPE html>
     <html lang="en">
     <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gerenciar Úteis</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link rel="stylesheet" href="../../css/admin/gerenciar_uteis.css">
     </head>
     <body>



     

    <div class="page-header">
        <h1 class="page-title">Gerenciar Recomendações Úteis (Úteis)</h1>
        <button class="btn-add-resource" onclick="openModal('add')">
            <i class="fas fa-plus-circle"></i> Adicionar Novo Recurso
        </button>
    </div>

    <?php if ($mensagem_status): ?>
        <div class="alert <?php echo $erro ? 'alert-danger' : 'alert-success'; ?>">
            <?php echo htmlspecialchars($mensagem_status); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erro_sql)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro_sql); ?></div>
    <?php endif; ?>

    <!-- Tabela de Recursos Existentes -->
    <div class="card card-full">
        <div class="card-header dark-header">
            <h2>Biblioteca de Recursos (<?php echo count($recursos); ?> Total)</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recursos)): ?>
                <p class="text-center" style="padding: 20px;">Nenhuma recomendação útil cadastrada ainda.</p>
            <?php else: ?>
                <table class="resource-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Descrição</th>
                            <th>Link</th>
                            <th>Data Criação</th>
                            <th style="width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recursos as $recurso): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($recurso['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($recurso['descricao']); ?></td>
                            <td>
                                <!-- Target _blank abre em nova aba -->
                                <a href="<?php echo htmlspecialchars($recurso['link']); ?>" target="_blank" class="action-link">
                                    <i class="fas fa-external-link-alt"></i> Acessar
                                </a>
                            </td>
                            <td><?php echo (new DateTime($recurso['data_criacao']))->format('d/m/Y'); ?></td>
                            <td class="action-buttons">
                                <!-- Passa os dados do recurso como JSON para a função JS -->
                                <button class="btn-edit" onclick='openModal("edit", <?php echo json_encode($recurso); ?>)'>
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn-delete" onclick="confirmDelete(<?php echo $recurso['id']; ?>)">
                                    <i class="fas fa-trash-alt"></i> Apagar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para Adicionar/Editar Recurso -->
    <div id="resourceModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Adicionar Novo Recurso</h3>
            <form id="resourceForm" method="POST" action="gerenciar_uteis.php">
                <input type="hidden" name="action" id="actionType" value="adicionar">
                <input type="hidden" name="resource_id" id="resourceId">

                <div class="form-group">
                    <label for="titulo">Título do Recurso (Obrigatório)</label>
                    <input type="text" id="titulo" name="titulo" required>
                </div>

                <div class="form-group">
                    <label for="link">Link (URL Completa, Obrigatório)</label>
                    <input type="text" id="link" name="link" required placeholder="Ex: https://www.linguee.com.br/">
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição Breve (Opcional)</label>
                    <textarea id="descricao" name="descricao" rows="3"></textarea>
                </div>

                <button type="submit" class="btn-primary" id="submitButton">Adicionar Recurso</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('resourceModal');
        const modalTitle = document.getElementById('modalTitle');
        const form = document.getElementById('resourceForm');
        const actionType = document.getElementById('actionType');
        const resourceId = document.getElementById('resourceId');
        const tituloInput = document.getElementById('titulo');
        const linkInput = document.getElementById('link');
        const descricaoInput = document.getElementById('descricao');
        const submitButton = document.getElementById('submitButton');

        function openModal(mode, data = {}) {
            // Limpa o formulário e reset o ID antes de abrir
            form.reset();
            resourceId.value = '';

            if (mode === 'add') {
                modalTitle.textContent = 'Adicionar Novo Recurso';
                actionType.value = 'adicionar';
                submitButton.textContent = 'Adicionar Recurso';
            } else if (mode === 'edit') {
                modalTitle.textContent = 'Editar Recurso: ' + data.titulo;
                actionType.value = 'editar';
                submitButton.textContent = 'Salvar Alterações';
                resourceId.value = data.id;
                tituloInput.value = data.titulo;
                linkInput.value = data.link;
                descricaoInput.value = data.descricao;
            }
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Funções para lidar com o fechamento do modal ao clicar fora
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        function confirmDelete(id) {
            // Usa window.confirm() para a exclusão
            if (window.confirm("Tem certeza que deseja apagar este recurso útil? Esta ação é irreversível.")) {
                const formDelete = document.createElement('form');
                formDelete.method = 'POST';
                formDelete.action = 'gerenciar_uteis.php';

                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'resource_id';
                inputId.value = id;

                const inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'apagar';

                formDelete.appendChild(inputId);
                formDelete.appendChild(inputAction);
                document.body.appendChild(formDelete);
                formDelete.submit();
            }
        }
    </script>

 </body>
     </html>