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

Тут лежат:

- настройки плагина;
- очистка и прогрев кэша;
- интерфейс импорта файлов.

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
- map-points payload;
- server-side cluster preparation;
- выдачи одной точки и фильтров.

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
