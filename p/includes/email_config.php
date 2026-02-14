<?php
// Carrega a biblioteca PHPMailer (Certifique-se de que o PHPMailer esteja instalado e acessível)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// =======================================================================================
// !!! CAMINHOS AJUSTADOS: voltando uma pasta (de 'includes' para 'php/') e entrando em 'vendor' !!!
// =======================================================================================
// Ajustar caminhos usando __DIR__ para localizar a pasta vendor no root do projeto
require_once __DIR__ . '/../../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer/src/SMTP.php';
// =======================================================================================

// Load environment variables (from project root .env)
require_once __DIR__ . '/env.php';

// SMTP config from environment with fallbacks
$smtp_host = getenv('SMTP_HOST') ?: 'smtp.hostinger.com.br';
$smtp_user = getenv('SMTP_USER') ?: 'contato@risenglish.com.br';
$smtp_pass = getenv('SMTP_PASS') ?: '@Lauraelucas371';
$smtp_port = getenv('SMTP_PORT') ? (int)getenv('SMTP_PORT') : 465;
$smtp_secure = getenv('SMTP_SECURE') ?: 'ssl'; // 'ssl' or 'tls'
$smtp_from = getenv('SMTP_FROM') ?: $smtp_user;
$smtp_from_name = getenv('SMTP_FROM_NAME') ?: 'Risenglish';


/**
 * Função que configura e envia o e-mail de redefinição de senha.
 * @param string $destinatario O email do usuário que solicitou a redefinição.
 * @param string $nomeDestinatario O nome do usuário.
 * @param string $linkReset O link de redefinição de senha com o token.
 * @return bool Retorna true se o envio foi bem-sucedido, false caso contrário.
 */
function enviarEmailReset($destinatario, $nomeDestinatario, $linkReset) {
    // Import SMTP config defined in file scope
    global $smtp_host, $smtp_user, $smtp_pass, $smtp_port, $smtp_secure, $smtp_from, $smtp_from_name;
    // Passar 'true' habilita exceções para tratamento de erros
    $mail = new PHPMailer(true); 

    try {
        // Configurações do Servidor SMTP (lidas do .env)
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        if (strtolower($smtp_secure) === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail->Port       = $smtp_port;

        // Define o charset para evitar problemas com acentuação
        $mail->CharSet = 'UTF-8';

        // Configurações de Remetente
        $mail->setFrom($smtp_from, $smtp_from_name . ' - Redefinir Senha');
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

/**
 * Envia um e-mail simples reutilizando as mesmas configurações SMTP.
 * @param string $destinatario
 * @param string $nomeDestinatario
 * @param string $assunto
 * @param string $mensagemHtml
 * @param string $mensagemAlt
 * @return bool
 */
function enviarEmailSimples($destinatario, $nomeDestinatario, $assunto, $mensagemHtml, $mensagemAlt = '') {
    // Import SMTP config defined in file scope
    global $smtp_host, $smtp_user, $smtp_pass, $smtp_port, $smtp_secure, $smtp_from, $smtp_from_name;
    $mail = new PHPMailer(true);
    try {
        // Reuse SMTP config from environment
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        if (strtolower($smtp_secure) === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail->Port       = $smtp_port;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($smtp_from, $smtp_from_name);
        $mail->addAddress($destinatario, $nomeDestinatario);

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagemHtml;
        $mail->AltBody = $mensagemAlt ?: strip_tags($mensagemHtml);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail: ' . $mail->ErrorInfo);
        return false;
    }
}
?>
