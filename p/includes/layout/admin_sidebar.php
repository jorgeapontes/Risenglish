<?php
/**
 * Sidebar compartilhada das páginas de admin, com botão de recolher/expandir.
 * Não mexe em sessão/autenticação — cada página continua responsável por isso.
 * Espera (opcional): $paginaAtiva (chave de $itensMenu abaixo, ex: 'dashboard').
 */
$paginaAtiva = $paginaAtiva ?? '';

$itensMenu = [
    'dashboard'     => ['href' => 'dashboard.php',              'icon' => 'fa-home',            'label' => 'Dashboard'],
    'leads'         => ['href' => 'leads.php',                  'icon' => 'fa-user-tie',         'label' => 'Leads'],
    'personalizar'  => ['href' => 'personalizar_index.php',     'icon' => 'fa-paint-brush',      'label' => 'Personalizar Site'],
    'turmas'        => ['href' => 'gerenciar_turmas.php',       'icon' => 'fa-users',            'label' => 'Turmas'],
    'usuarios'      => ['href' => 'gerenciar_usuarios.php',     'icon' => 'fa-user',             'label' => 'Usuários'],
    'uteis'         => ['href' => 'gerenciar_uteis.php',        'icon' => 'fa-book-open',        'label' => 'Recomendações'],
    'agendas'       => ['href' => 'agendas.php',                'icon' => 'fa-calendar-alt',     'label' => 'Agendas'],
    'pagamentos'    => ['href' => 'pagamentos.php',             'icon' => 'fa-dollar-sign',      'label' => 'Pagamentos'],
    'acessos'       => ['href' => 'acessos.php',                'icon' => 'fa-chart-line',       'label' => 'Relatório de Acessos'],
];
?>
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
<div class="col-md-2 d-flex flex-column sidebar p-3">
    <button type="button" id="sidebarToggle" class="sidebar-toggle-btn" title="Recolher/expandir menu" aria-label="Recolher ou expandir menu">
        <i class="fas fa-bars"></i>
    </button>

    <div class="mb-4 text-center">
        <h5 class="mt-4 user-name"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Admin'); ?></h5>
    </div>

    <div class="d-flex flex-column flex-grow-1 mb-5">
        <?php foreach ($itensMenu as $chave => $item): ?>
        <a href="<?php echo htmlspecialchars($item['href']); ?>"
           class="rounded<?php echo $chave === $paginaAtiva ? ' active' : ''; ?>"
           aria-label="<?php echo htmlspecialchars($item['label']); ?>"
           title="<?php echo htmlspecialchars($item['label']); ?>">
            <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i><span class="nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-auto">
        <a href="../logout.php" id="botao-sair" class="btn btn-outline-danger w-100" aria-label="Sair" title="Sair">
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
