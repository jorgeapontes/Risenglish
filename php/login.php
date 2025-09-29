<?php
// Inicia a sessão para controle do usuário
session_start();

// Inclui o arquivo de conexão com o banco de dados
require_once 'includes/conexao.php';

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // 1. Prepara a consulta para buscar o usuário pelo email
    $sql = "SELECT id, nome, senha, tipo_usuario FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verifica se o usuário existe e se a senha está correta
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Login bem-sucedido
        
        // 3. Armazena os dados do usuário na sessão
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nome'] = $usuario['nome'];
        $_SESSION['user_tipo'] = $usuario['tipo_usuario']; // 'admin', 'professor' ou 'aluno'
        
        // 4. Redireciona de acordo com o tipo de usuário [cite: 42, 48, 37]
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
    } else {
        // Credenciais inválidas
        $erro = "Email ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Risenglish/css/login.css">
</head>
<body>

    <div class="login-container">
        <h2 class="text-center">LOGIN - Risenglish</h2>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-4">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn btn-login w-100">ENTRAR</button>
        </form>
        
        <p class="mt-3 text-center"><a href="index.php" style="color: var(--cor-primaria);">← Voltar para a Home</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>