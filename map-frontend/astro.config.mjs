// @ts-check
import { defineConfig } from 'astro/config';

import tailwindcss from '@tailwindcss/vite';

// https://astro.build/config
export default defineConfig({
  base: '/mapa/',
  output: 'static',
  vite: {
    plugins: [tailwindcss()],
    build: {
      sourcemap: true
    }
  },
});
