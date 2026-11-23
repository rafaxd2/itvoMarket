(() => {
    const formulario = document.querySelector('[data-availability-form]');
    const interruptor = formulario?.querySelector('#estadoDisponibilidad');
    const etiqueta = formulario?.querySelector('#availability-switch-label');
    const tituloEstado = document.querySelector('#availability-status-heading');
    const notaEstado = formulario?.querySelector('.availability-note');
    const botonRespaldo = formulario?.querySelector('[data-availability-submit]');

    if (!formulario || !interruptor || !etiqueta || !tituloEstado || !notaEstado) {
        return;
    }

    if (botonRespaldo) {
        botonRespaldo.hidden = true;
    }

    interruptor.addEventListener('change', () => {
        const estaActivo = interruptor.checked;

        interruptor.setAttribute('aria-checked', estaActivo ? 'true' : 'false');
        etiqueta.textContent = estaActivo
            ? 'Activo · visible en el mapa'
            : 'Inactivo · oculto en el mapa';
        tituloEstado.textContent = estaActivo
            ? 'Estado actual: Activo'
            : 'Estado actual: Inactivo';
        notaEstado.textContent = estaActivo
            ? 'Los compradores ya pueden verte en el mapa.'
            : 'Los compradores ya no te ven en el mapa. Tus publicaciones se conservan.';

        formulario.requestSubmit();
    });
})();
