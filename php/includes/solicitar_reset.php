<?php
    include_once 'includes/conexao.php';
    include_once 'includes/email_config.php'; // Inclui a configuração do PHPMailer
    
    // Certifique-se de incluir os arquivos do PHPMailer aqui, conforme seu caminho
    // Ex: require 'caminho/para/PHPMailer/src/PHPMailer.php';
    // ...

    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
        $email = trim($_POST['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div class="alert alert-danger">Email inválido.</div>';
        } else {
            try {
                // 1. Verificar se o aluno existe
                $stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE email = ?");
                $stmt->execute([$email]);
                $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($aluno) {
                    // 2. Gerar Token Único e Tempo de Expiração (Ex: 1 hora)
                    $token = bin2hex(random_bytes(32)); // Token de 64 caracteres
                    $expira_em = date("Y-m-d H:i:s", time() + 3600); // Expira em 1 hora

                    // 3. Salvar Token no Banco
                    $stmt_update = $pdo->prepare("UPDATE alunos SET reset_token = ?, token_expira_em = ? WHERE id = ?");
                    $stmt_update->execute([$token, $expira_em, $aluno['id']]);

                    // 4. Enviar E-mail
                    $mail = configurarPHPMailer();
                    if ($mail) {
                        $mail->addAddress($email, $aluno['nome']);
                        $mail->Subject = 'Redefinicao de Senha - RisEnglish';
                        
                        // O link apontará para a página onde a senha será trocada
                        // ALtere 'http://seu-dominio.com.br/' para o seu domínio real!
                        $reset_link = "http://seu-dominio.com.br/redefinir_senha.php?token=" . $token;

                        $mail->Body    = "
                            <h2>Ola, {$aluno['nome']}!</h2>
                            <p>Voce solicitou a redefinicao de senha para sua conta RisEnglish.</p>
                            <p>Clique no link abaixo para criar uma nova senha. Este link expira em 1 hora.</p>
                            <p><a href='{$reset_link}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Redefinir Senha</a></p>
                            <p>Se voce nao solicitou esta redefinicao, por favor, ignore este email.</p>
                            <br>
                            <small>Link direto: {$reset_link}</small>
                        ";

                        $mail->send();
                        $message = '<div class="alert alert-success">Um link de redefinição de senha foi enviado para o seu email. Verifique sua caixa de entrada e spam.</div>';
                    } else {
                         // O PHPMailer falhou em ser configurado/enviado
                        $message = '<div class="alert alert-warning">Não foi possível enviar o e-mail de redefinição. Tente novamente mais tarde.</div>';
                    }
                } else {
                    // MENSAGEM GENÉRICA POR SEGURANÇA: Não revela se o email existe ou não
                    $message = '<div class="alert alert-success">Um link de redefinição de senha foi enviado para o seu email (se a conta existir). Verifique sua caixa de entrada e spam.</div>';
                }
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Erro no processamento da solicitação. Tente novamente.</div>';
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci Minha Senha</title>
    <!-- Inclua o CSS da sua tela de login aqui -->
    <link rel="stylesheet" href="caminho/para/seu/login.css">
    <!-- Font Awesome para ícones -->
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
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 style="text-align: center; color: #333;">Esqueci Minha Senha</h2>

        <?php echo $message; ?>

        <form method="POST" action="solicitar_reset.php">
            <p style="text-align: center; margin-bottom: 25px;">Informe o email cadastrado para receber o link de redefinição.</p>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="seu.email@exemplo.com">
            </div>

            <button type="submit" class="btn-primary">Enviar Link</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;"><a href="login.php" style="color: #007bff;">Voltar para o Login</a></p>
    </div>
</body>
</html>
