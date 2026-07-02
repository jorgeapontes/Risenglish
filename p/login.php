<?php
// Configurações de sessão antes de iniciar a sessão
$tempo_sessao = 1800;
ini_set('session.gc_maxlifetime', $tempo_sessao);
session_set_cookie_params([
    'lifetime' => $tempo_sessao,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443),
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Inicia a sessão para controle do usuário
session_start();

// =============================================
// SE O USUÁRIO JÁ ESTIVER LOGADO, REDIRECIONA
// =============================================
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_tipo']) {
        case 'admin':     header("Location: admin/dashboard.php"); break;
        case 'professor': header("Location: professor/dashboard.php"); break;
        case 'aluno':     header("Location: aluno/dashboard.php"); break;
        default:          header("Location: login.php?erro=sessao_invalida");
    }
    exit;
}

// =============================================
// GERAÇÃO DO TOKEN CSRF
// Gera um token único por sessão, caso ainda não exista
// =============================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inclui o arquivo de conexão com o banco de dados
require_once 'includes/conexao.php';

// Define o fuso horário para garantir que o tempo de bloqueio seja preciso
date_default_timezone_set('America/Sao_Paulo');

// Variáveis de Segurança (Configuráveis)
$MAX_TENTATIVAS = 5;
$TEMPO_BLOQUEIO_MINUTOS = 15;
$MAX_TENTATIVAS_IP = 20;
$TEMPO_BLOQUEIO_IP_MINUTOS = 60;

// Obtém o IP do cliente a partir da conexão direta.
// Evita confiar em headers como X-Forwarded-For, que podem ser forjados.
function getRealIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

$ip_usuario = getRealIP();

// =============================================
// FUNÇÃO PARA SIMULAR VERIFICAÇÃO DE SENHA (evita timing attack)
// =============================================
function simulatePasswordVerify() {
    usleep(rand(40000, 60000)); // 40-60ms (similar ao password_verify)
}

// =============================================
// 1. VERIFICA BLOQUEIO POR IP (tentativas excessivas)
// =============================================
$ip_bloqueado = false;
$sqlIpBlock = "SELECT tentativas, bloqueado_ate FROM tentativas_ip WHERE ip = :ip";
$stmtIpBlock = $pdo->prepare($sqlIpBlock);
$stmtIpBlock->bindParam(':ip', $ip_usuario);
$stmtIpBlock->execute();
$ipBlockData = $stmtIpBlock->fetch(PDO::FETCH_ASSOC);

$agora = new DateTime();

if ($ipBlockData) {
    if ($ipBlockData['tentativas'] >= $MAX_TENTATIVAS_IP && !empty($ipBlockData['bloqueado_ate'])) {
        $bloqueio_ip = new DateTime($ipBlockData['bloqueado_ate']);
        if ($agora < $bloqueio_ip) {
            $ip_bloqueado = true;
            $diff = $agora->diff($bloqueio_ip);
            $minutos_restantes = $diff->i + ($diff->h * 60) + 1;
            $erro = "Muitas tentativas deste endereço IP. Aguarde {$minutos_restantes} minutos.";
        } else {
            $sqlCleanIp = "DELETE FROM tentativas_ip WHERE ip = :ip";
            $stmtCleanIp = $pdo->prepare($sqlCleanIp);
            $stmtCleanIp->bindParam(':ip', $ip_usuario);
            $stmtCleanIp->execute();
        }
    }
}

// =============================================
// FUNÇÃO PARA REGISTRAR TENTATIVA FALHA POR IP
// =============================================
function registerFailedAttemptIP($pdo, $ip, $max_tentativas, $tempo_bloqueio_minutos) {
    $agora = new DateTime();
    $futuro = clone $agora;
    $futuro->modify("+{$tempo_bloqueio_minutos} minutes");
    $bloqueado_ate = $futuro->format('Y-m-d H:i:s');

    $sqlCheck = "SELECT id, tentativas FROM tentativas_ip WHERE ip = :ip";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindParam(':ip', $ip);
    $stmtCheck->execute();
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $novas_tentativas = $existing['tentativas'] + 1;
        $sqlUpdate = "UPDATE tentativas_ip SET tentativas = :tentativas, bloqueado_ate = :bloqueado_ate, ultima_tentativa = NOW() WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':tentativas', $novas_tentativas, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':bloqueado_ate', $bloqueado_ate);
        $stmtUpdate->bindParam(':id', $existing['id']);
        $stmtUpdate->execute();
    } else {
        $sqlInsert = "INSERT INTO tentativas_ip (ip, tentativas, bloqueado_ate, ultima_tentativa) VALUES (:ip, 1, :bloqueado_ate, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->bindParam(':ip', $ip);
        $stmtInsert->bindParam(':bloqueado_ate', $bloqueado_ate);
        $stmtInsert->execute();
    }
}

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$ip_bloqueado) {

    // =============================================
    // 2. VALIDAÇÃO DO TOKEN CSRF
    // Rejeita a requisição se o token estiver ausente ou incorreto
    // =============================================
    $csrf_token_post = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token_post) || !hash_equals($_SESSION['csrf_token'], $csrf_token_post)) {
        // Token inválido — rejeita silenciosamente e regenera o token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $erro = "Requisição inválida. Por favor, tente novamente.";
    } else {

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $usuario_encontrado = false;
        $usuario = null;

        $sql = "SELECT id, nome, senha, tipo_usuario, status, tentativas_falhas, bloqueado_ate FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $usuario_encontrado = true;
            $bloqueado = false;

            // JANELA DE ESQUECIMENTO (ZERAR ERROS POR TEMPO)
            if ($usuario['tentativas_falhas'] > 0 && !empty($usuario['bloqueado_ate'])) {
                $validade_erro = new DateTime($usuario['bloqueado_ate']);

                if ($usuario['tentativas_falhas'] < $MAX_TENTATIVAS && $agora > $validade_erro) {
                    $sqlResetTempo = "UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = :id";
                    $stmtResetTempo = $pdo->prepare($sqlResetTempo);
                    $stmtResetTempo->bindParam(':id', $usuario['id']);
                    $stmtResetTempo->execute();

                    $usuario['tentativas_falhas'] = 0;
                    $usuario['bloqueado_ate'] = null;
                }
            }

            // Verifica se a conta está bloqueada
            if ($usuario['tentativas_falhas'] >= $MAX_TENTATIVAS && !empty($usuario['bloqueado_ate'])) {
                $bloqueio = new DateTime($usuario['bloqueado_ate']);

                if ($agora < $bloqueio) {
                    $bloqueado = true;
                    $diff = $agora->diff($bloqueio);
                    $minutos_restantes = $diff->i + ($diff->h * 60) + 1;
                    $erro = "Por motivos de segurança, sua conta foi temporariamente bloqueada. Tente novamente em {$minutos_restantes} minuto(s).";
                }
            }

            if (!$bloqueado) {
                $senha_correta = password_verify($senha, $usuario['senha']);

                if ($senha_correta) {
                    if (isset($usuario['status']) && $usuario['status'] === 'desativado') {
                        $erro = "Conta desativada. Contate o administrador para reativação.";
                    } else {
                        $sqlReset = "UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = :id";
                        $stmtReset = $pdo->prepare($sqlReset);
                        $stmtReset->bindParam(':id', $usuario['id']);
                        $stmtReset->execute();

                        $sqlCleanIp = "DELETE FROM tentativas_ip WHERE ip = :ip";
                        $stmtCleanIp = $pdo->prepare($sqlCleanIp);
                        $stmtCleanIp->bindParam(':ip', $ip_usuario);
                        $stmtCleanIp->execute();

                        // Regenera a sessão (proteção contra session fixation)
                        session_regenerate_id(true);

                        // Remove o token CSRF após login bem-sucedido
                        unset($_SESSION['csrf_token']);

                        $_SESSION['user_id']    = $usuario['id'];
                        $_SESSION['user_nome']  = $usuario['nome'];
                        $_SESSION['user_tipo']  = $usuario['tipo_usuario'];
                        $_SESSION['user_ip']    = $ip_usuario;
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['login_time'] = time();

                        $sqlLog = "INSERT INTO logs_acesso (usuario_id, data_acesso) VALUES (:id, CONVERT_TZ(NOW(), '+00:00', '-03:00'))";
                        $stmtLog = $pdo->prepare($sqlLog);
                        $stmtLog->bindParam(':id', $usuario['id']);
                        $stmtLog->execute();

                        switch ($_SESSION['user_tipo']) {
                            case 'admin':     header("Location: admin/dashboard.php"); break;
                            case 'professor': header("Location: professor/dashboard.php"); break;
                            case 'aluno':     header("Location: aluno/dashboard.php"); break;
                            default:
                                session_unset();
                                session_destroy();
                                header("Location: login.php?erro=tipo_invalido");
                        }
                        exit;
                    }
                } else {
                    $tentativas = $usuario['tentativas_falhas'] + 1;
                    $futuro = new DateTime();
                    $futuro->modify("+{$TEMPO_BLOQUEIO_MINUTOS} minutes");
                    $bloqueado_ate = $futuro->format('Y-m-d H:i:s');

                    if ($tentativas >= $MAX_TENTATIVAS) {
                        $erro = "Muitas tentativas incorretas. Sua conta foi bloqueada por {$TEMPO_BLOQUEIO_MINUTOS} minutos.";
                    } else {
                        $tentativas_restantes = $MAX_TENTATIVAS - $tentativas;
                        $erro = "Email ou senha incorretos. Você tem mais {$tentativas_restantes} tentativa(s).";
                    }

                    $sqlUpdate = "UPDATE usuarios SET tentativas_falhas = :tentativas, bloqueado_ate = :bloqueado_ate WHERE id = :id";
                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(':tentativas', $tentativas, PDO::PARAM_INT);
                    $stmtUpdate->bindParam(':bloqueado_ate', $bloqueado_ate);
                    $stmtUpdate->bindParam(':id', $usuario['id']);
                    $stmtUpdate->execute();

                    registerFailedAttemptIP($pdo, $ip_usuario, $MAX_TENTATIVAS_IP, $TEMPO_BLOQUEIO_IP_MINUTOS);
                }
            }
        }

        // PROTEÇÃO CONTRA TIMING ATTACK
        if (!$usuario_encontrado) {
            simulatePasswordVerify();
            $erro = "Email ou senha incorretos.";
            registerFailedAttemptIP($pdo, $ip_usuario, $MAX_TENTATIVAS_IP, $TEMPO_BLOQUEIO_IP_MINUTOS);
        }

    } // fim da validação CSRF
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Risenglish</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/login.css">
  <link rel="shortcut icon" href="../LogoRisenglish.png" type="image/x-icon">
</head>

<body>

  <div class="login-card">
    <h2>RISENGLISH</h2>
    <h3>LOGIN</h3>

    <?php if (isset($erro)): ?>
      <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($erro) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <!-- Token CSRF: campo oculto validado no servidor -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div class="mb-3 text-start">
        <label for="email" class="form-label">E-MAIL</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Digite seu e-mail" required>
      </div>
      <div class="mb-3 text-start">
        <label for="senha" class="form-label">SENHA</label>
        <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required>
      </div>

      <button type="submit" class="btn btn-login">ENTRAR</button>
    </form>
    <a class="footer-text" href="../index.php">← Home</a><br>
    <a href="solicitar_reset.php" class="footer-text">Esqueceu sua senha?</a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>