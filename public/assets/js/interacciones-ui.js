function inicializarInteraccionesUI() {
    document.querySelectorAll('[data-role-change-form]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            const contenidoHeader = formulario.closest('.site-header')?.querySelector('.site-header-content');
            if (!contenidoHeader) {
                return;
            }

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            evento.preventDefault();
            contenidoHeader.classList.add('role-change-out');
            window.setTimeout(() => formulario.submit(), 200);
        });
    });
}

inicializarInteraccionesUI();
