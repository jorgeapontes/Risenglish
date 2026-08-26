<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Garante que apenas aluno acessa esta página
if ($_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login?erro=acesso_negado");
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
    <title>Recomendações - Risenglish</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/aluno/recomendacoes.css">
</head>
<body>
    <div class="container-fluid p-0">
        
        <?php
        $paginaAtiva = 'recomendacoes';
        $tituloMobile = 'Recomendações';
        require '../includes/layout/aluno_sidebar.php';
        ?>

        <div class="row g-0">
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