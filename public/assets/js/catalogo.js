document.addEventListener('error', (evento) => {
    const imagen = evento.target;

    if (!(imagen instanceof HTMLImageElement) || !imagen.matches('[data-product-image]')) {
        return;
    }

    const contenedor = imagen.closest('.catalog-image');
    const respaldo = contenedor?.querySelector('.catalog-image-placeholder');

    if (respaldo) {
        imagen.hidden = true;
        respaldo.hidden = false;
    }
}, true);
