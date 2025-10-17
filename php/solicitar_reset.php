<?php
// ATIVAR EXIBIÇÃO DE ERROS (REMOVA DEPOIS)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclui os arquivos necessários
include_once 'includes/conexao.php';
include_once 'includes/email_config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Debug
    error_log("Email recebido: " . $email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Email inválido.</div>';
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
                $expira_em = date("Y-m-d H:i:s", time() + 3600);

                // 3. Salvar Token no Banco
                $stmt_update = $pdo->prepare("UPDATE usuarios SET reset_token = ?, token_expira_em = ? WHERE id = ?");
                $stmt_update->execute([$token, $expira_em, $usuario_id]);
                
                // 4. Montar Link de Redefinição
                $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
                $linkReset = "{$host}/php/redefinir_senha.php?token={$token}";
                
                // Debug
                error_log("Link gerado: " . $linkReset);
                
                // 5. Enviar E-mail (CORRIGIDO: usar enviarEmailReset)
                if (enviarEmailReset($email, $usuario_nome, $linkReset)) {
                    $message = '<div class="alert alert-success">Se o e-mail estiver cadastrado, um link de redefinição foi enviado. Verifique sua caixa de entrada e spam.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Houve um erro no envio do e-mail. Tente novamente mais tarde.</div>';
                }

            } else {
                // Mensagem genérica por segurança
                $message = '<div class="alert alert-warning">Um link de redefinição de senha foi enviado para o e-amil fornecido.</div>';
            }
        } catch (Exception $e) {
            error_log("Erro no processo de reset: " . $e->getMessage());
            $message = '<div class="alert alert-danger">Erro de servidor: ' . $e->getMessage() . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci Minha Senha - Ris English</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(to bottom right, #8B0000, #0B2C59);; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .login-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.15); width: 100%; max-width: 400px; height: 350px; }
        h2 { text-align: center; color: #333; margin-top: 0; }
        .form-group { margin-bottom: 20px; margin-top: 40px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; transition: border-color 0.3s; }
        .form-control:focus { border-color: #007bff; outline: none; }
        .btn-primary { 
            background-color: #0B2C59; 
            color: white; 
            padding: 12px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease; 
            margin-top: 15px;
        }
        .btn-primary:hover { background-color: #173b6d; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; text-align: center; font-weight: bold; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Esqueci Minha Senha</h2>

        <?php echo $message; ?>

        <form method="POST" action="solicitar_reset.php">
            <p style="text-align: center; margin-bottom: 25px; color: #666;">Informe o email cadastrado para redefinir a senha.</p>
            <hr>
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <button type="submit" class="btn-primary">Solicitar Redefinição</button>
        </form>
    </div>
</body>
</html>