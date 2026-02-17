<?php
// Helper simples para configurações do site (armazenadas no banco)
// Cria tabela se não existir e fornece get/set
require_once __DIR__ . '/conexao.php';

// Cria tabela se não existe
$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(191) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

function get_setting($key, $default = ''){
    global $pdo;
    $stmt = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row && $row['setting_value'] !== null && $row['setting_value'] !== '') return $row['setting_value'];
    return $default;
}

function set_setting($key, $value){
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    return $stmt->execute([$key, $value]);
}

?>
