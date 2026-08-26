<?php
// Senha simples para testes: 'teste123'
$senha_teste = '12345';
$hash_nova = password_hash($senha_teste, PASSWORD_DEFAULT);
echo "A senha '{$senha_teste}' tem o HASH: <br><strong>{$hash_nova}</strong>";
?>