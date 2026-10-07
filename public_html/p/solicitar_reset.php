<?php
// Mesma configuração de cookie de sessão usada no login.php
session_set_cookie_params([
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Inclui os arquivos necessários
include_once 'includes/conexao.php';
include_once 'includes/email_config.php';

$msg_enviado = 'Se o e-mail estiver cadastrado, um link de redefinição foi enviado. Verifique sua caixa de entrada e spam.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flash = ['tipo' => 'danger', 'texto' => 'E-mail inválido.'];
    } else {
        try {
            // 1. Verificar se o usuário existe
            $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $usuario_id = $usuario['id'];
                $usuario_nome = $usuario['nome'];

                // 2. Gerar Token Único
                $token = bin2hex(random_bytes(32));

                // 3. Salvar Token no Banco
                // A expiração é calculada pelo próprio MySQL para usar o mesmo relógio do NOW() em redefinir_senha.php
                // (o PHP roda em America/Sao_Paulo e o MySQL de produção em UTC)
                $stmt_update = $pdo->prepare("UPDATE usuarios SET reset_token = ?, token_expira_em = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                $stmt_update->execute([$token, $usuario_id]);

                // 4. Montar Link de Redefinição
                // Usa APP_URL do .env para não depender do header Host (que pode ser forjado)
                $host = getenv('APP_URL') ?: ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}");
                $host = rtrim($host, '/');
                $linkReset = "{$host}/p/redefinir_senha?token={$token}";

                // 5. Enviar E-mail
                if (enviarEmailReset($email, $usuario_nome, $linkReset)) {
                    $flash = ['tipo' => 'success', 'texto' => $msg_enviado];
                } else {
                    $flash = ['tipo' => 'danger', 'texto' => 'Houve um erro no envio do e-mail. Tente novamente mais tarde.'];
                }

            } else {
                // Mensagem genérica por segurança
                $flash = ['tipo' => 'success', 'texto' => $msg_enviado];
            }
        } catch (Exception $e) {
            error_log("Erro no processo de reset: " . $e->getMessage());
            $flash = ['tipo' => 'danger', 'texto' => 'Houve um erro no servidor. Tente novamente mais tarde.'];
        }
    }

    // Post/Redirect/Get: guarda o feedback na sessão e redireciona,
    // assim recarregar a página não reenvia o formulário
    $_SESSION['flash_reset'] = $flash;
    header('Location: solicitar_reset', true, 303);
    exit;
}

// Lê e descarta o feedback (aparece só uma vez)
$flash = $_SESSION['flash_reset'] ?? null;
unset($_SESSION['flash_reset']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>Esqueci Minha Senha - Ris English</title>
    <link rel="stylesheet" href="../css/solicitar_reset.css">
    <link rel="shortcut icon" href="../LogoRisenglish.png" type="image/x-icon">
</head>
<body>
    <?php if ($flash): ?>
        <div class="toast toast-<?= $flash['tipo'] === 'success' ? 'success' : 'danger' ?>" id="toast"
             role="<?= $flash['tipo'] === 'success' ? 'status' : 'alert' ?>" aria-live="<?= $flash['tipo'] === 'success' ? 'polite' : 'assertive' ?>">
            <div class="toast-body">
                <span class="toast-icon" aria-hidden="true"><?= $flash['tipo'] === 'success' ? '&#10003;' : '&#10005;' ?></span>
                <span class="toast-text"><?= htmlspecialchars($flash['texto'], ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" class="toast-close" aria-label="Fechar" onclick="fecharToast()">&times;</button>
            </div>
            <div class="toast-progress"></div>
        </div>
    <?php endif; ?>

    <div class="login-container">
        <h2>Esqueci Minha Senha</h2>

        <form method="POST" action="solicitar_reset">
            <p style="text-align: center; margin-bottom: 25px; color: #666;">Informe o email cadastrado para redefinir a senha.</p>
            <hr>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <button type="submit" class="btn-primary">Solicitar Redefinição</button>
        </form>

        <a class="btn-home" href="../">&larr; Voltar para a Home</a>
    </div>

    <script>
        function fecharToast() {
            var toast = document.getElementById('toast');
            if (!toast || toast.classList.contains('toast-hide')) return;
            toast.classList.add('toast-hide');
            setTimeout(function () { toast.remove(); }, 300);
        }

        // Some sozinho depois de 5 segundos (mesma duração da barra de progresso no CSS)
        if (document.getElementById('toast')) {
            setTimeout(fecharToast, 5000);
        }
    </script>
</body>
</html>
