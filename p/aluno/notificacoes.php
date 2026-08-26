<?php
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';

// Garante que apenas aluno acessa esta página
if ($_SESSION['user_tipo'] !== 'aluno') {
    header("Location: ../login?erro=acesso_negado");
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
    header("Location: notificacoes");
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
    <link rel="stylesheet" href="../../css/aluno/dashboard.css">
    <link rel="stylesheet" href="../../css/aluno/notificacoes.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php
            $paginaAtiva = 'notificacoes';
            $alunoNome = $user_nome;
            $totalNotificacoesNaoLidas = $total_nao_lidas;
            $tituloMobile = 'Notificações';
            require '../includes/layout/aluno_sidebar.php';
            ?>

            <!-- Conteúdo principal -->
            <div class="col-12 col-md-10 main-content">
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
                            // Montar href de forma segura e consistente para o aluno
                            $href = '';
                            $aluno_root = '/p/aluno/';
                            // Preferir campo aula_id (quando presente na tabela)
                            if (!empty($notif['aula_id'])) {
                                $href = $aluno_root . 'detalhes_aula?id=' . intval($notif['aula_id']);
                            } else {
                                $link_raw = $notif['link'] ?? '';
                                // Tentar extrair id do query string (id ou aula_id)
                                if (preg_match('/[?&](?:id|aula_id)=(\d+)/', $link_raw, $m)) {
                                    $href = $aluno_root . 'detalhes_aula?id=' . intval($m[1]);
                                } else {
                                    // Se já é um link absoluto completo, manter
                                    if (preg_match('#^https?://#', $link_raw)) {
                                        $href = $link_raw;
                                    } elseif (strpos($link_raw, '/') === 0) {
                                        // Caminho absoluto no servidor: garantir prefixo /Risenglish
                                        if (strpos($link_raw, '/Risenglish/') === 0) {
                                            $href = $link_raw;
                                        } else {
                                            $href = '/Risenglish' . $link_raw;
                                        }
                                    } else {
                                        // Relativo: prefixar com a pasta do aluno
                                        $href = $aluno_root . ltrim($link_raw, './');
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
        fetch('ajax_notificacoes', {
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