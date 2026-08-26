<?php
/**
 * Sidebar compartilhada das páginas de aluno: menu offcanvas no mobile e
 * sidebar recolhível (rail de ícones) no desktop, no mesmo padrão da sidebar de admin.
 * Não mexe em sessão/autenticação — cada página continua responsável por isso.
 *
 * Espera (opcional):
 *   $paginaAtiva              chave de $itensMenu abaixo, ex: 'dashboard'
 *   $alunoNome                nome exibido no topo/offcanvas (fallback: sessão)
 *   $totalNotificacoesNaoLidas contador do badge de notificações (fallback: 0)
 *   $tituloMobile             título exibido no cabeçalho mobile (fallback: 'Menu')
 *   $comBotaoNotificacoes     exibe o sino de notificações rápido (usado só no dashboard)
 */
$paginaAtiva = $paginaAtiva ?? '';
$alunoNome = $alunoNome ?? ($aluno_nome ?? ($_SESSION['user_nome'] ?? 'Aluno'));
$totalNotificacoesNaoLidas = $totalNotificacoesNaoLidas ?? ($total_notificacoes_nao_lidas ?? 0);
$tituloMobile = $tituloMobile ?? 'Menu';
$comBotaoNotificacoes = $comBotaoNotificacoes ?? false;

$itensMenu = [
    'notificacoes'  => ['href' => 'notificacoes',  'icon' => 'fas fa-bell',            'label' => 'Notificações', 'badge' => true],
    'dashboard'     => ['href' => 'dashboard',      'icon' => 'fas fa-home',            'label' => 'Dashboard'],
    'minhas_aulas'  => ['href' => 'minhas_aulas',   'icon' => 'fas fa-calendar-alt',    'label' => 'Minhas Aulas'],
    'recomendacoes' => ['href' => 'recomendacoes',  'icon' => 'fas fa-lightbulb',       'label' => 'Recomendações'],
    'anotacoes'     => ['href' => 'anotacoes',      'icon' => 'fas fa-book-open',       'label' => 'Anotações'],
    'documentos'    => ['href' => 'documentos',     'icon' => 'fa-solid fa-box-archive', 'label' => 'Documentos'],
];
?>
<script>
    // Aplica o estado salvo da sidebar antes dela ser pintada, evitando flash do estado errado.
    (function () {
        try {
            if (localStorage.getItem('aluno_sidebar_collapsed') === '1') {
                document.documentElement.classList.add('aluno-sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>

<header class="d-flex d-md-none mobile-navbar-custom border-bottom shadow-sm p-3 align-items-center sticky-top">
    <button class="btn btn-outline-primary me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Abrir Menu">
        <i class="fas fa-bars"></i>
    </button>
    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($tituloMobile); ?></h5>
    <?php if ($comBotaoNotificacoes): ?>
        <button class="btn-notificacoes ms-auto" id="btnNotificacoesMobile" title="Notificações" style="color: white;">
            <i class="fas fa-bell"></i>
            <?php if ($totalNotificacoesNaoLidas > 0): ?>
                <span class="badge"><?php echo (int) $totalNotificacoesNaoLidas; ?></span>
            <?php endif; ?>
        </button>
    <?php endif; ?>
</header>

<div class="offcanvas offcanvas-top text-white mobile-offcanvas" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="sidebarOffcanvasLabel"><?php echo htmlspecialchars($alunoNome); ?></h5>
        <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <?php foreach ($itensMenu as $chave => $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>"
               class="rounded<?php echo $chave === $paginaAtiva ? ' active' : ''; ?><?php echo !empty($item['badge']) ? ' position-relative' : ''; ?>">
                <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>&nbsp;&nbsp;<?php echo htmlspecialchars($item['label']); ?>
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

    <div class="mb-4 text-center">
        <h5 class="mt-4 user-name"><?php echo htmlspecialchars($alunoNome); ?></h5>
    </div>

    <div class="d-flex flex-column flex-grow-1 mb-5">
        <?php foreach ($itensMenu as $chave => $item): ?>
        <a href="<?php echo htmlspecialchars($item['href']); ?>"
           class="rounded<?php echo $chave === $paginaAtiva ? ' active' : ''; ?><?php echo !empty($item['badge']) ? ' position-relative' : ''; ?>"
           aria-label="<?php echo htmlspecialchars($item['label']); ?>"
           title="<?php echo htmlspecialchars($item['label']); ?>">
            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i><span class="nav-label">&nbsp;&nbsp;<?php echo htmlspecialchars($item['label']); ?></span>
            <?php if (!empty($item['badge']) && $totalNotificacoesNaoLidas > 0): ?>
                <span class="badge bg-danger ms-2 nav-label"><?php echo (int) $totalNotificacoesNaoLidas; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-auto<?php echo $comBotaoNotificacoes ? ' d-flex align-items-center justify-content-between' : ''; ?>">
        <?php if ($comBotaoNotificacoes): ?>
            <button class="btn-notificacoes" id="btnNotificacoes" title="Notificações">
                <i class="fas fa-bell"></i>
                <?php if ($totalNotificacoesNaoLidas > 0): ?>
                    <span class="badge"><?php echo (int) $totalNotificacoesNaoLidas; ?></span>
                <?php endif; ?>
            </button>
            <a href="../logout" id="botao-sair" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-2"></i><span class="nav-label">Sair</span>
            </a>
        <?php else: ?>
            <a href="../logout" id="botao-sair" class="btn btn-outline-danger w-100">
                <i class="fas fa-sign-out-alt me-2"></i><span class="nav-label">Sair</span>
            </a>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var collapsed = document.documentElement.classList.toggle('aluno-sidebar-collapsed');
        try {
            localStorage.setItem('aluno_sidebar_collapsed', collapsed ? '1' : '0');
        } catch (e) {}
    });
})();
</script>
