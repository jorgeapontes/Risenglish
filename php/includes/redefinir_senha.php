<?php
    include_once 'includes/conexao.php'; 

    $token = $_GET['token'] ?? null;
    $aluno_id = 0;
    $message = '';
    $token_valido = false;

    // 1. Verifica a validade do token
    if ($token) {
        try {
            // Verifica se o token existe e se ainda não expirou
            $stmt = $pdo->prepare("SELECT id FROM alunos WHERE reset_token = ? AND token_expira_em > NOW()");
            $stmt->execute([$token]);
            $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($aluno) {
                $token_valido = true;
                $aluno_id = $aluno['id'];
            } else {
                $message = '<div class="alert alert-danger">O link de redefinição é inválido ou expirou. Solicite um novo link.</div>';
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Erro ao verificar o token.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Token de redefinição não fornecido.</div>';
    }

    // 2. Processa a Nova Senha (se o token for válido)
    if ($token_valido && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {
        $nova_senha = $_POST['nova_senha'];
        $confirma_senha = $_POST['confirma_senha'];

        if (strlen($nova_senha) < 6) {
            $message = '<div class="alert alert-danger">A nova senha deve ter pelo menos 6 caracteres.</div>';
        } elseif ($nova_senha !== $confirma_senha) {
            $message = '<div class="alert alert-danger">As senhas não coincidem.</div>';
        } else {
            try {
                // Encripta a nova senha (usando password_hash)
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

                // Atualiza a senha e limpa o token e a expiração (para invalidar o link)
                $stmt_update = $pdo->prepare("UPDATE alunos SET senha = ?, reset_token = NULL, token_expira_em = NULL WHERE id = ?");
                $stmt_update->execute([$senha_hash, $aluno_id]);

                $message = '<div class="alert alert-success">Sua senha foi redefinida com sucesso! Você pode <a href="login.php" style="font-weight: bold;">fazer login agora</a>.</div>';
                $token_valido = false; // Desativa o formulário após o sucesso

            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Erro ao salvar a nova senha.</div>';
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <!-- Inclua o CSS da sua tela de login aqui -->
    <link rel="stylesheet" href="caminho/para/seu/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Estilos básicos para este formulário, use seu CSS de login */
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f0f2f5; }
        .login-container { background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); width: 350px; }
        .form-group { margin-bottom: 20px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-primary { background-color: #007bff; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        .btn-primary:hover { background-color: #0056b3; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 style="text-align: center; color: #333;">Nova Senha</h2>

        <?php echo $message; ?>

        <?php if ($token_valido): ?>
            <form method="POST" action="redefinir_senha.php?token=<?php echo htmlspecialchars($token); ?>">
                <p style="text-align: center; margin-bottom: 25px;">Digite e confirme sua nova senha.</p>
                
                <div class="form-group">
                    <label for="nova_senha">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirma_senha">Confirme a Nova Senha</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" class="form-control" required>
                </div>

                <button type="submit" class="btn-primary">Salvar Nova Senha</button>
            </form>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 20px;"><a href="login.php" style="color: #007bff;">Voltar para o Login</a></p>
    </div>
</body>
</html>
