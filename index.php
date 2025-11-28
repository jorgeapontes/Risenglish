<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risenglish - English Learning</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h3>RISENGLISH</h3>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#home" class="nav-link">Início</a>
                </li>
                <li class="nav-item">
                    <a href="#about" class="nav-link">Sobre</a>
                </li>
                <li class="nav-item">
                    <a href="#contact" class="nav-link">Contato</a>
                </li>
                <li class="nav-item">
                    <a href="php/login.php" class="nav-link login-btn">Login</a>
                </li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-background">
            <div class="hero-shape hero-shape-1"></div>
            <div class="hero-shape hero-shape-2"></div>
            <div class="hero-shape hero-shape-3"></div>
        </div>
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span><i class="fas fa-star"></i> Método Exclusivo</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-line">Fale inglês com confiança.</span>
                    <span class="title-line accent">Cresça com propósito.</span>
                </h1>
                <p class="hero-subtitle">Desbloqueie seu potencial com uma metodologia focada em conversação e escuta ativa! Não é só sobre aprender inglês. É sobre transformar sua comunicação e seu futuro.</p>
                <div class="hero-buttons">
                    <a href="#about" class="btn btn-primary">
                        <span>Ver mais</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <a href="#contact" class="btn btn-secondary">
                        <span>Quero me Inscrever!</span>
                    </a>
                </div>
                <div class="hero-features">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Aulas Online</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Resultados Reais</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Suporte Individual</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="image-container">
                    <div class="image-placeholder">
                        <img src="LogoRisenglish.png" alt="Imagem Ilustrativa" class="hero-main-image" style="width: 100%; height: auto; border-radius: 20px; display: block;">
                    </div>
                    <div class="image-decoration"></div>
                </div>
            </div>
        </div>
        <div class="scroll-indicator">
            <div class="scroll-arrow"></div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-container">
                <div class="about-image">
                    <div class="image-frame">
                        <img src="professora.jpg" alt="Professora Risenglish">
                        <div class="image-overlay">
                            <div class="experience-badge">
                                <span>+ de 500 Alunos</span>
                                
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about-content">
                    <h2 class="section-title">Sobre Mim</h2>
                    <p class="about-text">
                        Sou Laura Antero, natural de Ponta Grossa (PR) e atualmente em Jundiaí (SP). Construí minha trajetória lecionando em escolas e descobri, nas aulas particulares, a melhor forma de acompanhar de perto e celebrar cada pequeno progresso dos meus alunos. Sou formada em Letras – Português/Inglês e Literaturas pela UEPG, com láurea acadêmica, e possuo certificação internacional pela ACE English Malta, onde também fiz intercâmbio. Na Rise English, transformo essa experiência em propósito: ajudar alunos a evoluírem com confiança, fluência e propósito, em um ambiente acolhedor e humano. Acredito que aprender é um processo de evolução contínua, e é esse crescimento, em cada história, que me inspira a seguir ensinando com entusiasmo e dedicação todos os dias.
                    </p>
                </div>
            </div>
        </div>
        <center>
        <div class="about-features">
            <div class="feature">
                <i class="fa-solid fa-comments"></i>
                <div class="feature-text">
                    <h4>Metodologia Conversacional</h4>
                    <p>Aprenda com foco em speaking e listening, tudo sobre conexão real.</p>
                </div>
            </div>
            <div class="feature">
                <i class="fa-solid fa-mug-hot"></i>
                <div class="feature-text">
                    <h4>Acompanhamento Individual</h4>
                    <p>Você evolui no seu ritmo, com suporte direto da professora.</p>
                </div>
            </div>
            <div class="feature">
                <i class="fa-solid fa-star"></i>
                <div class="feature-text">
                    <h4>Exclusividade Risenglish</h4>
                    <p>Conteúdos autorais e práticos, pensados para cada nível e objetivo.</p>
                </div>
            </div>
        </div>
        </center>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="contact-container">
                <h2 class="section-title">Vamos Conversar?</h2>
                <p class="contact-subtitle">Entre em contato e comece sua jornada no inglês hoje mesmo:</p>
                <div class="contact-buttons">
                    <a href="https://docs.google.com/forms/d/e/1FAIpQLSdEDqEX0jYnXMELzBEpa1H-QYoOAyxAFCc_xsPAXOK_PzTPeg/viewform" class="contact-btn forms" target="_blank">
                        <i class="fa-brands fa-wpforms"></i>
                        <div class="btn-text">
                            <span>Formulário</span>
                            <small>Inscrição online</small>
                        </div>
                    </a>
                    <a href="https://wa.me/5511999999999?text=Olá! Gostaria de mais informações sobre as aulas" class="contact-btn whatsapp" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                        <div class="btn-text">
                            <span>WhatsApp</span>
                            <small>Resposta rápida</small>
                        </div>
                    </a>
                    <a href="https://www.instagram.com/miss.antero/" class="contact-btn instagram" target="_blank">
                        <i class="fab fa-instagram"></i>
                        <div class="btn-text">
                            <span>Instagram</span>
                            <small>@miss.antero</small>
                        </div>
                    </a>
                </div>
            </div>
            <div class="backtop">
                <a href="#home" class="btn btn-backtop">
                    <span>Voltar ao topo</span>
                </a>
            </div>
            <div class="footerfooter">
                <h1>Risenglish</h1>
                <p>Copyright © Risenglish by Laura Antero.<br>
                    All Rights Reserved
                </p>
            </div>
        </div>
    </section>

    <script>
        // Menu Mobile
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
});

document.querySelectorAll('.nav-link').forEach(link =>
    link.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    })
);

// Scroll suave com easing personalizado
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const target = document.querySelector(targetId);
        if (!target) return;

        const navbarHeight = document.querySelector('.navbar').offsetHeight;
        const targetPos = target.getBoundingClientRect().top + window.scrollY - navbarHeight;
        const startPos = window.scrollY;
        const distance = targetPos - startPos;
        const duration = 1000;
        let start = null;

        function easeInOutCubic(t, b, c, d) {
            t /= d / 2;
            if (t < 1) return c / 2 * t * t * t + b;
            t -= 2;
            return c / 2 * (t * t * t + 2) + b;
        }

        function animation(currentTime) {
            if (start === null) start = currentTime;
            const timeElapsed = currentTime - start;
            const run = easeInOutCubic(timeElapsed, startPos, distance, duration);
            window.scrollTo(0, run);
            if (timeElapsed < duration) requestAnimationFrame(animation);
        }

        requestAnimationFrame(animation);
    });
});

// Navbar dinâmica ao rolar
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(10,25,49,0.98)';
        navbar.style.padding = '0.8rem 0';
        navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
    } else {
        navbar.style.background = 'rgba(10,25,49,0.95)';
        navbar.style.padding = '1rem 0';
        navbar.style.boxShadow = 'none';
    }
    updateActiveNavLink();
});

// Atualiza link ativo conforme scroll
function updateActiveNavLink() {
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('.nav-link');
    const scrollY = window.scrollY + document.querySelector('.navbar').offsetHeight + 100;
    let current = '';

    sections.forEach(section => {
        if (scrollY >= section.offsetTop && scrollY < section.offsetTop + section.offsetHeight)
            current = section.getAttribute('id');
    });

    navLinks.forEach(link => {
        link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
    });
}

// Animações de entrada
const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
    });
}, { threshold: 0.15 });

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right')
        .forEach(el => observer.observe(el));
});

updateActiveNavLink();
    </script>
</body>
</html>