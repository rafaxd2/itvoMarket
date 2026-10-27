(() => {
    const campos = document.querySelectorAll('input[type="password"][data-mostrar-contrasena]');

    const actualizarEstado = (campo, boton, visible) => {
        campo.type = visible ? 'text' : 'password';
        boton.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        boton.setAttribute('aria-pressed', visible ? 'true' : 'false');
        boton.querySelector('i').className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
    };

    campos.forEach((campo) => {
        const contenedor = campo.closest('.campo-contrasena');
        if (!contenedor) {
            return;
        }

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'boton-mostrar-contrasena';
        boton.setAttribute('aria-label', 'Mostrar contraseña');
        boton.setAttribute('aria-pressed', 'false');
        boton.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
        contenedor.appendChild(boton);

        boton.addEventListener('click', () => {
            const posicionInicio = campo.selectionStart;
            const posicionFin = campo.selectionEnd;
            const visible = campo.type === 'password';
            actualizarEstado(campo, boton, visible);
            campo.focus({ preventScroll: true });

            if (posicionInicio !== null && posicionFin !== null) {
                campo.setSelectionRange(posicionInicio, posicionFin);
            }
        });

        const formulario = campo.form;
        if (formulario && !formulario.dataset.ocultarContrasenasAlEnviar) {
            formulario.dataset.ocultarContrasenasAlEnviar = 'true';
            formulario.addEventListener('submit', () => {
                formulario.querySelectorAll('.campo-contrasena input[data-mostrar-contrasena]').forEach((campoFormulario) => {
                    campoFormulario.type = 'password';
                    const botonFormulario = campoFormulario.closest('.campo-contrasena').querySelector('.boton-mostrar-contrasena');
                    if (botonFormulario) {
                        actualizarEstado(campoFormulario, botonFormulario, false);
                    }
                });
            });
        }

        window.addEventListener('pageshow', () => {
            actualizarEstado(campo, boton, false);
        });
    });
})();
