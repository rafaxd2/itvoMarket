import { afterEach, beforeEach, describe, test, expect, vi } from "vitest";

describe("producto-animaciones.js (HU-02)", () => {
  beforeEach(() => {
    document.body.innerHTML = "";
    vi.useFakeTimers();
    vi.resetModules();
  });

  afterEach(() => {
    document.body.innerHTML = "";
    vi.useRealTimers();
  });

  test("agrega la clase de salida al cumplir 2.5 segundos", async () => {
    // Arrange (preparación)
    document.body.innerHTML = '<div class="product-published-toast"></div>';
    await import("../../producto-animaciones.js");
    const aviso = document.querySelector(".product-published-toast");

    // Act (ejecución)
    vi.advanceTimersByTime(2499);
    const claseAntesDelPlazo = aviso.classList.contains("is-leaving");
    vi.advanceTimersByTime(1);

    // Assert (verificación)
    expect(claseAntesDelPlazo).toBe(false);
    expect(aviso.classList.contains("is-leaving")).toBe(true);
  });

  test("el evento animationend elimina el aviso", async () => {
    // Arrange (preparación)
    document.body.innerHTML = '<div class="product-published-toast"></div>';
    await import("../../producto-animaciones.js");
    const aviso = document.querySelector(".product-published-toast");
    vi.advanceTimersByTime(2500);

    // Act (ejecución)
    aviso.dispatchEvent(new Event("animationend"));

    // Assert (verificación)
    expect(document.querySelector(".product-published-toast")).toBeNull();
  });

  test("no falla cuando no existe el aviso de publicación", async () => {
    // Arrange (preparación)
    document.body.innerHTML = "";

    // Act (ejecución)
    await expect(import("../../producto-animaciones.js")).resolves.toBeDefined();

    // Assert (verificación)
    expect(document.querySelector(".product-published-toast")).toBeNull();
    expect(vi.getTimerCount()).toBe(0);
  });
});
