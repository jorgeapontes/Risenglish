<?php
session_start();
require_once '../includes/conexao.php';

// 1. Checar a autenticação e permissão (Bloqueio de acesso)
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$nome_usuario = $_SESSION['user_nome'];

// 2. Consulta para exibir as turmas do professor logado
$sql_turmas = "SELECT id, nome_turma FROM turmas WHERE professor_id = :id_professor ORDER BY nome_turma";
$stmt_turmas = $pdo->prepare($sql_turmas);
$stmt_turmas->bindParam(':id_professor', $professor_id);
$stmt_turmas->execute();
$turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --cor-primaria: #0A1931; /* Marinho Escuro */
            --cor-secundaria: #B91D23; /* Vermelho */
            --cor-fundo: #F5F5DC; /* Creme/Bege */
        }
        body { background-color: var(--cor-fundo); }
        .sidebar {
            background-color: var(--cor-primaria);
            color: white;
            min-height: 100vh;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-decoration: none;
            display: block;
        }
        .sidebar a:hover {
            background-color: var(--cor-secundaria);
        }
        .main-content {
            padding: 30px;
        }
        .card-turma:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">PROFESSOR RISENGLISH</h4>
        <a href="dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
        <a href="gerenciar_aulas.php"><i class="fas fa-book-open me-2"></i> Gerenciar Aulas/Conteúdos</a>
        <a href="../logout.php" style="position: absolute; bottom: 20px; width: calc(100% - 30px);"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4" style="color: var(--cor-primaria);">Bem-vindo(a), Prof. <?= htmlspecialchars($nome_usuario) ?></h1>
        <p class="lead">Aqui estão as turmas que você administra. Clique em uma turma para **Gerenciar Aulas** e **Conteúdos**.</p>
        
        <h3 class="mt-5" style="color: var(--cor-secundaria);">Minhas Turmas</h3>

        <?php if (empty($turmas)): ?>
            <div class="alert alert-info mt-4">Você ainda não tem turmas associadas. Contate o Admin para ser alocado.</div>
        <?php else: ?>
            <div class="row mt-3">
                <?php foreach ($turmas as $turma): ?>
                    <div class="col-md-4 mb-4">
                        <a href="gerenciar_aulas.php?turma_id=<?= $turma['id'] ?>" class="text-decoration-none">
                            <div class="card shadow-sm card-turma" style="border-left: 5px solid var(--cor-secundaria); transition: all 0.3s;">
                                <div class="card-body">
                                    <h5 class="card-title" style="color: var(--cor-primaria);"><?= htmlspecialchars($turma['nome_turma']) ?></h5>
                                    <p class="card-text text-muted">Gerenciar aulas e arquivos.</p>
                                    <i class="fas fa-arrow-right float-end" style="color: var(--cor-secundaria);"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>