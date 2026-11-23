import { afterEach, beforeEach, describe, test, expect, vi } from "vitest";

const escuchasError = [];

async function cargarScriptCatalogo() {
  const espia = vi.spyOn(document, "addEventListener").mockImplementation(() => {});
  await import("../../catalogo.js");
  const llamadaError = espia.mock.calls.find(([tipo]) => tipo === "error");
  espia.mockRestore();

  if (llamadaError) {
    const [, manejador, captura] = llamadaError;
    document.addEventListener("error", manejador, captura);
    escuchasError.push(manejador);
  }
}

describe("catalogo.js (HU-07)", () => {
  beforeEach(() => {
    // Arrange (preparación)
    document.body.innerHTML = "";
    vi.resetModules();
  });

  afterEach(() => {
    // Arrange (preparación)
    escuchasError.forEach((manejador) => {
      document.removeEventListener("error", manejador, true);
    });
    escuchasError.length = 0;
    document.body.innerHTML = "";
    vi.resetModules();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  test("oculta la imagen fallida y muestra su placeholder", async () => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <div class="catalog-image">
        <img data-product-image>
        <span class="catalog-image-placeholder" hidden></span>
      </div>
    `;
    await cargarScriptCatalogo();
    const imagen = document.querySelector("img");
    const placeholder = document.querySelector(".catalog-image-placeholder");

    // Act (ejecución)
    imagen.dispatchEvent(new Event("error"));

    // Assert (verificación)
    expect(imagen.hidden).toBe(true);
    expect(placeholder.hidden).toBe(false);
  });

  test("no cambia una imagen que no tiene data-product-image", async () => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <div class="catalog-image">
        <img>
        <span class="catalog-image-placeholder" hidden></span>
      </div>
    `;
    await cargarScriptCatalogo();
    const imagen = document.querySelector("img");
    const placeholder = document.querySelector(".catalog-image-placeholder");

    // Act (ejecución)
    imagen.dispatchEvent(new Event("error"));

    // Assert (verificación)
    expect(imagen.hidden).toBe(false);
    expect(placeholder.hidden).toBe(true);
  });

  test("ignora un elemento que no es una imagen", async () => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <div class="catalog-image">
        <div data-product-image></div>
        <span class="catalog-image-placeholder" hidden></span>
      </div>
    `;
    await cargarScriptCatalogo();
    const elemento = document.querySelector("[data-product-image]");
    const placeholder = document.querySelector(".catalog-image-placeholder");

    // Act (ejecución)
    elemento.dispatchEvent(new Event("error"));

    // Assert (verificación)
    expect(elemento.hidden).toBe(false);
    expect(placeholder.hidden).toBe(true);
  });

  test("no falla ni oculta la imagen si falta el contenedor catalog-image", async () => {
    // Arrange (preparación)
    document.body.innerHTML = '<img data-product-image>';
    await cargarScriptCatalogo();
    const imagen = document.querySelector("img");

    // Act (ejecución)
    imagen.dispatchEvent(new Event("error"));

    // Assert (verificación)
    expect(imagen.hidden).toBe(false);
  });

  test("mantiene visible la imagen si el contenedor no tiene placeholder", async () => {
    // Arrange (preparación)
    document.body.innerHTML = '<div class="catalog-image"><img data-product-image></div>';
    await cargarScriptCatalogo();
    const imagen = document.querySelector("img");

    // Act (ejecución)
    imagen.dispatchEvent(new Event("error"));

    // Assert (verificación)
    expect(imagen.hidden).toBe(false);
  });

  test("solo cambia la tarjeta cuya imagen falló", async () => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <div class="catalog-image">
        <img data-product-image>
        <span class="catalog-image-placeholder" hidden></span>
      </div>
      <div class="catalog-image">
        <img data-product-image>
        <span class="catalog-image-placeholder" hidden></span>
      </div>
    `;
    await cargarScriptCatalogo();
    const [imagenFallida, imagenIntacta] = document.querySelectorAll("img");
    const [placeholderFallido, placeholderIntacto] = document.querySelectorAll(".catalog-image-placeholder");

    // Act (ejecución)
    imagenFallida.dispatchEvent(new Event("error"));

    // Assert (verificación)
    expect(imagenFallida.hidden).toBe(true);
    expect(placeholderFallido.hidden).toBe(false);
    expect(imagenIntacta.hidden).toBe(false);
    expect(placeholderIntacto.hidden).toBe(true);
  });
});
