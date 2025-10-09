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

// Consulta para obter todos os recursos úteis
$sql = "
    SELECT 
        id,
        titulo,
        link,
        descricao,
        data_criacao
    FROM 
        recursos_uteis
    ORDER BY 
        data_criacao DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$recursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendações - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <style>
        .card-recurso {
            transition: all 0.3s ease;
            border-left: 4px solid #c0392b;
            height: 100%;
        }
        .card-recurso:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .recurso-icon {
            font-size: 2.5rem;
            color: #081d40;
            margin-bottom: 1rem;
        }
        .btn-recurso {
            background-color: #081d40;
            color: white;
            border: none;
        }
        .btn-recurso:hover {
            background-color: #0a2550;
            color: white;
        }
        .categoria-badge {
            position: absolute;
            top: 15px;
            right: 15px;
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
                    <h5 class="mt-4"><?php echo $aluno_nome; ?></h5>
                </div>

                <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded active"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Recursos Recomendados</h3>
                    <span class="badge bg-primary fs-6"><?= count($recursos) ?> recursos disponíveis</span>
                </div>

                <!-- Introdução -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><i class="fas fa-info-circle me-2"></i>Como usar esses recursos</h5>
                                <p class="card-text mb-0">
                                    Aqui você encontra ferramentas e sites selecionados para ajudar no seu aprendizado de inglês. 
                                    Use esses recursos para complementar seus estudos, melhorar a pronúncia, expandir o vocabulário 
                                    e praticar fora das aulas.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid de Recursos -->
                <?php if (count($recursos) > 0): ?>
                <div class="row">
                    <?php foreach ($recursos as $recurso): 
                        // Determinar ícone com base no título ou descrição
                        $icone = 'fa-globe'; // Ícone padrão
                        $categoria = 'Ferramenta';
                        
                        $titulo_lower = strtolower($recurso['titulo']);
                        $descricao_lower = strtolower($recurso['descricao']);
                        
                        if (strpos($titulo_lower, 'dicionário') !== false || strpos($descricao_lower, 'dicionário') !== false) {
                            $icone = 'fa-book';
                            $categoria = 'Dicionário';
                        } elseif (strpos($titulo_lower, 'tradutor') !== false || strpos($descricao_lower, 'tradutor') !== false) {
                            $icone = 'fa-language';
                            $categoria = 'Tradutor';
                        } elseif (strpos($titulo_lower, 'pronúncia') !== false || strpos($descricao_lower, 'pronúncia') !== false) {
                            $icone = 'fa-volume-up';
                            $categoria = 'Pronúncia';
                        } elseif (strpos($titulo_lower, 'fonética') !== false || strpos($descricao_lower, 'fonética') !== false) {
                            $icone = 'fa-music';
                            $categoria = 'Fonética';
                        }
                        
                        // Formatar data
                        $data_criacao = new DateTime($recurso['data_criacao']);
                        $data_formatada = $data_criacao->format('d/m/Y');
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card card-recurso">
                            <div class="card-body position-relative">
                                <span class="badge bg-secondary categoria-badge"><?= $categoria ?></span>
                                
                                <div class="text-center">
                                    <i class="fas <?= $icone ?> recurso-icon"></i>
                                </div>
                                
                                <h5 class="card-title text-center"><?= htmlspecialchars($recurso['titulo']) ?></h5>
                                
                                <?php if (!empty($recurso['descricao'])): ?>
                                    <p class="card-text text-muted"><?= htmlspecialchars($recurso['descricao']) ?></p>
                                <?php endif; ?>
                                
                                <div class="mt-4 text-center">
                                    <a href="<?= htmlspecialchars($recurso['link']) ?>" 
                                       target="_blank" 
                                       class="btn btn-recurso w-100">
                                        <i class="fas fa-external-link-alt me-2"></i>Acessar Recurso
                                    </a>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Adicionado em <?= $data_formatada ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-lightbulb fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Nenhum recurso disponível</h4>
                        <p class="text-muted">Em breve teremos recomendações para você!</p>
                    </div>
                <?php endif; ?>

                <!-- Dicas de Uso -->
                
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>