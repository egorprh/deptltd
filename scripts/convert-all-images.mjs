// scripts/convert-all-images.js
import sharp from 'sharp';
import { readdirSync, mkdirSync, copyFileSync, statSync, unlinkSync } from 'fs';
import { resolve, extname, basename } from 'path';

async function convertImages() {
  const dirs = [
    'public/assets/images',
    'public/assets/icons/tokenico'
  ];

  for (const dir of dirs) {
    console.log(`\n🖼️  Processing ${dir}...`);
    
    const files = readdirSync(dir);
    let converted = 0;
    let skipped = 0;
    let deleted = 0;

    for (const file of files) {
      const inputPath = resolve(dir, file);
      const ext = extname(file).toLowerCase();
      
      if (/\.(png|jpg|jpeg)$/i.test(file)) {
        const webpPath = inputPath.replace(/\.(png|jpg|jpeg)$/i, '.webp');
        
        try {
          // Конвертируем в WebP
          await sharp(inputPath)
            .webp({ quality: 100 })
            .toFile(webpPath);
          
          // Удаляем исходный файл
          unlinkSync(inputPath);
          
          console.log(`✅ ${file} → ${basename(webpPath)} (deleted original)`);
          converted++;
          deleted++;
        } catch (error) {
          console.error(`❌ Error converting ${file}:`, error.message);
        }
      } else {
        console.log(`📁 Skipped ${file} (${ext})`);
        skipped++;
      }
    }
    
    console.log(`📊 Converted: ${converted}, Deleted: ${deleted}, Skipped: ${skipped}`);
  }
}

convertImages().catch(console.error);