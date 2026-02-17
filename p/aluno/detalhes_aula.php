<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/email_config.php';

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
// Recuperar email para a marca d'água
$aluno_email = $_SESSION['user_email'] ?? ''; 
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
        t.link_aula,
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

// ========== SISTEMA DE ANOTAÇÕES COM VISTO ==========
// Buscar anotação existente para esta aula
$sql_anotacao = "SELECT 
    id, 
    conteudo, 
    comentario_professor,
    visto,
    data_visto,
    data_atualizacao
FROM anotacoes_aula 
WHERE aula_id = :aula_id AND aluno_id = :aluno_id";

$stmt_anotacao = $pdo->prepare($sql_anotacao);
$stmt_anotacao->execute([':aula_id' => $aula_id, ':aluno_id' => $aluno_id]);
$anotacao_existente = $stmt_anotacao->fetch(PDO::FETCH_ASSOC);

$anotacao_id = null;
$anotacao_conteudo = '';
$comentario_professor = '';
$visto = 0;
$data_visto = null;

if ($anotacao_existente) {
    $anotacao_id = $anotacao_existente['id'];
    $anotacao_conteudo = $anotacao_existente['conteudo'];
    $comentario_professor = $anotacao_existente['comentario_professor'] ?? '';
    $visto = $anotacao_existente['visto'] ?? 0;
    $data_visto = $anotacao_existente['data_visto'] ?? null;
}

// Garantir que a tabela de itens exista (múltiplas anotações)
$sql_check_itens = "SHOW TABLES LIKE 'anotacoes_itens'";
$itens_exists = $pdo->query($sql_check_itens)->rowCount() > 0;
if (!$itens_exists) {
    $sql_create_itens = "CREATE TABLE anotacoes_itens (
        id INT(11) NOT NULL AUTO_INCREMENT,
        anotacao_id INT(11) NOT NULL,
        autor VARCHAR(50) NOT NULL,
        conteudo TEXT NOT NULL,
        data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY anotacao_id (anotacao_id),
        CONSTRAINT anotacoes_itens_ibfk_1 FOREIGN KEY (anotacao_id) REFERENCES anotacoes_aula (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    try { $pdo->exec($sql_create_itens); } catch (PDOException $e) {}
}

// Buscar itens existentes para exibir (se houver)
$anotacao_itens = [];
if ($anotacao_id) {
    $sql_itens = "SELECT id, autor, conteudo, data_criacao FROM anotacoes_itens WHERE anotacao_id = :anotacao_id ORDER BY data_criacao ASC";
    $stmt_itens = $pdo->prepare($sql_itens);
    $stmt_itens->execute([':anotacao_id' => $anotacao_id]);
    $anotacao_itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar total de visualizações para esta anotação
$total_visualizacoes = 0;
if ($anotacao_id) {
    $sql_visualizacoes = "SELECT COUNT(*) FROM anotacoes_visualizacoes WHERE anotacao_id = :anotacao_id";
    $stmt_visualizacoes = $pdo->prepare($sql_visualizacoes);
    $stmt_visualizacoes->execute([':anotacao_id' => $anotacao_id]);
    $total_visualizacoes = $stmt_visualizacoes->fetchColumn();
}

// Salvar anotação (criar novo item em vez de sobrescrever)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_anotacao'])) {
    $conteudo = trim($_POST['conteudo_anotacao'] ?? '');
    if ($conteudo !== '') {
        // garantir thread
        if ($anotacao_existente) {
            $thread_id = $anotacao_id;
        } else {
            $sql_create_thread = "INSERT INTO anotacoes_aula (aula_id, aluno_id, conteudo) VALUES (:aula_id, :aluno_id, '')";
            $stmt_ct = $pdo->prepare($sql_create_thread);
            $stmt_ct->execute([':aula_id' => $aula_id, ':aluno_id' => $aluno_id]);
            $thread_id = $pdo->lastInsertId();
        }

        // inserir novo item de aluno
        $sql_item = "INSERT INTO anotacoes_itens (anotacao_id, autor, conteudo) VALUES (:anotacao_id, 'aluno', :conteudo)";
        $stmt_item = $pdo->prepare($sql_item);
        $stmt_item->execute([':anotacao_id' => $thread_id, ':conteudo' => $conteudo]);

        // marcar thread como não visto e atualizar timestamp
        $sql_update_thread = "UPDATE anotacoes_aula SET visto = 0, data_visto = NULL, data_atualizacao = NOW() WHERE id = :id";
        $stmt_up = $pdo->prepare($sql_update_thread);
        $stmt_up->execute([':id' => $thread_id]);

        // Notificar o professor
        if (!empty($aula['email_professor'])) {
            $assunto = "Aluno {$aluno_nome} salvou uma nova anotação na aula: {$aula['titulo_aula']}";
            $excerpt = substr(strip_tags($conteudo), 0, 300);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $link = $scheme . '://' . $host . '/Risenglish/p/professor/detalhes_aula.php?id=' . $aula_id;
            $html = "<p>Olá " . htmlspecialchars($aula['nome_professor']) . ",</p>" .
                    "<p>O aluno <strong>" . htmlspecialchars($aluno_nome) . "</strong> adicionou uma anotação na aula <strong>" . htmlspecialchars($aula['titulo_aula']) . "</strong>.</p>" .
                    "<p>Trecho da anotação:</p><blockquote>" . nl2br(htmlspecialchars($excerpt)) . "</blockquote>" .
                    "<p><a href='" . $link . "'>Ver detalhes da aula e anotações</a></p><p>Atenciosamente,<br>Risenglish</p>";
            try { enviarEmailSimples($aula['email_professor'], $aula['nome_professor'], $assunto, $html); } catch (Exception $e) { error_log('Erro email anotacao insert: '. $e->getMessage()); }
        }
    }
    // Redirecionar para evitar reenvio do formulário
    header("Location: detalhes_aula.php?id=" . $aula_id . "&saved=1");
    exit;
}

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '';
    $date = new DateTime($data);
    return $date->format('d/m/Y H:i');
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

// Função para buscar arquivos de qualquer pasta (tema principal ou subpasta)
function buscarArquivosPastaVisivel($pdo, $pasta_id, $aula_id) {
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
            c.parent_id = :pasta_id
            AND c.eh_subpasta = 0
        ORDER BY 
            c.titulo ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pasta_id' => $pasta_id]);
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
                    $subpasta['filhos'] = buscarArquivosPastaVisivel($pdo, $subpasta['id'], $aula_id);
                    $tema['filhos'][] = $subpasta;
                }
            }
            
            // Buscar arquivos diretamente no tema principal
            $arquivos_tema_principal = buscarArquivosPastaVisivel($pdo, $tema['id'], $aula_id);
            if (!empty($arquivos_tema_principal)) {
                foreach ($arquivos_tema_principal as $arquivo) {
                    $tema['filhos'][] = $arquivo;
                }
            }
        } else {
            // É um arquivo solto no nível principal
            $tema['filhos'] = [];
        }
        $estrutura_conteudos[] = $tema;
    }
}

// Também incluir subpastas que são visíveis mas não têm parent
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
            $subpasta = $conteudo;
            $subpasta['filhos'] = buscarArquivosPastaVisivel($pdo, $subpasta['id'], $aula_id);
            $estrutura_conteudos[] = $subpasta;
        } elseif (!$ja_incluido && $conteudo['eh_subpasta'] == 0) {
            $estrutura_conteudos[] = $conteudo;
        }
    }
}

// CORREÇÃO ADICIONAL: Buscar arquivos diretamente nos temas principais
foreach ($estrutura_conteudos as &$item) {
    if ($item['parent_id'] === null && 
        ($item['eh_subpasta'] == 1 || empty($item['tipo_arquivo']) || $item['tipo_arquivo'] === 'TEMA') &&
        (!isset($item['filhos']) || empty($item['filhos']))) {
        
        $arquivos_diretos = buscarArquivosPastaVisivel($pdo, $item['id'], $aula_id);
        if (!empty($arquivos_diretos)) {
            $item['filhos'] = $arquivos_diretos;
        }
    }
}
unset($item);

// Usar a estrutura hierárquica filtrada
$conteudos = $estrutura_conteudos;

// Verificar se a aula já aconteceu
$data_aula = new DateTime($aula['data_aula']);
$data_atual = new DateTime();
$aula_passada = $data_aula < $data_atual;

// Formatar data e hora
$data_formatada = $data_aula->format('d/m/Y');
$hora_formatada = substr($aula['horario'], 0, 5);

// ===== BUSCAR NOTIFICAÇÕES NÃO LIDAS =====
$sql_notificacoes = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :aluno_id AND lida = 0";
$stmt_notif = $pdo->prepare($sql_notificacoes);
$stmt_notif->execute([':aluno_id' => $aluno_id]);
$total_notificacoes_nao_lidas = $stmt_notif->fetch(PDO::FETCH_ASSOC)['total'];

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
    
    $extensao = $tipo_arquivo;
    if (strpos($tipo_arquivo, '/') !== false) {
        $parts = explode('/', $tipo_arquivo);
        $extensao = end($parts);
    } else {
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

// FUNÇÃO PARA TRANSFORMAR LINKS EM TEXTO CLICÁVEL
function transformarLinksClicaveis($texto) {
    $padrao = '/(https?:\/\/[^\s]+)/i';
    $texto_com_links = preg_replace($padrao, '<a href="$1" target="_blank" class="text-primary link-descricao">$1</a>', $texto);
    $padrao_www = '/(\s|^)(www\.[^\s]+)/i';
    $texto_com_links = preg_replace($padrao_www, '$1<a href="http://$2" target="_blank" class="text-primary link-descricao">$2</a>', $texto_com_links);
    return $texto_com_links;
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

        // Verificação se é PDF para o sistema de proteção
        $extensao = pathinfo($conteudo['caminho_arquivo'], PATHINFO_EXTENSION);
        $is_pdf = (strtolower($extensao) === 'pdf' || $conteudo['tipo_arquivo'] === 'application/pdf');
    }
    ?>
    <div class="conteudo-item" style="margin-left: <?= $margem ?>px; border-bottom: 1px solid #eee; padding: 15px 0;">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="card-title mb-0 flex-grow-1">
                <?php if ($ePasta): ?>
                    <div class="toggle-pasta" 
                         data-bs-toggle="collapse" 
                         data-bs-target="#conteudos-<?= $conteudo['id'] ?>" 
                         aria-expanded="false">
                        <div class="toggle-pasta-content">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-folder text-primary me-2"></i>
                                <?= htmlspecialchars($conteudo['titulo']) ?>
                                
                                <?php if (isset($conteudo['autor_nome'])): ?>
                                    <small class="text-muted ms-2">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($conteudo['autor_nome']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <?php if ($temFilhos): ?>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-secondary me-2"><?= count($conteudo['filhos']) ?></span>
                                    <i class="fas fa-chevron-down collapse-icon"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center">
                        <i class="<?= $icone_info['icone'] ?> <?= $icone_info['cor'] ?> me-2"></i>
                        <?= htmlspecialchars($conteudo['titulo']) ?>
                        
                        <?php if (isset($conteudo['autor_nome'])): ?>
                            <small class="text-muted ms-2">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($conteudo['autor_nome']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </h6>
        </div>
        
        <?php if (!empty($conteudo['descricao'])): ?>
            <p class="card-text text-muted small mb-2"><?= htmlspecialchars($conteudo['descricao']) ?></p>
        <?php endif; ?>
        
        <?php if (!$ePasta && !empty($conteudo['caminho_arquivo'])): ?>
            <?php if ($conteudo['tipo_arquivo'] === 'URL'): ?>
                <?php if ($is_video && $youtube_id): ?>
                    <button class="btn btn-outline-primary btn-sm mt-2" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalYouTube"
                            data-video-id="<?= $youtube_id ?>"
                            data-video-title="<?= htmlspecialchars($conteudo['titulo']) ?>">
                        <i class="fas fa-play me-1"></i>Assistir Vídeo
                    </button>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="fas fa-external-link-alt me-1"></i>Acessar Link
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($is_pdf): ?>
                    <a href="#" onclick="abrirPDFSeguro('../<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>', '<?= htmlspecialchars($conteudo['titulo']) ?>'); return false;" class="btn btn-outline-danger btn-sm mt-2">
                        <i class="fa-solid fa-file-pdf me-1"></i>Visualizar Material
                    </a>
                <?php elseif ($is_video): ?>
                    <a href="../<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="fas fa-play me-1"></i>Assistir Vídeo
                    </a>
                <?php else: ?>
                    <a href="../<?= htmlspecialchars($conteudo['caminho_arquivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="fas fa-external-link me-1"></i>Abrir Arquivo
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($ePasta && $temFilhos): ?>
            <div class="mt-3">
                <div class="collapse" id="conteudos-<?= $conteudo['id'] ?>">
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
    <title><?= htmlspecialchars($aula['titulo_aula']) ?> - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
    </script>

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
        .conteudo-item:last-child {
            border-bottom: none !important;
        }
        .toggle-pasta {
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .toggle-pasta:hover {
            background-color: #e8e9ea;
        }
        .collapse-icon {
            transition: transform 0.3s ease;
        }
        .collapsed .collapse-icon {
            transform: rotate(-90deg);
        }
        .link-aula {
            text-decoration: none;
            font-weight: 500;
        }
        .link-aula:hover {
            text-decoration: underline;
        }
        
        /* Estilo para links na descrição */
        .link-descricao {
            text-decoration: none;
            font-weight: 500;
            word-break: break-all;
        }
        
        .link-descricao:hover {
            text-decoration: underline;
            color: #c0392b !important;
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

        .toggle-pasta {
            transition: all 0.3s ease;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            margin: 5px 0;
            width: 100%;
            cursor: pointer;
        }
        .toggle-pasta:hover {
            background-color: #e8e9ea;
            border-color: #081d40;
        }
        .toggle-pasta-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
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

        /* ========== ESTILOS ATUALIZADOS PARA ANOTAÇÕES COM SISTEMA DE VISTO ========== */
        .anotacoes-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .anotacao-aluno {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .minhas-anotacoes {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .comentario-professor {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            border-left: 4px solid #007bff;
            position: relative;
        }

        /* visual para itens que são comentários do professor na lista do aluno */
        .anotacao-de-professor {
            background: #e8f4fd;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }
        
        /* ===== NOVOS ESTILOS PARA O SISTEMA DE VISTO ===== */
        .visto-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .visto-badge-aluno {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .visto-badge-aluno.visto-true {
            background: #28a745;
            color: white;
        }
        
        .visto-badge-aluno.visto-false {
            background: #e9ecef;
            color: #495057;
        }
        
        .visto-badge-aluno i {
            font-size: 12px;
        }
        
        .data-visto-info {
            font-size: 11px;
            color: #6c757d;
        }
        
        .visualizacoes-count {
            font-size: 11px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        
        .professor-visto-icon {
            color: #28a745;
            font-size: 14px;
            cursor: help;
        }
        
        .professor-nao-visto-icon {
            color: #ffc107;
            font-size: 14px;
            cursor: help;
        }
        
        .professor-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .professor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #081d40 0%, #0a2351 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .professor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .comentario-content {
            margin-top: 10px;
        }
        /* ===== FIM NOVOS ESTILOS ===== */
        
        .anotacoes-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 0 0 8px 8px;
        }
        
        .btn-salvar-anotacao {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            border: none;
            color: white;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-salvar-anotacao:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
        }
        
        .data-atualizacao {
            font-size: 12px;
            color: #666;
            text-align: right;
        }
        
        .sem-anotacoes {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .anotacoes-textarea {
            background: white;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
            line-height: 1.6;
            min-height: 120px;
            padding: 15px;
            resize: vertical;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .anotacoes-textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }
        
        .badge-aluno {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
        }
        
        .badge-professor {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .aluno-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .avatar-container {
            display: flex;
            align-items: center;
        }
        
        .contador-caracteres {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
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
        
        /* --- CSS NOVO APENAS PARA O VISUALIZADOR PDF (SEM AFETAR O RESTO) --- */
        #pdf-render-container {
            background-color: #525659;
            text-align: center;
            overflow-y: auto;
            max-height: 85vh;
            position: relative;
            padding: 20px;
        }
        .pdf-page-canvas {
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            max-width: 100%;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body oncontextmenu="return false;">
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
                    <a href="minhas_aulas.php" class="rounded active"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                    <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                    <a href="anotacoes.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;&nbsp;Anotações</a>
                </div>

                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <a href="minhas_aulas.php" class="btn btn-outline-secondary mb-2">
                            <i class="fas fa-arrow-left me-2"></i>Voltar para Minhas Aulas
                        </a>
                        <h3 class="mb-0"><?= htmlspecialchars($aula['titulo_aula']) ?></h3>
                    </div>
                </div>

                <?php if (isset($_GET['saved'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        Anotações salvas com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-9">
                        <div class="card mb-4">
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
                        
                        <div class="card mb-4" id="anotacoes">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Minhas Anotações</h5>
                                <small class="text-white">Escreva suas anotações, respostas de dever de casa, dúvidas, etc.</small>
                            </div>
                            <form method="POST" action="" id="form-anotacoes">
                                <div class="card-body">
                                    <div class="anotacao-aluno">
                                        <div class="minhas-anotacoes">
                                            <strong class="d-block mb-2">
                                                <i class="fas fa-sticky-note me-2 text-success"></i>
                                                Suas Anotações:
                                            </strong>
                                                <?php if (!empty($anotacao_itens)): ?>
                                                    <div class="lista-minhas-anotacoes mb-3" id="lista-anotacoes">
                                                        <?php foreach ($anotacao_itens as $item): ?>
                                                            <?php $isAlunoItem = (isset($item['autor']) && $item['autor'] === 'aluno'); ?>
                                                            <div class="minha-anotacao-item mb-2 <?= $isAlunoItem ? '' : 'anotacao-de-professor' ?>" data-item-id="<?= $item['id'] ?>">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <small class="text-success small d-block">
                                                                            <?= isset($item['data_criacao']) ? formatarData($item['data_criacao']) : '' ?>
                                                                        </small>
                                                                        <small class="text-muted small ms-2">
                                                                            <?php if (isset($item['autor']) && $item['autor'] === 'professor'): ?>
                                                                                <span class="badge bg-primary">Professor</span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-success">Você</span>
                                                                            <?php endif; ?>
                                                                        </small>
                                                                    </div>
                                                                    <p class="mb-0 item-conteudo"><?= nl2br(htmlspecialchars($item['conteudo'])) ?></p>
                                                                    <div class="mt-1">
                                                                        <?php if ($isAlunoItem): ?>
                                                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-item" data-item-id="<?= $item['id'] ?>">Editar</button>
                                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item" data-item-id="<?= $item['id'] ?>">Apagar</button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="lista-minhas-anotacoes mb-3" id="lista-anotacoes"></div>
                                                <?php endif; ?>
                                                <textarea 
                                                    name="conteudo_anotacao" 
                                                    id="conteudo_anotacao" 
                                                    class="anotacoes-textarea" 
                                                    placeholder="Adicione uma nova anotação..."
                                                ></textarea>
                                                <div class="contador-caracteres mt-2">
                                                    Caracteres: <span id="contador_anotacoes">0</span>
                                                </div>
                                                <div class="mt-2">
                                                    <button type="button" id="btn-salvar-anotacao" class="btn btn-primary">Salvar anotação</button>
                                                </div>
                                        </div>
                                        
                                        <?php if (!empty($comentario_professor)): ?>
                                            <div class="comentario-professor mt-3">
                                                <div class="professor-header">
                                                    <div class="professor-info">
                                                        <div class="professor-avatar">
                                                            <?= strtoupper(substr($aula['nome_professor'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($aula['nome_professor']) ?></strong>
                                                            <small class="text-muted d-block">Professor</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- ===== INDICADOR DE VISTO DO PROFESSOR ===== -->
                                                    <?php if ($visto): ?>
                                                        <span class="visto-badge-aluno visto-true" 
                                                              title="Professor visualizou suas anotações em <?= $data_visto ? formatarData($data_visto) : '' ?>">
                                                            <i class="fas fa-check-circle"></i> Professor viu
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="visto-badge-aluno visto-false" 
                                                              title="Professor ainda não visualizou suas anotações">
                                                            <i class="fas fa-clock"></i> Aguardando visualização
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="comentario-content">
                                                    <strong class="d-block mb-2"><i class="fas fa-comment-dots me-2 text-primary"></i>Comentário do Professor:</strong>
                                                    <p class="mb-0"><?= nl2br(htmlspecialchars($comentario_professor)) ?></p>
                                                </div>
                                                
                                                <!-- Informações adicionais de visualização -->
                                                <?php if ($visto && $data_visto): ?>
                                                    <div class="visto-status mt-2">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <span class="data-visto-info">
                                                                <i class="far fa-clock"></i> Visto em: <?= formatarData($data_visto) ?>
                                                            </span>
                                                            <?php if ($total_visualizacoes > 0): ?>
                                                                <span class="visualizacoes-count" title="Número de vezes que o professor visualizou">
                                                                    <i class="fas fa-eye"></i> <?= $total_visualizacoes ?> visualização(ões)
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Seu professor ainda não fez comentários.
                                                
                                                <!-- Mesmo sem comentário, mostra o status de visto se existir -->
                                                <?php if ($visto): ?>
                                                    <div class="mt-2">
                                                        <span class="visto-badge-aluno visto-true" 
                                                              title="Professor visualizou suas anotações em <?= $data_visto ? formatarData($data_visto) : '' ?>">
                                                            <i class="fas fa-check-circle"></i> O Professor visualizou suas anotações
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Status de visto para anotações sem comentário -->
                                        <?php if (empty($comentario_professor) && $visto && !empty($anotacao_conteudo)): ?>
                                            <div class="visto-status mt-2">
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="data-visto-info">
                                                        <i class="far fa-clock"></i> Professor visualizou em: <?= formatarData($data_visto) ?>
                                                    </span>
                                                    <?php if ($total_visualizacoes > 0): ?>
                                                        <span class="visualizacoes-count">
                                                            <i class="fas fa-eye"></i> <?= $total_visualizacoes ?> visualização(ões)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-info-circle text-primary me-1"></i>
                                            <small class="text-muted">
                                                Suas anotações são salvas automaticamente quando você clica em "Salvar".
                                                <?php if (!empty($anotacao_conteudo)): ?>
                                                    <br>Status: <span class="<?= $visto ? 'text-success' : 'text-warning' ?>">
                                                        <i class="fas <?= $visto ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                                        <?= $visto ? 'Professor visualizou' : 'Aguardando visualização do professor' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações da Aula</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong><i class="fas fa-video me-2 text-primary"></i>Link da Aula:</strong>
                                    <?php if (!empty($aula['link_aula'])): ?>
                                        <div class="mt-2">
                                            <a href="<?= htmlspecialchars($aula['link_aula']) ?>" target="_blank" class="btn btn-primary btn-sm w-100 link-aula">
                                                <i class="fas fa-external-link-alt me-1"></i>Entrar na Aula
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <p class="mb-0 text-muted mt-1">Nenhum link disponível</p>
                                    <?php endif; ?>
                                </div>
                                
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
                                <div class="mb-3">
                                    <strong><i class="fas fa-file-alt me-2 text-primary"></i>Descrição da Aula:</strong>
                                    <?php if (!empty($aula['descricao'])): ?>
                                        <div class="mb-0 mt-1">
                                            <?= transformarLinksClicaveis(nl2br(htmlspecialchars($aula['descricao']))) ?>
                                        </div>
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

    <div class="modal fade" id="modalPDFViewer" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="pdfModalTitle">Visualização Protegida</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="progress" style="height: 4px; border-radius: 0;">
                    <div id="pdf-loading-bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                </div>
                <div class="modal-body p-0" style="background-color: #525659; display: flex; justify-content: center;">
                    <div id="pdf-render-container">
                    </div>
                </div>
                <div class="modal-footer bg-dark text-white justify-content-center">
                    <small>Risenglish &copy; Material Protegido. O download deste arquivo não é permitido.</small>
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

            // Adicionar evento de clique para as pastas (toggle-pasta)
            document.querySelectorAll('.toggle-pasta').forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    // Encontrar o ícone de collapse dentro desta pasta
                    var icon = this.querySelector('.collapse-icon');
                    if (icon) {
                        icon.classList.toggle('collapsed');
                    }
                });
            });

            // Adicionar evento para quando um collapse é mostrado ou escondido
            document.querySelectorAll('.collapse').forEach(function(collapse) {
                collapse.addEventListener('show.bs.collapse', function () {
                    var id = this.id;
                    var toggle = document.querySelector('[data-bs-target="#' + id + '"]');
                    if (toggle) {
                        var icon = toggle.querySelector('.collapse-icon');
                        if (icon) {
                            icon.classList.remove('collapsed');
                        }
                    }
                });
                
                collapse.addEventListener('hide.bs.collapse', function () {
                    var id = this.id;
                    var toggle = document.querySelector('[data-bs-target="#' + id + '"]');
                    if (toggle) {
                        var icon = toggle.querySelector('.collapse-icon');
                        if (icon) {
                            icon.classList.add('collapsed');
                        }
                    }
                });
            });

            // ========== SISTEMA DE ANOTAÇÕES ==========
            // Contador de caracteres
            var textareaAnotacao = document.getElementById('conteudo_anotacao');
            var contadorAnotacoes = document.getElementById('contador_anotacoes');
            
            if (textareaAnotacao && contadorAnotacoes) {
                // Atualizar contador inicial
                contadorAnotacoes.textContent = textareaAnotacao.value.length;
                
                // Atualizar contador quando o usuário digitar
                textareaAnotacao.addEventListener('input', function() {
                    contadorAnotacoes.textContent = this.value.length;
                });

                // salvar via AJAX
                var btnSalvar = document.getElementById('btn-salvar-anotacao');
                if (btnSalvar) {
                    btnSalvar.addEventListener('click', function() {
                        var conteudo = textareaAnotacao.value.trim();
                        if (conteudo === '') return;
                        btnSalvar.disabled = true;
                        btnSalvar.textContent = 'Salvando...';

                        var formData = new FormData();
                        formData.append('acao', 'create');
                        formData.append('aula_id', '<?= $aula_id ?>');
                        formData.append('conteudo', conteudo);

                        fetch('../ajax_anotacoes_item.php', {
                            method: 'POST',
                            body: formData
                        }).then(res => res.json()).then(data => {
                            btnSalvar.disabled = false;
                            btnSalvar.textContent = 'Salvar anotação';
                            if (data.success) {
                                // adicionar item na lista (mostra badge Você e botões somente para próprio aluno)
                                var lista = document.getElementById('lista-anotacoes');
                                var div = document.createElement('div');
                                div.className = 'minha-anotacao-item mb-2';
                                div.setAttribute('data-item-id', data.item.id);
                                div.innerHTML = '<div class="d-flex justify-content-between align-items-start"><small class="text-success small d-block">' + (data.item.data_criacao ? data.item.data_criacao : '') + '</small><small class="text-muted small ms-2"><span class="badge bg-success">Você</span></small></div>' +
                                        '<p class="mb-0 item-conteudo">' + (data.item.conteudo.replace(/\n/g, '<br>')) + '</p>' +
                                        '<div class="mt-1"><button type="button" class="btn btn-sm btn-outline-secondary btn-edit-item" data-item-id="' + data.item.id + '">Editar</button> <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item" data-item-id="' + data.item.id + '">Apagar</button></div>';
                                lista.appendChild(div);
                                // limpar textarea
                                textareaAnotacao.value = '';
                                textareaAnotacao.dispatchEvent(new Event('input', { bubbles: true }));
                                contadorAnotacoes.textContent = '0';
                            } else {
                                alert(data.error || 'Erro ao salvar');
                            }
                        }).catch(err => { btnSalvar.disabled = false; btnSalvar.textContent = 'Salvar anotação'; alert('Erro de conexão'); });
                    });
                }

                // delegação para editar/apagar (uso closest e checagens para evitar erros)
                document.addEventListener('click', function(e) {
                    var deleteBtn = e.target.closest ? e.target.closest('.btn-delete-item') : null;
                    if (deleteBtn) {
                        var itemId = deleteBtn.getAttribute('data-item-id');
                        if (!confirm('Confirma apagar esta anotação?')) return;
                        fetch('../ajax_anotacoes_item.php', {
                            method: 'POST',
                            body: new URLSearchParams({acao: 'delete', item_id: itemId})
                        }).then(r => r.json()).then(d => {
                            if (d.success) {
                                var el = document.querySelector('[data-item-id="' + itemId + '"]');
                                if (el) el.remove();
                            } else alert(d.error || 'Erro');
                        });
                        return;
                    }

                    var editBtn = e.target.closest ? e.target.closest('.btn-edit-item') : null;
                    if (editBtn) {
                        var itemId = editBtn.getAttribute('data-item-id');
                        var container = document.querySelector('[data-item-id="' + itemId + '"]');
                        if (!container) return;
                        var conteudoEl = container.querySelector('.item-conteudo');
                        var original = conteudoEl ? conteudoEl.innerText : '';
                        // substituir por textarea
                        var ta = document.createElement('textarea');
                        ta.className = 'form-control mb-1';
                        ta.value = original;
                        var saveBtn = document.createElement('button');
                        saveBtn.className = 'btn btn-sm btn-primary me-1';
                        saveBtn.textContent = 'Salvar';
                        var cancelBtn = document.createElement('button');
                        cancelBtn.className = 'btn btn-sm btn-secondary';
                        cancelBtn.textContent = 'Cancelar';

                        // ocultar conteúdo atual
                        if (conteudoEl) conteudoEl.style.display = 'none';
                        var insertion = container.querySelector('.mt-1') || container.lastElementChild;
                        container.insertBefore(ta, insertion);
                        var btnArea = container.querySelector('.mt-1');
                        if (btnArea) btnArea.style.display = 'none';
                        var editActions = document.createElement('div');
                        editActions.className = 'mt-1';
                        editActions.appendChild(saveBtn);
                        editActions.appendChild(cancelBtn);
                        container.appendChild(editActions);

                        cancelBtn.addEventListener('click', function() {
                            ta.remove(); editActions.remove(); if (conteudoEl) conteudoEl.style.display = '';
                            if (btnArea) btnArea.style.display = '';
                        });

                        saveBtn.addEventListener('click', function() {
                            var novo = ta.value.trim();
                            if (novo === '') return alert('Conteúdo vazio');
                            saveBtn.disabled = true; saveBtn.textContent = 'Salvando...';
                            fetch('../ajax_anotacoes_item.php', {
                                method: 'POST',
                                body: new URLSearchParams({acao: 'edit', item_id: itemId, conteudo: novo})
                            }).then(r => r.json()).then(d => {
                                saveBtn.disabled = false; saveBtn.textContent = 'Salvar';
                                if (d.success) {
                                    if (conteudoEl) { conteudoEl.innerHTML = d.item.conteudo.replace(/\n/g, '<br>'); conteudoEl.style.display = ''; }
                                    ta.remove(); editActions.remove(); if (btnArea) btnArea.style.display = '';
                                } else {
                                    alert(d.error || 'Erro');
                                }
                            }).catch(() => { saveBtn.disabled = false; saveBtn.textContent = 'Salvar'; alert('Erro de conexão'); });
                        });
                        return;
                    }
                });
                
                // Auto-salvar após 10 segundos de inatividade
                var autoSaveTimeout;
                textareaAnotacao.addEventListener('input', function() {
                    clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = setTimeout(function() {
                        var alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-info alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            <i class="fas fa-sync-alt me-2"></i>
                            <strong>Auto-salvando...</strong> Suas anotações estão sendo salvas automaticamente.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        
                        var form = textareaAnotacao.closest('form');
                        if (form) {
                            form.parentNode.insertBefore(alertDiv, form);
                            setTimeout(function() {
                                var bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                                bsAlert.close();
                            }, 3000);
                        }
                    }, 10000);
                });
            }
            
            // Mostrar mensagem de sucesso se acabou de salvar
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('saved')) {
                var toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '1050';
                toast.innerHTML = `
                    <div class="toast show" role="alert">
                        <div class="toast-header bg-success text-white">
                            <strong class="me-auto"><i class="fas fa-check-circle"></i> Sucesso</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                        </div>
                        <div class="toast-body">
                            Anotações salvas com sucesso!
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                setTimeout(function() { toast.remove(); }, 3000);
            }
        });

        // ==========================================
        // SCRIPT DE PROTEÇÃO E MARCA D'ÁGUA PDF (NOVO)
        // ==========================================
        
        // Variáveis globais para o PDF
        let pdfDoc = null;
        let pdfContainer = document.getElementById('pdf-render-container');
        let loadingBar = document.getElementById('pdf-loading-bar');
        
        // Dados do aluno para marca d'agua (vindo do PHP)
        const studentInfo = {
            name: "<?= htmlspecialchars($aluno_nome) ?>",
            email: "<?= htmlspecialchars($aluno_email) ?>",
            school: "RISENGLISH"
        };

        // Carregar logo da Risenglish
        const logoImg = new Image();
        logoImg.src = '../../LogoRisenglish.png'; 

        async function abrirPDFSeguro(url, titulo) {
            // Limpar container anterior
            pdfContainer.innerHTML = '<div class="text-white mt-5"><i class="fas fa-spinner fa-spin fa-3x"></i><br>Carregando material seguro...</div>';
            document.getElementById('pdfModalTitle').textContent = titulo;
            loadingBar.style.width = '10%';
            
            // Abrir Modal
            var myModal = new bootstrap.Modal(document.getElementById('modalPDFViewer'));
            myModal.show();

            try {
                // Carregar documento
                const loadingTask = pdfjsLib.getDocument(url);
                loadingTask.onProgress = function(p) {
                    if (p.total > 0) {
                        const percent = (p.loaded / p.total) * 100;
                        loadingBar.style.width = percent + '%';
                    }
                };

                pdfDoc = await loadingTask.promise;
                pdfContainer.innerHTML = ''; // Limpar loading
                
                // Renderizar todas as páginas
                for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
                    await renderPage(pageNum);
                }
                loadingBar.style.width = '0%';
            } catch (error) {
                console.error('Erro ao carregar PDF:', error);
                pdfContainer.innerHTML = '<div class="alert alert-danger">Erro ao carregar documento protegido.</div>';
            }
        }

        async function renderPage(num) {
            const page = await pdfDoc.getPage(num);
            
            // Configurar escala (qualidade)
            const scale = 1.5; 
            const viewport = page.getViewport({scale: scale});

            // Criar Canvas
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            canvas.className = 'pdf-page-canvas';
            
            // Adicionar ao DOM
            pdfContainer.appendChild(canvas);

            // Renderizar PDF no Canvas
            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            
            await page.render(renderContext).promise;

            // ==========================================
            // APLICAR MARCA D'ÁGUA (OVERLAY)
            // ==========================================
            ctx.save();
            
            // 1. Marca d'água de Texto (Repetida na diagonal)
            ctx.font = "bold 24px Arial";
            ctx.fillStyle = "rgba(200, 0, 0, 0.15)"; // Vermelho transparente
            ctx.rotate(-45 * Math.PI / 180); // Rotacionar
            
           const text = "RISENGLISH - MATERIAL PROTEGIDO";

            // Loop para preencher a página com o texto repetido
        for (let x = -canvas.height; x < canvas.width; x += 500) {
            for (let y = -canvas.height; y < canvas.height * 2; y += 200) {
        ctx.fillText(text, x, y);
            }
        }
            ctx.restore(); // Restaurar rotação para desenhar o logo normal

            // 2. Marca d'água do Logo (Centro ou Canto)
            if (logoImg.complete) {
                const logoWidth = 150; // Largura do logo
                const logoHeight = (logoImg.height / logoImg.width) * logoWidth;
                
                ctx.globalAlpha = 0.2; // Bem transparente
                // Desenhar no canto inferior direito
                ctx.drawImage(logoImg, canvas.width - logoWidth - 50, canvas.height - logoHeight - 50, logoWidth, logoHeight);
                // Desenhar no topo esquerdo também
                ctx.drawImage(logoImg, 50, 50, logoWidth, logoHeight);
                ctx.globalAlpha = 1.0;
            }
        }

        // Impedir atalhos de teclado comuns para salvar
        document.addEventListener('keydown', function(e) {
            // Ctrl+S, Ctrl+P, Ctrl+U (Source)
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p' || e.key === 'u')) {
                e.preventDefault();
                alert('Função desabilitada para proteção de direitos autorais Risenglish.');
            }
        });
    </script>
</body>
</html>