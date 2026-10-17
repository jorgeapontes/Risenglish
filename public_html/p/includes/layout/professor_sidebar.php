<?php
/**
 * Sidebar compartilhada das páginas de professor: menu offcanvas no mobile e
 * sidebar recolhível (rail de ícones) no desktop, no mesmo padrão de admin/aluno.
 * Não mexe em sessão/autenticação — cada página continua responsável por isso.
 *
 * Espera (opcional):
 *   $paginaAtiva               chave de $itensMenu abaixo, ex: 'dashboard'
 *   $totalNotificacoesNaoLidas contador do badge de notificações (fallback: 0)
 *   $tituloMobile              título exibido no cabeçalho mobile (fallback: label do item ativo)
 */
$paginaAtiva = $paginaAtiva ?? '';
$totalNotificacoesNaoLidas = $totalNotificacoesNaoLidas ?? ($total_notificacoes_nao_lidas ?? 0);

$itensMenu = [
    'notificacoes' => ['href' => 'notificacoes',        'icon' => 'fa-bell',         'label' => 'Notificações', 'badge' => true],
    'dashboard'    => ['href' => 'dashboard',            'icon' => 'fa-home',         'label' => 'Dashboard'],
    'aulas'        => ['href' => 'gerenciar_aulas',      'icon' => 'fa-calendar-alt', 'label' => 'Aulas'],
    'conteudos'    => ['href' => 'gerenciar_conteudos',  'icon' => 'fa-book-open',    'label' => 'Conteúdos'],
    'alunos'       => ['href' => 'gerenciar_alunos',     'icon' => 'fa-users',        'label' => 'Alunos/Turmas'],
    'perfil'       => ['href' => 'perfil',               'icon' => 'fa-user-circle',  'label' => 'Meu Perfil'],
];

require_once __DIR__ . '/../avatar.php';
$professorNomeBruto = $_SESSION['user_nome'] ?? 'Professor';
$professorNome = htmlspecialchars($professorNomeBruto);
$professorFoto = avatar_foto_sessao($pdo ?? null);
$tituloMobile = $tituloMobile ?? ($itensMenu[$paginaAtiva]['label'] ?? 'Menu');
?>
<link rel="stylesheet" href="../../css/avatar.css">
<?php require __DIR__ . '/sem_reenvio.php'; ?>
<script>
    // Aplica o estado salvo da sidebar antes dela ser pintada, evitando flash do estado errado.
    (function () {
        try {
            if (localStorage.getItem('professor_sidebar_collapsed') === '1') {
                document.documentElement.classList.add('professor-sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>

<header class="d-flex d-md-none mobile-navbar-custom border-bottom shadow-sm p-3 align-items-center sticky-top">
    <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#professorSidebarOffcanvas" aria-controls="professorSidebarOffcanvas" aria-label="Abrir Menu">
        <i class="fas fa-bars"></i>
    </button>
    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($tituloMobile); ?></h5>
</header>

<div class="offcanvas offcanvas-start text-white mobile-offcanvas" tabindex="-1" id="professorSidebarOffcanvas" aria-labelledby="professorSidebarOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="professorSidebarOffcanvasLabel"><?php echo avatar_html($professorNomeBruto, $professorFoto, 36); ?><span>Prof. <?php echo $professorNome; ?></span></h5>
        <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <?php foreach ($itensMenu as $chave => $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>"
               class="rounded<?php echo $chave === $paginaAtiva ? ' active' : ''; ?><?php echo !empty($item['badge']) ? ' position-relative' : ''; ?>">
                <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>&nbsp;&nbsp;<?php echo htmlspecialchars($item['label']); ?>
                <?php if (!empty($item['badge']) && $totalNotificacoesNaoLidas > 0): ?>
                    <span class="badge bg-danger ms-2"><?php echo (int) $totalNotificacoesNaoLidas; ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="mt-auto">
            <a href="../logout" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>
</div>

<div class="col-md-2 d-none d-md-flex flex-column sidebar p-3">
    <button type="button" id="sidebarToggle" class="sidebar-toggle-btn" title="Recolher/expandir menu" aria-label="Recolher ou expandir menu">
        <i class="fas fa-bars"></i>
    </button>

    <a href="perfil" class="sidebar-usuario mb-3" title="Meu Perfil" aria-label="Meu Perfil">
        <?php echo avatar_html($professorNomeBruto, $professorFoto, 56); ?>
        <h5 class="user-name">Prof. <?php echo $professorNome; ?></h5>
    </a>

    <div class="d-flex flex-column flex-grow-1 mb-5">
        <?php foreach ($itensMenu as $chave => $item): ?>
        <a href="<?php echo htmlspecialchars($item['href']); ?>"
           class="rounded<?php echo $chave === $paginaAtiva ? ' active' : ''; ?><?php echo !empty($item['badge']) ? ' position-relative' : ''; ?>"
           aria-label="<?php echo htmlspecialchars($item['label']); ?>"
           title="<?php echo htmlspecialchars($item['label']); ?>">
            <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i><span class="nav-label">&nbsp;&nbsp;<?php echo htmlspecialchars($item['label']); ?></span>
            <?php if (!empty($item['badge']) && $totalNotificacoesNaoLidas > 0): ?>
                <span class="badge bg-danger ms-2 nav-label"><?php echo (int) $totalNotificacoesNaoLidas; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-auto">
        <a href="../logout" id="botao-sair" class="btn btn-outline-danger w-100" aria-label="Sair" title="Sair">
            <i class="fas fa-sign-out-alt me-2"></i><span class="nav-label">Sair</span>
        </a>
    </div>
</div>
<script>
(function () {
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var collapsed = document.documentElement.classList.toggle('professor-sidebar-collapsed');
        try {
            localStorage.setItem('professor_sidebar_collapsed', collapsed ? '1' : '0');
        } catch (e) {}
    });
})();
</script>
