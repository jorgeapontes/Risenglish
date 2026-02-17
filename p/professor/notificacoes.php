<?php
session_start();
require_once '../includes/conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$user_nome = $_SESSION['user_nome'];

// Processar ação de marcar todas como lidas
if (isset($_POST['marcar_todas'])) {
    $sql = "UPDATE notificacoes SET lida = 1, data_leitura = NOW() WHERE usuario_id = :user_id AND lida = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    header("Location: notificacoes.php");
    exit;
}

// Buscar todas as notificações do usuário
$sql = "SELECT id, tipo, titulo, mensagem, link, icone, cor, lida, data_criacao, data_leitura 
        FROM notificacoes 
        WHERE usuario_id = :user_id 
        ORDER BY data_criacao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por data
$notificacoes_agrupadas = [];
foreach ($notificacoes as $notif) {
    $data = new DateTime($notif['data_criacao']);
    $hoje = new DateTime();
    $ontem = new DateTime('-1 day');
    
    if ($data->format('Y-m-d') == $hoje->format('Y-m-d')) {
        $grupo = 'Hoje';
    } elseif ($data->format('Y-m-d') == $ontem->format('Y-m-d')) {
        $grupo = 'Ontem';
    } else {
        $grupo = $data->format('d/m/Y');
    }
    
    $notificacoes_agrupadas[$grupo][] = $notif;
}

// Contar não lidas
$sql_nao_lidas = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :user_id AND lida = 0";
$stmt_nao_lidas = $pdo->prepare($sql_nao_lidas);
$stmt_nao_lidas->execute([':user_id' => $user_id]);
$total_nao_lidas = $stmt_nao_lidas->fetch(PDO::FETCH_ASSOC)['total'];

// Definir o diretório base baseado no tipo de usuário
$base_dir = ($user_tipo === 'professor') ? 'professor' : 'aluno';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        body {
            background-color: #FAF9F6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        }
        
        .sidebar .active {
            background-color: #c0392b;
        }
        
        .sidebar .active:hover {
            background-color: #c0392b;
        }
        
        .main-content {
            margin-left: 16.666667%;
            width: 83.333333%;
            min-height: 100vh;
            padding: 20px;
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
        
        .notificacao-card {
            transition: all 0.3s ease;
            border-left: 4px solid #c0392b;
            border-radius: 8px;
            margin-bottom: 10px;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .notificacao-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
        }
        
        .notificacao-card.lida {
            border-left-color: #6c757d;
            opacity: 0.7;
            background-color: #f8f9fa;
        }
        
        .notificacao-card.nao-lida {
            background-color: #fff;
            border-left-width: 4px;
        }
        
        .notificacao-icone {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .notificacao-titulo {
            font-weight: 600;
            margin-bottom: 2px;
            color: #333;
        }
        
        .notificacao-mensagem {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
            white-space: pre-line;
        }
        
        .notificacao-data {
            font-size: 0.8rem;
            color: #adb5bd;
        }
        
        .badge-nao-lida {
            background-color: #c0392b;
            color: white;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 10px;
            margin-left: 10px;
        }
        
        .grupo-data {
            font-size: 1.1rem;
            font-weight: 600;
            color: #081d40;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e9ecef;
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
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center">
                    <h5 class="mt-4"><?php echo htmlspecialchars($user_nome); ?></h5>
                </div>
                
                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <?php if ($user_tipo === 'professor'): ?>
                        <a href="notificacoes.php" class="rounded active">
                            <i class="fas fa-bell"></i>&nbsp;&nbsp;Notificações
                            <?php if ($total_nao_lidas > 0): ?>
                                <span class="badge bg-danger ms-2"><?= $total_nao_lidas ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                        <a href="gerenciar_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                        <a href="gerenciar_conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                        <a href="gerenciar_alunos.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                        <a href="minhas_aulas.php" class="rounded"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Minhas Aulas</a>
                        <a href="recomendacoes.php" class="rounded"><i class="fas fa-lightbulb"></i>&nbsp;&nbsp;&nbsp;Recomendações</a>
                        <a href="anotacoes.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;&nbsp;Anotações</a>
                        <a href="notificacoes.php" class="rounded active">
                            <i class="fas fa-bell"></i>&nbsp;&nbsp;Notificações
                            <?php if ($total_nao_lidas > 0): ?>
                                <span class="badge bg-danger ms-2"><?= $total_nao_lidas ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100">
                        <i class="fas fa-sign-out-alt me-2"></i>Sair
                    </a>
                </div>
            </div>
            
            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 fw-bold" style="color: #081d40;">
                            <i class="fas fa-bell me-2" style="color: #c0392b;"></i>
                            Notificações
                        </h2>
                        <p class="text-muted">Central de alertas e avisos</p>
                    </div>
                    
                    <?php if ($total_nao_lidas > 0): ?>
                        <form method="POST" onsubmit="return confirm('Marcar todas as notificações como lidas?');">
                            <button type="submit" name="marcar_todas" class="btn btn-outline-secondary">
                                <i class="fas fa-check-double me-2"></i>Marcar todas como lidas
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($notificacoes)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-bell-slash fa-4x text-muted"></i>
                        </div>
                        <h5 class="text-muted">Nenhuma notificação</h5>
                        <p class="text-muted">Você não possui notificações no momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notificacoes_agrupadas as $grupo => $notificacoes_grupo): ?>
                        <div class="grupo-data"><?= $grupo ?></div>
                        
                        <?php foreach ($notificacoes_grupo as $notif): 
                            $data = new DateTime($notif['data_criacao']);
                            // Montar href de forma segura e consistente
                            $href = '';
                            // Preferir campo aula_id (quando presente na tabela)
                            if (!empty($notif['aula_id'])) {
                                $href = '/Risenglish/p/professor/detalhes_aula.php?aula_id=' . intval($notif['aula_id']);
                            } else {
                                $link_raw = $notif['link'] ?? '';
                                // Tentar extrair id do query string
                                if (preg_match('/[?&](?:id|aula_id)=(\d+)/', $link_raw, $m)) {
                                    $href = '/Risenglish/p/professor/detalhes_aula.php?aula_id=' . intval($m[1]);
                                } else {
                                    // Se já é absoluto, manter
                                    if (strpos($link_raw, '/') === 0 || preg_match('#^https?://#', $link_raw)) {
                                        $href = $link_raw;
                                    } else {
                                        // Relativo: prefixar com a pasta do professor
                                        $href = '/Risenglish/p/professor/' . ltrim($link_raw, './');
                                    }
                                }
                            }
                        ?>
                            <a href="<?= htmlspecialchars($href) ?>" 
   class="notificacao-card card <?= $notif['lida'] ? 'lida' : 'nao-lida' ?>"
   onclick="marcarNotificacaoLida(<?= $notif['id'] ?>)">
    <div class="card-body">
        <div class="d-flex">
            <div class="notificacao-icone me-3" style="background-color: <?= $notif['cor'] ?? ($notif['lida'] ? '#6c757d' : '#c0392b') ?>;">
                <i class="<?= $notif['icone'] ?? 'fas fa-bell' ?>"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="notificacao-titulo">
                            <?= htmlspecialchars($notif['titulo']) ?>
                        </span>
                        <?php if (!$notif['lida']): ?>
                            <span class="badge-nao-lida">Nova</span>
                        <?php endif; ?>
                    </div>
                    <small class="notificacao-data">
                        <?= $data->format('H:i') ?>
                    </small>
                </div>
                <div class="notificacao-mensagem">
                    <?= nl2br(htmlspecialchars($notif['mensagem'])) ?>
                </div>
            </div>
        </div>
    </div>
</a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function marcarNotificacaoLida(notificacaoId) {
        fetch('ajax_notificacoes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'acao=marcar_lida&notificacao_id=' + notificacaoId,
            keepalive: true
        }).catch(error => console.error('Erro ao marcar notificação:', error));
        return true;
    }
    </script>
</body>
</html>