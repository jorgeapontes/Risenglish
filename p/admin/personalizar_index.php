<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/site_settings.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$uploadDir = __DIR__ . '/../../uploads/site/';
if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$messages = [];
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Texto e links
    $fields = [
        'hero_title_line1','hero_title_line2','hero_subtitle',
        'hero_button1_text','hero_button1_link','hero_button2_text','hero_button2_link',
        'methodology_title','methodology_text1','methodology_text2',
        'about_text','contact_form_link','contact_whatsapp_link','contact_instagram','footer_tagline'
    ];

    foreach($fields as $f){
        $val = isset($_POST[$f]) ? trim($_POST[$f]) : '';
        set_setting($f, $val);
    }

    // Upload de imagens (logo, hero, methodology, about)
    $imageFields = [
        'logo_image' => 'LogoRisenglish.png',
        'hero_image' => 'LogoRisenglish.png',
        'methodology_image' => 'Metodologia.png',
        'about_image' => 'php/professora.jpg'
    ];

    foreach($imageFields as $inputName => $default){
        if(isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK){
            $tmp = $_FILES[$inputName]['tmp_name'];
            $orig = basename($_FILES[$inputName]['name']);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','gif','webp','svg'];
            if(!in_array($ext, $allowed)){
                $messages[] = "Formato não suportado para $inputName";
                continue;
            }
            $name = uniqid('site_') . '.' . $ext;
            $dest = $uploadDir . $name;
            if(move_uploaded_file($tmp, $dest)){
                // armazena caminho relativo a partir da raiz do projeto
                $rel = 'uploads/site/' . $name;
                set_setting($inputName, $rel);
            } else {
                $messages[] = "Falha ao enviar $inputName";
            }
        }
    }

    $messages[] = 'Configurações salvas.';
}

function val($k, $d=''){
    return htmlspecialchars(get_setting($k, $d));
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Personalizar Página Inicial - Admin</title>
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
            width: 16.666667%;
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

        .sidebar a:hover { background-color: rgba(255,255,255,0.05); transform: translateX(3px); }
        .sidebar .active { background-color: #c0392b; }

        .main-content { margin-left: 280px; padding: 30px; background-color: white; min-height: 100vh; }

        h1, h2, h3, h4, h6 { color: var(--cor-primaria); font-weight: 600; }

        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }

        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; }
            .main-content { margin-left: 0; padding: 20px; }
        }

        /* Igual ao dashboard.php: compensa a largura da sidebar fixa */
        .main-content {
            margin-left: 16.666667%; /* Compensa a largura da sidebar fixa */
            width: 83.333333%;
        }
        #botao-sair {
            border: none !important;
        }

        #botao-sair:hover {
            background-color: #c0392b;
            color: white;
            transform: none;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="col-md-2 d-flex flex-column sidebar p-3">
        <div class="mb-4 text-center">
            <h5 class="mt-4"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Administrador'); ?></h5>
        </div>
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <a href="dashboard.php" class="rounded"><i class="fas fa-home"></i>&nbsp;&nbsp;Dashboard</a>
            <a href="personalizar_index.php" class="rounded active"><i class="fas fa-paint-brush"></i>&nbsp;&nbsp;Personalizar Site</a>
            <a href="gerenciar_turmas.php" class="rounded"><i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;Turmas</a>
            <a href="gerenciar_usuarios.php" class="rounded"><i class="fas fa-user"></i>&nbsp;&nbsp;Usuários</a>
            <a href="gerenciar_uteis.php" class="rounded"><i class="fas fa-book-open"></i>&nbsp;&nbsp;Recomendações</a>
            <a href="pagamentos.php" class="rounded"><i class="fas fa-dollar-sign"></i>&nbsp;&nbsp;Pagamentos</a>
        </div>
        <div class="mt-auto">
            <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>

    <div class="main-content flex-grow-1">
        <h1 class="mb-4">Personalizar Página Inicial</h1>
        <?php foreach($messages as $m): ?>
            <div class="alert alert-info"><?= $m ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Hero - Título Linha 1</label>
            <input class="form-control" name="hero_title_line1" value="<?= val('hero_title_line1','Fale inglês com confiança.') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Hero - Título Linha 2</label>
            <input class="form-control" name="hero_title_line2" value="<?= val('hero_title_line2','Cresça com propósito.') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Hero - Subtítulo</label>
            <textarea class="form-control" name="hero_subtitle"><?= val('hero_subtitle','Desbloqueie seu potencial...') ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Botão 1 - Texto</label>
                <input class="form-control" name="hero_button1_text" value="<?= val('hero_button1_text','Conheça o Método') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Botão 1 - Link</label>
                <input class="form-control" name="hero_button1_link" value="<?= val('hero_button1_link','#methodology') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Botão 2 - Texto</label>
                <input class="form-control" name="hero_button2_text" value="<?= val('hero_button2_text','Quero me Inscrever!') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Botão 2 - Link</label>
                <input class="form-control" name="hero_button2_link" value="<?= val('hero_button2_link','#contact') ?>">
            </div>
        </div>
        
        <hr>
        <h4>Logo / Hero</h4>
        <div class="mb-3">
            <label class="form-label">Logo (opcional)</label>
            <input type="file" name="logo_image" class="form-control">
            <img src="/<?= htmlspecialchars(get_setting('logo_image','LogoRisenglish.png')) ?>" alt="" style="max-width:120px;margin-top:8px">
        </div>
        <div class="mb-3">
            <label class="form-label">Hero Image (opcional)</label>
            <input type="file" name="hero_image" class="form-control">
            <img src="/<?= htmlspecialchars(get_setting('hero_image','LogoRisenglish.png')) ?>" alt="" style="max-width:200px;margin-top:8px">
        </div>

        <hr>
        <h4>Metodologia</h4>
        <div class="mb-3">
            <label class="form-label">Título</label>
            <input class="form-control" name="methodology_title" value="<?= val('methodology_title','Nossa Metodologia') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Texto 1</label>
            <textarea class="form-control" name="methodology_text1"><?= val('methodology_text1','Você nasceu biologicamente programado...') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Texto 2</label>
            <textarea class="form-control" name="methodology_text2"><?= val('methodology_text2','É porque o cérebro...') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Imagem da Metodologia (opcional)</label>
            <input type="file" name="methodology_image" class="form-control">
            <img src="/<?= htmlspecialchars(get_setting('methodology_image','Metodologia.png')) ?>" alt="" style="max-width:200px;margin-top:8px">
        </div>

        <hr>
        <h4>Sobre</h4>
        <div class="mb-3">
            <label class="form-label">Texto Sobre</label>
            <textarea class="form-control" name="about_text"><?= val('about_text') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Imagem Sobre (opcional)</label>
            <input type="file" name="about_image" class="form-control">
            <img src="/<?= htmlspecialchars(get_setting('about_image','php/professora.jpg')) ?>" alt="" style="max-width:200px;margin-top:8px">
        </div>

        <hr>
        <h4>Contato & Footer</h4>
        <div class="mb-3">
            <label class="form-label">Link do Formulário</label>
            <input class="form-control" name="contact_form_link" value="<?= val('contact_form_link','https://docs.google.com/forms/d/e/1FAIpQLSdEDqEX0jYnXMELzBEpa1H-QYoOAyxAFCc_xsPAXOK_PzTPeg/viewform') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Link WhatsApp</label>
            <input class="form-control" name="contact_whatsapp_link" value="<?= val('contact_whatsapp_link','https://wa.me/554197162705?text=Olá! Gostaria de mais informações sobre as aulas') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Link Instagram</label>
            <input class="form-control" name="contact_instagram" value="<?= val('contact_instagram','https://www.instagram.com/miss.antero/') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Footer - Tagline</label>
            <input class="form-control" name="footer_tagline" value="<?= val('footer_tagline','Transformando vidas através do inglês com metodologia natural e humana.') ?>">
        </div>


        <button class="btn btn-primary">Salvar</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
    </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
