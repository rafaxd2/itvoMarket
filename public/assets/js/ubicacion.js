let mapa;
let marcador;

function crearIconoMarcador() {
    return L.divIcon({
        className: 'itvo-map-marker',
        html: '<span class="map-marker-drop" aria-hidden="true"></span>',
        iconSize: [28, 42],
        iconAnchor: [14, 42],
        popupAnchor: [0, -38],
    });
}

function animarCaidaMarcador(instanciaMarcador) {
    const pin = instanciaMarcador.getElement()?.querySelector('.map-marker-drop');
    if (!pin) {
        return;
    }

    pin.classList.remove('is-dropping');
    void pin.offsetWidth;
    pin.classList.add('is-dropping');
    pin.addEventListener('animationend', () => pin.classList.remove('is-dropping'), { once: true });
}

function inicializarMapaVendedor() {
    const contenedorMapa = document.getElementById('mapaUbicacion');
    if (!contenedorMapa || typeof L === 'undefined') {
        return;
    }

    const centroInicial = [17.0685, -96.7257];
    mapa = L.map('mapaUbicacion').setView(centroInicial, 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapa);

    marcador = L.marker(centroInicial, {
        draggable: true,
        title: 'Ubicación del vendedor',
        icon: crearIconoMarcador(),
    }).addTo(mapa);
    marcador.on('dragend', actualizarCamposDesdeMarcador);
    if (contenedorMapa.dataset.pinGuardado === 'true') {
        animarCaidaMarcador(marcador);
    }
    mapa.on('click', (evento) => {
        marcador.setLatLng(evento.latlng);
        actualizarCamposDesdeMarcador();
    });

    const latitudInput = document.getElementById('latitud');
    const longitudInput = document.getElementById('longitud');
    if (latitudInput && longitudInput && latitudInput.value && longitudInput.value) {
        const latitud = parseFloat(latitudInput.value);
        const longitud = parseFloat(longitudInput.value);
        const posicion = [latitud, longitud];
        marcador.setLatLng(posicion);
        mapa.setView(posicion, 15);
    }

    document.getElementById('usarPosicionActual')?.addEventListener('click', usarPosicionActual);
    document.getElementById('guardarUbicacion')?.addEventListener('click', guardarUbicacion);
    document.getElementById('estadoZona')?.addEventListener('change', actualizarEstadoZona);
}

function inicializarMapaComprador() {
    const contenedorMapa = document.getElementById('mapaComprador');
    if (!contenedorMapa || typeof L === 'undefined') {
        return;
    }

    const centroInicial = [17.0685, -96.7257];
    mapa = L.map('mapaComprador').setView(centroInicial, 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapa);

    const vendedores = window.vendedoresActivos || [];
    vendedores.forEach((vendedor) => {
        if (!vendedor || !vendedor.latitud || !vendedor.longitud) {
            return;
        }

        const marcadorVendedor = L.marker([Number(vendedor.latitud), Number(vendedor.longitud)], {
            icon: crearIconoMarcador(),
        })
            .addTo(mapa)
            .bindPopup(`
                <strong>${(vendedor.nombre_negocio || 'Vendedor')}</strong><br>
                ${vendedor.punto_referencia || 'Ubicación activa'}
            `);

        animarCaidaMarcador(marcadorVendedor);
        marcadorVendedor.openPopup();
    });
}

function actualizarCamposDesdeMarcador() {
    const posicion = marcador.getLatLng();
    const latitud = posicion.lat;
    const longitud = posicion.lng;

    document.getElementById('latitud').value = latitud.toFixed(6);
    document.getElementById('longitud').value = longitud.toFixed(6);
}

function usarPosicionActual() {
    if (!navigator.geolocation) {
        window.alert('El navegador no otorga permiso de geolocalización.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (posicion) => {
            const ubicacion = [posicion.coords.latitude, posicion.coords.longitude];
            marcador.setLatLng(ubicacion);
            mapa.setView(ubicacion, 15);
            actualizarCamposDesdeMarcador();
        },
        () => {
            window.alert('Permite el acceso a la geolocalización para usar tu posición actual.');
        },
        { enableHighAccuracy: true }
    );
}

function guardarUbicacion() {
    const latitud = document.getElementById('latitud').value;
    const longitud = document.getElementById('longitud').value;
    const referencia = document.getElementById('referencia').value;
    const estadoZona = document.getElementById('estadoZona').checked ? 'Activo' : 'Inactivo';

    const formData = new FormData();
    formData.append('latitud', latitud);
    formData.append('longitud', longitud);
    formData.append('referencia', referencia);
    formData.append('estado_zona', estadoZona);

    fetch('index.php?accion=guardarUbicacion', {
        method: 'POST',
        body: formData,
    })
        .then((respuesta) => respuesta.text())
        .then((html) => {
            mapa?.remove();
            document.body.innerHTML = html;
            inicializarInteraccionesUI();
            inicializarMapaVendedor();
        })
        .catch(() => {
            window.alert('No fue posible guardar la ubicación.');
        });
}

function actualizarEstadoZona() {
    const controlEstado = document.getElementById('estadoZona');
    const estadoZona = controlEstado.checked ? 'Activo' : 'Inactivo';
    const fecha = new Date();
    document.getElementById('ultimaActualizacion').textContent = fecha.toLocaleString();
    controlEstado.value = estadoZona;
    if (controlEstado.checked) {
        const pulsoEstado = document.getElementById('pulsoEstadoZona');
        pulsoEstado.classList.remove('is-pulsing');
        void pulsoEstado.offsetWidth;
        pulsoEstado.classList.add('is-pulsing');
        pulsoEstado.addEventListener('animationend', () => {
            pulsoEstado.classList.remove('is-pulsing');
        }, { once: true });
    }
}

if (document.getElementById('mapaUbicacion')) {
    inicializarMapaVendedor();
}

if (document.getElementById('mapaComprador')) {
    inicializarMapaComprador();
}
