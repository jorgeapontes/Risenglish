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
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        body {
            background-color: #FAF9F6;
            overflow-x: hidden;
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

        .card-recurso {
            transition: all 0.3s ease;
            border-left: 4px solid #c0392b;
            height: 100%;
        }
        .card-recurso:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .card-text {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
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

        /* Menu Mobile */
        @media (max-width: 991px) {
            .sidebar {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .mobile-navbar-custom {
                background: #081d40 !important;
                color: #fff !important;
            }
            .mobile-navbar-custom h5 {
                color: #fff !important;
            }
            .mobile-navbar-custom .btn-outline-primary {
                color: #fff !important;
                border-color: #fff !important;
            }
            .mobile-navbar-custom .btn-outline-primary:active,
            .mobile-navbar-custom .btn-outline-primary:focus,
            .mobile-navbar-custom .btn-outline-primary:hover {
                background: #0a2a5c !important;
                color: #fff !important;
                border-color: #fff !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        
        <!-- Menu Mobile (Hamburger & Header) -->
        <header class="d-flex d-md-none mobile-navbar-custom border-bottom shadow-sm p-3 align-items-center sticky-top">
            <button class="btn btn-outline-primary me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Abrir Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0 fw-bold">Recomendações</h5>
        </header>

        <!-- Sidebar Offcanvas (Menu para Mobile) -->
        <div class="offcanvas offcanvas-top text-white mobile-offcanvas" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="background-color: #081d40; height: 50vh;">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title fw-bold" id="sidebarOffcanvasLabel"><?php echo $aluno_nome; ?></h5>
                <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                 <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded active"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                    <a href="anotacoes.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;&nbsp;Anotações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>
        </div>

        <div class="row g-0">
            <!-- Sidebar Desktop (Visível apenas em md e acima) -->
            <div class="col-md-2 d-none d-md-flex flex-column sidebar p-3">
                <!-- Nome do aluno -->
                <div class="mb-4 text-center">
                    <h5 class="mt-4"><?php echo $aluno_nome; ?></h5>
                </div>

                <!-- Menu -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded active"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                    <a href="anotacoes.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;&nbsp;Anotações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo Principal -->
            <div class="col-12 col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Recursos Recomendados</h3>
                </div>

                <!-- Introdução -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light">
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
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card card-recurso">
                            <div class="card-body position-relative flex">
                                
                                <div class="text-center">
                                    <i class="fas fa-link recurso-icon"></i>
                                </div>
                                
                                <h5 class="card-title text-center"><?= htmlspecialchars($recurso['titulo']) ?></h5>
                                
                                <?php if (!empty($recurso['descricao'])): ?>
                                    <p class="card-text text-muted"><?= htmlspecialchars($recurso['descricao']) ?></p>
                                <?php endif; ?>
                                
                                <div class="mt-4 text-center">
                                    <a href="<?= htmlspecialchars($recurso['link']) ?>" 
                                       target="_blank" 
                                       class="btn btn-recurso w-100">
                                        <i class="fas fa-external-link-alt me-2"></i>Acessar
                                    </a>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>