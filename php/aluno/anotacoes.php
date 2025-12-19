<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-aluno
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login.php");
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
    <style>
        body {
            background-color: #FAF9F6;
            overflow-x: hidden;
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
            margin-left: 16.666667%;
            width: 83.333333%;
            min-height: 100vh;
            overflow-y: auto;
        }

        .btn-danger {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        
        .btn-danger:hover {
            background-color: #a93226;
            border-color: #a93226;
        }
        
        .btn-outline-danger {
            color: #c0392b;
            border-color: #c0392b;
        }
        
        .btn-outline-danger:hover {
            background-color: #c0392b;
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

        .card-header {
            background-color: #081d40;
            color: white;
        }

        /* Estilos específicos para o caderno */
        .caderno-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .form-container {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .anotacoes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .anotacao-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .anotacao-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            border-color: #081d40;
        }
        
        .anotacao-titulo {
            font-size: 1.25rem;
            font-weight: 600;
            color: #081d40;
            margin-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .anotacao-conteudo {
            flex-grow: 1;
            color: #333;
            line-height: 1.6;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .anotacao-data {
            font-size: 0.85rem;
            color: #6c757d;
            border-top: 1px solid #f0f0f0;
            padding-top: 10px;
            margin-top: auto;
        }
        
        .anotacao-acoes {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-editar {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            color: white;
            padding: 6px 15px;
            font-size: 0.875rem;
        }
        
        .btn-excluir {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            border: none;
            color: white;
            padding: 6px 15px;
            font-size: 0.875rem;
        }
        
        .btn-nova-anotacao {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            font-weight: 500;
        }
        
        .btn-nova-anotacao:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
        }
        
        .form-control, .form-control:focus {
            border-color: #ced4da;
            box-shadow: none;
        }
        
        .form-control:focus {
            border-color: #081d40;
        }
        
        .contador-caracteres {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }
        
        .sem-anotacoes {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .sem-anotacoes i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .modal-header {
            background-color: #081d40;
            color: white;
        }
        
        .badge-contador {
            background: linear-gradient(135deg, #081d40 0%, #0a2351 100%);
            color: white;
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .anotacoes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <!-- Nome do aluno -->
                <div class="mb-4 text-center">
                    <h5 class="mt-4"><?php echo htmlspecialchars($aluno_nome); ?></h5>
                </div>

                <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                    <a href="anotacoes.php" class="rounded active"><i class="fas fa-book-open"></i>&nbsp;&nbsp;&nbsp;Anotações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
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
                
                <!-- Mensagens -->
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $tipo_mensagem == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= htmlspecialchars($mensagem) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário de anotação -->
                <div class="caderno-container p-4 mb-4">
                    <div class="form-container">
                        <h4 class="mb-4" style="color: #081d40;">
                            <i class="fas fa-edit me-2"></i>
                            <?= $anotacao_edit ? 'Editar Anotação' : 'Nova Anotação' ?>
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
                                    placeholder="Escreva suas anotações aqui... Você pode incluir:
- Vocabulário novo que aprendeu
- Regras gramaticais importantes
- Pronúncia de palavras difíceis
- Frases úteis para conversação
- Dicas de estudo
- Links de recursos
- Qualquer outra informação importante"
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
                
                <!-- Lista de anotações existentes -->
                <div class="caderno-container p-4">
                    <h4 class="mb-4" style="color: #081d40;">
                        <i class="fas fa-book me-2"></i>
                        Minhas Anotações
                    </h4>
                    
                    <?php if (empty($anotacoes)): ?>
                        <div class="sem-anotacoes">
                            <i class="fas fa-book-open"></i>
                            <h5 class="text-muted">Nenhuma anotação encontrada</h5>
                            <p class="text-muted">Comece criando sua primeira anotação usando o formulário acima!</p>
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
                                        
                                        <!-- Modal de confirmação para exclusão -->
                                        <button type="button" 
                                                class="btn btn-excluir" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalExcluir<?= $anotacao['id'] ?>">
                                            <i class="fas fa-trash me-1"></i>Excluir
                                        </button>
                                        
                                        <!-- Modal -->
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
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

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
                
                // Auto-salvar após 30 segundos de inatividade (opcional)
                let autoSaveTimeout;
                textareaConteudo.addEventListener('input', function() {
                    clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = setTimeout(function() {
                        // Se tiver título e conteúdo, pode salvar automaticamente
                        const titulo = document.getElementById('titulo').value;
                        if (titulo && textareaConteudo.value) {
                            const form = textareaConteudo.closest('form');
                            if (form) {
                                const submitBtn = form.querySelector('button[type="submit"]');
                                if (submitBtn) {
                                    // Criar notificação de auto-salvamento
                                    const alertDiv = document.createElement('div');
                                    alertDiv.className = 'alert alert-info alert-dismissible fade show mt-3';
                                    alertDiv.innerHTML = `
                                        <i class="fas fa-sync-alt me-2"></i>
                                        <strong>Auto-salvando rascunho...</strong>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    `;
                                    
                                    // Adicionar antes do formulário
                                    const container = document.querySelector('.form-container');
                                    if (container) {
                                        container.appendChild(alertDiv);
                                        
                                        // Simular clique no botão salvar (para rascunho automático)
                                        // Removemos essa parte para evitar salvamento automático sem confirmação
                                        
                                        // Remover alerta após 3 segundos
                                        setTimeout(function() {
                                            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                                            bsAlert.close();
                                        }, 3000);
                                    }
                                }
                            }
                        }
                    }, 30000);
                });
            }
            
            // Foco automático no título se estiver vazio
            const tituloInput = document.getElementById('titulo');
            if (tituloInput && !tituloInput.value.trim()) {
                tituloInput.focus();
            }
            
            // Foco no formulário se estiver editando
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('editar')) {
                window.scrollTo(0, 0);
                if (textareaConteudo) {
                    textareaConteudo.focus();
                }
            }
            
            // Limpar formulário após sucesso (se não estiver editando)
            <?php if ($mensagem && $tipo_mensagem == 'success' && !$anotacao_edit): ?>
                document.getElementById('titulo').value = '';
                document.getElementById('conteudo').value = '';
                document.getElementById('contador_conteudo').textContent = '0';
                
                // Rolar para o topo para ver a mensagem
                window.scrollTo(0, 0);
            <?php endif; ?>
            
            // Adicionar sugestões ao placeholder
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
            
            // Rotacionar sugestões a cada 10 segundos (opcional)
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