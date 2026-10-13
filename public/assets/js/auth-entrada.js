(() => {
    const logo = document.querySelector('.auth-logo');
    const tarjetaAutenticacion = document.querySelector('.auth-panel');

    if (logo && tarjetaAutenticacion) {
        document.body.classList.add('auth-entry-ready');
        logo.classList.add('auth-logo--visible');
        tarjetaAutenticacion.classList.add('auth-panel--visible');
    }
})();
