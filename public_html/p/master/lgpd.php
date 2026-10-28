<?php
/**
 * Pedidos da LGPD (art. 18, direito de acesso) — SÓ para o master.
 * Acha a pessoa (conta do site ou lead do funil de vendas) e gera um arquivo JSON com os dados
 * pessoais que o site guarda sobre ela. Cada exportação fica na Auditoria, como registro do atendimento.
 *
 * Entram só registros que com certeza são do titular. Ficam de fora: senha, tokens e controles
 * de bloqueio; o conteúdo dos arquivos (anexos e foto), dos quais vai só o nome/indicação; e textos
 * escritos por professores sobre OUTROS alunos (do professor vai só a contagem desses comentários).
 */
require_once '../includes/verifica_sessao.php';
require_once '../includes/conexao.php';
require_once '../includes/auditoria.php';

// (verifica_sessao.php já registra na auditoria quem não é master e tenta abrir esta pasta)
if (($_SESSION['user_tipo'] ?? '') !== 'master') {
    header("Location: ../login?erro=acesso_negado");
    exit;
}

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_monitoramento'])) {
    $_SESSION['csrf_monitoramento'] = bin2hex(random_bytes(32));
}

/** Nome de cada seção do arquivo na tela. */
const LGPD_SECOES = [
    'responsavel_financeiro'   => 'Responsável financeiro',
    'dependentes_financeiros'  => 'Dependentes financeiros',
    'turmas'                   => 'Turmas',
    'presencas'                => 'Presenças nas aulas',
    'anotacoes_das_aulas'      => 'Anotações das aulas',
    'caderno_de_anotacoes'     => 'Caderno de anotações',
    'pagamentos'               => 'Pagamentos',
    'documentos_anexados'      => 'Documentos anexados',
    'notificacoes'             => 'Notificações',
    'historico_de_logins'      => 'Histórico de logins',
    'acoes_registradas'        => 'Ações feitas pela pessoa',
    'registros_sobre_a_conta'  => 'Registros sobre a conta',
    'cadastro_como_interessado' => 'Cadastro como interessado (lead)',
    'turmas_que_leciona'       => 'Turmas que leciona',
    'aulas_que_deu'            => 'Aulas',
    'materiais_publicados'     => 'Materiais publicados',
    'comentarios_feitos'       => 'Comentários em anotações',
];

/** Linhas de uma consulta, sem colunas sensíveis; erro vira lista vazia (e vai para o log). */
function lgpd_linhas(PDO $pdo, string $sql, array $params): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('LGPD: ' . $e->getMessage());
        return [];
    }
    foreach ($linhas as &$linha) {
        foreach (array_keys($linha) as $coluna) {
            if (preg_match('/senha|token|password|caminho_arquivo/i', $coluna)) {
                unset($linha[$coluna]);
            }
        }
    }
    return $linhas;
}

/** Cabeçalho comum do arquivo. */
function lgpd_sobre(): array
{
    return [
        'gerado_em'   => date('d/m/Y H:i'),
        'controlador' => 'Risenglish (risenglish.com.br)',
        'contato'     => 'contato@risenglish.com.br',
        'observacao'  => 'Dados pessoais que o site guarda sobre o titular (LGPD, art. 18). Documentos anexados e foto de perfil não vão no arquivo: '
            . 'aparece só o nome do arquivo e a data. Senhas não são guardadas em texto (só uma versão cifrada), por isso não aparecem.',
    ];
}

/** Todos os dados pessoais de uma conta do site, organizados por assunto. */
function lgpd_dados_da_conta(PDO $pdo, int $id): array
{
    $conta = lgpd_linhas($pdo, "SELECT * FROM usuarios WHERE id = ? AND tipo_usuario <> 'master'", [$id])[0] ?? null;
    if (!$conta) {
        return [];
    }
    $conta['foto_perfil'] = !empty($conta['foto_perfil']) ? 'foto de perfil guardada' : 'sem foto';
    unset($conta['tentativas_falhas'], $conta['bloqueado_ate']);
    $tipo = $conta['tipo_usuario'];

    $dados = [
        'sobre_este_arquivo' => lgpd_sobre(),
        'conta'              => $conta,
    ];

    if ($tipo === 'aluno') {
        $dados['responsavel_financeiro'] = lgpd_linhas($pdo, "SELECT r.nome, r.email FROM usuarios u JOIN usuarios r ON r.id = u.responsavel_financeiro_id WHERE u.id = ?", [$id]);
        $dados['dependentes_financeiros'] = lgpd_linhas($pdo, "SELECT nome, email FROM usuarios WHERE responsavel_financeiro_id = ?", [$id]);
        $dados['turmas'] = lgpd_linhas($pdo, "SELECT t.nome_turma, p.nome AS professor, t.inicio_turma
                                              FROM alunos_turmas at JOIN turmas t ON t.id = at.turma_id LEFT JOIN usuarios p ON p.id = t.professor_id
                                              WHERE at.aluno_id = ? ORDER BY t.nome_turma", [$id]);
        $dados['presencas'] = lgpd_linhas($pdo, "SELECT a.data_aula, a.horario, a.titulo_aula, IF(pa.presente = 1, 'presente', 'faltou') AS situacao
                                                 FROM presenca_aula pa JOIN aulas a ON a.id = pa.aula_id
                                                 WHERE pa.aluno_id = ? ORDER BY a.data_aula", [$id]);
        // Anotações do aluno em cada aula, com os comentários do professor sobre elas (o aluno já vê esses comentários no site)
        $anotacoes = lgpd_linhas($pdo, "SELECT an.id, a.data_aula, a.titulo_aula, an.conteudo, an.comentario_professor, an.data_criacao, an.data_atualizacao
                                        FROM anotacoes_aula an JOIN aulas a ON a.id = an.aula_id
                                        WHERE an.aluno_id = ? ORDER BY a.data_aula", [$id]);
        foreach ($anotacoes as &$anotacao) {
            $anotacao['itens'] = lgpd_linhas($pdo, "SELECT autor, conteudo, data_criacao FROM anotacoes_itens WHERE anotacao_id = ? ORDER BY data_criacao", [$anotacao['id']]);
            unset($anotacao['id']);
        }
        unset($anotacao);
        $dados['anotacoes_das_aulas'] = $anotacoes;
        $dados['caderno_de_anotacoes'] = lgpd_linhas($pdo, "SELECT titulo, conteudo, data_criacao, data_atualizacao FROM caderno_anotacoes WHERE aluno_id = ? ORDER BY data_criacao", [$id]);
        $dados['pagamentos'] = lgpd_linhas($pdo, "SELECT DATE_FORMAT(mes_referencia, '%m/%Y') AS mes_referencia, valor, data_pagamento, observacoes, data_registro
                                                 FROM pagamentos WHERE aluno_id = ? ORDER BY mes_referencia", [$id]);
    }

    if ($tipo === 'professor') {
        $dados['turmas_que_leciona'] = lgpd_linhas($pdo, "SELECT nome_turma, inicio_turma FROM turmas WHERE professor_id = ? ORDER BY nome_turma", [$id]);
        $dados['aulas_que_deu'] = lgpd_linhas($pdo, "SELECT a.data_aula, a.horario, a.titulo_aula, t.nome_turma
                                                     FROM aulas a LEFT JOIN turmas t ON t.id = a.turma_id
                                                     WHERE a.professor_id = ? ORDER BY a.data_aula", [$id]);
        $dados['materiais_publicados'] = lgpd_linhas($pdo, "SELECT titulo, descricao, tipo_arquivo, data_upload,
                                                            IF(parent_id IS NULL, 'tema', IF(eh_subpasta = 1, 'subpasta', 'arquivo')) AS tipo
                                                            FROM conteudos WHERE professor_id = ? ORDER BY data_upload", [$id]);
        // Os comentários falam de outros alunos: vai só a quantidade
        $dados['comentarios_feitos'] = lgpd_linhas($pdo, "SELECT COUNT(*) AS quantidade FROM anotacoes_itens i JOIN anotacoes_aula an ON an.id = i.anotacao_id
                                                          JOIN aulas a ON a.id = an.aula_id WHERE i.autor = 'professor' AND a.professor_id = ?", [$id]);
    }

    $dados['documentos_anexados'] = lgpd_linhas($pdo, "SELECT nome_arquivo, data_upload FROM usuarios_anexos WHERE usuario_id = ? ORDER BY data_upload", [$id]);
    $dados['notificacoes'] = lgpd_linhas($pdo, "SELECT titulo, mensagem, IF(lida = 1, 'sim', 'não') AS lida, data_criacao, data_leitura FROM notificacoes WHERE usuario_id = ? ORDER BY data_criacao", [$id]);
    $dados['historico_de_logins'] = lgpd_linhas($pdo, "SELECT data_acesso, tipo_evento, ip, user_agent FROM logs_acesso WHERE usuario_id = ? ORDER BY data_acesso", [$id]);
    $dados['acoes_registradas'] = lgpd_linhas($pdo, "SELECT data_hora, acao, descricao, ip FROM logs_auditoria WHERE usuario_id = ? ORDER BY data_hora", [$id]);
    // O que outras pessoas fizeram na conta (sem dizer quem fez nem o IP de quem fez)
    $dados['registros_sobre_a_conta'] = lgpd_linhas($pdo, "SELECT data_hora, acao, descricao FROM logs_auditoria
                                                         WHERE entidade = 'usuario' AND entidade_id = ? AND (usuario_id IS NULL OR usuario_id <> ?) ORDER BY data_hora", [$id, $id]);
    $dados['cadastro_como_interessado'] = lgpd_linhas($pdo, "SELECT nome, telefone, email, origem, nivel_ingles, status, data_aula_experimental, observacoes, data_criacao
                                                           FROM leads WHERE email <> '' AND email = ?", [$conta['email']]);
    return $dados;
}

/** Dados de um lead (pessoa interessada que pode nunca ter virado aluno). */
function lgpd_dados_do_lead(PDO $pdo, int $id): array
{
    $lead = lgpd_linhas($pdo, "SELECT nome, telefone, email, origem, nivel_ingles, status, data_aula_experimental, observacoes, data_criacao, data_atualizacao
                               FROM leads WHERE id = ?", [$id])[0] ?? null;
    if (!$lead) {
        return [];
    }
    return [
        'sobre_este_arquivo'        => lgpd_sobre(),
        'cadastro_como_interessado' => $lead,
        'registros_sobre_o_cadastro' => lgpd_linhas($pdo, "SELECT data_hora, acao, descricao FROM logs_auditoria WHERE entidade = 'lead' AND entidade_id = ? ORDER BY data_hora", [$id]),
    ];
}

/** Resumo para a tela: quantos registros há em cada seção. */
function lgpd_resumo(array $dados): array
{
    $resumo = [];
    foreach ($dados as $secao => $conteudo) {
        if (in_array($secao, ['sobre_este_arquivo', 'conta'], true)) {
            continue;
        }
        $resumo[$secao] = is_array($conteudo) ? (array_is_list($conteudo) ? count($conteudo) : 1) : ($conteudo ? 1 : 0);
    }
    return $resumo;
}

// ===== Exportar (download do JSON) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'exportar') {
    $origem = ($_POST['origem'] ?? '') === 'lead' ? 'lead' : 'usuario';
    $id = (int) ($_POST['id'] ?? 0);
    $protocolo = trim((string) ($_POST['protocolo'] ?? ''));
    $dados = $origem === 'lead' ? lgpd_dados_do_lead($pdo, $id) : lgpd_dados_da_conta($pdo, $id);

    if (!hash_equals($_SESSION['csrf_monitoramento'], (string) ($_POST['csrf'] ?? '')) || !$dados) {
        $_SESSION['monitoramento_msg'] = ['danger', 'Não foi possível exportar: cadastro não encontrado ou requisição inválida.'];
        header('Location: lgpd');
        exit;
    }
    $nome = $origem === 'lead' ? $dados['cadastro_como_interessado']['nome'] : $dados['conta']['nome'];
    $perfil = $origem === 'lead' ? 'lead' : $dados['conta']['tipo_usuario'];
    registrar_auditoria('lgpd_exportacao', mb_substr("Exportou os dados de {$nome} ({$perfil}) a pedido do titular"
        . ($protocolo !== '' ? " · {$protocolo}" : ''), 0, 255), $origem === 'lead' ? 'lead' : 'usuario', $id);

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="dados_pessoais_' . $origem . '_' . $id . '_' . date('Y-m-d') . '.json"');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$mensagem = $_SESSION['monitoramento_msg'] ?? null;
unset($_SESSION['monitoramento_msg']);

// ===== Busca do titular (contas e leads) =====
$busca = trim((string) ($_GET['busca'] ?? ''));
$contas = [];
$leads = [];
if (mb_strlen($busca) >= 3) {
    $stmt = $pdo->prepare("SELECT id, nome, email, tipo_usuario, status FROM usuarios
                           WHERE tipo_usuario <> 'master' AND (email LIKE ? OR nome LIKE ?) ORDER BY nome LIMIT 20");
    $stmt->execute(["%{$busca}%", "%{$busca}%"]);
    $contas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT id, nome, email, telefone, status, data_criacao FROM leads
                           WHERE email LIKE ? OR nome LIKE ? OR telefone LIKE ? ORDER BY nome LIMIT 20");
    $stmt->execute(["%{$busca}%", "%{$busca}%", "%{$busca}%"]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===== Prévia do que vai no arquivo =====
$selecionado = null;
$origemSelecionada = ($_GET['origem'] ?? '') === 'lead' ? 'lead' : 'usuario';
$idSelecionado = (int) ($_GET['id'] ?? 0);
$resumoDados = [];
if ($idSelecionado > 0) {
    $previa = $origemSelecionada === 'lead' ? lgpd_dados_do_lead($pdo, $idSelecionado) : lgpd_dados_da_conta($pdo, $idSelecionado);
    if ($previa) {
        $selecionado = $origemSelecionada === 'lead'
            ? ['nome' => $previa['cadastro_como_interessado']['nome'], 'perfil' => 'lead (interessado)']
            : ['nome' => $previa['conta']['nome'], 'perfil' => $previa['conta']['tipo_usuario']];
        $resumoDados = lgpd_resumo($previa);
    }
}

$historico = $pdo->query("SELECT data_hora, usuario_nome, descricao FROM logs_auditoria WHERE acao = 'lgpd_exportacao' ORDER BY data_hora DESC LIMIT 30")
    ->fetchAll(PDO::FETCH_ASSOC);
$urlBusca = 'lgpd?busca=' . urlencode($busca);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LGPD - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/master/base.css?v=<?= @filemtime(__DIR__ . '/../../css/master/base.css') ?>">
    <link rel="stylesheet" href="../../css/master/monitoramento.css?v=<?= @filemtime(__DIR__ . '/../../css/master/monitoramento.css') ?>">
</head>
<body>

<div class="d-flex">
    <?php $paginaAtiva = 'lgpd'; $tituloMobile = 'LGPD'; require '../includes/layout/master_sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="page-header">
            <div>
                <div class="sobretitulo">Monitoramento</div>
                <h1 class="page-title">LGPD</h1>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?= e($mensagem[0]) ?> alert-dismissible fade show" role="alert">
                <?= e($mensagem[1]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>

        <p class="texto-secundario mb-3">
            Quando alguém pedir os dados que o site guarda sobre ela (pelo contato@risenglish.com.br), encontre o cadastro aqui e gere o arquivo.
            A busca procura em alunos, professores, admins e também nos leads (interessados que podem nunca ter virado aluno).
            Confira antes que o pedido veio do próprio titular, ou do responsável legal no caso de aluno menor de idade. A LGPD dá até 15 dias para responder.
        </p>

        <form method="GET" class="filtros-acessos row g-3 mb-4">
            <div class="col-md-10">
                <label class="form-label" for="buscaTitular">Nome, e-mail ou telefone do titular</label>
                <input type="text" class="form-control" id="buscaTitular" name="busca" value="<?= e($busca) ?>" minlength="3" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-filtrar w-100">Buscar</button>
            </div>
        </form>

        <?php if ($busca !== ''): ?>
            <div class="table-responsive mb-4">
                <table class="table tabela-acessos tabela-cartoes">
                    <thead><tr><th>Cadastro</th><th>Tipo</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($contas as $u): ?>
                            <tr>
                                <td><?= e($u['nome']) ?><br><span class="texto-secundario small"><?= e($u['email']) ?></span></td>
                                <td class="text-nowrap"><?= e($u['tipo_usuario']) ?><?= $u['status'] !== 'ativo' ? ' <span class="texto-secundario small">· ' . e($u['status']) . '</span>' : '' ?></td>
                                <td class="td-acoes text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e($urlBusca . '&origem=usuario&id=' . (int) $u['id']) ?>">Ver dados</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($leads as $l): ?>
                            <tr>
                                <td><?= e($l['nome']) ?><br><span class="texto-secundario small"><?= e(trim($l['email'] . ' ' . $l['telefone'])) ?></span></td>
                                <td class="text-nowrap">lead <span class="texto-secundario small">· <?= e($l['status']) ?></span></td>
                                <td class="td-acoes text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e($urlBusca . '&origem=lead&id=' . (int) $l['id']) ?>">Ver dados</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$contas && !$leads): ?>
                            <tr><td colspan="3" class="text-center py-4 texto-vazio">Nada encontrado (busque com pelo menos 3 letras).</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($selecionado): ?>
            <h2 class="titulo-secao">Dados de <?= e($selecionado['nome']) ?> <span class="texto-secundario small">· <?= e($selecionado['perfil']) ?></span></h2>
            <ul class="lista-simples mb-3">
                <?php if ($origemSelecionada === 'usuario'): ?><li>Dados da conta <span class="texto-secundario">· nome, e-mail, tipo, situação, informações e cobrança</span></li><?php endif; ?>
                <?php foreach ($resumoDados as $secao => $quantidade): ?>
                    <li><?= e(LGPD_SECOES[$secao] ?? ucfirst(str_replace('_', ' ', $secao))) ?> <span class="texto-secundario">· <?= (int) $quantidade ?> registro(s)</span></li>
                <?php endforeach; ?>
            </ul>
            <form method="POST" class="filtros-acessos row g-3 mb-4">
                <input type="hidden" name="acao" value="exportar">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_monitoramento']) ?>">
                <input type="hidden" name="origem" value="<?= e($origemSelecionada) ?>">
                <input type="hidden" name="id" value="<?= (int) $idSelecionado ?>">
                <div class="col-md-8">
                    <label class="form-label" for="protocolo">Protocolo ou observação do pedido (opcional)</label>
                    <input type="text" class="form-control" id="protocolo" name="protocolo" maxlength="120" placeholder="ex.: pedido por e-mail de 27/10">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-filtrar w-100"><i class="fas fa-file-download me-1"></i> Baixar os dados (JSON)</button>
                </div>
            </form>
            <p class="texto-secundario small mb-4">
                <i class="fas fa-lock"></i> O arquivo tem dados pessoais: mande só para o e-mail do próprio titular (ou do responsável legal) e apague a sua cópia depois.
                Os documentos anexados não vão no arquivo; se a pessoa pedir cópia deles, o admin baixa em Usuários → Anexos.
            </p>
        <?php endif; ?>

        <h2 class="titulo-secao">Pedidos atendidos</h2>
        <div class="table-responsive">
            <table class="table tabela-acessos tabela-cartoes">
                <thead><tr><th>Data</th><th>Quem exportou</th><th>Descrição</th></tr></thead>
                <tbody>
                    <?php foreach ($historico as $h): ?>
                        <tr>
                            <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($h['data_hora'])) ?></td>
                            <td><?= e($h['usuario_nome']) ?></td>
                            <td><?= e($h['descricao']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$historico): ?>
                        <tr><td colspan="3" class="text-center py-4 texto-vazio">Nenhum pedido atendido ainda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
