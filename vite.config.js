/**
 * Vite Configuration
 * Настройка сборки проекта с кастомными плагинами для копирования и минификации файлов
 */

// Импорты для работы с файловой системой и минификацией
import { defineConfig } from 'vite';
import { copyFileSync, readdirSync, mkdirSync, existsSync, readFileSync, writeFileSync } from 'fs';
import { resolve } from 'path';
import { minify } from 'terser';

export default defineConfig({
  // Базовая конфигурация Vite
  root: 'src',                    // Корневая папка исходников
  build: {
    outDir: '../dist',            // Папка для собранных файлов
    assetsDir: 'assets',         // Папка для статических ресурсов
  },
  
  // Кастомные плагины для обработки файлов
  plugins: [
    {
      name: 'copy-static-assets',  // Название плагина
      async buildStart() {         // Хук, выполняющийся при начале сборки
        
        // ========================================
        // КОПИРОВАНИЕ СТАТИЧЕСКИХ РЕСУРСОВ
        // ========================================
        
        // Копируем файлы из public/assets в dist/assets
        const srcAssets = 'public/assets';    // Источник статических файлов
        const distAssets = 'dist/assets';     // Назначение в dist
        
        if (existsSync(srcAssets)) {
          mkdirSync(distAssets, { recursive: true });  // Создаем папку назначения
          
          /**
           * Рекурсивная функция копирования файлов и папок
           * @param {string} src - Путь к исходной папке
           * @param {string} dest - Путь к папке назначения
           */
          function copyRecursive(src, dest) {
            const items = readdirSync(src);  // Получаем список файлов и папок
            
            items.forEach(item => {
              const srcPath = resolve(src, item);   // Полный путь к исходному файлу
              const destPath = resolve(dest, item); // Полный путь к файлу назначения
              
              if (existsSync(srcPath)) {
                const stat = require('fs').statSync(srcPath);  // Получаем информацию о файле
                
                if (stat.isDirectory()) {
                  // Если это папка - создаем её и рекурсивно копируем содержимое
                  mkdirSync(destPath, { recursive: true });
                  copyRecursive(srcPath, destPath);
                } else {
                  // Если это файл - просто копируем
                  copyFileSync(srcPath, destPath);
                }
              }
            });
          }
          
          copyRecursive(srcAssets, distAssets);
          console.log('✅ Static assets copied to dist/');
        }
        
        // ========================================
        // ОБРАБОТКА И МИНИФИКАЦИЯ JAVASCRIPT
        // ========================================
        
        // Копируем и минифицируем JavaScript файлы из src/js в dist/assets/js
        const srcJs = 'src/js';              // Папка с исходными JS файлами
        const distJs = 'dist/js';    // Папка для обработанных JS файлов
        
        if (existsSync(srcJs)) {
          mkdirSync(distJs, { recursive: true });  // Создаем папку для JS файлов
          
          const jsFiles = readdirSync(srcJs);      // Получаем список JS файлов
          
          // Обрабатываем каждый JS файл
          for (const file of jsFiles) {
            if (file.endsWith('.js')) {  // Проверяем, что это JS файл
              const srcPath = resolve(srcJs, file);   // Путь к исходному файлу
              const destPath = resolve(distJs, file); // Путь к обработанному файлу
              
              try {
                // Читаем исходный JavaScript код
                const jsCode = readFileSync(srcPath, 'utf8');
                
                // Минифицируем код с помощью Terser
                const minified = await minify(jsCode, {
                  compress: true,        // Включаем сжатие кода
                  mangle: true,        // Включаем сокращение имен переменных
                  format: {
                    comments: false      // Удаляем комментарии
                  }
                });
                
                // Записываем минифицированный код в файл
                writeFileSync(destPath, minified.code);
                console.log(`✅ Minified: ${file}`);
                
              } catch (error) {
                // Если минификация не удалась - копируем файл как есть
                console.warn(`⚠️ Failed to minify ${file}, copying as-is:`, error.message);
                copyFileSync(srcPath, destPath);
              }
            }
          }
          
          console.log('✅ JavaScript files copied to dist/assets/js/');
        }
      }
    }
  ]
});