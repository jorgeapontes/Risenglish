<?php
session_start();
require_once '../includes/conexao.php';

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
    <style>
        :root {
            --cor-primaria: #1a2a3a;
            --cor-secundaria: #92171B;
            --cor-destaque: #c0392b;
            --cor-texto: #333;
            --cor-fundo: #f8f9fa;
            --cor-borda: #dee2e6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            margin: 0;
            padding: 0;
        }

        .d-flex {
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--cor-primaria) 0%, #0d1b2a 100%);
            color: white;
            width: 280px;
            min-height: 100vh;
            position: fixed;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar h4 {
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            border-bottom: 2px solid var(--cor-secundaria);
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar a:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }

        .sidebar a.active {
            background-color: var(--cor-secundaria);
            box-shadow: 0 2px 8px rgba(146, 23, 27, 0.3);
        }

        .sidebar .link-sair {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .sidebar .link-sair:hover {
            background-color: rgba(220, 53, 69, 0.3);
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            background-color: white;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--cor-primaria);
            font-weight: 600;
        }

        h1 {
            border-bottom: 3px solid var(--cor-secundaria);
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .card-header {
            background: linear-gradient(135deg, var(--cor-primaria), #2c3e50);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
            border: none;
        }

        .card-body {
            padding: 25px;
        }

        .btn-acao {
            background: linear-gradient(135deg, var(--cor-secundaria), #b0151a);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-acao:hover {
            background: linear-gradient(135deg, #b0151a, var(--cor-secundaria));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(146, 23, 27, 0.3);
            color: white;
        }

        .cards .card {
            height: 100%;
        }

        .cards .card-title {
            color: var(--cor-primaria);
            font-weight: 600;
            font-size: 1.3rem;
        }

        .cards .card-text {
            color: #6c757d;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-content > * {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h4 class="text-center mb-4 border-bottom pb-3">ADMIN RISENGLISH</h4>
        <a href="dashboard.php" class="active"><i class="fas fa-home me-2"></i>Home</a>
        <a href="gerenciar_turmas.php"><i class="fas fa-users me-2"></i>Turmas</a>
        <a href="gerenciar_usuarios.php"><i class="fas fa-user-friends me-2"></i>Usuários</a>
        <a href="gerenciar_uteis.php"><i class="fas fa-book me-2"></i>Recomendações</a>
        <a href="../logout.php" class="link-sair" style="position: absolute; bottom: 20px; width: calc(100% - 30px);">
            <i class="fas fa-sign-out-alt me-2"></i>Sair
        </a>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4">Bem-vindo, Administrador.</h1>
        <p class="lead">Esta é a Tela de ADMIN. Aqui você pode gerenciar sua plataforma.</p>
        
        <div class="cards">
            <div class="row mt-5">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5 class="card-title">Gerenciar Turmas</h5>
                            <p class="card-text">Criar, editar e remover turmas, associar professores e gerenciar alunos.</p>
                            <a href="gerenciar_turmas.php" class="btn btn-acao">Acessar</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5 class="card-title">Gerenciar Usuários</h5>
                            <p class="card-text">Administrar professores e alunos, criar contas e definir permissões.</p>
                            <a href="gerenciar_usuarios.php" class="btn btn-acao">Acessar</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5 class="card-title">Gerenciar Recomendações</h5>
                            <p class="card-text">Adicionar, remover e editar recomendações úteis para os alunos.</p>
                            <a href="gerenciar_uteis.php" class="btn btn-acao">Acessar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>