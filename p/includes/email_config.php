<?php
// Carrega a biblioteca PHPMailer (Certifique-se de que o PHPMailer esteja instalado e acessível)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// =======================================================================================
// !!! CAMINHOS AJUSTADOS: voltando uma pasta (de 'includes' para 'php/') e entrando em 'vendor' !!!
// =======================================================================================
require '../vendor/PHPMailer/src/Exception.php'; 
require '../vendor/PHPMailer/src/PHPMailer.php'; 
require '../vendor/PHPMailer/src/SMTP.php';      
// =======================================================================================

/**
 * Função que configura e envia o e-mail de redefinição de senha.
 * @param string $destinatario O email do usuário que solicitou a redefinição.
 * @param string $nomeDestinatario O nome do usuário.
 * @param string $linkReset O link de redefinição de senha com o token.
 * @return bool Retorna true se o envio foi bem-sucedido, false caso contrário.
 */
function enviarEmailReset($destinatario, $nomeDestinatario, $linkReset) {
    // Passar 'true' habilita exceções para tratamento de erros
    $mail = new PHPMailer(true); 

    try {
        // Configurações do Servidor SMTP da Hostinger
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com.br'; // Servidor SMTP da Hostinger
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contato@risenglish.com.br'; // SEU NOVO E-MAIL
        
        // !!! ATENÇÃO: SUBSTITUA PELA SENHA REAL DO E-MAIL contato@risenglish.com.br !!!
        $mail->Password   = '@Lauraelucas371';      
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Criptografia SSL (recomendada)
        $mail->Port       = 465;                        // Porta Padrão para SSL

        // Define o charset para evitar problemas com acentuação
        $mail->CharSet = 'UTF-8';

        // Configurações de Remetente
        $mail->setFrom('contato@risenglish.com.br', 'Risenglish - Redefinir Senha');
        $mail->addAddress($destinatario, $nomeDestinatario);

        // Conteúdo do E-mail (HTML estilizado)
        $mail->isHTML(true);
        $mail->Subject = 'Redefinicao de Senha - Ris English';
        
        $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #007bff; border-radius: 8px; background-color: #ffffff; }
                    .header { background-color: #007bff; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                    .button { 
                        display: inline-block; padding: 12px 25px; margin-top: 25px; 
                        background-color: #28a745; color: white !important; 
                        text-decoration: none; border-radius: 5px; font-weight: bold; 
                        font-size: 16px; 
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Redefinicao de Senha</h2>
                    </div>
                    <p>Olá, {$nomeDestinatario}.</p>
                    <p>Recebemos uma solicitação para redefinir a senha da sua conta Risenglish. Para prosseguir, clique no botão abaixo:</p>
                    <p style='text-align: center;'>
                        <a href='{$linkReset}' class='button'>Redefinir Minha Senha</a>
                    </p>
                    <p>Se o botão nao funcionar, copie e cole o seguinte link no seu navegador:<br>{$linkReset}</p>
                    <p style='font-size: 0.9em; color: #777;'>Este link expirara em 1 hora por motivos de seguranca.</p>
                    <p>Atenciosamente,<br>Equipe Ris English</p>
                </div>
            </body>
            </html>
        ";
        
        $mail->Body = $body;
        $mail->AltBody = "Para redefinir sua senha, acesse o link: {$linkReset}. Se voce nao solicitou esta redefinicao, ignore este e-mail.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Loga o erro em vez de mostrar diretamente ao usuário
        error_log("Erro ao enviar e-mail de reset: " . $mail->ErrorInfo);
        return false;
    }
}
?>
