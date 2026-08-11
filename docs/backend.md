# Backend

Backend реализован как WordPress plugin в каталоге `billboardy-map-api/`.

## Задача backend-части

- хранить или читать данные рекламных плоскостей;
- нормализовать их;
- отдавать стабильный REST API для frontend-карты;
- предоставлять административные инструменты для настроек и импорта.

## Основные зоны кода

### Entry / bootstrap

- `billboardy-map-api/billboardy-map-api.php`
- `billboardy-map-api/src/Plugin.php`

Тут регистрируются хуки, поднимаются зависимости и подключаются REST/admin части.

### Admin

- `billboardy-map-api/src/Admin/SettingsPage.php`
- `billboardy-map-api/src/Admin/ImportPage.php`
- `billboardy-map-api/src/Admin/InquiryLogPage.php`

Тут лежат:

- настройки плагина;
- очистка и прогрев кэша;
- интерфейс импорта файлов.
- закрытый журнал заявок с поиском, фильтрами и CSV-экспортом.

### Database

- `billboardy-map-api/src/Database/Schema.php`

Тут описана собственная таблица и её создание/обновление.

### Import

- `billboardy-map-api/src/Import/AdSpaceImporter.php`

Тут живёт разбор файлов, нормализация входных строк и запись в таблицу.

### Repository

- `billboardy-map-api/src/Repository/`

Эта зона отвечает за доступ к данным:

- чтение из собственной таблицы;
- fallback на WooCommerce;
- переключение между источниками.

### Domain

- `billboardy-map-api/src/Domain/AdSpaceMapper.php`

Тут сосредоточены правила нормализации и подготовки доменной модели.

### Service

- `billboardy-map-api/src/Service/AdSpaceService.php`

Тут лежит логика:

- фильтрации;
- кэширования;
- SQL-backed pagination для `/ad-spaces`, когда runtime-источником служит собственная таблица;
- облегчённого `map-points` payload без дублирующего `data`;
- server-side cluster preparation;
- выдачи одной точки и SQL-backed фильтров.

### REST

- `billboardy-map-api/src/Rest/AdSpaceApiController.php`

Тут объявлены endpoints и response headers.

## Практические команды

Проверка синтаксиса PHP:

```powershell
Get-ChildItem -Path billboardy-map-api -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Когда идти в backend

Идите в backend, если нужно:

- изменить контракт API;
- ускорить SQL или кэш;
- добавить/изменить импорт;
- менять источник runtime-данных;
- добавить настройки WordPress.

## Текущие performance-заметки

- `/map-points` при работе с собственной таблицей использует отдельную SQL-проекцию только под поля карты и списка, без полной доменной модели.
- `/ad-spaces` при работе с собственной таблицей использует SQL `COUNT(*)` и `LIMIT/OFFSET`, а не пагинацию поверх полного массива в PHP.
- `/ad-spaces` принимает те же `bounds`, `media_type`, `city` и `search`, что и карта, поэтому frontend может лениво подгружать список объектов текущей области по 10 записей без запроса полного набора.
- `/inquiries` принимает публичный POST из dopyt-виджета frontend. Перед валидацией контактных полей endpoint проверяет одноразовый Cloudflare Turnstile token и его `action`; запрос без успешной серверной проверки завершается с HTTP 403 и не вызывает `wp_mail`. Secret задаётся только на сервере константой `BILLBOARDY_TURNSTILE_SECRET` в `wp-config.php`, а публичный site key — frontend-переменной `PUBLIC_TURNSTILE_SITE_KEY`. После anti-spam проверки endpoint валидирует контактные поля и выбранные плоскости, сохраняет заявку в таблице `wp_billboardy_inquiry_logs`, затем отправляет письмо через `wp_mail` и фиксирует статус `sent` или `failed`. Honeypot/CAPTCHA-спам и невалидные формы не записываются. Журнал доступен только администраторам в `Settings → Dopyty Billboardy`, поддерживает поиск, фильтры и CSV; записи автоматически удаляются через 180 дней. Адрес письма берётся из настройки плагина `E-mail pre dopyty`; если настройка пустая или невалидная, используется admin email сайта.
- `/filters` при работе с собственной таблицей строятся через `SELECT DISTINCT`, а не через проход по `allMapped()`.
- Если собственная таблица пуста и backend падает в WooCommerce fallback, путь остаётся заметно тяжелее, чем чтение из dedicated table.
