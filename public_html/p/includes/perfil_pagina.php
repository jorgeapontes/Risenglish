<?php
/**
 * Página "Meu Perfil" compartilhada por aluno e professor.
 * Incluída por aluno/perfil.php e professor/perfil.php, que definem:
 *   $perfilTipo  'aluno' ou 'professor'
 *
 * Ações (todas POST → redirect → GET):
 *   foto          envia uma nova foto (aplicada na hora, como no GitHub)
 *   remover_foto  volta para o avatar padrão com as iniciais
 *   nome          altera o nome
 *   senha         altera a senha (exige a senha atual)
 * O e-mail é exibido apenas para leitura (é usado no login e na recuperação de senha).
 */
require_once __DIR__ . '/verifica_sessao.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/avatar.php';

if (!in_array($perfilTipo ?? '', ['aluno', 'professor'], true) || $_SESSION['user_tipo'] !== $perfilTipo) {
    header("Location: ../login?erro=acesso_negado");
    exit;
}
date_default_timezone_set('America/Sao_Paulo');

$usuario_id = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function perfil_redirecionar(string $tipo, string $texto, string $secao = 'foto'): void
{
    $_SESSION['perfil_flash'] = ['tipo' => $tipo, 'texto' => $texto, 'secao' => $secao];
    header("Location: perfil");
    exit;
}

// ===== PROCESSAR FORMULÁRIOS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload acima do post_max_size chega com $_POST vazio
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        perfil_redirecionar('danger', 'A imagem é muito grande. Envie uma foto de até 5 MB.');
    }

    $acao = (string) ($_POST['acao'] ?? '');
    $secao = in_array($acao, ['nome', 'senha'], true) ? $acao : 'foto';

    $csrf_post = $_POST['csrf_token'] ?? '';
    if (!is_string($csrf_post) || !hash_equals($_SESSION['csrf_token'], $csrf_post)) {
        perfil_redirecionar('danger', 'Sessão expirada. Recarregue a página e tente novamente.', $secao);
    }

    $stmt = $pdo->prepare("SELECT nome, senha, foto_perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $atual = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$atual) {
        header("Location: ../logout");
        exit;
    }

    if ($acao === 'foto') {
        $resultado = avatar_processar_upload($_FILES['foto'] ?? [], $usuario_id);
        if (!$resultado['ok']) {
            perfil_redirecionar('danger', $resultado['erro']);
        }
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
            $stmt->execute([$resultado['foto'], $usuario_id]);
        } catch (PDOException $e) {
            error_log('Erro ao salvar foto de perfil: ' . $e->getMessage());
            avatar_apagar($resultado['foto']);
            perfil_redirecionar('danger', 'Erro ao salvar a foto. Tente novamente.');
        }
        avatar_apagar($atual['foto_perfil']);
        $_SESSION['user_foto'] = $resultado['foto'];
        perfil_redirecionar('success', 'Foto atualizada.');
    }

    if ($acao === 'remover_foto') {
        $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = NULL WHERE id = ?");
        $stmt->execute([$usuario_id]);
        avatar_apagar($atual['foto_perfil']);
        $_SESSION['user_foto'] = null;
        perfil_redirecionar('success', 'Foto removida. Seu avatar voltou a mostrar suas iniciais.');
    }

    if ($acao === 'nome') {
        $nome = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nome'] ?? '')));
        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
            perfil_redirecionar('danger', 'O nome deve ter entre 3 e 100 caracteres.', 'nome');
        }
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ? WHERE id = ?");
        $stmt->execute([$nome, $usuario_id]);
        $_SESSION['user_nome'] = $nome;
        perfil_redirecionar('success', 'Nome atualizado.', 'nome');
    }

    if ($acao === 'senha') {
        $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        $confirmaSenha = (string) ($_POST['confirma_senha'] ?? '');

        if (!password_verify($senhaAtual, $atual['senha'])) {
            usleep(random_int(300000, 600000)); // dificulta tentativas em sequência
            perfil_redirecionar('danger', 'A senha atual está incorreta.', 'senha');
        }
        if (strlen($novaSenha) < 6) {
            perfil_redirecionar('danger', 'A nova senha deve ter pelo menos 6 caracteres.', 'senha');
        }
        if ($novaSenha !== $confirmaSenha) {
            perfil_redirecionar('danger', 'A confirmação não confere com a nova senha.', 'senha');
        }

        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $usuario_id]);
        session_regenerate_id(true);
        perfil_redirecionar('success', 'Senha alterada.', 'senha');
    }

    perfil_redirecionar('danger', 'Ação inválida.');
}

// ===== DADOS PARA EXIBIÇÃO =====
$stmt = $pdo->prepare("SELECT nome, email, foto_perfil FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['user_nome'] = $usuario['nome'];
$_SESSION['user_foto'] = $usuario['foto_perfil'];

$stmt_notif = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0");
$stmt_notif->execute([$usuario_id]);
$total_notificacoes_nao_lidas = (int) $stmt_notif->fetchColumn();

$flash = $_SESSION['perfil_flash'] ?? null;
unset($_SESSION['perfil_flash']);
$temFoto = avatar_base_valida($usuario['foto_perfil']);
$senhaAberta = $flash && $flash['secao'] === 'senha' && $flash['tipo'] !== 'success';
$csrf = htmlspecialchars($_SESSION['csrf_token']);

function perfil_flash(?array $flash, string $secao): void
{
    if (!$flash || $flash['secao'] !== $secao) {
        return;
    }
    $classe = $flash['tipo'] === 'success' ? 'perfil-aviso-ok' : 'perfil-aviso-erro';
    echo '<p class="perfil-aviso ' . $classe . '" role="status">' . htmlspecialchars($flash['texto']) . '</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Risenglish</title>

    <?php if ($perfilTipo === 'aluno'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if ($perfilTipo === 'aluno'): ?>
        <link rel="stylesheet" href="../../css/aluno/dashboard.css?v=<?= @filemtime(__DIR__ . '/../../css/aluno/dashboard.css') ?>">
    <?php else: ?>
        <link rel="stylesheet" href="../../css/professor/base.css?v=<?= @filemtime(__DIR__ . '/../../css/professor/base.css') ?>">
        <link rel="stylesheet" href="../../css/professor/dashboard.css?v=<?= @filemtime(__DIR__ . '/../../css/professor/dashboard.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="../../css/avatar.css?v=<?= @filemtime(__DIR__ . '/../../css/avatar.css') ?>">
    <link rel="stylesheet" href="../../css/perfil.css?v=<?= @filemtime(__DIR__ . '/../../css/perfil.css') ?>">
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
</head>
<body>

<div class="container-fluid p-0">

    <?php
    $paginaAtiva = 'perfil';
    $tituloMobile = 'Meu Perfil';
    require __DIR__ . '/layout/' . $perfilTipo . '_sidebar.php';
    ?>

    <div class="row g-0">
        <div class="col-12 col-md-10 main-content p-4">
            <div class="perfil">
                <header class="perfil-cabecalho">
                    <h1>Meu Perfil</h1>
                    <p>Gerencie como você aparece para <?= $perfilTipo === 'aluno' ? 'seus professores' : 'seus alunos' ?> e a segurança da sua conta.</p>
                </header>

                <div class="perfil-painel">

                    <!-- ===== FOTO ===== -->
                    <section class="perfil-secao" id="secao-foto">
                        <div class="perfil-secao-info">
                            <h2>Foto de perfil</h2>
                            <p>Ajuda <?= $perfilTipo === 'aluno' ? 'seus professores' : 'seus alunos' ?> a reconhecerem você. Sem foto, mostramos suas iniciais.</p>
                        </div>
                        <div class="perfil-secao-campos">
                            <div class="perfil-foto-linha">
                                <div class="perfil-foto" id="fotoAtual">
                                    <?= avatar_html($usuario['nome'], $usuario['foto_perfil'], 88) ?>
                                </div>
                                <div>
                                    <div class="perfil-foto-botoes">
                                        <form method="post" enctype="multipart/form-data" id="formFoto">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="acao" value="foto">
                                            <input type="file" name="foto" id="inputFoto" accept="image/jpeg,image/png,image/webp" hidden>
                                            <button type="button" class="perfil-btn perfil-btn-secundario" id="btnEnviarFoto">
                                                <?= $temFoto ? 'Trocar foto' : 'Enviar foto' ?>
                                            </button>
                                        </form>
                                        <?php if ($temFoto): ?>
                                            <form method="post" id="formRemoverFoto">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="acao" value="remover_foto">
                                                <button type="submit" class="perfil-btn perfil-btn-perigo">Remover foto</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <p class="perfil-dica">JPG, PNG ou WebP de até 5 MB. Fotos retangulares são recortadas no centro.</p>
                                    <p class="perfil-aviso perfil-aviso-erro d-none" id="erroFoto" role="alert"></p>
                                    <?php perfil_flash($flash, 'foto'); ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ===== DADOS PESSOAIS ===== -->
                    <section class="perfil-secao" id="secao-nome">
                        <div class="perfil-secao-info">
                            <h2>Dados pessoais</h2>
                            <p>Seu nome é exibido em toda a plataforma.</p>
                        </div>
                        <div class="perfil-secao-campos">
                            <form method="post" id="formNome">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="acao" value="nome">

                                <label for="nome" class="perfil-label">Nome</label>
                                <input type="text" class="perfil-input" id="nome" name="nome" required minlength="3" maxlength="100"
                                       value="<?= htmlspecialchars($usuario['nome']) ?>" data-original="<?= htmlspecialchars($usuario['nome']) ?>">

                                <label for="email" class="perfil-label">E-mail</label>
                                <div class="perfil-input perfil-input-bloqueado" id="email" aria-describedby="emailAjuda">
                                    <span><?= htmlspecialchars($usuario['email']) ?></span>
                                    <i class="fas fa-lock" aria-hidden="true"></i>
                                </div>
                                <p class="perfil-dica" id="emailAjuda">O e-mail é usado para entrar na plataforma e não pode ser alterado.</p>

                                <div class="perfil-acoes">
                                    <?php perfil_flash($flash, 'nome'); ?>
                                    <button type="submit" class="perfil-btn perfil-btn-primario" id="btnSalvarNome" disabled>Salvar</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <!-- ===== SENHA ===== -->
                    <section class="perfil-secao" id="secao-senha">
                        <div class="perfil-secao-info">
                            <h2>Senha</h2>
                            <p>Use pelo menos 6 caracteres. Evite repetir senhas de outros sites.</p>
                        </div>
                        <div class="perfil-secao-campos">
                            <div class="perfil-senha-resumo<?= $senhaAberta ? ' d-none' : '' ?>" id="senhaResumo">
                                <span class="perfil-senha-pontos" aria-label="Senha oculta">••••••••</span>
                                <button type="button" class="perfil-btn perfil-btn-secundario" id="btnAbrirSenha">Alterar senha</button>
                            </div>
                            <?php if (!$senhaAberta) { perfil_flash($flash, 'senha'); } ?>

                            <form method="post" id="formSenha" class="<?= $senhaAberta ? '' : 'd-none' ?>">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="acao" value="senha">

                                <?php
                                $camposSenha = [
                                    'senha_atual'    => ['label' => 'Senha atual',          'auto' => 'current-password'],
                                    'nova_senha'     => ['label' => 'Nova senha',           'auto' => 'new-password'],
                                    'confirma_senha' => ['label' => 'Confirmar nova senha', 'auto' => 'new-password'],
                                ];
                                foreach ($camposSenha as $campo => $cfg): ?>
                                    <div class="perfil-label-linha">
                                        <label for="<?= $campo ?>" class="perfil-label"><?= $cfg['label'] ?></label>
                                        <?php if ($campo === 'senha_atual'): ?>
                                            <a href="../solicitar_reset" class="perfil-link">Esqueceu a senha?</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="perfil-senha-campo">
                                        <input type="password" class="perfil-input" id="<?= $campo ?>" name="<?= $campo ?>" required
                                               autocomplete="<?= $cfg['auto'] ?>"<?= $campo !== 'senha_atual' ? ' minlength="6"' : '' ?>>
                                        <button type="button" class="perfil-olho" data-alvo="<?= $campo ?>" aria-label="Mostrar senha" title="Mostrar senha">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>

                                <div class="perfil-acoes">
                                    <?php if ($senhaAberta) { perfil_flash($flash, 'senha'); } ?>
                                    <button type="button" class="perfil-btn perfil-btn-texto" id="btnCancelarSenha">Cancelar</button>
                                    <button type="submit" class="perfil-btn perfil-btn-primario">Salvar nova senha</button>
                                </div>
                            </form>
                        </div>
                    </section>

                </div>
            </div>

            <footer class="mt-5 text-center text-muted small">
                &copy; Risenglish
            </footer>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var MAX_BYTES = 5 * 1024 * 1024;

    // ----- Foto: enviada assim que é escolhida -----
    var inputFoto = document.getElementById('inputFoto');
    var btnEnviarFoto = document.getElementById('btnEnviarFoto');
    var erroFoto = document.getElementById('erroFoto');

    btnEnviarFoto.addEventListener('click', function () { inputFoto.click(); });

    inputFoto.addEventListener('change', function () {
        var arquivo = inputFoto.files && inputFoto.files[0];
        erroFoto.classList.add('d-none');
        if (!arquivo) return;

        var erro = '';
        if (['image/jpeg', 'image/png', 'image/webp'].indexOf(arquivo.type) === -1) {
            erro = 'Formato não suportado. Envie uma imagem JPG, PNG ou WebP.';
        } else if (arquivo.size > MAX_BYTES) {
            erro = 'A imagem é muito grande. Envie uma foto de até 5 MB.';
        }
        if (erro) {
            inputFoto.value = '';
            erroFoto.textContent = erro;
            erroFoto.classList.remove('d-none');
            return;
        }

        var leitor = new FileReader();
        leitor.onload = function (e) {
            var img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Nova foto de perfil';
            img.className = 'avatar';
            img.width = 88;
            img.height = 88;
            var alvo = document.getElementById('fotoAtual');
            alvo.replaceChildren(img);
            alvo.classList.add('perfil-foto-enviando');
        };
        leitor.readAsDataURL(arquivo);

        btnEnviarFoto.disabled = true;
        btnEnviarFoto.textContent = 'Enviando…';
        document.getElementById('formFoto').submit();
    });

    var formRemover = document.getElementById('formRemoverFoto');
    if (formRemover) {
        formRemover.addEventListener('submit', function (e) {
            if (!confirm('Remover sua foto de perfil? Seu avatar voltará a mostrar suas iniciais.')) {
                e.preventDefault();
            }
        });
    }

    // ----- Nome: "Salvar" só fica ativo quando algo mudou -----
    var inputNome = document.getElementById('nome');
    var btnSalvarNome = document.getElementById('btnSalvarNome');
    inputNome.addEventListener('input', function () {
        btnSalvarNome.disabled = inputNome.value.trim() === inputNome.dataset.original;
    });

    // ----- Senha: formulário aparece só ao clicar em "Alterar senha" -----
    var resumo = document.getElementById('senhaResumo');
    var formSenha = document.getElementById('formSenha');

    document.getElementById('btnAbrirSenha').addEventListener('click', function () {
        resumo.classList.add('d-none');
        formSenha.classList.remove('d-none');
        document.getElementById('senha_atual').focus();
    });

    document.getElementById('btnCancelarSenha').addEventListener('click', function () {
        formSenha.reset();
        formSenha.querySelectorAll('.perfil-aviso').forEach(function (el) { el.remove(); });
        formSenha.classList.add('d-none');
        resumo.classList.remove('d-none');
    });

    document.querySelectorAll('.perfil-olho').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var campo = document.getElementById(btn.dataset.alvo);
            var mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            btn.querySelector('i').className = mostrar ? 'fas fa-eye-slash' : 'fas fa-eye';
            btn.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
            btn.title = mostrar ? 'Ocultar senha' : 'Mostrar senha';
        });
    });

    var confirma = document.getElementById('confirma_senha');
    formSenha.addEventListener('submit', function (e) {
        if (document.getElementById('nova_senha').value !== confirma.value) {
            e.preventDefault();
            confirma.setCustomValidity('A confirmação não confere com a nova senha.');
            confirma.reportValidity();
        }
    });
    confirma.addEventListener('input', function () { confirma.setCustomValidity(''); });
})();
</script>
</body>
</html>
