<?php
// Inicia a sessão para controle do usuário
session_start();

// Inclui o arquivo de conexão com o banco de dados
require_once 'includes/conexao.php';

// Define o fuso horário para garantir que o tempo de bloqueio seja preciso
date_default_timezone_set('America/Sao_Paulo');

// Variáveis de Segurança (Configuráveis)
$MAX_TENTATIVAS = 5;          // Número de erros permitidos
$TEMPO_BLOQUEIO_MINUTOS = 15; // Tempo de castigo/esquecimento em minutos

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // 1. Busca o usuário pelo email, trazendo também os dados de bloqueio
    $sql = "SELECT id, nome, senha, tipo_usuario, status, tentativas_falhas, bloqueado_ate FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $agora = new DateTime();
        $bloqueado = false;

        // ==========================================================
        // JANELA DE ESQUECIMENTO (ZERAR ERROS POR TEMPO)
        // ==========================================================
        if ($usuario['tentativas_falhas'] > 0 && !empty($usuario['bloqueado_ate'])) {
            $validade_erro = new DateTime($usuario['bloqueado_ate']);
            
            // Se o usuário NÃO atingiu o limite máximo mas o tempo do último erro já passou, reseta os erros
            if ($usuario['tentativas_falhas'] < $MAX_TENTATIVAS && $agora > $validade_erro) {
                $sqlResetTempo = "UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = :id";
                $stmtResetTempo = $pdo->prepare($sqlResetTempo);
                $stmtResetTempo->bindParam(':id', $usuario['id']);
                $stmtResetTempo->execute();
                
                // Atualiza as variáveis locais para o restante do script
                $usuario['tentativas_falhas'] = 0;
                $usuario['bloqueado_ate'] = null;
            }
        }

        // 2. Verifica se a conta está atualmente bloqueada (estouro de limite)
        if ($usuario['tentativas_falhas'] >= $MAX_TENTATIVAS && !empty($usuario['bloqueado_ate'])) {
            $bloqueio = new DateTime($usuario['bloqueado_ate']);
            
            if ($agora < $bloqueio) {
                $bloqueado = true;
                $diff = $agora->diff($bloqueio);
                $minutos_restantes = $diff->i + ($diff->h * 60) + ($diff->days * 24 * 60) + 1; // Arredonda para cima
                $erro = "Por motivos de segurança, sua conta foi temporariamente bloqueada. Tente novamente em {$minutos_restantes} minuto(s).";
            }
        }

        // 3. Se não estiver bloqueado, prossegue com a validação da senha
        if (!$bloqueado) {
            if (password_verify($senha, $usuario['senha'])) {
                // ---> SENHA CORRETA <---
                if (isset($usuario['status']) && $usuario['status'] === 'desativado') {
                    $erro = "Conta desativada. Contate o administrador para reativação.";
                } else {
                    // Reseta os contadores de erro no banco de dados
                    $sqlReset = "UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = :id";
                    $stmtReset = $pdo->prepare($sqlReset);
                    $stmtReset->bindParam(':id', $usuario['id']);
                    $stmtReset->execute();

                    // ==========================================
                    // PROTEÇÃO CONTRA SESSION HIJACKING/FIXATION
                    // ==========================================
                    session_regenerate_id(true); 

                    // Define os dados do login bem-sucedido
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['user_nome'] = $usuario['nome'];
                    $_SESSION['user_tipo'] = $usuario['tipo_usuario'];

                    // Registra o acesso com horário de Brasília (UTC-3)
                    $sqlLog = "INSERT INTO logs_acesso (usuario_id, data_acesso) VALUES (:id, CONVERT_TZ(NOW(), '+00:00', '-03:00'))";
                    $stmtLog = $pdo->prepare($sqlLog);
                    $stmtLog->bindParam(':id', $usuario['id']);
                    $stmtLog->execute();

                    // Redireciona de acordo com o nível de acesso
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
                // ---> SENHA INCORRETA <---
                $tentativas = $usuario['tentativas_falhas'] + 1;
                
                // Define a validade desse erro ou do bloqueio (Sempre +15 minutos do horário atual)
                $futuro = new DateTime();
                $futuro->modify("+{$TEMPO_BLOQUEIO_MINUTOS} minutes");
                $bloqueado_ate = $futuro->format('Y-m-d H:i:s');

                if ($tentativas >= $MAX_TENTATIVAS) {
                    $erro = "Muitas tentativas incorretas. Sua conta foi bloqueada por {$TEMPO_BLOQUEIO_MINUTOS} minutos.";
                } else {
                    $tentativas_restantes = $MAX_TENTATIVAS - $tentativas;
                    $erro = "Email ou senha incorretos. Você tem mais {$tentativas_restantes} tentativa(s).";
                }

                // Atualiza o registro de falhas no banco
                $sqlUpdate = "UPDATE usuarios SET tentativas_falhas = :tentativas, bloqueado_ate = :bloqueado_ate WHERE id = :id";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':tentativas', $tentativas, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':bloqueado_ate', $bloqueado_ate);
                $stmtUpdate->bindParam(':id', $usuario['id']);
                $stmtUpdate->execute();
            }
        }
    } else {
        // Mensagem genérica para segurança (evita mapeamento de e-mails válidos)
        $erro = "Email ou senha incorretos.";
    }
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
        <?= $erro ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
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