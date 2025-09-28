<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost'); // Geralmente 'localhost' no XAMPP/MAMP/servidor local
define('DB_USER', 'root');      // Usuário padrão do MySQL (mude se necessário)
define('DB_PASS', '');          // Senha padrão do MySQL (mude se necessário)
define('DB_NAME', 'risenglish'); // Nome do banco que você criou

// Conexão PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    // Configura o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexão estabelecida com sucesso!"; // **Remova esta linha após o teste!**
} catch (PDOException $e) {
    // Em caso de falha na conexão
    die("Erro na Conexão com o Banco de Dados: " . $e->getMessage());
}
?>