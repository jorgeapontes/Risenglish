<?php
session_start();
require_once '../includes/conexao.php';

// Verifica se a conexão PDO existe
if (!isset($pdo)) {
    die("Erro: Conexão com o banco de dados não estabelecida.");
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$nome_usuario = $_SESSION['user_nome'];

// Inicializa variáveis
$mensagem = '';
$tipo_mensagem = '';
$erro = '';

// Processar ações do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    try {
        if ($_POST['acao'] === 'adicionar') {
            $nome = trim($_POST['nome'] ?? '');
            $telefone = trim($_POST['telefone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $origem = $_POST['origem'] ?? 'Outro';
            $nivel_ingles = trim($_POST['nivel_ingles'] ?? '');
            $observacoes = trim($_POST['observacoes'] ?? '');
            $data_aula = !empty($_POST['data_aula_experimental']) ? $_POST['data_aula_experimental'] : null;
            // MELHORIA 1: Agora aceita a categoria escolhida pelo usuário
            $status = $_POST['status'] ?? 'novo';
            
            if (empty($nome)) {
                throw new Exception("O nome é obrigatório.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO leads (nome, telefone, email, origem, nivel_ingles, observacoes, data_aula_experimental, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $telefone, $email, $origem, $nivel_ingles, $observacoes, $data_aula, $status]);
            
            $mensagem = "Lead adicionado com sucesso!";
            $tipo_mensagem = "success";
        }
        
        elseif ($_POST['acao'] === 'mover') {
            $lead_id = intval($_POST['lead_id']);
            $novo_status = $_POST['novo_status'];
            
            $stmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
            $stmt->execute([$novo_status, $lead_id]);
            
            $mensagem = "Lead movido com sucesso!";
            $tipo_mensagem = "success";
        }
        
        elseif ($_POST['acao'] === 'editar') {
            $lead_id = intval($_POST['lead_id']);
            $nome = trim($_POST['nome'] ?? '');
            $telefone = trim($_POST['telefone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $origem = $_POST['origem'] ?? 'Outro';
            $nivel_ingles = trim($_POST['nivel_ingles'] ?? '');
            $observacoes = trim($_POST['observacoes'] ?? '');
            $data_aula = !empty($_POST['data_aula_experimental']) ? $_POST['data_aula_experimental'] : null;
            
            if (empty($nome)) {
                throw new Exception("O nome é obrigatório.");
            }
            
            $stmt = $pdo->prepare("UPDATE leads SET nome = ?, telefone = ?, email = ?, origem = ?, nivel_ingles = ?, observacoes = ?, data_aula_experimental = ? WHERE id = ?");
            $stmt->execute([$nome, $telefone, $email, $origem, $nivel_ingles, $observacoes, $data_aula, $lead_id]);
            
            $mensagem = "Lead atualizado com sucesso!";
            $tipo_mensagem = "success";
        }
        
        elseif ($_POST['acao'] === 'excluir') {
            $lead_id = intval($_POST['lead_id']);
            
            $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->execute([$lead_id]);
            
            $mensagem = "Lead excluído com sucesso!";
            $tipo_mensagem = "success";
        }
    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar leads por status
$status_list = ['novo', 'em_contato', 'aula_experimental', 'matriculado'];
$leads_por_status = [];

foreach ($status_list as $status) {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE status = ? ORDER BY data_criacao DESC");
    $stmt->execute([$status]);
    $leads_por_status[$status] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Configurações dos status
$status_config = [
    'novo' => ['titulo' => 'Novo', 'cor' => '#6c757d', 'bg' => '#f8f9fa', 'border' => '#6c757d', 'icone' => 'fa-plus-circle'],
    'em_contato' => ['titulo' => 'Em contato', 'cor' => '#ffc107', 'bg' => '#fff3cd', 'border' => '#ffc107', 'icone' => 'fa-phone'],
    'aula_experimental' => ['titulo' => 'Entrevista inicial agendada', 'cor' => '#17a2b8', 'bg' => '#d1ecf1', 'border' => '#17a2b8', 'icone' => 'fa-calendar-check'],
    'matriculado' => ['titulo' => 'Matriculados', 'cor' => '#28a745', 'bg' => '#d4edda', 'border' => '#28a745', 'icone' => 'fa-user-graduate']
];

$origens = ['Instagram', 'Facebook', 'Google', 'Indicação', 'Site', 'Outro'];
$niveis = ['Básico', 'Intermediário', 'Avançado', 'Não informado'];

// Calcular total de leads
$total_leads = 0;
foreach ($leads_por_status as $leads) {
    $total_leads += count($leads);
}

// MELHORIA 4: Mapeamento de ícones SVG por origem
function getOrigemIcone($origem) {
    $icones = [
        'Instagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="url(#igGrad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><defs><linearGradient id="igGrad" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" style="stop-color:#f09433"/><stop offset="25%" style="stop-color:#e6683c"/><stop offset="50%" style="stop-color:#dc2743"/><stop offset="75%" style="stop-color:#cc2366"/><stop offset="100%" style="stop-color:#bc1888"/></linearGradient></defs><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
        'Facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#1877F2" style="flex-shrink:0"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'Google'   => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 48 48" style="flex-shrink:0"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></svg>',
        'Indicação' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6f42c1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'Site'     => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0d6efd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'Outro'    => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    ];
    return $icones[$origem] ?? $icones['Outro'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads - Admin Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        :root {
            --cor-primaria: #0A1931;
            --cor-secundaria: #c0392b;
            --cor-destaque: #c0392b;
            --cor-texto: #333;
            --cor-fundo: #f8f9fa;
            --cor-borda: #dee2e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
        }

        /* Sidebar */
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

        .sidebar .active:hover {
            background-color: #c0392b;
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        /* Main content */
        .main-content {
            margin-left: 16.666667%;
            width: 83.333333%;
            padding: 30px;
            min-height: 100vh;
        }

        h1 {
            color: var(--cor-primaria);
            font-weight: 600;
        }

        /* Estilo Kanban */
        .kanban-board {
            display: flex;
            gap: 16px;
            padding-bottom: 20px;
            min-height: calc(100vh - 180px);
            align-items: flex-start;
        }

        .kanban-column {
            flex: 1 1 0;
            min-width: 220px;
            background-color: #e9ecef;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 180px);
            transition: background-color 0.2s;
        }

        /* MELHORIA 5: Estilos drag and drop */
        .kanban-column.drag-over {
            background-color: #d0e8ff;
            outline: 2px dashed #0d6efd;
        }

        .lead-card[draggable="true"] {
            cursor: grab;
        }

        .lead-card[draggable="true"]:active {
            cursor: grabbing;
        }

        .lead-card.dragging {
            opacity: 0.4;
            transform: rotate(2deg);
        }

        .kanban-header {
            padding: 15px;
            background-color: #fff;
            border-radius: 12px 12px 0 0;
            border-bottom: 3px solid;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kanban-header h3 {
            font-size: 0.95rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-count {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #fff;
            min-width: 26px;
            text-align: center;
        }

        .kanban-cards {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            overflow-x: visible;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 80px;
        }

        /* Garante que o dropdown não seja cortado pela coluna */
        .kanban-column {
            overflow: visible !important;
        }

        /* Menu 3 pontos customizado (sem Bootstrap dropdown para evitar clipping) */
        .lead-menu-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .lead-dropdown {
            display: none;
            position: fixed;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            list-style: none;
            padding: 4px 0;
            margin: 0;
            min-width: 200px;
            z-index: 9999;
        }

        .lead-dropdown.open {
            display: block;
        }

        .lead-dropdown li a {
            display: block;
            padding: 8px 16px;
            font-size: 0.85rem;
            color: #333;
            text-decoration: none;
            transition: background 0.15s;
            white-space: nowrap;
        }

        .lead-dropdown li a:hover {
            background-color: #f8f9fa;
        }

        .lead-dropdown li a.text-danger {
            color: #dc3545 !important;
        }

        .lead-dropdown li a.text-danger:hover {
            background-color: #fff5f5;
        }

        .lead-dropdown li.divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 4px 0;
        }

        /* Post-it Card */
        .lead-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border-left: 4px solid;
        }

        .lead-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .lead-nome {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 8px;
            color: #1a1a2e;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .lead-contato {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .lead-contato i {
            width: 16px;
            font-size: 0.7rem;
        }

        .lead-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 500;
            margin-top: 6px;
            margin-right: 4px;
        }

        .lead-observacoes {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #f0f0f0;
            font-style: italic;
        }

        .data-criacao {
            font-size: 0.65rem;
            color: #adb5bd;
            margin-top: 6px;
        }

        /* Modal */
        .modal-lead .modal-header {
            background-color: var(--cor-primaria);
            color: white;
        }

        .modal-lead .btn-close-white {
            filter: brightness(0) invert(1);
        }

        /* Botão flutuante */
        .btn-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--cor-secundaria);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s;
            z-index: 100;
        }

        .btn-floating:hover {
            transform: scale(1.05);
            background-color: #a93226;
            color: white;
        }

        /* Scrollbar */
        .kanban-cards::-webkit-scrollbar {
            width: 6px;
        }

        .kanban-cards::-webkit-scrollbar-track {
            background: #e9ecef;
            border-radius: 10px;
        }

        .kanban-cards::-webkit-scrollbar-thumb {
            background: #c0392b;
            border-radius: 10px;
        }

        .btn-acao {
            background: var(--cor-secundaria);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-acao:hover {
            background: #a93226;
            transform: translateY(-1px);
            color: white;
        }

        .btn-outline-acao {
            background: transparent;
            border: 1px solid var(--cor-secundaria);
            color: var(--cor-secundaria);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .btn-outline-acao:hover {
            background: var(--cor-secundaria);
            color: white;
        }

        /* MELHORIA 2: Área de clique maior no menu de 3 pontos */
        .lead-menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            color: #6c757d;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
            margin: -6px -4px -6px 4px;
        }

        .lead-menu-btn:hover {
            background-color: #e9ecef;
            color: #333;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .kanban-board {
                flex-direction: column;
                overflow-x: unset;
            }
            .kanban-column {
                width: 100%;
                min-width: auto;
                max-height: none;
            }
            .btn-floating {
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Admin'); ?></h5>
        </div>

        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" class="rounded active"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="personalizar_index.php" class="rounded"><i class="fas fa-paint-brush"></i>&nbsp;&nbsp;Personalizar Site</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
            <a href="acessos.php" class="rounded"><i class="fas fa-chart-line"></i>&nbsp;&nbsp;Relatório de Acessos</a>
        </div>

        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="mb-1"><i class="fas fa-users me-2 text-danger"></i>Leads</h1>
                <p class="text-muted mb-0">Gerencie seus contatos e alunos em potencial</p>
            </div>
            <button class="btn-acao" data-bs-toggle="modal" data-bs-target="#modalLead" onclick="openAddModal()">
                <i class="fas fa-plus me-2"></i>Novo Lead
            </button>
        </div>

        <!-- Mensagem de erro apenas (sucesso removido) -->
        <?php if ($mensagem && $tipo_mensagem === 'danger'): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <?php echo htmlspecialchars($mensagem); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Kanban Board -->
        <div class="kanban-board" id="kanbanBoard">
            <?php foreach ($status_list as $status): 
                $config = $status_config[$status];
                $leads = $leads_por_status[$status];
            ?>
            <!-- MELHORIA 5: data-status adicionado para drag and drop -->
            <div class="kanban-column" data-status="<?php echo $status; ?>">
                <div class="kanban-header" style="border-bottom-color: <?php echo $config['cor']; ?>">
                    <h3>
                        <i class="fas <?php echo $config['icone']; ?>" style="color: <?php echo $config['cor']; ?>; font-size: 12px;"></i>
                        <?php echo $config['titulo']; ?>
                    </h3>
                    <span class="badge-count" id="count-<?php echo $status; ?>" style="background-color: <?php echo $config['cor']; ?>;"><?php echo count($leads); ?></span>
                </div>
                <div class="kanban-cards" id="cards-<?php echo $status; ?>">
                    <?php if (empty($leads)): ?>
                    <div class="text-center text-muted py-4 empty-placeholder">
                        <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i>
                        <p class="small mb-0">Nenhum lead nesta coluna</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($leads as $lead): ?>
                        <!-- MELHORIA 5: draggable="true" + data attributes para drag and drop -->
                        <div class="lead-card"
                             draggable="true"
                             data-lead-id="<?php echo $lead['id']; ?>"
                             data-current-status="<?php echo $lead['status']; ?>"
                             style="border-left-color: <?php echo $config['cor']; ?>;"
                             onclick="openEditModal(<?php echo htmlspecialchars(json_encode($lead)); ?>)">
                            <div class="lead-nome">
                                <?php echo htmlspecialchars($lead['nome']); ?>
                                <!-- Menu 3 pontos com posicionamento manual -->
                                <div class="lead-menu-wrapper" onclick="event.stopPropagation()">
                                    <div class="lead-menu-btn" onclick="toggleLeadMenu(this, <?php echo $lead['id']; ?>)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </div>
                                    <ul class="lead-dropdown" id="menu-<?php echo $lead['id']; ?>">
                                        <li><a href="#" onclick="event.preventDefault(); closeAllMenus(); openEditModal(<?php echo htmlspecialchars(json_encode($lead)); ?>)"><i class="fas fa-edit me-2"></i>Editar</a></li>
                                        <li class="divider"></li>
                                        <?php foreach ($status_list as $novo_status):
                                            if ($novo_status !== $lead['status']):
                                        ?>
                                        <li><a href="#" onclick="event.preventDefault(); closeAllMenus(); moverLead(<?php echo $lead['id']; ?>, '<?php echo $novo_status; ?>')">
                                            <i class="fas fa-arrow-right me-2"></i>Mover para <?php echo $status_config[$novo_status]['titulo']; ?>
                                        </a></li>
                                        <?php endif; endforeach; ?>
                                        <li class="divider"></li>
                                        <li><a href="#" class="text-danger" onclick="event.preventDefault(); closeAllMenus(); excluirLead(<?php echo $lead['id']; ?>, '<?php echo htmlspecialchars(addslashes($lead['nome'])); ?>')"><i class="fas fa-trash-alt me-2"></i>Excluir</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="lead-contato">
                                <?php if ($lead['telefone']): ?>
                                <span><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($lead['telefone']); ?></span>
                                <?php endif; ?>
                                <?php if ($lead['email']): ?>
                                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($lead['email']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($lead['origem']): ?>
                                <!-- MELHORIA 4: Ícone SVG por origem -->
                                <span class="lead-badge" style="background-color: #e7f1ff; color: #0d6efd;">
                                    <?php echo getOrigemIcone($lead['origem']); ?>
                                    <?php echo htmlspecialchars($lead['origem']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($lead['nivel_ingles']): ?>
                                <span class="lead-badge" style="background-color: #f8f9fa; color: #6c757d;">
                                    <i class="fas fa-language"></i> <?php echo htmlspecialchars($lead['nivel_ingles']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($lead['status'] === 'aula_experimental' && $lead['data_aula_experimental']): ?>
                                <span class="lead-badge" style="background-color: #d1ecf1; color: #0c5460;">
                                    <i class="fas fa-calendar-check"></i> <?php echo date('d/m/Y', strtotime($lead['data_aula_experimental'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($lead['observacoes']): ?>
                            <div class="lead-observacoes">
                                <i class="fas fa-sticky-note me-1"></i> <?php echo nl2br(htmlspecialchars(substr($lead['observacoes'], 0, 80))); ?>
                                <?php if (strlen($lead['observacoes']) > 80): ?>...<?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="data-criacao">
                                <i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($lead['data_criacao'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Botão Flutuante -->
<button class="btn-floating" data-bs-toggle="modal" data-bs-target="#modalLead" onclick="openAddModal()">
    <i class="fas fa-plus fa-lg"></i>
</button>

<!-- Modal de Lead -->
<div class="modal fade modal-lead" id="modalLead" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formLead">
                <input type="hidden" name="acao" id="formAcao" value="adicionar">
                <input type="hidden" name="lead_id" id="leadId" value="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i><span id="modalTitle">Novo Lead</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="nome" id="leadNome" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="telefone" id="leadTelefone" class="form-control" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" id="leadEmail" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Origem</label>
                            <select name="origem" id="leadOrigem" class="form-select">
                                <?php foreach ($origens as $origem): ?>
                                <option value="<?php echo $origem; ?>"><?php echo $origem; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nível de Inglês</label>
                            <select name="nivel_ingles" id="leadNivel" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach ($niveis as $nivel): ?>
                                <option value="<?php echo $nivel; ?>"><?php echo $nivel; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- MELHORIA 1: Campo de categoria ao criar lead -->
                    <div class="mb-3" id="campoStatus">
                        <label class="form-label">Categoria</label>
                        <select name="status" id="leadStatus" class="form-select" onchange="toggleDataAula()">
                            <?php foreach ($status_config as $key => $cfg): ?>
                            <option value="<?php echo $key; ?>"><?php echo $cfg['titulo']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- MELHORIA 3: Campo data só aparece quando categoria = aula_experimental -->
                    <div class="mb-3" id="campoDataAula" style="display:none;">
                        <label class="form-label">Data da Aula Experimental</label>
                        <input type="date" name="data_aula_experimental" id="leadDataAula" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" id="leadObservacoes" class="form-control" rows="3" placeholder="Anotações sobre o contato..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-acao">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// MELHORIA 3: Mostra/oculta campo de data conforme categoria
function toggleDataAula() {
    var status = document.getElementById('leadStatus').value;
    var campo = document.getElementById('campoDataAula');
    if (status === 'aula_experimental') {
        campo.style.display = 'block';
    } else {
        campo.style.display = 'none';
        document.getElementById('leadDataAula').value = '';
    }
}

function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Novo Lead';
    document.getElementById('formAcao').value = 'adicionar';
    document.getElementById('leadId').value = '';
    document.getElementById('leadNome').value = '';
    document.getElementById('leadTelefone').value = '';
    document.getElementById('leadEmail').value = '';
    document.getElementById('leadOrigem').value = 'Outro';
    document.getElementById('leadNivel').value = '';
    document.getElementById('leadDataAula').value = '';
    document.getElementById('leadObservacoes').value = '';
    // MELHORIA 1: Mostra campo de categoria ao adicionar
    document.getElementById('campoStatus').style.display = 'block';
    document.getElementById('leadStatus').value = 'novo';
    toggleDataAula();
}

function openEditModal(lead) {
    document.getElementById('modalTitle').innerText = 'Editar Lead';
    document.getElementById('formAcao').value = 'editar';
    document.getElementById('leadId').value = lead.id;
    document.getElementById('leadNome').value = lead.nome || '';
    document.getElementById('leadTelefone').value = lead.telefone || '';
    document.getElementById('leadEmail').value = lead.email || '';
    document.getElementById('leadOrigem').value = lead.origem || 'Outro';
    document.getElementById('leadNivel').value = lead.nivel_ingles || '';
    document.getElementById('leadDataAula').value = lead.data_aula_experimental || '';
    document.getElementById('leadObservacoes').value = lead.observacoes || '';
    // Ao editar, oculta o campo de categoria (mantém status atual)
    document.getElementById('campoStatus').style.display = 'none';
    // MELHORIA 3: Mostra/oculta data conforme status atual
    var campo = document.getElementById('campoDataAula');
    if (lead.status === 'aula_experimental') {
        campo.style.display = 'block';
    } else {
        campo.style.display = 'none';
    }

    const modal = new bootstrap.Modal(document.getElementById('modalLead'));
    modal.show();
}

function moverLead(leadId, novoStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const inputAcao = document.createElement('input');
    inputAcao.type = 'hidden';
    inputAcao.name = 'acao';
    inputAcao.value = 'mover';
    
    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'lead_id';
    inputId.value = leadId;
    
    const inputStatus = document.createElement('input');
    inputStatus.type = 'hidden';
    inputStatus.name = 'novo_status';
    inputStatus.value = novoStatus;
    
    form.appendChild(inputAcao);
    form.appendChild(inputId);
    form.appendChild(inputStatus);
    document.body.appendChild(form);
    form.submit();
}

function excluirLead(leadId, leadNome) {
    if (confirm(`Tem certeza que deseja excluir o lead "${leadNome}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const inputAcao = document.createElement('input');
        inputAcao.type = 'hidden';
        inputAcao.name = 'acao';
        inputAcao.value = 'excluir';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'lead_id';
        inputId.value = leadId;
        
        form.appendChild(inputAcao);
        form.appendChild(inputId);
        document.body.appendChild(form);
        form.submit();
    }
}

// =============================================
// Menu 3 pontos customizado (posicionamento via viewport)
// =============================================
function closeAllMenus() {
    document.querySelectorAll('.lead-dropdown.open').forEach(m => m.classList.remove('open'));
}

function toggleLeadMenu(btn, leadId) {
    const menu = document.getElementById('menu-' + leadId);
    const isOpen = menu.classList.contains('open');

    closeAllMenus();
    if (isOpen) return;

    const rect = btn.getBoundingClientRect();
    menu.classList.add('open');

    // Posiciona abaixo do botão alinhado à direita
    let top = rect.bottom + 4;
    let left = rect.right - menu.offsetWidth;

    // Se sair pela direita da viewport
    if (left < 8) left = 8;
    // Se sair pela parte inferior da viewport
    if (top + menu.offsetHeight > window.innerHeight - 8) {
        top = rect.top - menu.offsetHeight - 4;
    }

    menu.style.top = top + 'px';
    menu.style.left = left + 'px';
}

// Fecha ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('.lead-menu-wrapper')) {
        closeAllMenus();
    }
});

// =============================================
// =============================================
(function() {
    let draggedCard = null;
    let sourceColumn = null;
    let dropIndicator = null;

    // Cria o indicador de posição de drop
    function createDropIndicator() {
        const el = document.createElement('div');
        el.id = 'drop-indicator';
        el.style.cssText = 'height:3px;background:#0d6efd;border-radius:4px;margin:4px 0;transition:none;pointer-events:none;';
        return el;
    }

    function removeDropIndicator() {
        if (dropIndicator && dropIndicator.parentNode) {
            dropIndicator.parentNode.removeChild(dropIndicator);
        }
    }

    // Retorna o card mais próximo de onde o mouse está (para reordenação)
    function getCardAfterCursor(container, y) {
        const cards = [...container.querySelectorAll('.lead-card:not(.dragging)')];
        return cards.reduce((closest, card) => {
            const box = card.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset, element: card };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function updateCount(columnEl) {
        const status = columnEl.dataset.status;
        const count = columnEl.querySelectorAll('.lead-card').length;
        const badge = document.getElementById('count-' + status);
        if (badge) badge.textContent = count;

        const cards = columnEl.querySelector('.kanban-cards');
        let placeholder = cards.querySelector('.empty-placeholder');
        if (count === 0) {
            if (!placeholder) {
                placeholder = document.createElement('div');
                placeholder.className = 'text-center text-muted py-4 empty-placeholder';
                placeholder.innerHTML = '<i class="fas fa-inbox fa-2x mb-2 opacity-50"></i><p class="small mb-0">Nenhum lead nesta coluna</p>';
                cards.appendChild(placeholder);
            }
        } else {
            if (placeholder) placeholder.remove();
        }
    }

    function initDragAndDrop() {
        document.querySelectorAll('.lead-card[draggable="true"]').forEach(card => {
            card.addEventListener('dragstart', function(e) {
                draggedCard = this;
                sourceColumn = this.closest('.kanban-column');
                setTimeout(() => this.classList.add('dragging'), 0);
                e.dataTransfer.effectAllowed = 'move';
                if (!dropIndicator) dropIndicator = createDropIndicator();
            });

            card.addEventListener('dragend', function() {
                this.classList.remove('dragging');
                removeDropIndicator();
                draggedCard = null;
                sourceColumn = null;
                document.querySelectorAll('.kanban-column').forEach(col => col.classList.remove('drag-over'));
            });
        });

        document.querySelectorAll('.kanban-column').forEach(column => {
            column.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');

                const cardsContainer = this.querySelector('.kanban-cards');
                const afterCard = getCardAfterCursor(cardsContainer, e.clientY);

                removeDropIndicator();
                if (!dropIndicator) dropIndicator = createDropIndicator();

                if (afterCard) {
                    cardsContainer.insertBefore(dropIndicator, afterCard);
                } else {
                    cardsContainer.appendChild(dropIndicator);
                }
            });

            column.addEventListener('dragleave', function(e) {
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drag-over');
                    removeDropIndicator();
                }
            });

            column.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                if (!draggedCard) return;

                const targetColumn = this;
                const newStatus = targetColumn.dataset.status;
                const oldStatus = draggedCard.dataset.currentStatus;
                const cardsContainer = targetColumn.querySelector('.kanban-cards');
                const afterCard = getCardAfterCursor(cardsContainer, e.clientY);

                removeDropIndicator();

                // Insere o card na posição correta
                if (afterCard) {
                    cardsContainer.insertBefore(draggedCard, afterCard);
                } else {
                    cardsContainer.appendChild(draggedCard);
                }

                // Atualiza cor da borda e status se mudou de coluna
                if (newStatus !== oldStatus) {
                    const statusColors = {
                        'novo': '#6c757d',
                        'em_contato': '#ffc107',
                        'aula_experimental': '#17a2b8',
                        'matriculado': '#28a745'
                    };
                    draggedCard.style.borderLeftColor = statusColors[newStatus] || '#6c757d';
                    draggedCard.dataset.currentStatus = newStatus;

                    updateCount(sourceColumn);
                    updateCount(targetColumn);

                    // Persiste mudança de coluna no servidor
                    const leadId = draggedCard.dataset.leadId;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    [['acao', 'mover'], ['lead_id', leadId], ['novo_status', newStatus]].forEach(([n, v]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = n;
                        input.value = v;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                }
                // Se for mesma coluna, apenas reordenou visualmente (sem submit necessário)
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initDragAndDrop);
})();
</script>
</body>
</html>