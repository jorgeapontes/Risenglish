<?php
/**
 * Melhora os campos de senha marcados com o atributo data-senha:
 *   - botão de olho para mostrar/ocultar o que está sendo digitado
 *   - aviso quando o Caps Lock está ativado
 * Incluir no fim do <body> das páginas públicas com senha (login, redefinir senha).
 * Ícones em SVG embutido porque essas páginas não carregam o Font Awesome.
 */
?>
<style>
    .senha-campo { position: relative; }
    .senha-campo .form-control { padding-right: 44px; }
    .senha-olho {
        position: absolute; top: 0; right: 0; width: 44px; height: 100%;
        display: flex; align-items: center; justify-content: center;
        border: 0; background: transparent; color: #6c757d; cursor: pointer;
    }
    .senha-olho:hover, .senha-olho:focus-visible { color: #0B2C59; }
    .senha-capslock { display: none; margin-top: 6px; font-size: 0.8rem; color: #b45309; text-align: left; }
    .senha-capslock.ativo { display: block; }
</style>
<script>
(function () {
    var SVG = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
    var OLHO = SVG + '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var OLHO_RISCADO = SVG + '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    document.querySelectorAll('input[data-senha]').forEach(function (input) {
        var campo = document.createElement('div');
        campo.className = 'senha-campo';
        input.parentNode.insertBefore(campo, input);
        campo.appendChild(input);

        var olho = document.createElement('button');
        olho.type = 'button';
        olho.className = 'senha-olho';
        olho.innerHTML = OLHO;
        olho.setAttribute('aria-label', 'Mostrar senha');
        olho.title = 'Mostrar senha';
        campo.appendChild(olho);

        olho.addEventListener('click', function () {
            var mostrar = input.type === 'password';
            input.type = mostrar ? 'text' : 'password';
            olho.innerHTML = mostrar ? OLHO_RISCADO : OLHO;
            olho.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
            olho.title = mostrar ? 'Ocultar senha' : 'Mostrar senha';
            input.focus();
        });

        var aviso = document.createElement('div');
        aviso.className = 'senha-capslock';
        aviso.setAttribute('role', 'status');
        aviso.textContent = 'Caps Lock está ativado';
        campo.parentNode.insertBefore(aviso, campo.nextSibling);

        function verificarCapsLock(e) {
            if (e.getModifierState) {
                aviso.classList.toggle('ativo', e.getModifierState('CapsLock'));
            }
        }
        input.addEventListener('keydown', verificarCapsLock);
        input.addEventListener('keyup', verificarCapsLock);
        input.addEventListener('blur', function () { aviso.classList.remove('ativo'); });
    });
})();
</script>
