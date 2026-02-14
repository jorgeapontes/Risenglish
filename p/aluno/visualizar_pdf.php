<?php
session_start();
if (!isset($_SESSION['user_id'])) exit;

$arquivo = $_GET['file'] ?? '';
$titulo = $_GET['titulo'] ?? 'Material de Aula';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo) ?> - Risenglish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <style>
        body { background: #525659; margin: 0; padding: 0; }
        #pdf-render-container { text-align: center; padding: 20px; }
        canvas { box-shadow: 0 5px 15px rgba(0,0,0,0.4); margin-bottom: 30px; max-width: 100%; display: block; margin-left: auto; margin-right: auto; }
        .loading-msg { color: white; text-align: center; margin-top: 50px; font-family: sans-serif; }
    </style>
</head>
<body oncontextmenu="return false;">
    <div id="loading" class="loading-msg">Carregando material protegido...</div>
    <div id="pdf-render-container"></div>

    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
        
        const url = '<?= $arquivo ?>';
        const container = document.getElementById('pdf-render-container');
        
        // 1. Carregar a Logo antes de tudo
        const logoImg = new Image();
        logoImg.src = '../../LogoRisenglish.png'; // Verifique se este caminho está correto em relação a este arquivo

        async function renderPDF() {
            try {
                const pdf = await pdfjsLib.getDocument(url).promise;
                document.getElementById('loading').style.display = 'none';

                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const scale = 1.5;
                    const viewport = page.getViewport({scale: scale});
                    
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    container.appendChild(canvas);

                    await page.render({canvasContext: ctx, viewport: viewport}).promise;

                    // --- INÍCIO DA MARCA D'ÁGUA ---
                    
                    // A. Texto Vermelho Suave Repetido
                    ctx.save();
                    ctx.font = "bold 25px Arial";
                    // rgba(vermelho, verde, azul, transparência) -> 0.15 é 15% de opacidade
                    ctx.fillStyle = "rgba(200, 0, 0, 0.15)"; 
                    ctx.rotate(-45 * Math.PI / 180);
                    
                    const text = "RISENGLISH - MATERIAL PROTEGIDO";
                    
                    for (let x = -canvas.height; x < canvas.width; x += 500) {
                        for (let y = -canvas.height; y < canvas.height * 2; y += 200) {
                            ctx.fillText(text, x, y);
                        }
                    }
                    ctx.restore();

                    // B. Logo da Escola (Desenha se a imagem já tiver carregado)
                    if (logoImg.complete) {
                        const logoWidth = 150;
                        const logoHeight = (logoImg.height / logoImg.width) * logoWidth;
                        
                        ctx.save();
                        ctx.globalAlpha = 0.2; // Transparência da logo
                        // Desenha no canto inferior direito
                        ctx.drawImage(logoImg, canvas.width - logoWidth - 50, canvas.height - logoHeight - 50, logoWidth, logoHeight);
                        // Desenha no canto superior esquerdo
                        ctx.drawImage(logoImg, 50, 50, logoWidth, logoHeight);
                        ctx.restore();
                    }
                    
                    // --- FIM DA MARCA D'ÁGUA ---
                }
            } catch (error) {
                document.getElementById('loading').innerHTML = "Erro ao carregar material.";
                console.error(error);
            }
        }

        // Aguarda a imagem carregar para iniciar o PDF (garante que a logo apareça na primeira página)
        logoImg.onload = renderPDF;
        // Caso a imagem falhe ou demore, inicia o PDF mesmo assim após 1 segundo
        setTimeout(() => { if(container.innerHTML === "") renderPDF(); }, 1000);

        // Bloqueios de teclado
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p' || e.key === 'u')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>