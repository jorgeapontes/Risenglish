<?php
session_start();
require_once '../includes/conexao.php';

// Definir fuso horário do Brasil
date_default_timezone_set('America/Sao_Paulo');

// Bloqueio de acesso para usuários não-professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$mensagem = '';
$sucesso = false;

// --- FUNÇÕES AUXILIARES PARA AULAS RECORRENTES ---
function gerarDatasRecorrentes($data_inicio, $dia_semana, $quantidade_semanas = 4) {
    $datas = [];
    $data_atual = new DateTime($data_inicio);
    $data_atual->setTime(0, 0, 0);
    
    $data_atual->modify('next ' . $dia_semana);
    
    for ($i = 0; $i < $quantidade_semanas; $i++) {
        $datas[] = $data_atual->format('Y-m-d');
        $data_atual->modify('+1 week');
    }
    
    return $datas;
}

function diaSemanaParaPortugues($dia_ingles) {
    $dias = [
        'monday' => 'Segunda-feira',
        'tuesday' => 'Terça-feira',
        'wednesday' => 'Quarta-feira',
        'thursday' => 'Quinta-feira',
        'friday' => 'Sexta-feira',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo'
    ];
    return $dias[strtolower($dia_ingles)] ?? $dia_ingles;
}

function renderTimePicker($id_prefix, $currentTime = '09:00') {
    $parts = explode(':', $currentTime);
    $h_sel = isset($parts[0]) ? $parts[0] : '09';
    $m_sel = isset($parts[1]) ? $parts[1] : '00';
    ?>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select time-hour" data-prefix="<?= $id_prefix ?>" style="width: 80px;" onchange="atualizarHorario('<?= $id_prefix ?>')">
            <?php for($i=0; $i<24; $i++): 
                $v = sprintf("%02d", $i); ?>
                <option value="<?= $v ?>" <?= $v == $h_sel ? 'selected' : '' ?>><?= $v ?></option>
            <?php endfor; ?>
        </select>
        <strong>:</strong>
        <select class="form-select time-minute" data-prefix="<?= $id_prefix ?>" style="width: 80px;" onchange="atualizarHorario('<?= $id_prefix ?>')">
            <?php for($i=0; $i<60; $i+=5): 
                $v = sprintf("%02d", $i); ?>
                <option value="<?= $v ?>" <?= $v == $m_sel ? 'selected' : '' ?>><?= $v ?></option>
            <?php endfor; ?>
        </select>
        <input type="hidden" name="horario" id="real_time_<?= $id_prefix ?>" value="<?= $currentTime ?>">
    </div>
    <?php
}

// --- PROCESSAMENTO DAS AÇÕES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Aula Única
    if ($acao === 'adicionar_unica') {
        $titulo_aula = trim($_POST['titulo_aula']);
        $descricao = trim($_POST['descricao']);
        $data_aula = $_POST['data_aula'];
        $horario = $_POST['horario'];
        $turma_id = $_POST['turma_id'];

        if (empty($titulo_aula) || empty($data_aula) || empty($horario) || empty($turma_id)) {
            $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            try {
                $sql = "INSERT INTO aulas (professor_id, titulo_aula, descricao, data_aula, horario, turma_id, recorrente) 
                        VALUES (:professor_id, :titulo_aula, :descricao, :data_aula, :horario, :turma_id, 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':professor_id' => $professor_id,
                    ':titulo_aula' => $titulo_aula,
                    ':descricao' => $descricao,
                    ':data_aula' => $data_aula,
                    ':horario' => $horario,
                    ':turma_id' => $turma_id
                ]);
                
                $mensagem = "Aula única agendada com sucesso!";
                $sucesso = true;
                header("Location: gerenciar_aulas.php?sucesso=1");
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro ao agendar a aula: " . $e->getMessage();
            }
        }
    }
    
    // Aulas Recorrentes
    if ($acao === 'adicionar_recorrente') {
        $turma_id = $_POST['turma_id'];
        $descricao = trim($_POST['descricao']);
        $dia_semana = $_POST['dia_semana'];
        $horario = $_POST['horario'];
        $quantidade_semanas = (int)$_POST['quantidade_semanas'];
        
        $sql_turma = "SELECT nome_turma FROM turmas WHERE id = :turma_id AND professor_id = :professor_id";
        $stmt_turma = $pdo->prepare($sql_turma);
        $stmt_turma->execute([':turma_id' => $turma_id, ':professor_id' => $professor_id]);
        $turma = $stmt_turma->fetch(PDO::FETCH_ASSOC);
        
        $titulo_aula = "Aulas " . ($turma['nome_turma'] ?? 'da Turma');

        if (empty($turma_id) || empty($dia_semana) || empty($horario)) {
            $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            try {
                $data_inicio = date('Y-m-d');
                $datas_aulas = gerarDatasRecorrentes($data_inicio, $dia_semana, $quantidade_semanas);
                $aulas_criadas = 0;
                
                foreach ($datas_aulas as $data_aula) {
                    $sql = "INSERT INTO aulas (professor_id, titulo_aula, descricao, data_aula, horario, turma_id, recorrente, dia_semana) 
                            VALUES (:professor_id, :titulo_aula, :descricao, :data_aula, :horario, :turma_id, 1, :dia_semana)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':professor_id' => $professor_id,
                        ':titulo_aula' => $titulo_aula,
                        ':descricao' => $descricao,
                        ':data_aula' => $data_aula,
                        ':horario' => $horario,
                        ':turma_id' => $turma_id,
                        ':dia_semana' => $dia_semana
                    ]);
                    $aulas_criadas++;
                }
                
                $mensagem = "{$aulas_criadas} aulas recorrentes agendadas com sucesso!";
                $sucesso = true;
                header("Location: gerenciar_aulas.php?sucesso=1");
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro ao agendar as aulas recorrentes: " . $e->getMessage();
            }
        }
    }
    
    // Editar Aula
    if ($acao === 'editar') {
        $titulo_aula = trim($_POST['titulo_aula']);
        $descricao = trim($_POST['descricao']);
        $data_aula = $_POST['data_aula'];
        $horario = $_POST['horario'];
        $turma_id = $_POST['turma_id'];
        $aula_id = $_POST['aula_id'];

        if (empty($titulo_aula) || empty($data_aula) || empty($horario) || empty($turma_id)) {
            $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            try {
                $sql = "UPDATE aulas SET titulo_aula = :titulo_aula, descricao = :descricao, data_aula = :data_aula, horario = :horario, turma_id = :turma_id 
                        WHERE id = :id AND professor_id = :professor_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $aula_id,
                    ':professor_id' => $professor_id,
                    ':titulo_aula' => $titulo_aula,
                    ':descricao' => $descricao,
                    ':data_aula' => $data_aula,
                    ':horario' => $horario,
                    ':turma_id' => $turma_id
                ]);
                
                $mensagem = "Aula atualizada com sucesso!";
                $sucesso = true;
                header("Location: gerenciar_aulas.php?sucesso=1");
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro ao atualizar a aula: " . $e->getMessage();
            }
        }
    }
    
    // Excluir Aula
    if ($acao === 'excluir') {
        $aula_id = isset($_POST['aula_id']) ? intval($_POST['aula_id']) : 0;

        if (empty($aula_id) || $aula_id <= 0) {
            $mensagem = "ID da aula inválido.";
        } else {
            try {
                $sql_delete_ac = "DELETE FROM aulas_conteudos WHERE aula_id = :aula_id";
                $pdo->prepare($sql_delete_ac)->execute([':aula_id' => $aula_id]);
                
                $sql_delete = "DELETE FROM aulas WHERE id = :id AND professor_id = :professor_id";
                $stmt_delete = $pdo->prepare($sql_delete);
                $stmt_delete->execute([':id' => $aula_id, ':professor_id' => $professor_id]);

                if ($stmt_delete->rowCount() > 0) {
                    $mensagem = "Aula excluída com sucesso!";
                    $sucesso = true;
                } else {
                    $mensagem = "Aula não encontrada ou você não tem permissão para excluí-la.";
                }
                
                header("Location: gerenciar_aulas.php?sucesso=1");
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro ao excluir a aula: " . $e->getMessage();
            }
        }
    }
}

// Verificar mensagem de sucesso na URL
if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1) {
    $mensagem = "Operação realizada com sucesso!";
    $sucesso = true;
}

// --- CONSULTAS ---
$data_atual = date('Y-m-d');
$hora_atual = date('H:i:s');

// Lista de Aulas
$sql_aulas = "
    SELECT 
        a.id, a.titulo_aula, a.data_aula, a.horario, a.descricao, t.nome_turma,
        t.id as turma_id
    FROM 
        aulas a
    JOIN 
        turmas t ON a.turma_id = t.id
    WHERE 
        a.professor_id = :professor_id
        AND (
            a.data_aula > :data_atual
            OR (a.data_aula = :data_atual AND a.horario >= :hora_atual)
        )
    ORDER BY 
        a.data_aula ASC, a.horario ASC
";
$stmt_aulas = $pdo->prepare($sql_aulas);
$stmt_aulas->execute([
    ':professor_id' => $professor_id,
    ':data_atual' => $data_atual,
    ':hora_atual' => $hora_atual
]);
$lista_aulas = $stmt_aulas->fetchAll(PDO::FETCH_ASSOC);

// Lista de Turmas
$sql_turmas = "SELECT id, nome_turma FROM turmas WHERE professor_id = :professor_id ORDER BY nome_turma ASC";
$stmt_turmas = $pdo->prepare($sql_turmas);
$stmt_turmas->execute([':professor_id' => $professor_id]);
$lista_turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);

// Notificações
$sql_notificacoes = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = :professor_id AND lida = 0";
$stmt_notif = $pdo->prepare($sql_notificacoes);
$stmt_notif->execute([':professor_id' => $professor_id]);
$total_notificacoes_nao_lidas = $stmt_notif->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Aulas - Professor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        body { background-color: #FAF9F6; overflow-x: hidden; }
        .sidebar { position: fixed; left: 0; top: 0; height: 100vh; width: 16.666667%; background-color: #081d40; color: #fff; z-index: 1000; overflow-y: auto; }
        .sidebar a { color: #fff; text-decoration: none; display: block; padding: 10px 15px; margin-bottom: 5px; border-radius: 5px; transition: 0.3s; }
        .sidebar a:hover { background-color: rgba(255,255,255,0.1); transform: translateX(3px); }
        .sidebar .active { background-color: #c0392b; }
        .main-content { margin-left: 16.666667%; width: 83.333333%; min-height: 100vh; overflow-y: auto; padding: 20px; }
        .card-header { background-color: #081d40; color: white; }
        .btn-danger { background-color: #c0392b; border-color: #c0392b; }
        .btn-danger:hover { background-color: #a93226; border-color: #a93226; }
        .btn-success { background-color: #28a745; border-color: #28a745; }
        .btn-success:hover { background-color: #218838; border-color: #218838; }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-primary:hover { background-color: #0069d9; border-color: #0062cc; }
        #botao-sair { border: none; }
        #botao-sair:hover { background-color: #c0392b; color: white; }
        .table th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; }
        .btn-info-detalhes { background-color: #099410ff; border-color: #099410ff; color: white; }
        .btn-info-detalhes:hover { background-color: #087e0eff; border-color: #087e0eff; color: white; }
        @media (max-width: 768px) { .sidebar { position: relative; width: 100%; height: auto; } .main-content { margin-left: 0; width: 100%; } }
        .btn-group-agendar .btn { margin-right: 10px; }
        .preview-datas { max-height: 100px; overflow-y: auto; font-size: 0.85rem; padding: 8px; background-color: #f8f9fa; border: 1px solid #ced4da; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 d-flex flex-column sidebar p-3">
                <div class="mb-4 text-center">
                    <h5 class="mt-4">Prof. <?php echo $_SESSION['user_nome'] ?? 'Professor'; ?></h5>
                </div>

                <div class="d-flex flex-column flex-grow-1 mb-5">
                    <a href="notificacoes.php" class="rounded position-relative">
                        <i class="fas fa-bell"></i>&nbsp;&nbsp;Notificações
                        <?php if ($total_notificacoes_nao_lidas > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $total_notificacoes_nao_lidas ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
                    <a href="gerenciar_aulas.php" class="rounded active"><i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;&nbsp;Aulas</a>
                    <a href="gerenciar_conteudos.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Conteúdos</a>
                    <a href="gerenciar_alunos.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;Alunos/Turmas</a>
                </div>

                <div class="mt-auto">
                    <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between">
                    <h2 class="mb-4 mt-3">Agendamento e Gerenciamento de Aulas</h2>
                </div>
                
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= $mensagem ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Botões de Agendamento -->
                <div class="card rounded mb-4">
                    <div class="card-header text-white">
                        Agendar Nova Aula
                    </div>
                    <div class="card-body">
                        <div class="btn-group-agendar">
                            <button class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#modalAulaUnica">
                                <i class="fas fa-calendar-day me-2"></i> Aula Única
                            </button>
                            <button class="btn btn-success mb-2" data-bs-toggle="modal" data-bs-target="#modalAulaRecorrente">
                                <i class="fas fa-calendar-week me-2"></i> Aula Recorrente
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Aulas -->
                <div class="card rounded">
                    <div class="card-header text-white">
                        Próximas Aulas Agendadas
                    </div>
                    <div class="card-body p-0 rounded">
                        <?php if (empty($lista_aulas)): ?>
                            <p class="p-4 text-center text-muted">Nenhuma aula agendada ainda ou todas já aconteceram.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0 rounded">
                                    <thead>
                                        <tr>
                                            <th>Data / Horário</th>
                                            <th>Título da Aula</th>
                                            <th>Turma</th>
                                            <th style="width: 170px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lista_aulas as $aula): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($aula['data_aula'])) ?> às <?= substr($aula['horario'], 0, 5) ?></td>
                                                <td><?= htmlspecialchars($aula['titulo_aula']) ?></td>
                                                <td><?= htmlspecialchars($aula['nome_turma']) ?></td>
                                                <td>
                                                    <a href="detalhes_aula.php?aula_id=<?= $aula['id'] ?>" class="btn btn-sm btn-info-detalhes" title="Ver Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <button class="btn btn-sm btn-primary" 
                                                            onclick="abrirModalEdicao(<?= $aula['id'] ?>, <?= $aula['turma_id'] ?>, '<?= htmlspecialchars(addslashes($aula['titulo_aula'])) ?>', '<?= $aula['data_aula'] ?>', '<?= substr($aula['horario'], 0, 5) ?>', '<?= htmlspecialchars(addslashes($aula['descricao'] ?? '')) ?>')"
                                                            title="Editar Aula">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $aula['id'] ?>)" title="Excluir Aula">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Aula Única -->
    <div class="modal fade" id="modalAulaUnica" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                    <h5 class="modal-title">Agendar Aula Única</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="adicionar_unica">
                        <div class="mb-3">
                            <label class="form-label">Turma *</label>
                            <select class="form-select" name="turma_id" required>
                                <option value="">Selecione a Turma</option>
                                <?php foreach ($lista_turmas as $turma): ?>
                                    <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome_turma']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Título da Aula *</label>
                            <input type="text" class="form-control" name="titulo_aula" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data *</label>
                                <input type="date" class="form-control" name="data_aula" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Horário *</label>
                                <?php renderTimePicker('unica', '09:00'); ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Agendar Aula</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Aula Recorrente -->
    <div class="modal fade" id="modalAulaRecorrente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                    <h5 class="modal-title">Agendar Aulas Recorrentes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="adicionar_recorrente">
                        <div class="mb-3">
                            <label class="form-label">Turma *</label>
                            <select class="form-select" id="turma_id_recorrente" name="turma_id" required>
                                <option value="">Selecione a Turma</option>
                                <?php foreach ($lista_turmas as $turma): ?>
                                    <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome_turma']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Título das Aulas</label>
                            <input type="text" class="form-control" id="titulo_aula_recorrente" value="Aulas da Turma" readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dia da Semana *</label>
                                <select class="form-select" id="dia_semana" name="dia_semana" required>
                                    <option value="monday">Segunda-feira</option>
                                    <option value="tuesday">Terça-feira</option>
                                    <option value="wednesday">Quarta-feira</option>
                                    <option value="thursday">Quinta-feira</option>
                                    <option value="friday">Sexta-feira</option>
                                    <option value="saturday">Sábado</option>
                                    <option value="sunday">Domingo</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Horário *</label>
                                <?php renderTimePicker('recorrente', '09:00'); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de Semanas *</label>
                                <select class="form-select" id="quantidade_semanas" name="quantidade_semanas" required>
                                    <option value="2">2 semanas</option>
                                    <option value="4" selected>4 semanas</option>
                                    <option value="8">8 semanas</option>
                                    <option value="12">12 semanas</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Próximas Datas</label>
                                <div id="preview_datas" class="preview-datas">Selecione o dia da semana</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Agendar Aulas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Aula -->
    <div class="modal fade" id="modalEditarAula" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                    <h5 class="modal-title">Editar Aula</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEditarAula">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="aula_id" id="edit_aula_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Turma *</label>
                            <select class="form-select" id="edit_turma_id" name="turma_id" required>
                                <option value="">Selecione a Turma</option>
                                <?php foreach ($lista_turmas as $turma): ?>
                                    <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome_turma']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Título da Aula *</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo_aula" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data *</label>
                                <input type="date" class="form-control" id="edit_data" name="data_aula" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Horário *</label>
                                <div class="d-flex align-items-center gap-2">
                                    <select class="form-select" id="edit_hora" style="width: 80px;">
                                        <?php for($i=0; $i<24; $i++): 
                                            $v = sprintf("%02d", $i); ?>
                                            <option value="<?= $v ?>"><?= $v ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <strong>:</strong>
                                    <select class="form-select" id="edit_minuto" style="width: 80px;">
                                        <?php for($i=0; $i<60; $i+=5): 
                                            $v = sprintf("%02d", $i); ?>
                                            <option value="<?= $v ?>"><?= $v ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <input type="hidden" name="horario" id="edit_horario">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Atualizar Aula</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Excluir -->
    <div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p>Tem certeza que deseja excluir esta aula?</p>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="aula_id" id="aula_id_excluir">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Função para atualizar o horário
    function atualizarHorario(prefix) {
        const h = document.querySelector(`.time-hour[data-prefix="${prefix}"]`).value;
        const m = document.querySelector(`.time-minute[data-prefix="${prefix}"]`).value;
        document.getElementById(`real_time_${prefix}`).value = h + ':' + m;
    }

    // Função para confirmar exclusão
    function confirmarExclusao(aulaId) {
        document.getElementById('aula_id_excluir').value = aulaId;
        const modalExcluir = new bootstrap.Modal(document.getElementById('modalExcluir'));
        modalExcluir.show();
    }

    // Função para abrir modal de edição
    function abrirModalEdicao(id, turmaId, titulo, data, horario, descricao) {
        // Preencher os campos
        document.getElementById('edit_aula_id').value = id;
        document.getElementById('edit_turma_id').value = turmaId;
        document.getElementById('edit_titulo').value = titulo;
        document.getElementById('edit_data').value = data;
        document.getElementById('edit_descricao').value = descricao;
        
        // Preencher hora e minuto
        const parts = horario.split(':');
        document.getElementById('edit_hora').value = parts[0];
        document.getElementById('edit_minuto').value = parts[1];
        
        // Atualizar o campo hidden com o horário completo
        document.getElementById('edit_horario').value = horario;
        
        // Abrir o modal
        const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarAula'));
        modalEditar.show();
    }

    // Atualizar o campo hidden quando hora ou minuto mudar
    document.getElementById('edit_hora')?.addEventListener('change', atualizarHorarioEdicao);
    document.getElementById('edit_minuto')?.addEventListener('change', atualizarHorarioEdicao);

    function atualizarHorarioEdicao() {
        const hora = document.getElementById('edit_hora').value;
        const minuto = document.getElementById('edit_minuto').value;
        document.getElementById('edit_horario').value = hora + ':' + minuto;
    }

    $(document).ready(function() {
        // Inicializar todos os horários
        $('.time-hour, .time-minute').each(function() {
            const prefix = $(this).data('prefix');
            atualizarHorario(prefix);
        });

        // Preview de datas para aula recorrente
        $('#turma_id_recorrente, #dia_semana, #quantidade_semanas').change(function() {
            const turmaId = $('#turma_id_recorrente').val();
            const diaSemana = $('#dia_semana').val();
            const quantidade = parseInt($('#quantidade_semanas').val());
            
            if (turmaId && diaSemana) {
                const turmaNome = $('#turma_id_recorrente option:selected').text();
                $('#titulo_aula_recorrente').val('Aulas ' + turmaNome);
                
                // Preview das datas
                const dias = {
                    'monday': 'Segunda', 'tuesday': 'Terça', 'wednesday': 'Quarta',
                    'thursday': 'Quinta', 'friday': 'Sexta', 'saturday': 'Sábado', 'sunday': 'Domingo'
                };
                
                const hoje = new Date();
                const diaSemanaMap = {'monday':1,'tuesday':2,'wednesday':3,'thursday':4,'friday':5,'saturday':6,'sunday':0};
                
                let diaAlvo = diaSemanaMap[diaSemana];
                let diasAteProximo = (diaAlvo - hoje.getDay() + 7) % 7;
                if (diasAteProximo === 0) diasAteProximo = 7;
                
                let primeiraData = new Date(hoje);
                primeiraData.setDate(hoje.getDate() + diasAteProximo);
                
                let datasPreview = [];
                for (let i = 0; i < quantidade; i++) {
                    let data = new Date(primeiraData);
                    data.setDate(primeiraData.getDate() + (i * 7));
                    let dia = data.getDate().toString().padStart(2,'0');
                    let mes = (data.getMonth()+1).toString().padStart(2,'0');
                    let ano = data.getFullYear();
                    datasPreview.push(`${dia}/${mes}/${ano}`);
                }
                
                $('#preview_datas').html(`<small><strong>${quantidade} aulas às ${dias[diaSemana]}s:</strong><br>${datasPreview.join('<br>')}</small>`);
            }
        });
    });
    </script>
</body>
</html>