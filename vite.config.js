import { defineConfig } from 'vite';

export default defineConfig({
  // Корневая папка с исходниками
  root: 'src',
  
  // Настройки разработки
  server: {
    port: 3000,
    open: true,
    host: true,
    hmr: {
      port: 3000
    }
  },
  
  // Настройки сборки
  build: {
    outDir: '../dist',
    assetsDir: 'assets',
    sourcemap: true,
    minify: 'esbuild',
    rollupOptions: {
      output: {
        // Разделение на чанки для лучшего кэширования
        manualChunks: {
          // vendor: ['bootstrap'] // Bootstrap загружается через CDN
        },
        // Оптимизация имен файлов
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.');
          const ext = info[info.length - 1];
          if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name)) {
            return `assets/images/[name]-[hash][extname]`;
          }
          if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name)) {
            return `assets/fonts/[name]-[hash][extname]`;
          }
          return `assets/[name]-[hash][extname]`;
        },
        chunkFileNames: 'assets/js/[name]-[hash].js',
        entryFileNames: 'assets/js/[name]-[hash].js'
      }
    }
  },
  
  // Оптимизация зависимостей
  optimizeDeps: {
    // include: ['bootstrap'] // Bootstrap загружается через CDN
  }
});
