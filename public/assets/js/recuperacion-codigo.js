document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.querySelector('#formulario-codigo');
    const contenedor = formulario?.querySelector('[data-code-inputs]');
    const casillas = Array.from(contenedor?.querySelectorAll('[data-code-digit]') ?? []);
    const campoCodigo = formulario?.querySelector('#codigo-completo');

    if (!formulario || casillas.length !== 6 || !campoCodigo) {
        return;
    }

    const actualizarCodigo = () => {
        campoCodigo.value = casillas.map((casilla) => casilla.value).join('');
    };

    const enfocarCasilla = (indice) => {
        casillas[Math.max(0, Math.min(indice, casillas.length - 1))].focus();
    };

    casillas.forEach((casilla, indice) => {
        casilla.addEventListener('input', () => {
            casilla.value = casilla.value.replace(/\D/g, '').slice(-1);
            actualizarCodigo();
            if (casilla.value !== '') {
                enfocarCasilla(indice + 1);
            }
        });

        casilla.addEventListener('keydown', (evento) => {
            if (evento.key === 'Backspace' && casilla.value === '' && indice > 0) {
                evento.preventDefault();
                casillas[indice - 1].value = '';
                actualizarCodigo();
                enfocarCasilla(indice - 1);
            }
        });

        casilla.addEventListener('paste', (evento) => {
            evento.preventDefault();
            const digitos = evento.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
            digitos.split('').forEach((digito, posicion) => {
                casillas[posicion].value = digito;
            });
            casillas.slice(digitos.length).forEach((entrada) => {
                entrada.value = '';
            });
            actualizarCodigo();
            enfocarCasilla(Math.min(digitos.length, casillas.length - 1));
        });
    });

    formulario.addEventListener('submit', (evento) => {
        actualizarCodigo();
        if (!/^\d{6}$/.test(campoCodigo.value)) {
            evento.preventDefault();
            enfocarCasilla(casillas.findIndex((casilla) => casilla.value === '') === -1
                ? casillas.length - 1
                : casillas.findIndex((casilla) => casilla.value === ''));
        }
    });

    formulario.classList.add('has-code-inputs');
    campoCodigo.disabled = false;
    casillas[0].focus();
});
