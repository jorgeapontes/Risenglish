<?php
session_start();
require_once '../includes/conexao.php';

// Bloqueio de acesso para usuários não-aluno
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login.php");
    exit;
}

// Verificar se o ID da aula foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: minhas_aulas.php");
    exit;
}

$aluno_id = $_SESSION['user_id'];
$aluno_nome = $_SESSION['user_nome'] ?? 'Aluno';
$aula_id = $_GET['id'];

// Consulta para obter os detalhes da aula
$sql_aula = "
    SELECT 
        a.id AS aula_id,
        a.data_aula, 
        a.horario, 
        a.titulo_aula, 
        a.descricao,
        t.id AS turma_id,
        t.nome_turma,
        u.nome AS nome_professor,
        u.email AS email_professor
    FROM 
        aulas a
    JOIN 
        turmas t ON a.turma_id = t.id
    JOIN 
        alunos_turmas at ON t.id = at.turma_id
    JOIN 
        usuarios u ON a.professor_id = u.id
    WHERE 
        at.aluno_id = :aluno_id
        AND a.id = :aula_id
";
$stmt_aula = $pdo->prepare($sql_aula);
$stmt_aula->execute([
    ':aluno_id' => $aluno_id,
    ':aula_id' => $aula_id
]);
$aula = $stmt_aula->fetch(PDO::FETCH_ASSOC);

// Verificar se a aula existe e pertence ao aluno
if (!$aula) {
    header("Location: minhas_aulas.php");
    exit;
}

// BUSCAR APENAS CONTEÚDOS EXPLICITAMENTE MARCADOS COMO VISÍVEIS PARA ESTA AULA
$sql_conteudos_visiveis = "
    SELECT DISTINCT
        c.id,
        c.titulo,
        c.descricao,
        c.tipo_arquivo,
        c.caminho_arquivo,
        c.parent_id,
        c.eh_subpasta,
        1 as planejado,
        u.nome AS autor_nome
    FROM 
        aulas_conteudos ac
    JOIN 
        conteudos c ON ac.conteudo_id = c.id
    LEFT JOIN
        usuarios u ON c.professor_id = u.id
    WHERE 
        ac.aula_id = :aula_id
        AND ac.planejado = 1
    ORDER BY 
        c.parent_id IS NULL DESC, c.titulo ASC
";
$stmt_conteudos = $pdo->prepare($sql_conteudos_visiveis);
$stmt_conteudos->execute([':aula_id' => $aula_id]);
$conteudos_visiveis = $stmt_conteudos->fetchAll(PDO::FETCH_ASSOC);

// Função para buscar apenas os arquivos de subpastas que estão visíveis
function buscarArquivosSubpastaVisivel($pdo, $subpasta_id, $aula_id) {
    $sql = "
        SELECT 
            c.id,
            c.titulo,
            c.descricao,
            c.tipo_arquivo,
            c.caminho_arquivo,
            c.parent_id,
            c.eh_subpasta,
            1 as planejado,
            u.nome AS autor_nome
        FROM 
            conteudos c
        LEFT JOIN
            usuarios u ON c.professor_id = u.id
        WHERE 
            c.parent_id = :subpasta_id
            AND c.eh_subpasta = 0
        ORDER BY 
            c.titulo ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':subpasta_id' => $subpasta_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Construir estrutura apenas com conteúdos explicitamente visíveis
$estrutura_conteudos = [];

foreach ($conteudos_visiveis as $conteudo) {
    if ($conteudo['parent_id'] === null) {
        // É um tema principal visível
        $tema = $conteudo;
        if ($conteudo['eh_subpasta'] == 1 || empty($conteudo['tipo_arquivo']) || $conteudo['tipo_arquivo'] === 'TEMA') {
            // É uma pasta - buscar apenas subpastas que também estão visíveis
            $tema['filhos'] = [];
            
            // Buscar subpastas visíveis dentro deste tema
            foreach ($conteudos_visiveis as $possivel_filho) {
                if ($possivel_filho['parent_id'] == $tema['id'] && $possivel_filho['eh_subpasta'] == 1) {
                    $subpasta = $possivel_filho;
                    // Buscar arquivos desta subpasta visível
                    $subpasta['filhos'] = buscarArquivosSubpastaVisivel($pdo, $subpasta['id'], $aula_id);
                    $tema['filhos'][] = $subpasta;
                }
            }
            
            // Se não há subpastas visíveis, buscar arquivos diretamente no tema
            if (empty($tema['filhos'])) {
                $tema['filhos'] = buscarArquivosSubpastaVisivel($pdo, $tema['id'], $aula_id);
            }
        }
        $estrutura_conteudos[] = $tema;
    }
}

// Também incluir subpastas que são visíveis mas não têm parent (caso raro)
foreach ($conteudos_visiveis as $conteudo) {
    if ($conteudo['parent_id'] !== null) {
        $ja_incluido = false;
        foreach ($estrutura_conteudos as $tema) {
            if (isset($tema['filhos'])) {
                foreach ($tema['filhos'] as $filho) {
                    if ($filho['id'] == $conteudo['id']) {
                        $ja_incluido = true;
                        break 2;
                    }
                }
            }
        }
        
        if (!$ja_incluido && $conteudo['eh_subpasta'] == 1) {
            // É uma subpasta visível sem tema pai visível (adicionar como item principal)
            $subpasta = $conteudo;
            $subpasta['filhos'] = buscarArquivosSubpastaVisivel($pdo, $subpasta['id'], $aula_id);
            $estrutura_conteudos[] = $subpasta;
        } elseif (!$ja_incluido && $conteudo['eh_subpasta'] == 0) {
            // É um arquivo solto visível (adicionar como item principal)
            $estrutura_conteudos[] = $conteudo;
        }
    }
}

// Usar a estrutura hierárquica filtrada
$conteudos = $estrutura_conteudos;

// Verificar se a aula já aconteceu
$data_aula = new DateTime($aula['data_aula']);
$data_atual = new DateTime();
$aula_passada = $data_aula < $data_atual;

// Formatar data e hora
$data_formatada = $data_aula->format('d/m/Y');
$hora_formatada = substr($aula['horario'], 0, 5);

// Função para extrair ID do YouTube
function get_youtube_id($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}

// Função para verificar se é vídeo
function is_video_file($tipo_arquivo, $caminho_arquivo) {
    if ($tipo_arquivo === 'URL') {
        return (strpos($caminho_arquivo, 'youtube.com') !== false || strpos($caminho_arquivo, 'youtu.be') !== false);
    }
    
    $extensao = pathinfo($caminho_arquivo, PATHINFO_EXTENSION);
    $video_extensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    return in_array(strtolower($extensao), $video_extensions);
}

// Função para obter ícone baseado no tipo de arquivo
function get_arquivo_icone($tipo_arquivo, $caminho_arquivo) {
    if ($tipo_arquivo === 'URL') {
        if (is_video_file($tipo_arquivo, $caminho_arquivo)) {
            return ['icone' => 'fa-brands fa-youtube', 'cor' => 'text-danger'];
        } else {
            return ['icone' => 'fas fa-link', 'cor' => 'text-primary'];
        }
    }
    
    // Extrair extensão do tipo_arquivo (que pode ser como "application/pdf")
    $extensao = $tipo_arquivo;
    if (strpos($tipo_arquivo, '/') !== false) {
        $parts = explode('/', $tipo_arquivo);
        $extensao = end($parts);
    } else {
        // Se não tem barra, tentar extrair do caminho do arquivo
        $extensao = pathinfo($caminho_arquivo, PATHINFO_EXTENSION);
    }

    $icone = 'fas fa-file';
    $cor = 'text-primary';
    
    switch(strtolower($extensao)) {
        case 'pdf':
            $icone = 'fa-solid fa-file-pdf';
            $cor = 'text-danger';
            break;
        case 'mp3':
        case 'wav':
        case 'ogg':
            $icone = 'fas fa-file-audio';
            $cor = 'text-info';
            break;
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'bmp':
        case 'svg':
            $icone = 'fa-solid fa-image';
            $cor = 'text-success';
            break;
        case 'gif':
            $icone = 'fa-solid fa-gif';
            $cor = 'text-success';
            break;
        case 'mp4':
        case 'avi':
        case 'mov':
        case 'wmv':
        case 'flv':
        case 'webm':
            $icone = 'fas fa-file-video';
            $cor = 'text-warning';
            break;
        case 'doc':
        case 'docx':
            $icone = 'fas fa-file-word';
            $cor = 'text-primary';
            break;
        case 'xls':
        case 'xlsx':
            $icone = 'fas fa-file-excel';
            $cor = 'text-success';
            break;
        case 'ppt':
        case 'pptx':
            $icone = 'fas fa-file-powerpoint';
            $cor = 'text-danger';
            break;
        case 'zip':
        case 'rar':
        case '7z':
            $icone = 'fas fa-file-archive';
            $cor = 'text-warning';
            break;
        case 'txt':
            $icone = 'fas fa-file-alt';
            $cor = 'text-secondary';
            break;
    }
    
    return ['icone' => $icone, 'cor' => $cor];
}

// Função recursiva para exibir conteúdos
function displayConteudo($conteudo, $nivel = 0) {
    $margem = $nivel * 20;
    $temFilhos = isset($conteudo['filhos']) && count($conteudo['filhos']) > 0;
    $ePasta = $conteudo['eh_subpasta'] == 1 || empty($conteudo['tipo_arquivo']) || $conteudo['tipo_arquivo'] === 'TEMA';
    
    // Se não for pasta, é um arquivo
    if (!$ePasta) {
        $is_video = is_video_file($conteudo['tipo_arquivo'], $conteudo['caminho_arquivo']);
        $youtube_id = null;
        if ($is_video && $conteudo['tipo_arquivo'] === 'URL') {
            $youtube_id = get_youtube_id($conteudo['caminho_arquivo']);
        }
        $icone_info = get_arquivo_icone($conteudo['tipo_arquivo'], $conteudo['caminho_arquivo']);
    }
    ?>
    <div class="conteudo-item" style="margin-left: <?= $margem ?>px; border-bottom: 1px solid #eee; padding: 15px 0;">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="card-title mb-0">
                <?php if ($ePasta): ?>
                    <i class="fas fa-folder text-primary me-1"></i>
                <?php else: ?>
                    <i class="<?= $icone_info['icone'] ?> <?= $icone_info['cor'] ?> me-1"></i>
                <?php endif; ?>
                <?= htmlspecialchars($conteudo['titulo']) ?>
                
                <?php if (isset($conteudo['autor_nome']) && $ePasta): ?>
                    <small class="text-muted ms-2">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($conteudo['autor_nome']) ?>
                    </small>
                <?php endif; ?>
            </h6>
            <span class="badge bg-success">
                Disponível
            </span>
        </div>
        
        <?php if (!empty($conteudo['descricao'])): ?>
            <p class="card-text text-muted small mb-2"><?= htmlspecialchars($conteudo['descricao']) ?></p>
        <?php endif; ?>
        
        <!-- Botões para arquivos (não pastas) -->
        <?php if (!$ePasta && !empty($conteudo['caminho_arquivo'])): ?>
            <?php if ($conteudo['tipo_arquivo'] === 'URL'): ?>
                <?php if ($is_video && $youtube_id): ?>
                    <button class="btn btn-outline-primary btn-sm mt-1" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalYouTube"
                            data-video-id="<?= $youtube_id ?>"
                            data-video-title="<?= htmlspecialchars($conteudo['titulo']) ?>">
                        <i class="fas fa-play me-1"></i>Assistir Vídeo
                    </button>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-1">
                        <i class="fas fa-external-link-alt me-1"></i>Acessar Link
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($is_video): ?>
                    <a href="../<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-1">
                        <i class="fas fa-play me-1"></i>Assistir Vídeo
                    </a>
                <?php else: ?>
                    <a href="../<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-1">
                        <i class="fas fa-download me-1"></i>Baixar Arquivo
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Conteúdos filhos (para pastas) -->
        <?php if ($ePasta && $temFilhos): ?>
            <div class="mt-3">
                <div class="toggle-arquivos p-2 border rounded" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#conteudos-<?= $conteudo['id'] ?>" 
                     aria-expanded="false">
                    <small class="d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-folder-open me-1"></i>
                            Conteúdos (<?= count($conteudo['filhos']) ?>)
                        </span>
                        <i class="fas fa-chevron-down collapse-icon"></i>
                    </small>
                </div>
                
                <div class="collapse mt-2" id="conteudos-<?= $conteudo['id'] ?>">
                    <div class="conteudos-filhos border rounded p-3 bg-light">
                        <?php foreach ($conteudo['filhos'] as $filho): ?>
                            <?php displayConteudo($filho, $nivel + 1); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($aula['titulo_aula']) ?> - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .conteudo-card {
            transition: all 0.3s ease;
            border-left: 4px solid #c0392b;
            height: 100%;
            cursor: default;
        }
        .conteudo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .badge-planejado {
            background-color: #28a745;
            color: white;
        }
        .badge-adicionado {
            background-color: #17a2b8;
            color: white;
        }
        .info-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #081d40;
        }
        .card-header {
            background-color: #081d40;
            color: white;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .conteudos-filhos {
            max-height: 500px;
            overflow-y: auto;
        }
        .conteudo-item {
            transition: background-color 0.2s;
        }
        .conteudo-item:hover {
            background-color: #f8f9fa;
        }
        .conteudo-item:last-child {
            border-bottom: none !important;
        }
        .toggle-arquivos {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .toggle-arquivos:hover {
            background-color: #f8f9fa;
        }
        .collapse-icon {
            transition: transform 0.3s ease;
        }
        .collapsed .collapse-icon {
            transform: rotate(-90deg);
        }

        /* Modal YouTube personalizado */
        .modal-youtube .modal-dialog {
            max-width: 70%;
            max-height: 70vh;
        }
        
        .modal-youtube .modal-content {
            background: #081d40;
            border: none;
            padding: 10px;
        }
        
        .modal-youtube .modal-header {
            border-bottom: none;
            padding-bottom: 25px;
        }
        
        .modal-youtube .btn-close {
            background-color: white;
            opacity: 1;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            
            .modal-youtube .modal-dialog {
                max-width: 95%;
                margin: 10px auto;
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
                    <a href="minhas_aulas.php" class="rounded active"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                </div>

                <!-- Botão sair no rodapé -->
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <a href="minhas_aulas.php" class="btn btn-outline-secondary mb-2">
                            <i class="fas fa-arrow-left me-2"></i>Voltar para Minhas Aulas
                        </a>
                        <h3 class="mb-0"><?= htmlspecialchars($aula['titulo_aula']) ?></h3>
                    </div>
                </div>

                <div class="row">
                    <!-- Conteúdos da Aula -->
                    <div class="col-md-9">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Conteúdos da Aula</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($conteudos) > 0): ?>
                                    <div class="conteudos-lista">
                                        <?php foreach ($conteudos as $conteudo): ?>
                                            <?php displayConteudo($conteudo); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-folder-open fa-2x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">Nenhum conteúdo disponível para esta aula.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Informações da Aula -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações da Aula</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong><i class="fas fa-calendar me-2 text-primary"></i>Data:</strong>
                                    <p class="mb-0"><?= $data_formatada ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-clock me-2 text-primary"></i>Horário:</strong>
                                    <p class="mb-0"><?= $hora_formatada ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-users me-2 text-primary"></i>Turma:</strong>
                                    <p class="mb-0"><?= htmlspecialchars($aula['nome_turma']) ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-user me-2 text-primary"></i>Professor:</strong>
                                    <p class="mb-0"><?= htmlspecialchars($aula['nome_professor']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($aula['email_professor']) ?></small>
                                </div>
                                <!-- DESCRIÇÃO DA AULA - ADICIONADA AQUI -->
                                <div class="mb-3">
                                    <strong><i class="fas fa-file-alt me-2 text-primary"></i>Descrição da Aula:</strong>
                                    <?php if (!empty($aula['descricao'])): ?>
                                        <p class="mb-0 mt-1"><?= htmlspecialchars($aula['descricao']) ?></p>
                                    <?php else: ?>
                                        <p class="mb-0 mt-1 text-muted">Nenhuma descrição fornecida.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para YouTube -->
    <div class="modal fade modal-youtube" id="modalYouTube" tabindex="-1" aria-labelledby="modalYouTubeLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 style="color: white;" id="header-title"></h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="ratio ratio-16x9">
                        <iframe id="youtubePlayer" src="" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar todos os collapses como fechados por padrão
        document.addEventListener('DOMContentLoaded', function() {
            var collapses = document.querySelectorAll('.collapse');
            collapses.forEach(function(collapse) {
                var bsCollapse = new bootstrap.Collapse(collapse, {
                    toggle: false
                });
            });

            // Função para o modal do YouTube
            var modalYouTube = document.getElementById('modalYouTube');
            if (modalYouTube) {
                modalYouTube.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var videoId = button.getAttribute('data-video-id');
                    var videoTitle = button.getAttribute('data-video-title');
                    
                    var iframe = document.getElementById('youtubePlayer');
                    var headerTitle = document.getElementById('header-title');
                    
                    iframe.setAttribute('src', 'https://www.youtube.com/embed/' + videoId + '?autoplay=1');
                    headerTitle.textContent = videoTitle;
                });

                modalYouTube.addEventListener('hidden.bs.modal', function () {
                    var iframe = document.getElementById('youtubePlayer');
                    iframe.setAttribute('src', '');
                });
            }

            // Adicionar evento de clique para os toggles de pasta
            document.querySelectorAll('.toggle-arquivos').forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    var icon = this.querySelector('.collapse-icon');
                    icon.classList.toggle('collapsed');
                });
            });
        });
    </script>
</body>
</html>