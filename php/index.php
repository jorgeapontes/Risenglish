<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risenglish - Plataforma de Inglês Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Paleta de Cores e Estilo Minimalista */
        :root {
            --cor-primaria: #0A1931; /* Marinho Escuro */
            --cor-secundaria: #B91D23; /* Vermelho */
            --cor-fundo: #F5F5DC; /* Creme/Bege */
            --cor-ouro: #C5A358; /* Dourado */
        }
        body {
            background-color: var(--cor-fundo);
            color: var(--cor-primaria);
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .header-content {
            padding: 50px 20px;
        }
        h1 {
            font-size: 3.5rem;
            color: var(--cor-primaria);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .subtitle-gold {
            font-size: 2rem;
            color: var(--cor-ouro);
            font-weight: 500;
        }
        .btn-custom {
            background-color: var(--cor-secundaria);
            border-color: var(--cor-secundaria);
            color: white;
            font-size: 1.2rem;
            padding: 10px 30px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .btn-custom:hover {
            background-color: #92171B; /* Vermelho mais escuro no hover */
            border-color: #92171B;
            color: white;
        }
        .contact-bar {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: var(--cor-primaria);
            color: white;
            padding: 10px 0;
        }
        .contact-bar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
        }
    </style>
</head>
<body>

    <div class="header-content">
        <h1 class="display-3">RISENGLISH</h1>
        <p class="subtitle-gold">Sua plataforma de **VOCABULÁRIO DE OURO**</p>
        [cite_start]<p class="lead mt-4">Ministrar aulas e conteúdos e fornecer acesso dos conteúdos pros alunos[cite: 12].</p>
        
        <a href="login.php" class="btn btn-custom btn-lg">ACESSAR PLATAFORMA</a>
    </div>

    <div class="contact-bar">
        Entre em contato:
        <a href="https://wa.me/seu_whatsapp" target="_blank">WhatsApp</a>
        <a href="mailto:seu_email@dominio.com">Email</a>
        <a href="https://instagram.com/miss.antero" target="_blank">Instagram (@miss.antero)</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>