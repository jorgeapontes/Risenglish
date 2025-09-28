<?php
session_start();
require_once '../includes/conexao.php';

// Checar autenticação e permissão
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$mensagem = '';
$tipo_mensagem = '';
$turma_id = $_GET['turma_id'] ?? null;

// Garante que um ID de turma válido foi fornecido
if (empty($turma_id) || !is_numeric($turma_id)) {
    header("Location: dashboard.php");
    exit;
}

// 1. Verifica se o professor é responsável por esta turma
$sql_check = "SELECT nome_turma FROM turmas WHERE id = :turma_id AND professor_id = :professor_id";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->bindParam(':turma_id', $turma_id);
$stmt_check->bindParam(':professor_id', $professor_id);
$stmt_check->execute();
$turma_info = $stmt_check->fetch(PDO::FETCH_ASSOC);

if (!$turma_info) {
    header("Location: dashboard.php");
    exit;
}

$nome_turma = $turma_info['nome_turma'];

// --- LÓGICA DE UPLOAD DE CONTEÚDO ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'upload_conteudo') {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'] ?? '';
    $tipo = $_POST['tipo_conteudo']; // 'arquivo' ou 'link'
    $caminho_arquivo = '';
    $tipo_arquivo_db = ''; 

    try {
        if ($tipo == 'link') {
            $caminho_arquivo = $_POST['link_url'];
            if (empty($caminho_arquivo)) throw new Exception("O campo URL do link não pode ser vazio.");
            $tipo_arquivo_db = 'link';

        } elseif ($tipo == 'arquivo') {
            if (!isset($_FILES['arquivo_upload']) || $_FILES['arquivo_upload']['error'] != UPLOAD_ERR_OK) {
                throw new Exception("Erro no upload do arquivo. Verifique o tamanho e permissões.");
            }

            $arquivo = $_FILES['arquivo_upload'];
            $nome_base = pathinfo($arquivo['name'], PATHINFO_FILENAME);
            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            $tipo_arquivo_db = $extensao;

            $extensoes_permitidas = ['doc', 'docx', 'ppt', 'pptx', 'pdf', 'xls', 'xlsx'];
            if (!in_array($extensao, $extensoes_permitidas)) {
                throw new Exception("Tipo de arquivo não permitido. Apenas Word, PPT, PDF, Excel, etc.");
            }

            // Garante que a pasta uploads exista.
            $pasta_destino = '../uploads/';
            if (!is_dir($pasta_destino)) {
                mkdir($pasta_destino, 0777, true);
            }

            $novo_nome = time() . "_" . md5($nome_base) . "." . $extensao;
            $destino = $pasta_destino . $novo_nome;

            if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                throw new Exception("Falha ao mover o arquivo para o destino.");
            }
            $caminho_arquivo = $destino; // Salva o caminho relativo no BD
        }

        // Insere o conteúdo no banco de dados
        $sql = "INSERT INTO conteudos (titulo, descricao, tipo_arquivo, caminho_arquivo) VALUES (:titulo, :descricao, :tipo_arquivo, :caminho_arquivo)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':tipo_arquivo', $tipo_arquivo_db);
        $stmt->bindParam(':caminho_arquivo', $caminho_arquivo);
        $stmt->execute();
        
        $mensagem = "Conteúdo **{$titulo}** enviado/cadastrado com sucesso!";
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $mensagem = "Erro no Upload/Cadastro: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA DE CRIAÇÃO/EDIÇÃO DE AULA ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && ($_POST['acao'] == 'add_aula' || $_POST['acao'] == 'editar_aula')) {
    $titulo_aula = $_POST['titulo_aula'];
    $data_aula = $_POST['data_aula'];
    $descricao_aula = $_POST['descricao_aula'] ?? '';
    $aula_id = $_POST['aula_id'] ?? null;
    $acao = $_POST['acao'];
    
    try {
        if ($acao == 'add_aula') {
            $sql = "INSERT INTO aulas (titulo_aula, descricao, data_aula, turma_id) VALUES (:titulo_aula, :descricao, :data_aula, :turma_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':turma_id', $turma_id);
            $mensagem = "Aula **{$titulo_aula}** agendada com sucesso!";
        } else { // editar_aula
            $sql = "UPDATE aulas SET titulo_aula = :titulo_aula, descricao = :descricao, data_aula = :data_aula WHERE id = :aula_id AND turma_id = :turma_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':aula_id', $aula_id);
            $stmt->bindParam(':turma_id', $turma_id);
            $mensagem = "Aula **{$titulo_aula}** atualizada com sucesso!";
        }
        
        $stmt->bindParam(':titulo_aula', $titulo_aula);
        $stmt->bindParam(':descricao', $descricao_aula);
        $stmt->bindParam(':data_aula', $data_aula);
        $stmt->execute();
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $mensagem = "Erro ao gerenciar aula: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA DE ASSOCIAÇÃO CONTEÚDO-AULA ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'associar_conteudo_aula') {
    $aula_id = $_POST['aula_id_associacao'];
    $conteudos_selecionados = $_POST['conteudos_selecionados'] ?? [];

    try {
        $pdo->beginTransaction();

        // 1. Remove todas as associações atuais da aula
        $sql_delete = "DELETE FROM aulas_conteudos WHERE aula_id = :aula_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->bindParam(':aula_id', $aula_id);
        $stmt_delete->execute();

        // 2. Insere as novas associações
        if (!empty($conteudos_selecionados)) {
            $sql_insert = "INSERT INTO aulas_conteudos (aula_id, conteudo_id) VALUES (:aula_id, :conteudo_id)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->bindParam(':aula_id', $aula_id);
            
            foreach ($conteudos_selecionados as $conteudo_id) {
                $stmt_insert->bindParam(':conteudo_id', $conteudo_id);
                $stmt_insert->execute();
            }
        }
        
        $pdo->commit();
        $mensagem = "Conteúdos da aula atualizados com sucesso!";
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao associar conteúdos à aula: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA DE REMOÇÃO DE AULA (NOVO) ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'remover_aula') {
    $id_aula = $_POST['id_aula'];

    try {
        // Verifica se a aula pertence a este professor (para segurança)
        $sql_check = "SELECT id FROM aulas WHERE id = :id_aula AND turma_id = :turma_id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':id_aula', $id_aula);
        $stmt_check->bindParam(':turma_id', $turma_id);
        $stmt_check->execute();
        if ($stmt_check->rowCount() == 0) throw new Exception("Aula não encontrada ou você não tem permissão.");
        
        $sql = "DELETE FROM aulas WHERE id = :id_aula";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_aula', $id_aula);
        $stmt->execute();
        
        // As associações são limpas automaticamente devido ao ON DELETE CASCADE na FK
        
        $mensagem = "Aula removida com sucesso!";
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = "Erro ao remover aula: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// --- LÓGICA DE REMOÇÃO DE CONTEÚDO (NOVO) ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'remover_conteudo') {
    $id_conteudo = $_POST['id_conteudo'];

    try {
        // 1. Busca o caminho do arquivo antes de deletar o registro
        $sql_select = "SELECT tipo_arquivo, caminho_arquivo FROM conteudos WHERE id = :id_conteudo";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->bindParam(':id_conteudo', $id_conteudo);
        $stmt_select->execute();
        $conteudo = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if (!$conteudo) throw new Exception("Conteúdo não encontrado.");
        
        $pdo->beginTransaction();

        // 2. Apaga o registro do banco
        $sql_delete = "DELETE FROM conteudos WHERE id = :id_conteudo";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->bindParam(':id_conteudo', $id_conteudo);
        $stmt_delete->execute();
        
        // 3. Se for um arquivo físico (não link), apaga o arquivo
        if ($conteudo['tipo_arquivo'] !== 'link' && file_exists($conteudo['caminho_arquivo'])) {
             // Usa unlink para apagar o arquivo do sistema
             if (!unlink($conteudo['caminho_arquivo'])) {
                 throw new Exception("O registro foi excluído, mas houve falha ao apagar o arquivo físico.");
             }
        }
        
        $pdo->commit();
        $mensagem = "Conteúdo **{$conteudo['titulo']}** removido com sucesso!";
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao remover conteúdo: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}


// --- Consultas para Listagem ---

// 1. Listar Aulas da Turma (INCLUINDO CONTEÚDOS ASSOCIADOS)
$sql_aulas = "SELECT a.*, GROUP_CONCAT(ac.conteudo_id) AS conteudos_ids, GROUP_CONCAT(c.titulo SEPARATOR ' | ') AS conteudos_nomes
              FROM aulas a
              LEFT JOIN aulas_conteudos ac ON a.id = ac.aula_id
              LEFT JOIN conteudos c ON ac.conteudo_id = c.id
              WHERE a.turma_id = :turma_id
              GROUP BY a.id
              ORDER BY a.data_aula DESC";
$stmt_aulas = $pdo->prepare($sql_aulas);
$stmt_aulas->bindParam(':turma_id', $turma_id);
$stmt_aulas->execute();
$aulas = $stmt_aulas->fetchAll(PDO::FETCH_ASSOC);

// 2. Listar todos os Conteúdos (para o modal de associação e a aba de Uploads)
$sql_conteudos = "SELECT id, titulo, descricao, tipo_arquivo, caminho_arquivo FROM conteudos ORDER BY data_upload DESC";
$todos_conteudos = $pdo->query($sql_conteudos)->fetchAll(PDO::FETCH_ASSOC);

// 3. Listar Alunos da Turma
$sql_alunos = "SELECT u.nome, u.email
               FROM usuarios u
               JOIN alunos_turmas at ON u.id = at.aluno_id
               WHERE at.turma_id = :turma_id
               ORDER BY u.nome";
$stmt_alunos = $pdo->prepare($sql_alunos);
$stmt_alunos->bindParam(':turma_id', $turma_id);
$stmt_alunos->execute();
$alunos_turma = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Aulas - <?= htmlspecialchars($nome_turma) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --cor-primaria: #0A1931; 
            --cor-secundaria: #B91D23; 
            --cor-fundo: #F5F5DC; 
        }
        body { background-color: var(--cor-fundo); }
        .sidebar { background-color: var(--cor-primaria); color: white; min-height: 100vh; }
        .sidebar a { color: white; padding: 15px; text-decoration: none; display: block; }
        .sidebar a:hover { background-color: var(--cor-secundaria); }
        .main-content { padding: 30px; }
        .btn-acao { background-color: var(--cor-secundaria); border-color: var(--cor-secundaria); color: white; }
        .btn-acao:hover { background-color: #92171B; border-color: #92171B; color: white; }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">PROFESSOR RISENGLISH</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
        <a href="gerenciar_aulas.php" style="background-color: #92171B;"><i class="fas fa-book-open me-2"></i> Gerenciar Aulas/Conteúdos</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Gerenciando: <?= htmlspecialchars($nome_turma) ?></h1>
        <p><a href="dashboard.php" style="color: var(--cor-secundaria); text-decoration: none;"><i class="fas fa-arrow-left me-2"></i> Voltar para Minhas Turmas</a></p>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4" id="professorTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="aulas-tab" data-bs-toggle="tab" data-bs-target="#aulas" type="button" role="tab" aria-controls="aulas" aria-selected="true" style="color: var(--cor-primaria);">Aulas</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab" aria-controls="upload" aria-selected="false" style="color: var(--cor-primaria);">Uploads/Conteúdos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="alunos-tab" data-bs-toggle="tab" data-bs-target="#alunos" type="button" role="tab" aria-controls="alunos" aria-selected="false" style="color: var(--cor-primaria);">Alunos da Turma</button>
            </li>
        </ul>

        <div class="tab-content" id="professorTabContent">
            
            <div class="tab-pane fade show active" id="aulas" role="tabpanel" aria-labelledby="aulas-tab">
                <button class="btn btn-sm btn-acao mb-3" data-bs-toggle="modal" data-bs-target="#modalAddAula">
                    <i class="fas fa-plus"></i> Agendar Nova Aula
                </button>
                
                <h4 style="color: var(--cor-primaria);">Próximas Aulas</h4>
                <?php if (empty($aulas)): ?>
                    <div class="alert alert-warning mt-3">Nenhuma aula agendada para esta turma.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($aulas as $aula): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title" style="color: var(--cor-secundaria);"><?= htmlspecialchars($aula['titulo_aula']) ?></h5>
                                    <p class="card-text mb-1">Data: **<?= date('d/m/Y', strtotime($aula['data_aula'])) ?>**</p>
                                    <p class="card-text mb-2"><small class="text-muted"><?= htmlspecialchars($aula['descricao'] ?? 'Sem descrição.') ?></small></p>
                                    
                                    <p>Conteúdos Associados: <span class="badge bg-secondary"><?= htmlspecialchars($aula['conteudos_nomes'] ?: 'Nenhum') ?></span></p>

                                    <button class="btn btn-sm btn-outline-success me-2" 
                                            onclick="openAssociarConteudoModal(<?= $aula['id'] ?>, '<?= htmlspecialchars($aula['titulo_aula'], ENT_QUOTES) ?>', '<?= htmlspecialchars($aula['conteudos_ids'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="fas fa-link"></i> Associar Conteúdos
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="openEditAulaModal(<?= $aula['id'] ?>, '<?= htmlspecialchars($aula['titulo_aula'], ENT_QUOTES) ?>', '<?= htmlspecialchars($aula['descricao'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($aula['data_aula'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i> Editar</button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmRemove('remover_aula', <?= $aula['id'] ?>, '<?= htmlspecialchars($aula['titulo_aula'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash-alt"></i> Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-pane fade" id="upload" role="tabpanel" aria-labelledby="upload-tab">
                <h4 style="color: var(--cor-primaria);">Fazer Upload / Adicionar Link</h4>
                
                <form method="POST" action="gerenciar_aulas.php?turma_id=<?= $turma_id ?>" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">
                    <input type="hidden" name="acao" value="upload_conteudo">
                    
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título do Conteúdo</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao" name="descricao"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Conteúdo:</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_conteudo" id="tipoArquivo" value="arquivo" checked onchange="toggleInputs()">
                            <label class="form-check-label" for="tipoArquivo">Arquivo (Word, PPT, PDF)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_conteudo" id="tipoLink" value="link" onchange="toggleInputs()">
                            <label class="form-check-label" for="tipoLink">Link/URL</label>
                        </div>
                    </div>
                    
                    <div id="inputArquivo" class="mb-3">
                        <label for="arquivo_upload" class="form-label">Selecione o Arquivo</label>
                        <input class="form-control" type="file" id="arquivo_upload" name="arquivo_upload" required>
                    </div>
                    
                    <div id="inputLink" class="mb-3" style="display: none;">
                        <label for="link_url" class="form-label">URL do Link</label>
                        <input type="url" class="form-control" id="link_url" name="link_url">
                    </div>
                    
                    <button type="submit" class="btn btn-acao mt-3"><i class="fas fa-upload me-2"></i> Fazer Upload / Cadastrar</button>
                </form>
                
                <h4 class="mt-5" style="color: var(--cor-primaria);">Conteúdos Disponíveis (Para Associações)</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="bg-light" style="color: var(--cor-primaria);">
                            <tr>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todos_conteudos as $conteudo): ?>
                            <tr>
                                <td><?= htmlspecialchars($conteudo['titulo']) ?></td>
                                <td>
                                    <?php 
                                        $tipo_exibicao = htmlspecialchars(strtoupper($conteudo['tipo_arquivo']));
                                        if ($tipo_exibicao == 'LINK') {
                                            echo '<span class="badge bg-info">LINK</span>';
                                        } else {
                                            echo '<span class="badge bg-primary">' . $tipo_exibicao . '</span>';
                                        }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($conteudo['descricao'] ?? '---') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-2"><i class="fas fa-edit"></i> Editar</button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="confirmRemove('remover_conteudo', <?= $conteudo['id'] ?>, '<?= htmlspecialchars($conteudo['titulo'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash-alt"></i> Excluir
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="tab-pane fade" id="alunos" role="tabpanel" aria-labelledby="alunos-tab">
                <h4 style="color: var(--cor-primaria);">Lista de Alunos</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="bg-light" style="color: var(--cor-primaria);">
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($alunos_turma)): ?>
                                <tr><td colspan="2">Nenhum aluno associado a esta turma.</td></tr>
                            <?php else: ?>
                                <?php foreach ($alunos_turma as $aluno): ?>
                                <tr>
                                    <td><?= htmlspecialchars($aluno['nome']) ?></td>
                                    <td><?= htmlspecialchars($aluno['email']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddAula" tabindex="-1" aria-labelledby="modalAddAulaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddAulaLabel" style="color: var(--cor-primaria);">Agendar Nova Aula</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="gerenciar_aulas.php?turma_id=<?= $turma_id ?>">
        <div class="modal-body">
            <input type="hidden" name="acao" id="aula_acao" value="add_aula">
            <input type="hidden" name="aula_id" id="aula_id">
            
            <div class="mb-3">
                <label for="titulo_aula" class="form-label">Título da Aula</label>
                <input type="text" class="form-control" id="titulo_aula" name="titulo_aula" required>
            </div>
            <div class="mb-3">
                <label for="data_aula" class="form-label">Data da Aula</label>
                <input type="date" class="form-control" id="data_aula" name="data_aula" required>
            </div>
            <div class="mb-3">
                <label for="descricao_aula" class="form-label">Descrição (Opcional)</label>
                <textarea class="form-control" id="descricao_aula" name="descricao_aula"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-acao" id="btn_salvar_aula">Salvar Aula</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAssociarConteudo" tabindex="-1" aria-labelledby="modalAssociarConteudoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAssociarConteudoLabel" style="color: var(--cor-primaria);">Associar Conteúdos à Aula: <span id="aula_nome_associacao"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="gerenciar_aulas.php?turma_id=<?= $turma_id ?>">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="associar_conteudo_aula">
                    <input type="hidden" name="aula_id_associacao" id="aula_id_associacao">
                    
                    <p>Selecione os conteúdos disponíveis que farão parte desta aula:</p>
                    <div id="lista_conteudos_checkbox" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                        <?php if (empty($todos_conteudos)): ?>
                            <p class="text-danger">Nenhum conteúdo (arquivo ou link) foi cadastrado ainda. Use a aba **Uploads/Conteúdos**.</p>
                        <?php else: ?>
                            <?php foreach ($todos_conteudos as $conteudo): ?>
                            <div class="form-check">
                                <input class="form-check-input conteudo-checkbox" type="checkbox" name="conteudos_selecionados[]" value="<?= $conteudo['id'] ?>" id="conteudo_<?= $conteudo['id'] ?>">
                                <label class="form-check-label" for="conteudo_<?= $conteudo['id'] ?>">
                                    [<?= htmlspecialchars(strtoupper($conteudo['tipo_arquivo'])) ?>] - <?= htmlspecialchars($conteudo['titulo']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-acao">Salvar Associações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="formRemover" method="POST" action="gerenciar_aulas.php?turma_id=<?= $turma_id ?>">
    <input type="hidden" name="acao" id="remover_acao">
    <input type="hidden" name="id_aula" id="remover_id_aula">
    <input type="hidden" name="id_conteudo" id="remover_id_conteudo">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Função para mostrar/esconder campos de Arquivo ou Link
    function toggleInputs() {
        const tipo = document.querySelector('input[name="tipo_conteudo"]:checked')?.value;
        const inputArquivo = document.getElementById('inputArquivo');
        const inputLink = document.getElementById('inputLink');
        const fileInput = document.getElementById('arquivo_upload');
        const linkInput = document.getElementById('link_url');

        if (inputArquivo && inputLink) {
            if (tipo === 'arquivo') {
                inputArquivo.style.display = 'block';
                inputLink.style.display = 'none';
                fileInput.setAttribute('required', 'required');
                linkInput.removeAttribute('required');
            } else if (tipo === 'link') {
                inputArquivo.style.display = 'none';
                inputLink.style.display = 'block';
                fileInput.removeAttribute('required');
                linkInput.setAttribute('required', 'required');
            }
        }
    }
    
    // Inicializa a função ao carregar a página
    window.onload = toggleInputs;
    
    // Função para preencher o Modal para EDIÇÃO de Aula
    function openEditAulaModal(id, titulo, descricao, data) {
        document.getElementById('modalAddAulaLabel').innerText = 'Editar Aula';
        document.getElementById('aula_acao').value = 'editar_aula';
        document.getElementById('aula_id').value = id;
        document.getElementById('titulo_aula').value = titulo;
        document.getElementById('descricao_aula').value = descricao;
        document.getElementById('data_aula').value = data;
        document.getElementById('btn_salvar_aula').innerText = 'Atualizar Aula';
        
        var myModal = new bootstrap.Modal(document.getElementById('modalAddAula'));
        myModal.show();
    }
    
    // Função para resetar o Modal de Adicionar Aula
    document.getElementById('modalAddAula').addEventListener('hidden.bs.modal', function () {
        document.getElementById('modalAddAulaLabel').innerText = 'Agendar Nova Aula';
        document.getElementById('aula_acao').value = 'add_aula';
        document.getElementById('aula_id').value = '';
        document.getElementById('titulo_aula').value = '';
        document.getElementById('descricao_aula').value = '';
        document.getElementById('data_aula').value = '';
        document.getElementById('btn_salvar_aula').innerText = 'Salvar Aula';
    });
    
    // Função para abrir o Modal de Associação de Conteúdos
    function openAssociarConteudoModal(aulaId, aulaNome, conteudosIdsString) {
        // Limpa todos os checkboxes
        document.querySelectorAll('.conteudo-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Configura o modal com o ID e Nome da Aula
        document.getElementById('aula_id_associacao').value = aulaId;
        document.getElementById('aula_nome_associacao').innerText = aulaNome;

        // Marca os checkboxes dos conteúdos já associados
        if (conteudosIdsString) {
            const ids = conteudosIdsString.split(',');
            ids.forEach(id => {
                const checkbox = document.getElementById('conteudo_' + id);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }

        // Exibe o modal
        var myModal = new bootstrap.Modal(document.getElementById('modalAssociarConteudo'));
        myModal.show();
    }
    
    // NOVO: Função para confirmar e enviar a remoção de Aula ou Conteúdo
    function confirmRemove(acao, id, nome) {
        let entidade = acao === 'remover_aula' ? 'a aula' : 'o conteúdo';
        let aviso = `Tem certeza que deseja remover ${entidade} "${nome}"? Esta ação é irreversível e, no caso de arquivos, o arquivo físico será apagado.`;

        if (confirm(aviso)) {
            document.getElementById('remover_acao').value = acao;
            document.getElementById('remover_id_aula').value = ''; // Limpa ambos para evitar conflito
            document.getElementById('remover_id_conteudo').value = '';

            if (acao === 'remover_aula') {
                document.getElementById('remover_id_aula').value = id;
            } else {
                document.getElementById('remover_id_conteudo').value = id;
            }
            document.getElementById('formRemover').submit();
        }
    }
</script>
</body>
</html>