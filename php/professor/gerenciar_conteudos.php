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

// --- 1. LÓGICA DE UPLOAD/INSERÇÃO DE CONTEÚDO (CREATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    
    // Configuração do Upload
    $diretorio_upload = '../uploads/conteudos/';
    if (!is_dir($diretorio_upload)) {
        mkdir($diretorio_upload, 0777, true);
    }

    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == UPLOAD_ERR_OK) {
        $nome_original = $_FILES['arquivo']['name'];
        $extensao = pathinfo($nome_original, PATHINFO_EXTENSION);
        $tipo_arquivo = $_FILES['arquivo']['type'];
        
        // Gera um nome único para evitar colisões
        $nome_arquivo_salvo = uniqid('cont_', true) . '.' . $extensao;
        $caminho_completo = $diretorio_upload . $nome_arquivo_salvo;

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_completo)) {
            
            // Verifica o tipo_arquivo (varchar(50))
            $tipo_final = substr($tipo_arquivo, 0, 49); 

            $sql_insert = "INSERT INTO conteudos (professor_id, titulo, descricao, tipo_arquivo, caminho_arquivo) 
                           VALUES (:professor_id, :titulo, :descricao, :tipo_arquivo, :caminho_arquivo)";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            if ($stmt_insert->execute([
                ':professor_id' => $professor_id,
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':tipo_arquivo' => $extensao, // Usando a extensão no VARCHAR(50) para simplicidade
                ':caminho_arquivo' => $caminho_completo
            ])) {
                $mensagem = "Conteúdo '{$titulo}' enviado com sucesso!";
                $sucesso = true;
            } else {
                $mensagem = "Erro ao registrar o conteúdo no banco de dados.";
            }
        } else {
            $mensagem = "Erro ao mover o arquivo para o diretório de uploads.";
        }
    } else {
        $mensagem = "Nenhum arquivo enviado ou erro no upload. Verifique o tamanho do arquivo.";
    }
}

// --- 2. LÓGICA DE EXCLUSÃO DE CONTEÚDO (DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $conteudo_id = $_POST['conteudo_id'];

    try {
        // 1. Puxa o caminho do arquivo para deletá-lo do servidor
        $sql_select = "SELECT caminho_arquivo FROM conteudos WHERE id = :id AND professor_id = :professor_id";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->execute([':id' => $conteudo_id, ':professor_id' => $professor_id]);
        $conteudo = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if ($conteudo) {
            // 2. Deleta o registro do banco de dados (que também deleta as associações em aulas_conteudos)
            $sql_delete = "DELETE FROM conteudos WHERE id = :id AND professor_id = :professor_id";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute([':id' => $conteudo_id, ':professor_id' => $professor_id]);

            // 3. Deleta o arquivo físico
            if (file_exists($conteudo['caminho_arquivo'])) {
                unlink($conteudo['caminho_arquivo']);
            }
            $mensagem = "Conteúdo excluído com sucesso!";
            $sucesso = true;
        } else {
            $mensagem = "Conteúdo não encontrado ou você não tem permissão para excluí-lo.";
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir o conteúdo: " . $e->getMessage();
    }
}

// --- 3. CONSULTA PARA LISTAR CONTEÚDOS (READ) ---
$sql_conteudos = "SELECT id, titulo, descricao, tipo_arquivo, data_upload FROM conteudos WHERE professor_id = :professor_id ORDER BY data_upload DESC";
$stmt_conteudos = $pdo->prepare($sql_conteudos);
$stmt_conteudos->bindParam(':professor_id', $professor_id);
$stmt_conteudos->execute();
$lista_conteudos = $stmt_conteudos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Conteúdos - Professor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Paleta de Cores: Creme, Vermelho Escuro, Marinho Escuro */
        :root {
            --cor-primaria: #0A1931; /* Marinho Escuro */
            --cor-secundaria: #B91D23; /* Vermelho Escuro */
            --cor-fundo: #F5F5DC; /* Creme/Bege */
        }
        body { background-color: var(--cor-fundo); }
        .sidebar { background-color: var(--cor-primaria); color: white; min-height: 100vh; }
        .sidebar a { color: white; padding: 15px; text-decoration: none; display: block; }
        .sidebar a:hover { background-color: var(--cor-secundaria); }
        .main-content { padding: 30px; }
        .card-header-custom {
            background-color: var(--cor-primaria);
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">RISENGLISH PROFESSOR</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard (Agenda)</a>
        <a href="gerenciar_aulas.php"><i class="fas fa-calendar-alt me-2"></i> Agendar/Gerenciar Aulas</a>
        <a href="gerenciar_conteudos.php" style="background-color: #92171B;"><i class="fas fa-book-open me-2"></i> **Conteúdos (Biblioteca)**</a>
        <a href="gerenciar_alunos.php"><i class="fas fa-users me-2"></i> Alunos/Turmas</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Biblioteca de Conteúdos</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <button class="btn text-white mb-3" data-bs-toggle="modal" data-bs-target="#modalAdicionarConteudo" style="background-color: var(--cor-secundaria);">
            <i class="fas fa-upload me-2"></i> Adicionar Novo Conteúdo
        </button>

        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                Lista de Conteúdos Disponíveis
            </div>
            <div class="card-body p-0">
                <?php if (empty($lista_conteudos)): ?>
                    <p class="p-4 text-center text-muted">Nenhum conteúdo foi carregado ainda.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Data Upload</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_conteudos as $conteudo): ?>
                                    <tr>
                                        <td class="align-middle"><strong><?= htmlspecialchars($conteudo['titulo']) ?></strong></td>
                                        <td class="align-middle">
                                            <span class="badge bg-secondary"><?= strtoupper(htmlspecialchars($conteudo['tipo_arquivo'])) ?></span>
                                        </td>
                                        <td class="align-middle"><?= nl2br(htmlspecialchars($conteudo['descricao'])) ?></td>
                                        <td class="align-middle"><?= date('d/m/Y H:i', strtotime($conteudo['data_upload'])) ?></td>
                                        <td class="align-middle">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir o conteúdo \'<?= htmlspecialchars($conteudo['titulo']) ?>\'? Esta ação é irreversível.');">
                                                <input type="hidden" name="acao" value="excluir">
                                                <input type="hidden" name="conteudo_id" value="<?= $conteudo['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                            
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

<div class="modal fade" id="modalAdicionarConteudo" tabindex="-1" aria-labelledby="modalAdicionarConteudoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: var(--cor-primaria); color: white;">
                <h5 class="modal-title" id="modalAdicionarConteudoLabel">Adicionar Novo Conteúdo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título do Conteúdo</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="arquivo" class="form-label">Selecionar Arquivo (Word, PDF, PPT, etc.)</label>
                        <input type="file" class="form-control" id="arquivo" name="arquivo" required>
                        <div class="form-text">Tipos suportados: DOCX, PDF, PPTX e outros. O tamanho máximo permitido depende da sua configuração do PHP/servidor.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn text-white" style="background-color: var(--cor-secundaria);">
                        <i class="fas fa-upload me-2"></i> Fazer Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>