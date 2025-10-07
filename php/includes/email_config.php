
<?php
// Carrega a biblioteca PHPMailer (Certifique-se de que o PHPMailer esteja instalado e acessível)
// Você deve ter os arquivos baixados e colocados em algum lugar, ex: includes/PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclua os arquivos do PHPMailer
// Os caminhos abaixo são exemplos. Ajuste conforme a sua estrutura de pastas.
// require 'PHPMailer/src/Exception.php';
// require 'PHPMailer/src/PHPMailer.php';
// require 'PHiver::mailer/src/SMTP.php';


function configurarPHPMailer() {
    $mail = new PHPMailer(true);

    try {
        // Configurações do Servidor
        $mail->isSMTP();
        // **!!! PREENCHA COM AS SUAS INFORMAÇÕES SMTP !!!**
        $mail->Host       = 'SEU_HOST_SMTP';        // Ex: smtp.seudominio.com.br
        $mail->SMTPAuth   = true;
        $mail->Username   = 'SEU_EMAIL_DE_ENVIO';   // Ex: noreply@risenglish.com.br
        $mail->Password   = 'SUA_SENHA_SMTP';       // Senha do email
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use 'ssl' ou 'tls'
        $mail->Port       = 465;                    // Porta padrão para SMTPS

        // Remetente
        $mail->setFrom('SEU_EMAIL_DE_ENVIO', 'RisEnglish - Suporte');
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        return $mail;

    } catch (Exception $e) {
        // Em um ambiente de produção, registre o erro
        // echo "Erro ao configurar o email: {$mail->ErrorInfo}";
        return false;
    }
}
?>
