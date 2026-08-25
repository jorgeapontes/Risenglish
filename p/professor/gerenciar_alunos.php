<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Garante que apenas professor acessa esta página
if ($_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];

// Variável para armazenar o termo de pesquisa
$termo_pesquisa = '';
$resultados_filtrados = [];

// Verificar se há um termo de pesquisa enviado via GET
if (isset($_GET['pesquisa']) && !empty(trim($_GET['pesquisa']))) {
    $termo_pesquisa = trim($_GET['pesquisa']);
    
    // --- CONSULTA PARA PESQUISAR TURMAS E ALUNOS ---
    $sql_pesquisa = "
        SELECT DISTINCT
            t.id,
            t.nome_turma,
            t.inicio_turma,
            u.nome AS nome_professor
        FROM 
            turmas t
        JOIN 
            usuarios u ON t.professor_id = u.id
        LEFT JOIN 
            alunos_turmas at ON t.id = at.turma_id
        LEFT JOIN 
            usuarios a ON at.aluno_id = a.id AND a.tipo_usuario = 'aluno'
        WHERE 
            t.professor_id = :professor_id 
            AND (
                t.nome_turma LIKE :termo
                OR a.nome LIKE :termo
                OR a.email LIKE :termo
            )
        ORDER BY 
            t.nome_turma ASC
    ";
    
    $stmt_pesquisa = $pdo->prepare($sql_pesquisa);
    $termo_like = "%" . $termo_pesquisa . "%";
    $stmt_pesquisa->bindParam(':professor_id', $professor_id);
    $stmt_pesquisa->bindParam(':termo', $termo_like);
    $stmt_pesquisa->execute();
    $turmas_pesquisa = $stmt_pesquisa->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada turma encontrada na pesquisa, busca os alunos (todos ou filtrados pelo termo)
    foreach ($turmas_pesquisa as $turma) {
        $sql_alunos_turma = "
            SELECT 
                u.id AS aluno_id, 
                u.nome AS nome_aluno,
                u.email AS email_aluno
            FROM 
                alunos_turmas at
            JOIN 
                usuarios u ON at.aluno_id = u.id
            WHERE 
                at.turma_id = :turma_id
                AND u.tipo_usuario = 'aluno'
                AND (
                    u.nome LIKE :termo_aluno
                    OR u.email LIKE :termo_aluno
                    OR :termo_global LIKE '%'  -- Permite mostrar todos alunos da turma se a turma foi encontrada
                )
            ORDER BY
                u.nome ASC
        ";
        
        $stmt_alunos_turma = $pdo->prepare($sql_alunos_turma);
        $stmt_alunos_turma->bindParam(':turma_id', $turma['id']);
        $stmt_alunos_turma->bindParam(':termo_aluno', $termo_like);
        $stmt_alunos_turma->bindParam(':termo_global', $termo_pesquisa);
        $stmt_alunos_turma->execute();
        $turma['alunos'] = $stmt_alunos_turma->fetchAll(PDO::FETCH_ASSOC);
        
        $resultados_filtrados[] = $turma;
    }
} else {
    // --- CONSULTA NORMAL PARA LISTAR TODAS AS TURMAS E ALUNOS ---
    $sql_turmas = "
        SELECT 
            t.id, t.nome_turma, t.inicio_turma, u.nome AS nome_professor
        FROM 
            turmas t
        JOIN 
            usuarios u ON t.professor_id = u.id
        WHERE 
            t.professor_id = :professor_id 
        ORDER BY 
            t.nome_turma ASC
    ";
    $stmt_turmas = $pdo->prepare($sql_turmas);
    $stmt_turmas->bindParam(':professor_id', $professor_id);
    $stmt_turmas->execute();
    $turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada turma, busca os alunos associados
    foreach ($turmas as $turma) {
        $sql_alunos = "
            SELECT 
                u.id AS aluno_id, 
                u.nome AS nome_aluno,
                u.email AS email_aluno
            FROM 
                alunos_turmas at
            JOIN 
                usuarios u ON at.aluno_id = u.id
            WHERE 
                at.turma_id = :turma_id
                AND u.tipo_usuario = 'aluno'
            ORDER BY
                u.nome ASC
        ";
        $stmt_alunos = $pdo->prepare($sql_alunos);
        $stmt_alunos->bindParam(':turma_id', $turma['id']);
        $stmt_alunos->execute();
        $turma['alunos'] = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);
        
        $resultados_filtrados[] = $turma;


        // ===== BUSCAR NOTIFICAÇÕES NÃO LIDAS =====
$sql_notificacoes = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :professor_id AND lida = 0";
$stmt_notif = $pdo->prepare($sql_notificacoes);
$stmt_notif->execute([':professor_id' => $professor_id]);
$total_notificacoes_nao_lidas = $stmt_notif->fetch(PDO::FETCH_ASSOC)['total'];
    }
}

// Variável para exibir os resultados
$turmas_com_alunos = $resultados_filtrados;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos/Turmas - Professor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/professor/gerenciar_alunos.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <!-- Nome do professor -->
                <div class="mb-4 text-center">
                    <h5 class="mt-4">Prof. <?php echo $_SESSION['user_nome'] ?? 'Professor'; ?></h5>
                </div>

                <!-- Menu centralizado verticalmente -->
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="notificacoes.php" class="rounded position-relative">
                        <i class="fas fa-bell"></i>&nbsp;&nbsp;Notificações
                        <?php if ($total_notificacoes_nao_lidas > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $total_notificacoes_nao_lidas ?></span>
                        <?php endif; ?>
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded active"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                    <h2 class="mb-0">Gerenciamento de Turmas</h2>
                    <?php if (!empty($termo_pesquisa)): ?>
                        <a href="gerenciar_alunos.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Limpar Pesquisa
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Barra de Pesquisa -->
                <div class="search-container">
                    <form method="GET" action="" class="mb-0">
                        <div class="search-input-group">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" 
                                   name="pesquisa" 
                                   class="form-control search-input" 
                                   placeholder="Pesquisar por nome de turma, nome de aluno ou email..." 
                                   value="<?php echo htmlspecialchars($termo_pesquisa); ?>"
                                   autocomplete="off">
                        </div>
                        <?php if (!empty($termo_pesquisa)): ?>
                            <div class="search-results-info mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Pesquisando por: <strong>"<?php echo htmlspecialchars($termo_pesquisa); ?>"</strong>
                                <span class="badge ms-2"><?php echo count($turmas_com_alunos); ?> turma(s) encontrada(s)</span>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if (empty($turmas_com_alunos) && !empty($termo_pesquisa)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h4 class="mt-3">Nenhum resultado encontrado</h4>
                        <p class="text-muted">Não encontramos turmas ou alunos com o termo "<?php echo htmlspecialchars($termo_pesquisa); ?>"</p>
                        <a href="gerenciar_alunos.php" class="btn btn-outline-danger">
                             Ver todas as turmas
                        </a>
                    </div>
                <?php elseif (empty($turmas_com_alunos)): ?>
                    <div class="alert alert-info">
                        Você ainda não possui turmas cadastradas ou associadas ao seu perfil.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="accordionTurmas">
                        <?php foreach ($turmas_com_alunos as $index => $turma): 
                            $turma_id = $turma['id'];
                            $collapse_id = "collapse" . $turma_id;
                            $heading_id = "heading" . $turma_id;
                            $num_alunos = count($turma['alunos']);
                            
                            // Destacar o termo de pesquisa no nome da turma
                            $nome_turma_display = htmlspecialchars($turma['nome_turma']);
                            $nome_professor_display = htmlspecialchars($turma['nome_professor']);
                            
                            if (!empty($termo_pesquisa)) {
                                $termo_highlight = preg_quote(htmlspecialchars($termo_pesquisa), '/');
                                $nome_turma_display = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $nome_turma_display);
                                $nome_professor_display = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $nome_professor_display);
                            }
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header accordion-header-custom" id="<?= $heading_id ?>">
                                    <button class="container-fluid accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" 
                                        aria-expanded="false" aria-controls="<?= $collapse_id ?>">
                                        
                                        <span class="me-2"><i class="fas fa-graduation-cap"></i></span>
                                        <div class="text-start flex-grow-1">
                                            <span class="d-inline-block fw-bold"><?= $nome_turma_display ?></span> 
                                            <br>
                                            <small class="text-muted d-block">
                                                Professor: <?= $nome_professor_display ?>
                                            </small>
                                        </div>
                                        <!-- Tirei essa badge pq estava dando problema de css e não era tão importante -->
                                        <!-- <span class="aluno-badge">
                                            <?= $num_alunos ?> Aluno(s)
                                        </span> -->
                                    </button>
                                    
                                    <a href="detalhes_turma.php?turma_id=<?= $turma_id ?>" class="btn btn-sm btn-gerenciar" title="Gerenciar Turma e Aulas">
                                        <i class="fas fa-cog me-1"></i> Gerenciar
                                    </a>
                                </h2>
                                <div id="<?= $collapse_id ?>" class="accordion-collapse collapse" aria-labelledby="<?= $heading_id ?>" data-bs-parent="#accordionTurmas">
                                    <div class="accordion-body p-0">
                                        <?php if ($num_alunos > 0): ?>
                                            <div class="p-3 bg-light border-top">
                                                <h6 class="mb-3"><i class="fas fa-users me-2 text-danger" style="color: #081d40;"></i> Alunos desta turma:</h6>
                                                <ul class="list-unstyled mb-0">
                                                    <?php foreach ($turma['alunos'] as $aluno): 
                                                        // Destacar o termo de pesquisa nos resultados
                                                        $nome_aluno = htmlspecialchars($aluno['nome_aluno']);
                                                        $email_aluno = htmlspecialchars($aluno['email_aluno']);
                                                        
                                                        if (!empty($termo_pesquisa)) {
                                                            $termo_highlight = preg_quote(htmlspecialchars($termo_pesquisa), '/');
                                                            $nome_aluno = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $nome_aluno);
                                                            $email_aluno = preg_replace("/($termo_highlight)/i", '<mark class="bg-warning">$1</mark>', $email_aluno);
                                                        }
                                                    ?>
                                                        <li class="aluno-item d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <i class="fas fa-user-circle me-2" style="color: #081d40;"></i> 
                                                                <strong><?= $nome_aluno ?></strong>
                                                            </div>
                                                            <small class="text-muted"><?= $email_aluno ?></small>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <p class="p-3 text-center text-muted m-0 bg-light border-top">
                                                <i class="fas fa-exclamation-circle me-2"></i> Esta turma ainda não tem alunos associados.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($termo_pesquisa)): ?>
                        <div class="alert alert-light border mt-3">
                            <i class="fas fa-lightbulb me-2 text-warning"></i>
                            <small>Os termos pesquisados estão destacados em <mark class="bg-warning">amarelo</mark> nos resultados acima.</small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus na barra de pesquisa se houver um termo de pesquisa
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="pesquisa"]');
            if (searchInput && searchInput.value) {
                searchInput.focus();
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
            }
            
            // Expandir automaticamente o primeiro resultado se estiver pesquisando
            const urlParams = new URLSearchParams(window.location.search);
            const pesquisa = urlParams.get('pesquisa');
            if (pesquisa && document.querySelector('.accordion-button')) {
                const firstAccordionButton = document.querySelector('.accordion-button');
                if (firstAccordionButton && firstAccordionButton.getAttribute('aria-expanded') === 'false') {
                    firstAccordionButton.click();
                }
            }
        });
    </script>
</body>
</html>