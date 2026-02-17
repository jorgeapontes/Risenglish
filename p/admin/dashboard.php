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
    <link rel="shortcut icon" href="../../LogoRisenglish.png" type="image/x-icon">
    <style>
        :root {
            --cor-primaria: #0A1931;
            --cor-secundaria: #c0392b;
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

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 16.666667%; /* Equivale a col-md-2 */
            background-color: #081d40;
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
            transition: 0.3s;
        }

        .sidebar .active {
            background-color: #c0392b;
        }

        .sidebar .active:hover{
            background-color: #c0392b;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            background-color: white;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h6 {
            color: var(--cor-primaria);
            font-weight: 600;
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

        .card-body {
            padding: 25px;
        }

        .btn-acao {
            background: var(--cor-secundaria);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-acao:hover {
            background: var(--cor-secundaria);
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

        #botao-sair {
            border: none;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
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

        .main-content {
            margin-left: 16.666667%; /* Compensa a largura da sidebar fixa */
            width: 83.333333%;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <!-- Nome do professor -->
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo $_SESSION['user_nome'] ?? 'Professor'; ?></h5>
        </div>

        <!-- Menu centralizado verticalmente -->
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" class="rounded active"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="personalizar_index.php" class="rounded"><i class="fas fa-paint-brush"></i>&nbsp;&nbsp;Personalizar Site</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
        </div>

        <!-- Botão sair no rodapé -->
        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
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

                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5 class="card-title">Pagamentos</h5>
                            <p class="card-text">Gerencie os pagamentos.</p>
                            <a href="pagamentos.php" class="btn btn-acao">Acessar</a>
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