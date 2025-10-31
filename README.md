# Department Website - Modern Trading Platform

Современный одностраничный сайт для компании Department, занимающейся алгоритмической торговлей, с PHP админ-панелью для управления контентом.

## 🚀 Особенности проекта

- **Современный дизайн** - адаптивный одностраничный сайт с анимациями
- **Динамический контент** - портфель загружается из JSON файла
- **PHP админ-панель** - удобное управление данными и файлами
- **Docker контейнеризация** - для локальной разработки
- **Валидация данных** - строгая проверка JSON и файлов
- **Автоматические бэкапы** - сохранение резервных копий

## 📁 Структура проекта

```
deptltd/
├── src/                           # Исходный код сайта
│   ├── index.html                 # Главная страница
│   ├── js/                        # JavaScript файлы
│   │   ├── portfolio.js          # Логика портфеля
│   │   └── scroll.js             # Скролл анимации
│   ├── styles/                   # CSS стили
│   └── assets/                   # Статические ресурсы
│       ├── data/                 # JSON данные
│       ├── icons/                # Иконки и токены (50+ криптовалют)
│       ├── images/               # Изображения
│       └── fonts/                # Шрифты (Manrope, SF Pro, SF Mono)
├── dist/                         # Собранный сайт (Vite)
├── admin/                        # PHP админ-панель
│   ├── index.php                 # Авторизация
│   ├── dashboard.php             # Главная панель
│   ├── portfolio.php             # Редактор портфеля
│   ├── upload.php                # Загрузка файлов
│   ├── config.php                # Конфигурация (не в git)
│   ├── functions.php             # Вспомогательные функции
│   ├── logout.php                # Выход из системы
│   ├── styles.css                # Стили админки
│   └── favicon.ico               # Иконка админки
├── data/                         # JSON файлы
│   └── portfolio-data.json        # Данные портфеля
├── uploads/                      # Загруженные файлы (не в git)
│   └── images/                   # Изображения по категориям
├── public/                       # Статические ресурсы для Vite
│   └── assets/                   # Дополнительные ресурсы
├── scripts/                      # Вспомогательные скрипты
│   └── convert-all-images.mjs    # Конвертация изображений (PNG -> WebP)
├── .github/workflows/            # GitHub Actions
│   └── deploy.yml                # Автоматический деплой
├── Dockerfile                    # Docker конфигурация (только для локальной разработки)
├── docker-compose.yml            # Docker Compose (только для локальной разработки)
├── nginx.conf                    # Nginx для Docker
├── dept.ltd.conf                 # Nginx для продакшена
├── dev.dept.ltd.conf             # Nginx для dev сервера
├── package.json                  # Зависимости (Vite, Sharp, Terser)
├── vite.config.js                # Конфигурация Vite с кастомными плагинами
└── README.md
```

## 🛠 Технологии

### Frontend
- **HTML5** - семантическая разметка с ARIA атрибутами
- **CSS3** - современные стили, CSS Grid, Flexbox, CSS Variables
- **JavaScript (ES6+)** - модули, классы, async/await, Intersection Observer
- **Vite 7.1.11** - современный сборщик с HMR и кастомными плагинами
- **Terser** - минификация JavaScript кода
- **Sharp** - оптимизация изображений

### Backend
- **PHP 8.3-FPM** - серверная логика и админ панель
- **Nginx** - веб-сервер, статический контент и прокси
- **Docker** - контейнеризация приложения
- **Docker Compose** - оркестрация сервисов

### DevOps
- **GitHub Actions** - автоматический деплой при push в main
- **SSH** - безопасное подключение к серверу
- **rsync** - синхронизация файлов без удаления существующих
- **Docker** - контейнеризация и пересборка при деплое

### Шрифты
- **Manrope** - основной шрифт для заголовков
- **SF Pro Display** - системный шрифт для текста
- **SF Mono** - моноширинный шрифт для кода и меток

## 🚀 Установка и запуск

### Локальная разработка

```bash
# Установка зависимостей
npm install

# Запуск dev сервера
npm run dev

# Сборка для продакшена
npm run build

# Предварительный просмотр сборки
npm run preview
```

### Docker (только для локальной разработки)

**Docker используется только для локальной разработки! На сервере используется системный PHP-FPM.**

```bash
# Сборка и запуск
docker compose up -d --build

# Просмотр логов
docker compose logs -f

# Остановка
docker compose down
```

**Доступ:**
- **Сайт:** http://localhost:8080
- **Админка:** http://localhost:8080/admin

## 🔐 Админ-панель

### Доступ
- **URL:** `/admin/`
- **Логин и пароль:** настраиваются в `admin/config.php`

### Возможности

#### 1. Управление портфелем
- ✅ Редактирование `portfolio-data.json` через веб-интерфейс
- ✅ Загрузка JSON файлов с валидацией
- ✅ Автоматические бэкапы перед изменениями
- ✅ Проверка структуры данных
- ✅ Валидация уникальности ID кошельков
- ✅ Проверка суммы процентов (должна быть ровно 100%)

#### 2. Загрузка файлов
- ✅ Загрузка изображений по категориям
- ✅ Валидация типов и размеров файлов
- ✅ Просмотр и удаление файлов
- ✅ Копирование путей в буфер обмена
- ✅ Превью изображений

#### 3. Безопасность
- ✅ Простая авторизация
- ✅ Валидация всех входных данных
- ✅ Защита от небезопасных файлов
- ✅ Очистка имен файлов

## 📊 Структура данных портфеля

```json
{
  "wallets": [
    {
      "id": 1,                           // Уникальный ID кошелька
      "name": "КОШЕЛЁК 1",               // Название (отображается в кнопке)
      "capital": "$125,450",             // Капитал
      "winRate": "42%",                  // Процент выигрышных сделок
      "annualReturn": "38%",             // Годовая доходность
      "yearlyReturn": "32%",             // Годовая доходность (альтернативная)
      "assets": [
        {
          "name": "USDT",                // Название токена (иконка ищется по имени)
          "percentage": 45               // Процент в портфеле
        }
      ],
      "portfolioChart": "assets/images/graph.png",  // График портфеля
      "sharpeChart": "assets/images/graph.png"       // График Шарпа
    }
  ],
  "activeWalletId": 1                    // ID кошелька по умолчанию
}
```

### Валидация данных
- **Уникальность ID** - каждый кошелек должен иметь уникальный ID
- **Сумма процентов** - сумма всех процентов в `assets` должна быть ровно 100%
- **Обязательные поля** - все поля должны быть заполнены
- **Типы данных** - проверка корректности типов


## ⚙️ Конфигурация

### Изменение пароля админки

```php
// admin/config.php
define('ADMIN_PASSWORD_HASH', password_hash('ваш_новый_пароль', PASSWORD_DEFAULT));
```

### Настройки загрузки файлов

```php
// admin/config.php
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico']);
```

### Docker переменные

```yaml
# docker-compose.yml
environment:
  - PHP_FPM_PM=dynamic
  - PHP_FPM_PM_MAX_CHILDREN=10
```

## 🔧 Разработка

### Локальная разработка

```bash
# Установка зависимостей
npm install

# Запуск dev сервера
npm run dev

# Сборка
npm run build

# Превью сборки
npm run preview
```

### Структура JavaScript

- **`portfolio.js`** - основная логика портфеля
  - Загрузка данных из `/data/portfolio-data.json`
  - Обновление UI при смене кошелька
  - Обработка ошибок загрузки
- **`scroll.js`** - анимации при скролле

### Структура PHP

- **`config.php`** - конфигурация и константы
- **`functions.php`** - вспомогательные функции
- **`index.php`** - авторизация
- **`dashboard.php`** - главная панель
- **`portfolio.php`** - редактор портфеля
- **`upload.php`** - загрузка файлов

## 🚨 Устранение неполадок

### 502 на /admin/ из-за Permission denied к PHP-FPM сокету

Симптомы:
- В логах nginx: `connect() to unix:/run/php/php8.3-fpm.sock failed (13: Permission denied)`
- `ls -l /run/php/php8.3-fpm.sock` показывает `www-data www-data` и права `srw-rw----`
- В `/etc/nginx/nginx.conf` пользователь nginx: `user nginx;`

Решение (вариант A — рекомендовано, применено в проде): добавить пользователя `nginx` в группу `www-data` и перезапустить nginx.

```bash
sudo usermod -aG www-data nginx
getent group www-data   # убедитесь, что nginx добавлен в группу
sudo systemctl restart nginx   # нужен restart, reload не подхватит новую группу
```

Альтернативы:
- B) Поменять группу сокета PHP-FPM на `nginx` в `/etc/php/8.3/fpm/pool.d/www.conf`:
  - `listen.owner = www-data`
  - `listen.group = nginx`
  - `listen.mode = 0660`
  - затем: `sudo systemctl restart php8.3-fpm && sudo systemctl reload nginx`
- C) Перейти на TCP вместо unix-сокета:
  - В `/etc/php/8.3/fpm/pool.d/www.conf`: `listen = 127.0.0.1:9000` и `sudo systemctl restart php8.3-fpm`
  - В nginx (`dev.dept.ltd.conf`): `fastcgi_pass 127.0.0.1:9000;` и `sudo nginx -t && sudo systemctl reload nginx`

Примечание: текущая конфигурация для `dev.dept.ltd` использует unix-сокет `/run/php/php8.3-fpm.sock` и работает при наличии прав группы `www-data` у процесса nginx (через добавление пользователя `nginx` в группу `www-data`).

### Ошибки Docker

```bash
# Просмотр логов
docker compose logs -f

# Пересборка контейнеров
docker compose up -d --build

# Очистка контейнеров
docker compose down -v
```

### Ошибки загрузки файлов

- Проверьте права доступа: `chmod -R 755 uploads/`
- Убедитесь в размере файла (максимум 5MB)
- Проверьте тип файла (jpg, png, gif, svg, ico)

### Ошибки JSON

- Проверьте синтаксис JSON
- Убедитесь в уникальности ID кошельков
- Проверьте сумму процентов (должна быть 100%)
- Проверьте права доступа: `chmod -R 755 data/`

### Проблемы с Nginx

```bash
# Проверка конфигурации
sudo nginx -t

# Перезагрузка
sudo systemctl reload nginx

# Просмотр логов
sudo tail -f /var/log/nginx/error.log
```


## 🚀 Деплой

### Автоматический деплой через GitHub Actions

Проект настроен для автоматического деплоя на сервер при push в main ветку с использованием Docker контейнеров.

#### Настройка GitHub Secrets

В настройках репозитория (Settings → Secrets and variables → Actions) добавьте:

- **`HOST`** - IP адрес или домен вашего сервера
- **`USERNAME`** - имя пользователя для SSH подключения  
- **`PASSWORD`** - пароль для SSH подключения

#### Настройка SSH на сервере

Убедитесь, что на сервере включена аутентификация по паролю:

```bash
# На сервере отредактируйте SSH конфигурацию
sudo nano /etc/ssh/sshd_config

# Убедитесь, что включены:
PasswordAuthentication yes
PubkeyAuthentication yes

# Перезапустите SSH сервис
sudo systemctl restart ssh
```

#### Настройка сервера

```bash
# Создание папки для сайта
mkdir -p /var/www/dev.dept.ltd
chown username:username /var/www/dev.dept.ltd

# Убедитесь, что SSH сервер запущен
sudo systemctl status ssh

# Убедитесь, что Docker установлен
docker --version
docker compose --version
```

#### Процесс деплоя

Файл `.github/workflows/deploy.yml` автоматически выполняет:

1. **Создание временной папки** - создает папку `deploy` для подготовки файлов
2. **Копирование файлов** - копирует необходимые файлы и папки:
   - `src/` - исходники
   - `dist/` - собранные файлы (основное для использования)
   - `admin/` - админ панель
   - `data/` - данные
   - `docker-compose.yml` - конфигурация Docker
   - `Dockerfile` - образ Docker
   - `dept.ltd.conf` - конфигурация сервера
3. **Исключение файлов** - НЕ копирует:
   - `config.php` (не перезаписываем конфиг на сервере)
   - `.github/`, `node_modules/`, `scripts/`, `uploads/`
   - `.gitignore`, `nginx.conf`
4. **Деплой на сервер** - копирует файлы в `/var/www/dev.dept.ltd/` БЕЗ удаления существующих
5. **Пересборка контейнеров** - выполняет `docker compose down && docker compose up --build -d`
6. **Очистка** - удаляет временную папку `deploy`

#### Особенности деплоя

- **Безопасность**: файлы на сервере не удаляются, только перезаписываются
- **Docker**: автоматическая пересборка и перезапуск контейнеров
- **Админка**: сохраняется функциональность админ панели
- **Данные**: сохраняются загруженные файлы в папке `uploads/`

### Настройка Nginx

#### Для dev.dept.ltd

```bash
# Скопировать конфигурацию
sudo cp dev.dept.ltd.conf /etc/nginx/sites-available/

# Создать символическую ссылку
sudo ln -s /etc/nginx/sites-available/dev.dept.ltd.conf /etc/nginx/sites-enabled/

# Создать SSL сертификат с Let's Encrypt
sudo certbot --nginx -d dev.dept.ltd  # добавит блок 443 в существующую конфигурацию

# Проверить конфигурацию
sudo nginx -t

# Перезагрузить nginx
sudo systemctl reload nginx
```

#### Для продакшена (dept.ltd) с SSL

```bash
# Скопировать конфигурацию
sudo cp dept.ltd.conf /etc/nginx/sites-available/

# Создать символическую ссылку
sudo ln -s /etc/nginx/sites-available/dept.ltd.conf /etc/nginx/sites-enabled/

# Создать SSL сертификат с Let's Encrypt
sudo certbot --nginx -d dept.ltd -d www.dept.ltd

# Проверить конфигурацию
sudo nginx -t

# Перезагрузить nginx
sudo systemctl reload nginx
```

**Примечание:** Certbot автоматически обновит конфигурацию nginx с правильными путями к сертификатам.

#### Политика кэширования (актуально)

- Долгий кэш (1 год, immutable):
  - Все файлы под `/assets/`
  - Файлы с хешем в имени: `name-<hash>.{css,js,png,jpg,jpeg,gif,svg,ttf,otf,webp,ico}`
- Короткий кэш (5 минут):
  - Обычные `*.js`, `*.css` (например, `/js/portfolio.js`)
  - Статика админки `/admin/*.css|*.js|*.png|...`
- JSON под `/data/` — без долгого кэша (добавлены заголовки и CORS)

#### Админка `/admin` (FastCGI)

- Блок `location /admin/` использует `alias` и `try_files $uri $uri/ /admin/index.php?$args`.
- Обработка PHP:
  - Через Unix‑сокет: `fastcgi_pass unix:/run/php/php8.3-fpm.sock;`
  - Обязательный параметр: `fastcgi_param SCRIPT_FILENAME /var/www/<domain>/admin/$1;`
- Защита служебных файлов: `location ~ ^/admin/(config|functions)\.php$ { deny all; }`

Примечание по правам сокета: если Nginx работает от пользователя `nginx`, добавьте его в группу `www-data` и перезапустите nginx (см. раздел «Устранение неполадок»).

### Выпуск SSL сертификата

#### Установка Certbot

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install certbot python3-certbot-nginx

# CentOS/RHEL
sudo yum install certbot python3-certbot-nginx
```

#### Создание сертификата

```bash
# Для домена dept.ltd
sudo certbot --nginx -d dept.ltd -d www.dept.ltd

# Следуйте инструкциям:
# 1. Введите email для уведомлений
# 2. Согласитесь с условиями (A)
# 3. Выберите редирект HTTP->HTTPS (2)
```

#### Проверка и обновление

```bash
# Проверка статуса сертификатов
sudo certbot certificates

# Тестовое обновление
sudo certbot renew --dry-run

# Автоматическое обновление (добавить в cron)
sudo crontab -e
# Добавить строку:
# 0 12 * * * /usr/bin/certbot renew --quiet
```

#### Полезные команды

```bash
# Просмотр конфигурации nginx после certbot
sudo nginx -t

# Перезагрузка nginx
sudo systemctl reload nginx

# Проверка SSL сертификата
openssl s_client -connect dept.ltd:443 -servername dept.ltd
```