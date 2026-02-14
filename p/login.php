<?php
// Inicia a sessão para controle do usuário
session_start();

// Inclui o arquivo de conexão com o banco de dados
// Certifique-se de que o caminho 'includes/conexao.php' está correto.
require_once 'includes/conexao.php';

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Recebe os dados do POST de forma segura, usando o operador null coalescing (??)
  $email = $_POST['email'] ?? ''; // Esta é a linha 10 que estava com erro.
  $senha = $_POST['senha'] ?? '';

  // 1. Prepara a consulta para buscar o usuário pelo email (inclui status)
  $sql = "SELECT id, nome, senha, tipo_usuario, status FROM usuarios WHERE email = :email";
  $stmt = $pdo->prepare($sql);
  $stmt->bindParam(':email', $email);
  $stmt->execute();
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  // 2. Verifica se o usuário existe e se a senha está correta
  if ($usuario && password_verify($senha, $usuario['senha'])) {
    // Verifica se conta está desativada
    if (isset($usuario['status']) && $usuario['status'] === 'desativado') {
      $erro = "Conta desativada. Contate o administrador para reativação.";
    } else {
      // Login bem-sucedido
      // 3. Armazena os dados do usuário na sessão
      $_SESSION['user_id'] = $usuario['id'];
      $_SESSION['user_nome'] = $usuario['nome'];
      $_SESSION['user_tipo'] = $usuario['tipo_usuario']; // 'admin', 'professor' ou 'aluno'

      // 4. Redireciona de acordo com o tipo de usuário
      switch ($_SESSION['user_tipo']) {
        case 'admin':
          header("Location: admin/dashboard.php");
          break;
        case 'professor':
          header("Location: professor/dashboard.php");
          break;
        case 'aluno':
          header("Location: aluno/dashboard.php");
          break;
        default:
          // Se o tipo for desconhecido, destrói a sessão e volta para o login
          session_unset();
          session_destroy();
          header("Location: login.php?erro=tipo_invalido");
      }
      exit;
    }
  } else {
    // Credenciais inválidas
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
