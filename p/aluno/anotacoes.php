<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Garante que apenas aluno acessa esta página
if ($_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login.php?erro=acesso_negado");
    exit;
}

$aluno_id = $_SESSION['user_id'];
$aluno_nome = $_SESSION['user_nome'] ?? 'Aluno';

// ========== SISTEMA DE CADERNO DE ANOTAÇÕES ==========
// Verificar se a tabela de caderno existe
$sql_check_table = "SHOW TABLES LIKE 'caderno_anotacoes'";
$table_exists = $pdo->query($sql_check_table)->rowCount() > 0;

if (!$table_exists) {
    // Criar tabela de caderno de anotações se não existir
    $sql_create_table = "CREATE TABLE caderno_anotacoes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        aluno_id INT(11) NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        conteudo TEXT NOT NULL,
        data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY aluno_id (aluno_id),
        CONSTRAINT caderno_anotacoes_ibfk_1 FOREIGN KEY (aluno_id) REFERENCES usuarios (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    try {
        $pdo->exec($sql_create_table);
    } catch (PDOException $e) {
        // Ignora erros se a tabela já existir
    }
}

// Ações: salvar, editar, excluir
$acao = $_POST['acao'] ?? '';
$anotacao_id = $_POST['anotacao_id'] ?? null;
$titulo = $_POST['titulo'] ?? '';
$conteudo = $_POST['conteudo'] ?? '';
$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($acao === 'salvar') {
        if (empty($titulo)) {
            $mensagem = 'O título é obrigatório!';
            $tipo_mensagem = 'danger';
        } else {
            if ($anotacao_id) {
                // Editar anotação existente
                $sql_update = "UPDATE caderno_anotacoes SET titulo = :titulo, conteudo = :conteudo WHERE id = :id AND aluno_id = :aluno_id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([
                    ':titulo' => $titulo,
                    ':conteudo' => $conteudo,
                    ':id' => $anotacao_id,
                    ':aluno_id' => $aluno_id
                ]);
                $mensagem = 'Anotação atualizada com sucesso!';
                $tipo_mensagem = 'success';
            } else {
                // Criar nova anotação
                $sql_insert = "INSERT INTO caderno_anotacoes (aluno_id, titulo, conteudo) VALUES (:aluno_id, :titulo, :conteudo)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([
                    ':aluno_id' => $aluno_id,
                    ':titulo' => $titulo,
                    ':conteudo' => $conteudo
                ]);
                $mensagem = 'Anotação criada com sucesso!';
                $tipo_mensagem = 'success';
            }
        }
    } elseif ($acao === 'excluir' && $anotacao_id) {
        // Excluir anotação
        $sql_delete = "DELETE FROM caderno_anotacoes WHERE id = :id AND aluno_id = :aluno_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([
            ':id' => $anotacao_id,
            ':aluno_id' => $aluno_id
        ]);
        $mensagem = 'Anotação excluída com sucesso!';
        $tipo_mensagem = 'success';
    }
}

// Buscar todas as anotações do aluno
$sql_anotacoes = "SELECT id, titulo, conteudo, data_criacao, data_atualizacao 
                  FROM caderno_anotacoes 
                  WHERE aluno_id = :aluno_id 
                  ORDER BY data_atualizacao DESC";
$stmt_anotacoes = $pdo->prepare($sql_anotacoes);
$stmt_anotacoes->execute([':aluno_id' => $aluno_id]);
$anotacoes = $stmt_anotacoes->fetchAll(PDO::FETCH_ASSOC);

// Buscar anotação específica para edição (se fornecido via GET)
$anotacao_edit = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $sql_edit = "SELECT id, titulo, conteudo FROM caderno_anotacoes WHERE id = :id AND aluno_id = :aluno_id";
    $stmt_edit = $pdo->prepare($sql_edit);
    $stmt_edit->execute([':id' => $_GET['editar'], ':aluno_id' => $aluno_id]);
    $anotacao_edit = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}

// Determinar se o formulário deve começar aberto (se estiver editando ou se houve erro)
// A classe 'show' do Bootstrap mantém o collapse aberto
$classe_collapse = ($anotacao_edit || ($mensagem && $tipo_mensagem == 'danger')) ? 'show' : '';

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '';
    $date = new DateTime($data);
    return $date->format('d/m/Y H:i');
}

// Função para resumir texto
function resumirTexto($texto, $limite = 150) {
    if (strlen($texto) <= $limite) {
        return $texto;
    }
    return substr($texto, 0, $limite) . '...';
}

// ===== BUSCAR NOTIFICAÇÕES NÃO LIDAS =====
$sql_notificacoes = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :aluno_id AND lida = 0";
$stmt_notif = $pdo->prepare($sql_notificacoes);
$stmt_notif->execute([':aluno_id' => $aluno_id]);
$total_notificacoes_nao_lidas = $stmt_notif->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Anotações - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/aluno/anotacoes.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center">
                    <h5 class="mt-4"><?php echo htmlspecialchars($aluno_nome); ?></h5>
                </div>

                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="notificacoes.php" class="rounded position-relative">
                        <i class="fas fa-bell"></i>&nbsp;&nbsp;Notificações
                        <?php if ($total_notificacoes_nao_lidas > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $total_notificacoes_nao_lidas ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                    <a href="anotacoes.php" class="rounded active"><i class="fas fa-book-open"></i>&nbsp;&nbsp;&nbsp;Anotações</a>
                    <a href="documentos.php" class="rounded"><i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;&nbsp;Documentos</a>
                </div>

                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 style="color: #081d40;">Caderno de Anotações</h1>
                    <div>
                        <span class="badge badge-contador">
                            <i class="fas fa-book me-1"></i>
                            <?= count($anotacoes) ?> anotações
                        </span>
                    </div>
                </div>
                
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $tipo_mensagem == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= htmlspecialchars($mensagem) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <button class="btn btn-toggle-form shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFormulario" aria-expanded="<?= $anotacao_edit ? 'true' : 'false' ?>" aria-controls="collapseFormulario">
                        <span>
                            <i class="fas fa-plus-circle me-2"></i>
                            <?= $anotacao_edit ? 'Modo de Edição' : 'Criar Nova Anotação' ?>
                        </span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>

                <div class="collapse <?= $classe_collapse ?>" id="collapseFormulario">
                    <div class="caderno-container p-4 mb-4">
                        <div class="form-container">
                            <h4 class="mb-4" style="color: #081d40;">
                                <i class="fas fa-edit me-2"></i>
                                <?= $anotacao_edit ? 'Editar Anotação' : 'Preencha os dados' ?>
                            </h4>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="acao" value="salvar">
                                <input type="hidden" name="anotacao_id" value="<?= $anotacao_edit ? $anotacao_edit['id'] : '' ?>">
                                
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">
                                        <strong>Título <span class="text-danger">*</span></strong>
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="titulo" 
                                           name="titulo" 
                                           value="<?= htmlspecialchars($anotacao_edit ? $anotacao_edit['titulo'] : $titulo) ?>" 
                                           placeholder="Digite um título para sua anotação" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="conteudo" class="form-label">
                                        <strong>Conteúdo</strong>
                                    </label>
                                    <textarea 
                                        class="form-control" 
                                        id="conteudo" 
                                        name="conteudo" 
                                        rows="8" 
                                        placeholder="Escreva suas anotações aqui..."
                                    ><?= htmlspecialchars($anotacao_edit ? $anotacao_edit['conteudo'] : $conteudo) ?></textarea>
                                    <div class="contador-caracteres">
                                        Caracteres: <span id="contador_conteudo"><?= strlen($anotacao_edit ? $anotacao_edit['conteudo'] : $conteudo) ?></span>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div>
                                        <?php if ($anotacao_edit): ?>
                                            <a href="anotacoes.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-1"></i>Cancelar
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#collapseFormulario">
                                                <i class="fas fa-chevron-up me-1"></i>Fechar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-nova-anotacao">
                                            <i class="fas fa-save me-1"></i>
                                            <?= $anotacao_edit ? 'Atualizar Anotação' : 'Salvar Anotação' ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="caderno-container p-4">
                    <h4 class="mb-4" style="color: #081d40;">
                        <i class="fas fa-book me-2"></i>
                        Minhas Anotações
                    </h4>
                    
                    <?php if (empty($anotacoes)): ?>
                        <div class="sem-anotacoes">
                            <i class="fas fa-book-open"></i>
                            <h5 class="text-muted">Nenhuma anotação encontrada</h5>
                            <p class="text-muted">Clique em "Criar Nova Anotação" para começar!</p>
                        </div>
                    <?php else: ?>
                        <div class="anotacoes-grid">
                            <?php foreach ($anotacoes as $anotacao): ?>
                                <div class="anotacao-card">
                                    <div class="anotacao-titulo">
                                        <?= htmlspecialchars($anotacao['titulo']) ?>
                                    </div>
                                    
                                    <div class="anotacao-conteudo">
                                        <?= nl2br(htmlspecialchars(resumirTexto($anotacao['conteudo'], 200))) ?>
                                    </div>
                                    
                                    <div class="anotacao-data">
                                        <small>
                                            <i class="fas fa-calendar me-1"></i>
                                            Criada: <?= formatarData($anotacao['data_criacao']) ?>
                                            <?php if ($anotacao['data_atualizacao'] != $anotacao['data_criacao']): ?>
                                                <br>
                                                <i class="fas fa-sync-alt me-1"></i>
                                                Atualizada: <?= formatarData($anotacao['data_atualizacao']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="anotacao-acoes">
                                        <a href="?editar=<?= $anotacao['id'] ?>" class="btn btn-editar">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-excluir" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalExcluir<?= $anotacao['id'] ?>">
                                            <i class="fas fa-trash me-1"></i>Excluir
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($anotacoes)): ?>
        <?php foreach ($anotacoes as $anotacao): ?>
            <div class="modal fade" id="modalExcluir<?= $anotacao['id'] ?>" tabindex="-1" aria-labelledby="modalExcluirLabel<?= $anotacao['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalExcluirLabel<?= $anotacao['id'] ?>">Confirmar Exclusão</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Atenção!</strong> Esta ação não pode ser desfeita.
                            </div>
                            <p>Tem certeza que deseja excluir a anotação <strong>"<?= htmlspecialchars($anotacao['titulo']) ?>"</strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="anotacao_id" value="<?= $anotacao['id'] ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-2"></i> Sim, Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Contador de caracteres
            const textareaConteudo = document.getElementById('conteudo');
            const contadorConteudo = document.getElementById('contador_conteudo');
            
            if (textareaConteudo && contadorConteudo) {
                // Atualizar contador inicial
                contadorConteudo.textContent = textareaConteudo.value.length;
                
                // Atualizar contador quando o usuário digitar
                textareaConteudo.addEventListener('input', function() {
                    contadorConteudo.textContent = this.value.length;
                });
                
                // Auto-salvar (mantido, mas opcional)
                let autoSaveTimeout;
                textareaConteudo.addEventListener('input', function() {
                    clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = setTimeout(function() {
                        const titulo = document.getElementById('titulo').value;
                        if (titulo && textareaConteudo.value) {
                           // Lógica de auto-save aqui se desejar
                        }
                    }, 30000);
                });
            }
            
            // Foco automático e rolagem se estiver no modo de edição
            <?php if ($anotacao_edit): ?>
                const formCollapse = document.getElementById('collapseFormulario');
                if (formCollapse) {
                    formCollapse.addEventListener('shown.bs.collapse', function () {
                        if (textareaConteudo) textareaConteudo.focus();
                    });
                    // Rolar até o formulário
                    formCollapse.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            <?php endif; ?>
            
            // Sugestões no placeholder
            const suggestions = [
                "Vocabulário: apple, banana, car...",
                "Gramática: Present Perfect structure...",
                "Pronúncia: 'thought' se pronuncia...",
                "Frases úteis: Can you help me with...",
                "Dúvidas: Quando usar 'much' vs 'many'?",
                "Links: https://dictionary.cambridge.org/",
                "Exercícios: Complete as frases com...",
                "Objetivos: Aprender 10 palavras novas por semana"
            ];
            
            let currentSuggestion = 0;
            const conteudoTextarea = document.getElementById('conteudo');
            
            setInterval(function() {
                if (conteudoTextarea && !conteudoTextarea.value) {
                    conteudoTextarea.placeholder = suggestions[currentSuggestion];
                    currentSuggestion = (currentSuggestion + 1) % suggestions.length;
                }
            }, 10000);
        });
    </script>
</body>
</html>