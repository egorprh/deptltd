import { defineConfig } from 'vite';
import { copyFileSync, readdirSync, mkdirSync, existsSync } from 'fs';
import { resolve } from 'path';

export default defineConfig({
  // ... существующие настройки
  root: 'src',
  build: {
    outDir: '../dist',
    assetsDir: 'assets',
    // ... остальные настройки
  },
  
  plugins: [
    {
      name: 'copy-static-assets',
      buildStart() {
        // Копируем файлы из src/assets в dist/assets
        const srcAssets = 'public/assets';
        const distAssets = 'dist/assets';
        
        if (existsSync(srcAssets)) {
          mkdirSync(distAssets, { recursive: true });
          
          // Копируем все файлы рекурсивно
          function copyRecursive(src, dest) {
            const items = readdirSync(src);
            
            items.forEach(item => {
              const srcPath = resolve(src, item);
              const destPath = resolve(dest, item);
              
              if (existsSync(srcPath)) {
                const stat = require('fs').statSync(srcPath);
                
                if (stat.isDirectory()) {
                  mkdirSync(destPath, { recursive: true });
                  copyRecursive(srcPath, destPath);
                } else {
                  copyFileSync(srcPath, destPath);
                }
              }
            });
          }
          
          copyRecursive(srcAssets, distAssets);
          console.log('✅ Static assets copied to dist/');
        }
      }
    }
  ]
});