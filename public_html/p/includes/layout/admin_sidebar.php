<?php
/**
 * Sidebar compartilhada das páginas de admin: menu offcanvas no mobile e
 * sidebar recolhível (rail de ícones) no desktop.
 * Não mexe em sessão/autenticação — cada página continua responsável por isso.
 * Espera (opcional):
 *   $paginaAtiva   chave de $itensMenu abaixo, ex: 'dashboard'
 *   $tituloMobile  título exibido no cabeçalho mobile (fallback: label do item ativo)
 */
$paginaAtiva = $paginaAtiva ?? '';

$itensMenu = [
    'dashboard'     => ['href' => 'dashboard',              'icon' => 'fa-home',            'label' => 'Dashboard'],
    'leads'         => ['href' => 'leads',                  'icon' => 'fa-user-tie',         'label' => 'Leads'],
    'personalizar'  => ['href' => 'personalizar_index',     'icon' => 'fa-paint-brush',      'label' => 'Personalizar Site'],
    'turmas'        => ['href' => 'gerenciar_turmas',       'icon' => 'fa-users',            'label' => 'Turmas'],
    'usuarios'      => ['href' => 'gerenciar_usuarios',     'icon' => 'fa-user',             'label' => 'Usuários'],
    'uteis'         => ['href' => 'gerenciar_uteis',        'icon' => 'fa-book-open',        'label' => 'Recomendações'],
    'agendas'       => ['href' => 'agendas',                'icon' => 'fa-calendar-alt',     'label' => 'Agendas'],
    'pagamentos'    => ['href' => 'pagamentos',             'icon' => 'fa-dollar-sign',      'label' => 'Pagamentos'],
    'acessos'       => ['href' => 'acessos',                'icon' => 'fa-chart-line',       'label' => 'Relatório de Acessos'],
];
// O master (dono do sistema) não usa as páginas do admin: o menu dele são as seções do monitoramento.
// $alertaSaude (opcional, definido pela página) mostra um ponto de atenção em "Saúde".
if (!empty($_SESSION['user_master'])) {
    $itensMenu = [
        'saude'         => ['href' => 'monitoramento?aba=saude',         'icon' => 'fa-heartbeat',       'label' => 'Saúde', 'alerta' => !empty($alertaSaude)],
        'auditoria'     => ['href' => 'monitoramento?aba=auditoria',     'icon' => 'fa-clipboard-list',  'label' => 'Auditoria'],
        'seguranca'     => ['href' => 'monitoramento?aba=seguranca',     'icon' => 'fa-shield-alt',      'label' => 'Segurança'],
        'erros'         => ['href' => 'monitoramento?aba=erros',         'icon' => 'fa-bug',             'label' => 'Erros'],
        'backups'       => ['href' => 'monitoramento?aba=backups',       'icon' => 'fa-database',        'label' => 'Backups'],
        'configuracoes' => ['href' => 'monitoramento?aba=configuracoes', 'icon' => 'fa-cog',             'label' => 'Configurações'],
    ];
}

$adminNome = htmlspecialchars($_SESSION['user_nome'] ?? 'Admin');

// O admin não tem foto de perfil: o avatar é o logo da escola (versão pequena em WebP do LogoRisenglish.png).
// O master não usa o logo: fica com as iniciais em cinza, como os demais usuários sem foto.
require_once __DIR__ . '/../avatar.php';
if (!function_exists('admin_avatar_logo')) {
    function admin_avatar_logo(int $tamanho): string
    {
        if (!empty($_SESSION['user_master'])) {
            return avatar_html($_SESSION['user_nome'] ?? 'Master', null, $tamanho);
        }
        return '<img src="../../logo-avatar.webp" alt="Risenglish" class="avatar" width="' . $tamanho . '" height="' . $tamanho . '"'
            . ' style="width:' . $tamanho . 'px;height:' . $tamanho . 'px;">';
    }
}
$tituloMobile = $tituloMobile ?? ($itensMenu[$paginaAtiva]['label'] ?? 'Menu');
?>
<link rel="stylesheet" href="../../css/avatar.css?v=<?= @filemtime(__DIR__ . '/../../../css/avatar.css') ?>">
<?php require __DIR__ . '/sem_reenvio.php'; ?>
<?php require __DIR__ . '/responsivo.php'; ?>
<script>
    // Aplica o estado salvo da sidebar antes dela ser pintada, evitando flash do estado errado.
    (function () {
        try {
            if (localStorage.getItem('admin_sidebar_collapsed') === '1') {
                document.documentElement.classList.add('admin-sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>

<header class="d-flex d-xl-none mobile-navbar-custom border-bottom shadow-sm p-3 align-items-center sticky-top">
    <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebarOffcanvas" aria-controls="adminSidebarOffcanvas" aria-label="Abrir Menu">
        <i class="fas fa-bars"></i>
    </button>
    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($tituloMobile); ?></h5>
</header>

<div class="offcanvas offcanvas-start text-white mobile-offcanvas" tabindex="-1" id="adminSidebarOffcanvas" aria-labelledby="adminSidebarOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="adminSidebarOffcanvasLabel"><?php echo admin_avatar_logo(36); ?><span><?php echo $adminNome; ?></span></h5>
        <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="d-flex flex-column flex-grow-1 mb-5">
            <?php foreach ($itensMenu as $chave => $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="rounded<?php echo $chave === $paginaAtiva ? ' active' : ''; ?>">
                <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>&nbsp;&nbsp;<?php echo htmlspecialchars($item['label']); ?><?php if (!empty($item['alerta'])): ?><span class="ponto-alerta-menu" title="Pede atenção" aria-label="pede atenção"></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="mt-auto">
            <a href="../logout" id="botao-sair" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i>Sair</a>
        </div>
    </div>
</div>

<div class="col-xl-2 d-none d-xl-flex flex-column sidebar p-3">
    <button type="button" id="sidebarToggle" class="sidebar-toggle-btn" title="Recolher/expandir menu" aria-label="Recolher ou expandir menu">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar-usuario mb-3">
        <?php echo admin_avatar_logo(56); ?>
        <h5 class="user-name"><?php echo $adminNome; ?></h5>
    </div>

    <div class="d-flex flex-column flex-grow-1 mb-5">
        <?php foreach ($itensMenu as $chave => $item): ?>
        <a href="<?php echo htmlspecialchars($item['href']); ?>"
           class="rounded<?php echo $chave === $paginaAtiva ? ' active' : ''; ?>"
           aria-label="<?php echo htmlspecialchars($item['label']); ?>"
           title="<?php echo htmlspecialchars($item['label']); ?>">
            <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i><span class="nav-label"><?php echo htmlspecialchars($item['label']); ?></span><?php if (!empty($item['alerta'])): ?><span class="ponto-alerta-menu" title="Pede atenção" aria-label="pede atenção"></span><?php endif; ?>
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
        var collapsed = document.documentElement.classList.toggle('admin-sidebar-collapsed');
        try {
            localStorage.setItem('admin_sidebar_collapsed', collapsed ? '1' : '0');
        } catch (e) {}
    });
})();
</script>
