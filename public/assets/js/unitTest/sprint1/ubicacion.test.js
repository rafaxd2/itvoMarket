import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { afterEach, beforeAll, beforeEach, describe, test, expect, vi } from "vitest";

const codigoUbicacion = readFileSync(
  resolve(process.cwd(), "public", "assets", "js", "ubicacion.js"),
  "utf8",
);

function crearLeafletSimulado() {
  const mapa = {
    remove: vi.fn(),
    on: vi.fn(() => mapa),
    setView: vi.fn(() => mapa),
  };
  const marcador = {
    posicion: [17.0685, -96.7257],
    addTo: vi.fn(() => marcador),
    getLatLng: vi.fn(() => ({
      lat: Array.isArray(marcador.posicion) ? marcador.posicion[0] : marcador.posicion.lat,
      lng: Array.isArray(marcador.posicion) ? marcador.posicion[1] : marcador.posicion.lng,
    })),
    on: vi.fn(() => marcador),
    setLatLng: vi.fn((posicion) => {
      marcador.posicion = posicion;
      return marcador;
    }),
  };

  return {
    mapa,
    marcador,
    leaflet: {
      divIcon: vi.fn((opciones) => opciones),
      map: vi.fn(() => mapa),
      marker: vi.fn(() => marcador),
      tileLayer: vi.fn(() => ({ addTo: vi.fn() })),
    },
  };
}

describe("ubicacion.js (HU-03)", () => {
  beforeAll(() => {
    // Arrange (preparación)
    document.body.innerHTML = "";

    // Act (ejecución)
    window.eval(codigoUbicacion);
  });

  beforeEach(() => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <div id="mapaUbicacion" data-pin-guardado="false"></div>
      <input id="latitud" value="">
      <input id="longitud" value="">
      <input id="referencia" value="Entrada principal">
      <input id="estadoZona" type="checkbox" checked>
      <button id="usarPosicionActual"></button>
      <button id="guardarUbicacion"></button>
      <span id="ultimaActualizacion"></span>
      <span id="pulsoEstadoZona"></span>
    `;
    const simulacion = crearLeafletSimulado();
    vi.stubGlobal("L", simulacion.leaflet);
    vi.stubGlobal("fetch", vi.fn());
    vi.spyOn(window, "alert").mockImplementation(() => {});
    window.inicializarInteraccionesUI = vi.fn();
    window.inicializarMapaVendedor();
    window._simulacionUbicacion = simulacion;
  });

  afterEach(() => {
    // Arrange (preparación)
    document.body.innerHTML = "";
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    delete window._simulacionUbicacion;
    delete window.inicializarInteraccionesUI;
  });

  test("crearIconoMarcador crea un icono Leaflet con las dimensiones esperadas", () => {
    // Arrange (preparación)
    const { leaflet } = window._simulacionUbicacion;

    // Act (ejecución)
    const icono = window.crearIconoMarcador();

    // Assert (verificación)
    expect(leaflet.divIcon).toHaveBeenCalledWith(
      expect.objectContaining({
        className: "itvo-map-marker",
        iconSize: [28, 42],
        iconAnchor: [14, 42],
      }),
    );
    expect(icono.className).toBe("itvo-map-marker");
  });

  test("actualizarCamposDesdeMarcador escribe coordenadas con seis decimales", () => {
    // Arrange (preparación)
    window._simulacionUbicacion.marcador.posicion = { lat: 17.0685123, lng: -96.7257891 };

    // Act (ejecución)
    window.actualizarCamposDesdeMarcador();

    // Assert (verificación)
    expect(document.getElementById("latitud").value).toBe("17.068512");
    expect(document.getElementById("longitud").value).toBe("-96.725789");
  });

  test("usarPosicionActual actualiza el marcador y las coordenadas cuando hay permiso", () => {
    // Arrange (preparación)
    const getCurrentPosition = vi.fn((exito) => {
      exito({ coords: { latitude: 19.432608, longitude: -99.133209 } });
    });
    vi.stubGlobal("navigator", { geolocation: { getCurrentPosition } });

    // Act (ejecución)
    window.usarPosicionActual();

    // Assert (verificación)
    expect(getCurrentPosition).toHaveBeenCalledWith(
      expect.any(Function),
      expect.any(Function),
      { enableHighAccuracy: true },
    );
    expect(window._simulacionUbicacion.marcador.setLatLng).toHaveBeenCalledWith([19.432608, -99.133209]);
    expect(document.getElementById("latitud").value).toBe("19.432608");
    expect(document.getElementById("longitud").value).toBe("-99.133209");
  });

  test("usarPosicionActual muestra un aviso cuando se deniega el permiso", () => {
    // Arrange (preparación)
    const getCurrentPosition = vi.fn((_exito, error) => error(new Error("Permiso denegado")));
    vi.stubGlobal("navigator", { geolocation: { getCurrentPosition } });

    // Act (ejecución)
    window.usarPosicionActual();

    // Assert (verificación)
    expect(window.alert).toHaveBeenCalledWith(
      "Permite el acceso a la geolocalización para usar tu posición actual.",
    );
    expect(window._simulacionUbicacion.marcador.setLatLng).not.toHaveBeenCalled();
  });

  test("guardarUbicacion reemplaza el contenido con el HTML de una respuesta exitosa", async () => {
    // Arrange (preparación)
    fetch.mockResolvedValue({ text: () => Promise.resolve("<main>Ubicación guardada</main>") });
    document.getElementById("latitud").value = "17.068500";
    document.getElementById("longitud").value = "-96.725700";

    // Act (ejecución)
    window.guardarUbicacion();
    await vi.waitFor(() => {
      expect(document.body.innerHTML).toContain("Ubicación guardada");
    });

    // Assert (verificación)
    expect(fetch).toHaveBeenCalledWith(
      "index.php?accion=guardarUbicacion",
      expect.objectContaining({ method: "POST" }),
    );
    expect(window.inicializarInteraccionesUI).toHaveBeenCalledOnce();
  });

  test("guardarUbicacion avisa y conserva la página cuando fetch rechaza la petición", async () => {
    // Arrange (preparación)
    fetch.mockRejectedValue(new Error("Fallo de red"));

    // Act (ejecución)
    window.guardarUbicacion();
    await vi.waitFor(() => {
      expect(window.alert).toHaveBeenCalledWith("No fue posible guardar la ubicación.");
    });

    // Assert (verificación)
    expect(document.getElementById("mapaUbicacion")).not.toBeNull();
    expect(window.inicializarInteraccionesUI).not.toHaveBeenCalled();
  });

  test("guardarUbicacion avisa y conserva la página cuando el servidor responde con error HTTP", async () => {
    // Arrange (preparación)
    fetch.mockResolvedValue({
      ok: false,
      status: 500,
      text: () => Promise.resolve("<main>Error del servidor</main>"),
    });

    // Act (ejecución)
    window.guardarUbicacion();
    await new Promise((resolver) => setTimeout(resolver, 0));

    // Assert (verificación)
    expect(window.alert).toHaveBeenCalledWith("No fue posible guardar la ubicación.");
    expect(document.getElementById("mapaUbicacion")).not.toBeNull();
  });
});
