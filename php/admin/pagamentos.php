<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$nome_usuario = $_SESSION['user_nome'];

if (!isset($pdo)) {
    die('<div class="alert alert-danger">Erro de Conexão: Variável PDO não definida.</div>');
}

// ===================================
// PROCESSAMENTO DE AÇÕES (CRUD)
// ===================================
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'registrar_pagamento') {
            $aluno_id = $_POST['aluno_id'] ?? null;
            $mes_referencia = $_POST['mes_referencia'] ?? null;
            $valor = $_POST['valor'] ?? 0;
            $data_pagamento = $_POST['data_pagamento'] ?? null;
            $observacoes = trim($_POST['observacoes'] ?? '');

            if (empty($aluno_id) || empty($mes_referencia) || empty($data_pagamento)) {
                throw new Exception("Dados obrigatórios faltando.");
            }

            // Verifica se já existe pagamento para este aluno neste mês
            $stmt_check = $pdo->prepare("SELECT id FROM pagamentos WHERE aluno_id = ? AND mes_referencia = ?");
            $stmt_check->execute([$aluno_id, $mes_referencia . '-01']);
            
            if ($stmt_check->fetch()) {
                // Atualiza o pagamento existente
                $stmt = $pdo->prepare("UPDATE pagamentos SET valor = ?, data_pagamento = ?, observacoes = ? WHERE aluno_id = ? AND mes_referencia = ?");
                $stmt->execute([$valor, $data_pagamento, $observacoes, $aluno_id, $mes_referencia . '-01']);
                $mensagem = "Pagamento atualizado com sucesso!";
            } else {
                // Cria novo pagamento
                $stmt = $pdo->prepare("INSERT INTO pagamentos (aluno_id, mes_referencia, valor, data_pagamento, observacoes) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$aluno_id, $mes_referencia . '-01', $valor, $data_pagamento, $observacoes]);
                $mensagem = "Pagamento registrado com sucesso!";
            }
            
            $tipo_mensagem = 'success';

        } elseif ($action === 'atualizar_vencimento') {
            $aluno_id = $_POST['aluno_id'] ?? null;
            $dia_vencimento = $_POST['dia_vencimento'] ?? null;

            if (empty($aluno_id)) {
                throw new Exception("ID do aluno é obrigatório.");
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET dia_vencimento = ? WHERE id = ?");
            $stmt->execute([$dia_vencimento ?: null, $aluno_id]);
            
            $mensagem = "Data de vencimento atualizada com sucesso!";
            $tipo_mensagem = 'success';

        } elseif ($action === 'remover_pagamento') {
            $pagamento_id = $_POST['pagamento_id'] ?? null;
            
            if (empty($pagamento_id)) {
                throw new Exception("ID do pagamento é obrigatório.");
            }

            $stmt = $pdo->prepare("DELETE FROM pagamentos WHERE id = ?");
            $stmt->execute([$pagamento_id]);
            
            $mensagem = "Pagamento removido com sucesso!";
            $tipo_mensagem = 'success';
        }

    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// ===================================
// BUSCAR DADOS
// ===================================

// Filtros
$filtro_mes = $_GET['filtro_mes'] ?? date('Y-m');
$filtro_busca = $_GET['filtro_busca'] ?? '';
$mes_referencia_inicio = $filtro_mes . '-01';

// Buscar todos os alunos com seus pagamentos do mês
$alunos_pagamentos = [];
try {
    $sql = "SELECT 
                u.id,
                u.nome,
                u.email,
                u.dia_vencimento,
                p.id as pagamento_id,
                p.valor,
                p.data_pagamento,
                p.observacoes,
                p.mes_referencia
            FROM usuarios u
            LEFT JOIN pagamentos p ON u.id = p.aluno_id AND p.mes_referencia = ?
            WHERE u.tipo_usuario = 'aluno'";
    
    $params = [$mes_referencia_inicio];
    
    // Adicionar filtro de busca
    if (!empty($filtro_busca)) {
        $sql .= " AND (u.nome LIKE ? OR u.email LIKE ?)";
        $busca_param = '%' . $filtro_busca . '%';
        $params[] = $busca_param;
        $params[] = $busca_param;
    }
    
    $sql .= " ORDER BY u.nome";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $alunos_pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao carregar dados: " . $e->getMessage();
    $tipo_mensagem = 'danger';
}

// Calcular estatísticas e status
$total_mes = 0;
$total_pago = 0;
$total_pendente = 0;
$total_atrasado = 0;
$count_pagos = 0;
$count_pendentes = 0;
$count_atrasados = 0;

$hoje = new DateTime();
foreach ($alunos_pagamentos as &$aluno) {
    // Calcular status
    if ($aluno['data_pagamento']) {
        $aluno['status'] = 'pago';
        $total_pago += $aluno['valor'];
        $total_mes += $aluno['valor'];
        $count_pagos++;
    } else {
        // Verificar se está atrasado
        if ($aluno['dia_vencimento']) {
            $data_vencimento = new DateTime($filtro_mes . '-' . str_pad($aluno['dia_vencimento'], 2, '0', STR_PAD_LEFT));
            
            if ($hoje > $data_vencimento) {
                $aluno['status'] = 'atrasado';
                $count_atrasados++;
            } else {
                $aluno['status'] = 'pendente';
                $count_pendentes++;
            }
        } else {
            $aluno['status'] = 'pendente';
            $count_pendentes++;
        }
    }
    
    $aluno['data_vencimento_calculada'] = null;
    if ($aluno['dia_vencimento']) {
        $aluno['data_vencimento_calculada'] = $filtro_mes . '-' . str_pad($aluno['dia_vencimento'], 2, '0', STR_PAD_LEFT);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pagamentos - Admin Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/admin/pagamentos.css">
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo htmlspecialchars($nome_usuario); ?></h5>
        </div>

        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="gerenciar_turmas.php"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="pagamentos.php" class="active"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
        </div>

        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <h1 class="mb-4">Controle de Pagamentos - PIX</h1>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Cards de Estatísticas -->
        <div class="stats-cards">
            <div class="stat-card pago">
                <h6><i class="fas fa-check-circle"></i> Pagos</h6>
                <div class="value">R$ <?= number_format($total_pago, 2, ',', '.') ?></div>
                <div class="count"><?= $count_pagos ?> aluno(s)</div>
            </div>
            <div class="stat-card pendente">
                <h6><i class="fas fa-clock"></i> Pendentes</h6>
                <div class="value"><?= $count_pendentes ?></div>
                <div class="count">aluno(s)</div>
            </div>
            <div class="stat-card atrasado">
                <h6><i class="fas fa-exclamation-triangle"></i> Atrasados</h6>
                <div class="value"><?= $count_atrasados ?></div>
                <div class="count">aluno(s)</div>
            </div>
            <div class="stat-card total">
                <h6><i class="fas fa-calendar-alt"></i> Total do Mês</h6>
                <div class="value">R$ <?= number_format($total_mes, 2, ',', '.') ?></div>
            </div>
        </div>

        <!-- Filtro de Mês e Busca -->
        <div class="filter-section">
            <form method="GET" class="d-flex gap-3 w-100 align-items-end">
                <div style="flex: 1;">
                    <label class="form-label mb-1">
                        <i class="fas fa-search"></i> Buscar Aluno
                    </label>
                    <input type="text" 
                           name="filtro_busca" 
                           class="form-control" 
                           placeholder="Digite o nome ou email do aluno..."
                           value="<?= htmlspecialchars($filtro_busca) ?>">
                </div>
                <div style="flex: 0 0 250px;">
                    <label class="form-label mb-1">
                        <i class="fas fa-calendar-alt"></i> Mês de Referência
                    </label>
                    <input type="month" 
                           name="filtro_mes" 
                           class="form-control" 
                           value="<?= htmlspecialchars($filtro_mes) ?>">
                </div>
                <div style="flex: 0 0 auto;">
                    <button type="submit" class="btn btn-registrar">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
                <?php if (!empty($filtro_busca)): ?>
                <div style="flex: 0 0 auto;">
                    <a href="?filtro_mes=<?= htmlspecialchars($filtro_mes) ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($filtro_busca)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Mostrando resultados para: <strong>"<?= htmlspecialchars($filtro_busca) ?>"</strong>
            <?php if (empty($alunos_pagamentos)): ?>
                - Nenhum aluno encontrado.
            <?php else: ?>
                - <?= count($alunos_pagamentos) ?> aluno(s) encontrado(s).
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Tabela de Alunos -->
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Data Pagamento</th>
                        <th>Valor Pago</th>
                        <th>Observações</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alunos_pagamentos)): ?>
                        <tr><td colspan="7" class="text-center">Nenhum aluno cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($alunos_pagamentos as $aluno): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($aluno['nome']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($aluno['email']) ?></small>
                            </td>
                            <td>
                                <?php if ($aluno['dia_vencimento']): ?>
                                    <span class="badge vencimento-badge bg-info">
                                        Dia <?= $aluno['dia_vencimento'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Não definido</span>
                                <?php endif; ?>
                                <button class="btn btn-vencimento btn-sm ms-1" 
                                        onclick="editarVencimento(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome']) ?>', <?= $aluno['dia_vencimento'] ?? 'null' ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                            <td>
                                <span class="badge badge-<?= $aluno['status'] ?>">
                                    <?php 
                                        $status_texto = [
                                            'pago' => 'Pago',
                                            'pendente' => 'Pendente',
                                            'atrasado' => 'Atrasado'
                                        ];
                                        echo $status_texto[$aluno['status']];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($aluno['data_pagamento']): ?>
                                    <?= (new DateTime($aluno['data_pagamento']))->format('d/m/Y') ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($aluno['valor']): ?>
                                    <strong>R$ <?= number_format($aluno['valor'], 2, ',', '.') ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($aluno['observacoes']): ?>
                                    <div style="max-width: 200px; white-space: normal;">
                                        <small><?= htmlspecialchars($aluno['observacoes']) ?></small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-registrar btn-sm" 
                                        onclick='registrarPagamento(<?= json_encode([
                                            "aluno_id" => $aluno["id"],
                                            "aluno_nome" => $aluno["nome"],
                                            "mes_referencia" => $filtro_mes,
                                            "pagamento_id" => $aluno["pagamento_id"],
                                            "valor" => $aluno["valor"],
                                            "data_pagamento" => $aluno["data_pagamento"],
                                            "observacoes" => $aluno["observacoes"]
                                        ]) ?>)'>
                                    <i class="fas fa-<?= $aluno['data_pagamento'] ? 'edit' : 'plus' ?>"></i>
                                    <?= $aluno['data_pagamento'] ? 'Editar' : 'Registrar' ?>
                                </button>
                                <?php if ($aluno['pagamento_id']): ?>
                                    <button class="btn btn-remover btn-sm ms-1" 
                                            onclick="removerPagamento(<?= $aluno['pagamento_id'] ?>, '<?= htmlspecialchars($aluno['nome']) ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Registrar Pagamento -->
<div class="modal fade" id="modalRegistrarPagamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagamentoLabel">Registrar Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="pagamentos.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="registrar_pagamento">
                    <input type="hidden" name="aluno_id" id="pagamento_aluno_id">
                    <input type="hidden" name="mes_referencia" id="pagamento_mes_referencia">
                    
                    <div class="mb-3">
                        <label class="form-label">Aluno</label>
                        <input type="text" class="form-control" id="pagamento_aluno_nome" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mês de Referência</label>
                        <input type="text" class="form-control" id="pagamento_mes_display" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor Pago (R$) *</label>
                        <input type="number" step="0.01" class="form-control" id="pagamento_valor" name="valor" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="data_pagamento" class="form-label">Data do Pagamento *</label>
                        <input type="date" class="form-control" id="pagamento_data" name="data_pagamento" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="pagamento_observacoes" name="observacoes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-registrar">Salvar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Vencimento -->
<div class="modal fade" id="modalEditarVencimento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVencimentoLabel">Definir Vencimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="pagamentos.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="atualizar_vencimento">
                    <input type="hidden" name="aluno_id" id="vencimento_aluno_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Aluno</label>
                        <input type="text" class="form-control" id="vencimento_aluno_nome" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="dia_vencimento" class="form-label">Dia do Vencimento *</label>
                        <select class="form-select" name="dia_vencimento" id="vencimento_dia" required>
                            <option value="">Selecione o dia</option>
                            <?php for($i = 1; $i <= 31; $i++): ?>
                                <option value="<?= $i ?>">Dia <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <small class="text-muted">Dia do mês em que o pagamento vence</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-vencimento">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form oculto para remoção -->
<form id="formRemover" method="POST" action="pagamentos.php">
    <input type="hidden" name="action" value="remover_pagamento">
    <input type="hidden" name="pagamento_id" id="remover_pagamento_id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function registrarPagamento(dados) {
        document.getElementById('pagamento_aluno_id').value = dados.aluno_id;
        document.getElementById('pagamento_aluno_nome').value = dados.aluno_nome;
        document.getElementById('pagamento_mes_referencia').value = dados.mes_referencia;
        
        // Formatar mês para exibição
        const [ano, mes] = dados.mes_referencia.split('-');
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                       'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        document.getElementById('pagamento_mes_display').value = meses[parseInt(mes) - 1] + '/' + ano;
        
        // Preencher dados se já existir pagamento
        document.getElementById('pagamento_valor').value = dados.valor || '';
        document.getElementById('pagamento_data').value = dados.data_pagamento || '';
        document.getElementById('pagamento_observacoes').value = dados.observacoes || '';
        
        // Mudar título do modal
        if (dados.pagamento_id) {
            document.getElementById('modalPagamentoLabel').innerText = 'Editar Pagamento - ' + dados.aluno_nome;
        } else {
            document.getElementById('modalPagamentoLabel').innerText = 'Registrar Pagamento - ' + dados.aluno_nome;
        }
        
        var myModal = new bootstrap.Modal(document.getElementById('modalRegistrarPagamento'));
        myModal.show();
    }

    function editarVencimento(aluno_id, aluno_nome, dia_atual) {
        document.getElementById('vencimento_aluno_id').value = aluno_id;
        document.getElementById('vencimento_aluno_nome').value = aluno_nome;
        document.getElementById('vencimento_dia').value = dia_atual || '';
        
        var myModal = new bootstrap.Modal(document.getElementById('modalEditarVencimento'));
        myModal.show();
    }
    
    function removerPagamento(pagamento_id, aluno_nome) {
        if (confirm(`Tem certeza que deseja remover o pagamento de ${aluno_nome}? Esta ação é irreversível.`)) {
            document.getElementById('remover_pagamento_id').value = pagamento_id;
            document.getElementById('formRemover').submit();
        }
    }
</script>
</body>
</html>