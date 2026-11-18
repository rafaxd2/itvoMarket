import { defineConfig } from "vitest/config";

export default defineConfig({
  test: {
    environment: "jsdom",
    include: ["public/assets/js/unitTest/**/*.test.js"],
  },
});
