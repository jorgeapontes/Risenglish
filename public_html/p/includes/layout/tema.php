<?php
/**
 * Modo claro/escuro da área logada (aluno, professor e admin).
 *
 * Incluído no início de cada sidebar. A escolha fica no localStorage ('risenglish_tema')
 * e é aplicada no <html> antes do conteúdo ser pintado:
 *   data-bs-theme="dark"  -> modo escuro nativo do Bootstrap 5.3 (cards, tabelas, formulários, modais…)
 *   data-tema="escuro"    -> ajustes das classes próprias do site (css/tema-escuro.css)
 * Padrão: modo claro.
 */
if (!function_exists('tema_botao')) {
    /** Botão que alterna o tema; o texto fica em .nav-label, que some com a sidebar recolhida. */
    function tema_botao(string $classes = ''): string
    {
        // Ícones em SVG (o sol desta versão do Font Awesome parece uma engrenagem); o CSS mostra um ou outro
        $svg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="%s">%s</svg>';
        $lua = sprintf($svg, 'tema-icone-lua', '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>');
        $sol = sprintf($svg, 'tema-icone-sol', '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/>'
            . '<line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>'
            . '<line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/>'
            . '<line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>'
            . '<line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>');

        return '<button type="button" class="btn-tema ' . htmlspecialchars($classes) . '" data-alternar-tema'
            . ' aria-label="Ativar modo escuro" title="Ativar modo escuro">'
            . $lua . $sol . '<span class="nav-label">Modo escuro</span>'
            . '</button>';
    }
}
?>
<script>
    (function () {
        try {
            if (localStorage.getItem('risenglish_tema') === 'escuro') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                document.documentElement.setAttribute('data-tema', 'escuro');
            }
        } catch (e) {}
    })();
</script>
<link rel="stylesheet" href="../../css/tema-escuro.css">
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var html = document.documentElement;

        function atualizarBotoes() {
            var escuro = html.getAttribute('data-tema') === 'escuro';
            document.querySelectorAll('[data-alternar-tema]').forEach(function (btn) {
                var rotulo = escuro ? 'Modo claro' : 'Modo escuro';
                btn.querySelector('.nav-label').textContent = rotulo;
                btn.setAttribute('aria-label', 'Ativar ' + rotulo.toLowerCase());
                btn.title = 'Ativar ' + rotulo.toLowerCase();
            });
        }

        document.querySelectorAll('[data-alternar-tema]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var escuro = html.getAttribute('data-tema') !== 'escuro';
                if (escuro) {
                    html.setAttribute('data-bs-theme', 'dark');
                    html.setAttribute('data-tema', 'escuro');
                } else {
                    html.removeAttribute('data-bs-theme');
                    html.removeAttribute('data-tema');
                }
                try { localStorage.setItem('risenglish_tema', escuro ? 'escuro' : 'claro'); } catch (e) {}
                atualizarBotoes();
            });
        });

        atualizarBotoes();
    });
</script>
