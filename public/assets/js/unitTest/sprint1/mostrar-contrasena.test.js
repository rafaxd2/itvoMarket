import { afterEach, beforeEach, describe, test, expect, vi } from "vitest";

describe("mostrar-contrasena.js (HU-01)", () => {
  beforeEach(() => {
    document.body.innerHTML = "";
    vi.resetModules();
  });

  afterEach(() => {
    document.body.innerHTML = "";
  });

  test("el clic alterna el campo entre contraseña oculta y visible", async () => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <form>
        <div class="campo-contrasena">
          <input type="password" data-mostrar-contrasena>
        </div>
      </form>
    `;
    await import("../../mostrar-contrasena.js");
    const campo = document.querySelector("[data-mostrar-contrasena]");
    const boton = document.querySelector(".boton-mostrar-contrasena");

    // Act (ejecución)
    boton.click();

    // Assert (verificación)
    expect(campo.type).toBe("text");
    boton.click();
    expect(campo.type).toBe("password");
  });

  test("el clic actualiza la etiqueta accesible y el icono", async () => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <div class="campo-contrasena">
        <input type="password" data-mostrar-contrasena>
      </div>
    `;
    await import("../../mostrar-contrasena.js");
    const boton = document.querySelector(".boton-mostrar-contrasena");
    const icono = boton.querySelector("i");

    // Act (ejecución)
    boton.click();

    // Assert (verificación)
    expect(boton.getAttribute("aria-label")).toBe("Ocultar contraseña");
    expect(boton.getAttribute("aria-pressed")).toBe("true");
    expect(icono.className).toBe("bi bi-eye-slash");
  });

  test("el envío del formulario vuelve a ocultar la contraseña", async () => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <form>
        <div class="campo-contrasena">
          <input type="password" data-mostrar-contrasena>
        </div>
      </form>
    `;
    await import("../../mostrar-contrasena.js");
    const formulario = document.querySelector("form");
    const campo = document.querySelector("[data-mostrar-contrasena]");
    const boton = document.querySelector(".boton-mostrar-contrasena");
    boton.click();

    // Act (ejecución)
    formulario.dispatchEvent(new Event("submit"));

    // Assert (verificación)
    expect(campo.type).toBe("password");
    expect(boton.getAttribute("aria-label")).toBe("Mostrar contraseña");
    expect(boton.getAttribute("aria-pressed")).toBe("false");
  });

  test("cada campo alterna su visibilidad de forma independiente", async () => {
    // Arrange (preparación)
    document.body.innerHTML = `
      <form>
        <div class="campo-contrasena"><input type="password" data-mostrar-contrasena></div>
        <div class="campo-contrasena"><input type="password" data-mostrar-contrasena></div>
      </form>
    `;
    await import("../../mostrar-contrasena.js");
    const campos = document.querySelectorAll("[data-mostrar-contrasena]");
    const botones = document.querySelectorAll(".boton-mostrar-contrasena");

    // Act (ejecución)
    botones[0].click();

    // Assert (verificación)
    expect(campos[0].type).toBe("text");
    expect(campos[1].type).toBe("password");
  });

  test("no agrega un botón si el campo no tiene el contenedor requerido", async () => {
    // Arrange (preparación)
    document.body.innerHTML = '<input type="password" data-mostrar-contrasena>';

    // Act (ejecución)
    await import("../../mostrar-contrasena.js");

    // Assert (verificación)
    expect(document.querySelector(".boton-mostrar-contrasena")).toBeNull();
    expect(document.querySelector("[data-mostrar-contrasena]").type).toBe("password");
  });
});
