<?php
/**
 * Trilha de auditoria: registra quem fez o quê, em qual registro e quando.
 *
 * Uso: registrar_auditoria('usuario_excluido', 'Excluiu o usuário Maria (aluno)', 'usuario', 12);
 *
 * Regras:
 *   - Nunca grava senhas, tokens ou conteúdo (texto de anotações, arquivos etc.), só a descrição da ação.
 *   - O IP é gravado ofuscado (LGPD), como em logs_acesso.
 *   - Se a gravação falhar, a ação do usuário continua normalmente: o erro vai só para o log de erros.
 *   - Os registros ficam 1 ano (a limpeza é feita pelo cron, em limpar_logs.php).
 */
require_once __DIR__ . '/conexao.php';

/** Rótulos das ações (usados na tela de monitoramento). Agrupados por categoria. */
const AUDITORIA_ACOES = [
    'Usuários' => [
        'usuario_criado'       => 'Usuário criado',
        'usuario_editado'      => 'Usuário editado',
        'usuario_excluido'     => 'Usuário excluído',
        'usuario_status'       => 'Usuário ativado/desativado',
        'usuario_desbloqueado' => 'Usuário desbloqueado',
        'anexo_enviado'        => 'Anexo enviado',
        'anexo_excluido'       => 'Anexo excluído',
    ],
    'Pagamentos' => [
        'pagamento_registrado' => 'Pagamento registrado/editado',
        'pagamento_excluido'   => 'Pagamento excluído',
        'vencimento_alterado'  => 'Dia de vencimento alterado',
    ],
    'Turmas' => [
        'turma_criada'         => 'Turma criada',
        'turma_editada'        => 'Turma editada',
        'turma_excluida'       => 'Turma excluída',
        'alunos_turma'         => 'Alunos da turma alterados',
    ],
    'Aulas e materiais' => [
        'aula_excluida'        => 'Aula excluída',
        'grupo_excluido'       => 'Grupo de temas excluído',
        'tema_excluido'        => 'Tema excluído',
        'arquivo_excluido'     => 'Arquivo/subpasta excluído',
    ],
    'Senhas e segurança' => [
        'senha_alterada'       => 'Senha alterada',
        'senha_redefinida'     => 'Senha redefinida (esqueci a senha)',
        'reset_solicitado'     => 'Redefinição de senha solicitada',
        'conta_bloqueada'      => 'Conta bloqueada por tentativas',
        'acesso_negado'        => 'Acesso negado',
        'backup_baixado'       => 'Backup baixado',
    ],
    'Site' => [
        'lead_excluido'        => 'Lead excluído',
        'util_excluido'        => 'Recurso útil excluído',
        'site_personalizado'   => 'Página inicial alterada',
    ],
];

/** Ofusca o IP para os logs (LGPD): 189.40.22.10 → 189.40.*.* ; IPv6 mantém só os 4 primeiros grupos. */
function auditoria_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return PHP_SAPI === 'cli' ? 'cron' : '';
    }
    if (strpos($ip, ':') === false) {
        $partes = explode('.', $ip);
        return $partes[0] . '.' . $partes[1] . '.*.*';
    }
    // Expande o IPv6 (sem "::") antes de cortar, para nunca mostrar a parte que identifica o aparelho
    $grupos = array_map(fn ($g) => ltrim($g, '0') ?: '0', str_split(bin2hex(inet_pton($ip)), 4));
    return implode(':', array_slice($grupos, 0, 4)) . ':****:****:****:****';
}

/**
 * Registra uma ação. $usuario_id/$usuario_nome vêm da sessão quando não informados
 * (informe-os em ações feitas sem login, como "esqueci a senha").
 */
function registrar_auditoria(string $acao, string $descricao, ?string $entidade = null, $entidade_id = null,
                             ?int $usuario_id = null, ?string $usuario_nome = null, ?string $perfil = null): void
{
    global $pdo;
    try {
        if ($usuario_id === null && isset($_SESSION['user_id'])) {
            $usuario_id = (int) $_SESSION['user_id'];
            $usuario_nome = $usuario_nome ?? ($_SESSION['user_nome'] ?? null);
            $perfil = $perfil ?? ($_SESSION['user_tipo'] ?? null);
        }
        $stmt = $pdo->prepare(
            "INSERT INTO logs_auditoria (data_hora, usuario_id, usuario_nome, perfil, acao, entidade, entidade_id, descricao, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            date('Y-m-d H:i:s'),
            $usuario_id,
            $usuario_nome !== null ? mb_substr($usuario_nome, 0, 100) : null,
            $perfil,
            $acao,
            $entidade,
            $entidade_id !== null && $entidade_id !== '' ? (int) $entidade_id : null,
            mb_substr($descricao, 0, 255),
            auditoria_ip(),
        ]);
    } catch (Throwable $e) {
        error_log('Auditoria: falha ao registrar "' . $acao . '": ' . $e->getMessage());
    }
}

/** Nome e perfil de um usuário, para descrever a ação antes de ele ser alterado/excluído. */
function auditoria_usuario(int $id): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT nome, tipo_usuario FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        return $u ? $u['nome'] . ' (' . $u['tipo_usuario'] . ')' : "#{$id}";
    } catch (Throwable $e) {
        return "#{$id}";
    }
}
