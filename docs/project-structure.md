# Структура проекта

Ниже перечислены основные каталоги и файлы, которые имеют смысл для разработки. Сгенерированные каталоги и зависимости (`node_modules`, `.astro`, `dist`) здесь описаны только как артефакты, а не как место для ручного редактирования.

## Корень репозитория

- `billboardy-map-api/` — WordPress plugin
- `map-frontend/` — standalone Astro frontend
- `docs/` — внутренняя документация проекта
- `README.md` — краткое описание репозитория
- `AGENTS.md` — общие инструкции по работе в этом репозитории

## Backend plugin

Каталог: `billboardy-map-api/`

Ключевые файлы:

- `billboardy-map-api.php` — точка входа плагина
- `src/Plugin.php` — bootstrap, регистрация хуков, сборка зависимостей

Подкаталоги:

- `src/Admin/` — страницы настроек и импорта в админке WordPress
- `src/Database/` — схема и создание собственной таблицы
- `src/Domain/` — маппинг и нормализация доменной модели
- `src/Import/` — импорт из CSV/XLSX/KMZ-derived data
- `src/Repository/` — доступ к данным
- `src/Rest/` — REST endpoints
- `src/Service/` — сервисная логика, кэш, фильтрация, подготовка данных

Искать по задачам:

- изменить API: `src/Rest/`, `src/Service/`
- изменить SQL/таблицу: `src/Database/`, `src/Repository/`
- изменить импорт: `src/Import/`
- изменить правила нормализации: `src/Domain/`
- изменить настройки админки: `src/Admin/`

## Frontend Astro

Каталог: `map-frontend/`

Ключевые файлы:

- `src/pages/index.astro` — основная страница приложения
- `src/pages/typ/[slug].astro` — страницы типов носителей с общей картой и фиксированным фильтром
- `src/components/MapSection.astro` — секция карты для главной страницы
- `src/data/adTypes.ts` — контент и slug-и страниц типов носителей
- `src/scripts/map.ts` — логика карты, запросы к API, маркеры, popup, фильтры
- `src/styles/global.css` — стили интерфейса
- `.env.example` — пример переменных окружения
- `astro.config.mjs` — конфигурация Astro
- `package.json` — команды и зависимости

Служебные каталоги:

- `public/` — статические файлы
- `dist/` — результат `npm run build`
- `node_modules/` — зависимости
- `.astro/` — служебные файлы Astro

Искать по задачам:

- изменить поведение карты: `src/scripts/map.ts`
- изменить разметку секции карты: `src/components/MapSection.astro`
- изменить состав главной страницы: `src/pages/index.astro`
- изменить визуал и layout: `src/styles/global.css`
- изменить env/config: `.env.example`, `astro.config.mjs`

## Документация

Каталог: `docs/`

- `README.md` — индекс документации
- `current-state.md` — снимок текущего состояния
- `backend.md` — backend-часть
- `frontend.md` — frontend-часть
- `data-and-imports.md` — источники данных и импорт

## Данные и вспомогательные файлы в корне

- `wc-adsPlaces.csv` — reference/export файл
- `kmz-relevant-adspaces.csv` — подготовленный CSV из KMZ
- `knosic_blb.xlsx`, `knosic_clv.xlsx` — исходные XLSX
- `knosic_blb.csv`, `knosic_clv.csv` — конвертированные CSV
- `Interaktívna mapa reklamných plôch.kmz` — исходный KMZ

Эти файлы полезны как источник или reference, но не являются кодом runtime frontend/backend.
