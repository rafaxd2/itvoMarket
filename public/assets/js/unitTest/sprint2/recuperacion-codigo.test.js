import { afterEach, beforeEach, describe, test, expect, vi } from "vitest";

function crearFormularioCodigo(cantidadCasillas = 6) {
  const casillas = Array.from(
    { length: cantidadCasillas },
    () => '<input type="text" data-code-digit>',
  ).join("");

  document.body.innerHTML = `
    <form id="formulario-codigo">
      <div data-code-inputs>${casillas}</div>
      <input id="codigo-completo" disabled>
    </form>
  `;
}

async function inicializarScriptCodigo() {
  const espia = vi.spyOn(document, "addEventListener").mockImplementation(() => {});
  await import("../../recuperacion-codigo.js");
  const llamadaDomContentLoaded = espia.mock.calls.find(([tipo]) => tipo === "DOMContentLoaded");

  if (!llamadaDomContentLoaded) {
    throw new Error("No se registró el evento DOMContentLoaded.");
  }

  const [, inicializar] = llamadaDomContentLoaded;
  inicializar();
}

describe("recuperacion-codigo.js (HU-10)", () => {
  beforeEach(() => {
    // Arrange (preparación)
    document.body.innerHTML = "";
    vi.resetModules();
  });

  afterEach(() => {
    // Arrange (preparación)
    document.body.innerHTML = "";
    vi.resetModules();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  test("inicializa las seis casillas y enfoca la primera", async () => {
    // Arrange (preparación)
    crearFormularioCodigo();

    // Act (ejecución)
    await inicializarScriptCodigo();

    // Assert (verificación)
    expect(document.querySelector("#formulario-codigo").classList.contains("has-code-inputs")).toBe(true);
    expect(document.querySelector("#codigo-completo").disabled).toBe(false);
    expect(document.activeElement).toBe(document.querySelectorAll("[data-code-digit]")[0]);
  });

  test("no inicializa el formulario si hay menos de seis casillas", async () => {
    // Arrange (preparación)
    crearFormularioCodigo(5);

    // Act (ejecución)
    await inicializarScriptCodigo();

    // Assert (verificación)
    expect(document.querySelector("#formulario-codigo").classList.contains("has-code-inputs")).toBe(false);
    expect(document.querySelector("#codigo-completo").disabled).toBe(true);
  });

  test("descarta letras y al escribir un dígito actualiza el código y mueve el foco", async () => {
    // Arrange (preparación)
    crearFormularioCodigo();
    await inicializarScriptCodigo();
    const casillas = document.querySelectorAll("[data-code-digit]");
    const campoCodigo = document.querySelector("#codigo-completo");
    casillas[0].value = "a";

    // Act (ejecución)
    casillas[0].dispatchEvent(new Event("input"));
    casillas[0].value = "4";
    casillas[0].dispatchEvent(new Event("input"));

    // Assert (verificación)
    expect(casillas[0].value).toBe("4");
    expect(campoCodigo.value).toBe("4");
    expect(document.activeElement).toBe(casillas[1]);
  });

  test("conserva solo el último dígito cuando se escriben varios", async () => {
    // Arrange (preparación)
    crearFormularioCodigo();
    await inicializarScriptCodigo();
    const casillas = document.querySelectorAll("[data-code-digit]");
    const campoCodigo = document.querySelector("#codigo-completo");
    casillas[0].value = "12";

    // Act (ejecución)
    casillas[0].dispatchEvent(new Event("input"));

    // Assert (verificación)
    expect(casillas[0].value).toBe("2");
    expect(campoCodigo.value).toBe("2");
  });

  test("Backspace en una casilla vacía borra la anterior y enfoca esa casilla", async () => {
    // Arrange (preparación)
    crearFormularioCodigo();
    await inicializarScriptCodigo();
    const casillas = document.querySelectorAll("[data-code-digit]");
    const campoCodigo = document.querySelector("#codigo-completo");
    casillas[0].value = "3";
    casillas[1].value = "";
    casillas[2].focus();
    casillas[1].dispatchEvent(new Event("input"));
    const evento = new KeyboardEvent("keydown", { key: "Backspace", cancelable: true });

    // Act (ejecución)
    casillas[1].dispatchEvent(evento);

    // Assert (verificación)
    expect(evento.defaultPrevented).toBe(true);
    expect(casillas[0].value).toBe("");
    expect(campoCodigo.value).toBe("");
    expect(document.activeElement).toBe(casillas[0]);
  });

  test("pegar un texto con letras reparte únicamente sus primeros seis dígitos", async () => {
    // Arrange (preparación)
    crearFormularioCodigo();
    await inicializarScriptCodigo();
    const casillas = document.querySelectorAll("[data-code-digit]");
    const campoCodigo = document.querySelector("#codigo-completo");
    const evento = new Event("paste", { cancelable: true });
    Object.defineProperty(evento, "clipboardData", {
      value: { getData: () => "12a3456789" },
    });

    // Act (ejecución)
    casillas[0].dispatchEvent(evento);

    // Assert (verificación)
    expect(evento.defaultPrevented).toBe(true);
    expect(Array.from(casillas, (casilla) => casilla.value).join("")).toBe("123456");
    expect(campoCodigo.value).toBe("123456");
  });

  test("pegar menos de seis dígitos limpia las casillas restantes y enfoca la siguiente", async () => {
    // Arrange (preparación)
    crearFormularioCodigo();
    await inicializarScriptCodigo();
    const casillas = document.querySelectorAll("[data-code-digit]");
    const campoCodigo = document.querySelector("#codigo-completo");
    casillas.forEach((casilla) => {
      casilla.value = "9";
    });
    const evento = new Event("paste", { cancelable: true });
    Object.defineProperty(evento, "clipboardData", {
      value: { getData: () => "12" },
    });

    // Act (ejecución)
    casillas[0].dispatchEvent(evento);

    // Assert (verificación)
    expect(Array.from(casillas, (casilla) => casilla.value).join("")).toBe("12");
    expect(campoCodigo.value).toBe("12");
    expect(document.activeElement).toBe(casillas[2]);
  });

  test("cancela el envío incompleto y permite enviar un código de seis dígitos", async () => {
    // Arrange (preparación)
    crearFormularioCodigo();
    await inicializarScriptCodigo();
    const formulario = document.querySelector("#formulario-codigo");
    const casillas = document.querySelectorAll("[data-code-digit]");
    const campoCodigo = document.querySelector("#codigo-completo");
    casillas.forEach((casilla, indice) => {
      casilla.value = indice < 5 ? String(indice + 1) : "";
    });
    const eventoIncompleto = new Event("submit", { cancelable: true });

    // Act (ejecución)
    formulario.dispatchEvent(eventoIncompleto);

    // Assert (verificación)
    expect(eventoIncompleto.defaultPrevented).toBe(true);
    expect(campoCodigo.value).toBe("12345");
    expect(document.activeElement).toBe(casillas[5]);

    // Arrange (preparación)
    casillas[5].value = "6";
    const eventoCompleto = new Event("submit", { cancelable: true });

    // Act (ejecución)
    formulario.dispatchEvent(eventoCompleto);

    // Assert (verificación)
    expect(eventoCompleto.defaultPrevented).toBe(false);
    expect(campoCodigo.value).toBe("123456");
  });
});
