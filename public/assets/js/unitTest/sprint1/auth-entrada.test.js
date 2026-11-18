import { afterEach, beforeEach, describe, test, expect, vi } from "vitest";

describe("auth-entrada.js (HU-01)", () => {
  beforeEach(() => {
    document.body.innerHTML = "";
    document.body.className = "";
    vi.resetModules();
  });

  afterEach(() => {
    document.body.innerHTML = "";
    document.body.className = "";
  });

  test("agrega las clases de entrada si existen el logo y el panel", async () => {
    // Arrange (preparación)
    document.body.innerHTML = '<img class="auth-logo"><div class="auth-panel"></div>';

    // Act (ejecución)
    await import("../../auth-entrada.js");

    // Assert (verificación)
    expect(document.body.classList.contains("auth-entry-ready")).toBe(true);
    expect(document.querySelector(".auth-logo").classList.contains("auth-logo--visible")).toBe(true);
    expect(document.querySelector(".auth-panel").classList.contains("auth-panel--visible")).toBe(true);
  });

  test("no agrega clases de entrada cuando falta el logo", async () => {
    // Arrange (preparación)
    document.body.innerHTML = '<div class="auth-panel"></div>';

    // Act (ejecución)
    await import("../../auth-entrada.js");

    // Assert (verificación)
    expect(document.body.classList.contains("auth-entry-ready")).toBe(false);
    expect(document.querySelector(".auth-panel").classList.contains("auth-panel--visible")).toBe(false);
  });

  test("no agrega clases de entrada cuando falta el panel", async () => {
    // Arrange (preparación)
    document.body.innerHTML = '<img class="auth-logo">';

    // Act (ejecución)
    await import("../../auth-entrada.js");

    // Assert (verificación)
    expect(document.body.classList.contains("auth-entry-ready")).toBe(false);
    expect(document.querySelector(".auth-logo").classList.contains("auth-logo--visible")).toBe(false);
  });
});
