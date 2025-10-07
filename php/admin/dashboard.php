<?php
session_start();
// 1. Incluir a conexão com o banco
require_once '../includes/conexao.php';

// 2. Checar a autenticação e permissão (Bloqueio de acesso)
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$nome_usuario = $_SESSION['user_nome'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/dashboard.css">
    
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">ADMIN RISENGLISH</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i>Home</a>
        <a href="gerenciar_alunos_turmas.php"><i class="fas fa-users me-2"></i>Gerenciar Alunos/Turmas</a>
        <a href="gerenciar_uteis.php"><i class="fas fa-book"></i> Recomendações</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Bem-vindo, <?= htmlspecialchars($nome_usuario) ?></h1>
        <p class="lead">Esta é a **Tela de ADMIN**. Aqui você pode cadastrar e organizar alunos e turmas.</p>
        <div class="cards">
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">Gerenciar Alunos e Turmas</h5>
                        <p class="card-text">Adicionar, Remover e Editar alunos e suas respectivas turmas.</p>
                        <a href="gerenciar_alunos_turmas.php" class="btn btn-sm" style="background-color: var(--cor-secundaria); color: white;">Acessar</a>
                    </div><br>
                </div>
            </div>
            </div>

            <div class="row mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">Gerenciar Recomendações</h5>
                        <p class="card-text">Adicionar, Remover e Editar Recomendações para os alunos.</p>
                        <a href="recomendacoes.php" class="btn btn-sm" style="background-color: var(--cor-secundaria); color: white;">Acessar</a>
                    </div><br>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>