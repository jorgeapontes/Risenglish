<?php
session_start();
require_once '../includes/conexao.php';

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
    
    // Encontrar a primeira ocorrência do dia da semana
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

// --- 1. LÓGICA DE CRIAÇÃO DE AULA ÚNICA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_unica') {
    
    $titulo_aula = trim($_POST['titulo_aula']);
    $descricao = trim($_POST['descricao']);
    $data_aula = $_POST['data_aula'];
    $horario = $_POST['horario'];
    $turma_id = $_POST['turma_id'];

    // Validação básica
    if (empty($titulo_aula) || empty($data_aula) || empty($horario) || empty($turma_id)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Insere a nova aula única
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
        } catch (PDOException $e) {
            $mensagem = "Erro ao agendar a aula: " . $e->getMessage();
        }
    }
}

// --- 2. LÓGICA DE CRIAÇÃO DE AULAS RECORRENTES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_recorrente') {
    
    $turma_id = $_POST['turma_id'];
    $descricao = trim($_POST['descricao']);
    $dia_semana = $_POST['dia_semana'];
    $horario = $_POST['horario'];
    $quantidade_semanas = $_POST['quantidade_semanas'];
    
    // Busca o nome da turma para o título automático
    $sql_turma = "SELECT nome_turma FROM turmas WHERE id = :turma_id AND professor_id = :professor_id";
    $stmt_turma = $pdo->prepare($sql_turma);
    $stmt_turma->execute([':turma_id' => $turma_id, ':professor_id' => $professor_id]);
    $turma = $stmt_turma->fetch(PDO::FETCH_ASSOC);
    
    $titulo_aula = "Aulas " . ($turma['nome_turma'] ?? 'da Turma');

    // Validação básica
    if (empty($turma_id) || empty($dia_semana) || empty($horario)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Gera as datas recorrentes
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
            
            $mensagem = "{$aulas_criadas} aulas recorrentes agendadas com sucesso para as {$quantidade_semanas} próximas " . diaSemanaParaPortugues($dia_semana) . "s!";
            $sucesso = true;
        } catch (PDOException $e) {
            $mensagem = "Erro ao agendar as aulas recorrentes: " . $e->getMessage();
        }
    }
}

// --- 3. LÓGICA DE EDIÇÃO DE AULA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    
    $titulo_aula = trim($_POST['titulo_aula']);
    $descricao = trim($_POST['descricao']);
    $data_aula = $_POST['data_aula'];
    $horario = $_POST['horario'];
    $turma_id = $_POST['turma_id'];
    $aula_id = $_POST['aula_id'];

    // Validação básica
    if (empty($titulo_aula) || empty($data_aula) || empty($horario) || empty($turma_id)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Atualiza a aula existente
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
        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar a aula: " . $e->getMessage();
        }
    }
}

// --- 4. LÓGICA DE EXCLUSÃO DE AULA (DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $aula_id = $_POST['aula_id'];

    try {
        // Deleta associações de conteúdo (mantido para integridade do DB)
        $sql_delete_ac = "DELETE FROM aulas_conteudos WHERE aula_id = :aula_id";
        $pdo->prepare($sql_delete_ac)->execute([':aula_id' => $aula_id]);
        
        // Deleta a aula
        $sql_delete = "DELETE FROM aulas WHERE id = :id AND professor_id = :professor_id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([':id' => $aula_id, ':professor_id' => $professor_id]);

        $mensagem = "Aula excluída com sucesso!";
        $sucesso = true;
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir a aula: " . $e->getMessage();
    }
}

// --- CONSULTAS DE LEITURA (READ) ---

// A. Lista de Aulas
$sql_aulas = "
    SELECT 
        a.id, a.titulo_aula, a.data_aula, a.horario, a.descricao, t.nome_turma 
    FROM 
        aulas a
    JOIN 
        turmas t ON a.turma_id = t.id
    WHERE 
        a.professor_id = :professor_id
        AND CONCAT(a.data_aula, ' ', a.horario) >= NOW()
    ORDER BY 
        a.data_aula ASC, a.horario ASC
";
$stmt_aulas = $pdo->prepare($sql_aulas);
$stmt_aulas->bindParam(':professor_id', $professor_id);
$stmt_aulas->execute();
$lista_aulas = $stmt_aulas->fetchAll(PDO::FETCH_ASSOC);

// B. Lista de Turmas (para os SELECTs dos formulários)
$sql_turmas = "SELECT id, nome_turma FROM turmas WHERE professor_id = :professor_id ORDER BY nome_turma ASC";
$stmt_turmas = $pdo->prepare($sql_turmas);
$stmt_turmas->bindParam(':professor_id', $professor_id);
$stmt_turmas->execute();
$lista_turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);

// --- CONFIGURAÇÃO DE EDIÇÃO ---
$aula_para_editar = null;
$abrir_modal_edicao = false;

if (isset($_GET['editar'])) {
    $aula_id_editar = $_GET['editar'];
    $sql_edit = "SELECT * FROM aulas WHERE id = :id AND professor_id = :professor_id";
    $stmt_edit = $pdo->prepare($sql_edit);
    $stmt_edit->execute([':id' => $aula_id_editar, ':professor_id' => $professor_id]);
    $aula_para_editar = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    
    if ($aula_para_editar) {
        $abrir_modal_edicao = true;
    }
}

function renderTimePicker($id_prefix, $currentTime = '09:00') {
    $parts = explode(':', $currentTime);
    $h_sel = $parts[0];
    $m_sel = $parts[1];
    ?>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select time-hour" data-prefix="<?= $id_prefix ?>" style="width: 80px;">
            <?php for($i=0; $i<24; $i++) { $v = sprintf("%02d", $i); echo "<option value='$v' ".($v==$h_sel?'selected':'').">$v</option>"; } ?>
        </select>
        <strong>:</strong>
        <select class="form-select time-minute" data-prefix="<?= $id_prefix ?>" style="width: 80px;">
            <?php for($i=0; $i<60; $i+=5) { $v = sprintf("%02d", $i); echo "<option value='$v' ".($v==$m_sel?'selected':'').">$v</option>"; } ?>
        </select>
        <input type="hidden" name="horario" id="real_time_<?= $id_prefix ?>" value="<?= $currentTime ?>">
    </div>
    <?php
}
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

        .card-header {
            background-color: #081d40;
            color: white;
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

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
        }

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }

        .table-responsive {
            border-radius: 0 0 5px 5px;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            border-radius: .2rem;
        }
        
        .btn-info-detalhes {
            background-color: #099410ff;
            border-color: #099410ff;
            color: white;
        }
        
        .btn-info-detalhes:hover {
            background-color: #087e0eff;
            border-color: #087e0eff;
            color: white;
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

        .btn-group-agendar .btn {
            margin-right: 10px;
        }
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
            <div class="col-md-10 main-content p-4">
                <div class="d-flex justify-content-between">
                    <h2 class="mb-4 mt-3">Agendamento e Gerenciamento de Aulas</h2>
                </div>
                
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?= $sucesso ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= $mensagem ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                        <p class="text-muted mt-2">
                            <small>
                                <strong>Aula Única:</strong> Para agendar uma aula em data específica.<br>
                                <strong>Aula Recorrente:</strong> Para agendar a mesma aula em múltiplas semanas (ex: toda terça-feira por 1 mês).
                            </small>
                        </p>
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
                                                <td>
                                                    <?= date('d/m/Y', strtotime($aula['data_aula'])) ?> às <?= substr($aula['horario'], 0, 5) ?>
                                                </td>
                                                <td><?= htmlspecialchars($aula['titulo_aula']) ?></td>
                                                <td><?= htmlspecialchars($aula['nome_turma']) ?></td>
                                                <td>
                                                    <a href="detalhes_aula.php?aula_id=<?= $aula['id'] ?>" class="btn btn-sm btn-info-detalhes" title="Ver Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <a href="?editar=<?= $aula['id'] ?>" class="btn btn-sm btn-primary" title="Editar Aula">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalExcluir" data-aula-id="<?= $aula['id'] ?>" title="Excluir Aula">
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
<div class="modal fade" id="modalAulaUnica" tabindex="-1" aria-labelledby="modalAulaUnicaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                <h5 class="modal-title" id="modalAulaUnicaLabel">Agendar Aula Única</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="gerenciar_aulas.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar_unica">

                    <div class="mb-3">
                        <label for="turma_id_unica" class="form-label">Turma <span class="text-danger">*</span></label>
                        <select class="form-select" id="turma_id_unica" name="turma_id" required>
                            <option value="">Selecione a Turma</option>
                            <?php foreach ($lista_turmas as $turma): ?>
                                <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome_turma']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="titulo_aula_unica" class="form-label">Título da Aula <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titulo_aula_unica" name="titulo_aula" >
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_aula_unica" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="data_aula_unica" name="data_aula" required 
                                    value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Horário</label>
                            <?php renderTimePicker('unica', '09:00'); ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao_unica" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao_unica" name="descricao" rows="3" placeholder="Descreva o conteúdo que será ministrado..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-calendar-plus me-1"></i> Agendar Aula Única
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Aula Recorrente -->
<div class="modal fade" id="modalAulaRecorrente" tabindex="-1" aria-labelledby="modalAulaRecorrenteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                <h5 class="modal-title" id="modalAulaRecorrenteLabel">Agendar Aulas Recorrentes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="gerenciar_aulas.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar_recorrente">

                    <div class="mb-3">
                        <label for="turma_id_recorrente" class="form-label">Turma <span class="text-danger">*</span></label>
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
                        <small class="form-text text-muted">O título será automaticamente gerado como "Aulas [Nome da Turma]"</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dia_semana" class="form-label">Dia da Semana <span class="text-danger">*</span></label>
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
                            <label class="form-label">Horário</label>
                             <?php renderTimePicker('recorrente', '09:00'); ?>
                        
                        
                    </div>

                    <div class="col-md-6 mb-3">
                            <label for="quantidade_semanas" class="form-label">Número de Semanas <span class="text-danger">*</span></label>
                            <select class="form-select" id="quantidade_semanas" name="quantidade_semanas" required>
                                <option value="2">2 semanas</option>
                                <option value="4" selected>4 semanas (1 mês)</option>
                                <option value="8">8 semanas (2 meses)</option>
                                <option value="12">12 semanas (3 meses)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Próximas Datas</label>
                            <div id="preview_datas" class="form-control" style="background-color: #f8f9fa; height: auto; min-height: 38px; font-size: 0.9em;">
                                Selecione o dia da semana para visualizar as datas
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao_recorrente" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao_recorrente" name="descricao" rows="3" placeholder="Descreva o conteúdo que será ministrado nas aulas..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Serão criadas <span id="quantidade_aulas_span">4</span> aulas automaticamente para as próximas semanas no mesmo dia e horário.
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-calendar-plus me-1"></i> Agendar Aulas Recorrentes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edição (usando o mesmo estilo do modal de aula única) -->
<div class="modal fade" id="modalEdicao" tabindex="-1" aria-labelledby="modalEdicaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1a2a3a; color: white;">
                <h5 class="modal-title" id="modalEdicaoLabel">Editar Aula</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="gerenciar_aulas.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="aula_id" id="aula_id_editar" value="<?= $aula_para_editar['id'] ?? '' ?>">

                    <div class="mb-3">
                        <label for="turma_id_editar" class="form-label">Turma <span class="text-danger">*</span></label>
                        <select class="form-select" id="turma_id_editar" name="turma_id" required>
                            <option value="">Selecione a Turma</option>
                            <?php foreach ($lista_turmas as $turma): ?>
                                <option value="<?= $turma['id'] ?>" 
                                    <?= (($aula_para_editar['turma_id'] ?? '') == $turma['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($turma['nome_turma']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="titulo_aula_editar" class="form-label">Título da Aula <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titulo_aula_editar" name="titulo_aula" required 
                                value="<?= htmlspecialchars($aula_para_editar['titulo_aula'] ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_aula_editar" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="data_aula_editar" name="data_aula" required 
                                    value="<?= htmlspecialchars($aula_para_editar['data_aula'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Horário</label>
                            <?php renderTimePicker('editar', substr($aula_para_editar['horario'] ?? '09:00', 0, 5)); ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao_editar" class="form-label">Descrição (Opcional)</label>
                        <textarea class="form-control" id="descricao_editar" name="descricao" rows="3"><?= htmlspecialchars($aula_para_editar['descricao'] ?? '') ?></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save me-1"></i> Atualizar Aula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Excluir (mantido igual) -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-labelledby="modalExcluirLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalExcluirLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="gerenciar_aulas.php" method="POST">
                <div class="modal-body">
                    <p>Tem certeza de que deseja <strong>excluir permanentemente</strong> esta aula?</p>
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
$(document).ready(function() {
    // Função para preencher o ID no modal de exclusão
    $('#modalExcluir').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var aulaId = button.data('aula-id');
        var modal = $(this);
        modal.find('.modal-body #aula_id_excluir').val(aulaId);
    });

    // Atualizar título automático no modal de aula recorrente
    $('#turma_id_recorrente').change(function() {
        var turmaNome = $(this).find('option:selected').text();
        if (turmaNome !== 'Selecione a Turma') {
            $('#titulo_aula_recorrente').val('Aulas ' + turmaNome);
        } else {
            $('#titulo_aula_recorrente').val('Aulas da Turma');
        }
        atualizarPreviewDatas();
    });

    // Atualizar preview das datas quando mudar dia da semana ou quantidade
    $('#dia_semana, #quantidade_semanas').change(function() {
        atualizarPreviewDatas();
    });

    function atualizarPreviewDatas() {
        var diaSemana = $('#dia_semana').val();
        var quantidadeSemanas = $('#quantidade_semanas').val();
        
        if (!diaSemana) {
            $('#preview_datas').html('Selecione o dia da semana para visualizar as datas');
            return;
        }

        // Simulação das próximas datas (em uma implementação real, você pode usar uma função PHP via AJAX)
        var dias = {
            'monday': 'Segunda',
            'tuesday': 'Terça', 
            'wednesday': 'Quarta',
            'thursday': 'Quinta',
            'friday': 'Sexta',
            'saturday': 'Sábado',
            'sunday': 'Domingo'
        };
        
        $('#preview_datas').html('<small>Serão agendadas ' + quantidadeSemanas + ' aulas às ' + dias[diaSemana] + 's-feiras</small>');
    }

    // Atualizar quantidade de aulas no texto informativo
    $('#quantidade_semanas').change(function() {
        $('#quantidade_aulas_span').text($(this).val());
    });

    // Sincroniza os selects de Hora/Minuto com o input oculto que o PHP lê
    $('.time-hour, .time-minute').on('change', function() {
    var prefix = $(this).data('prefix');
    var h = $('.time-hour[data-prefix="'+prefix+'"]').val();
    var m = $('.time-minute[data-prefix="'+prefix+'"]').val();
    $('#real_time_' + prefix).val(h + ':' + m);
    });

    // Abrir modal de edição automaticamente se necessário
    <?php if ($abrir_modal_edicao): ?>
        $(document).ready(function() {
            var modalEdicao = new bootstrap.Modal(document.getElementById('modalEdicao'));
            modalEdicao.show();
        });
    <?php endif; ?>
});
</script>
</body>
</html>