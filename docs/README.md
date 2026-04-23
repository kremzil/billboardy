# Project Docs

Этот каталог содержит рабочую документацию по проекту.

## С чего начать

- [Текущее состояние](./current-state.md) — что уже реализовано и что считать актуальным состоянием проекта.
- [Структура проекта](./project-structure.md) — где что лежит в репозитории.

## Навигация по темам

- Backend WordPress plugin: [backend.md](./backend.md)
- Frontend Astro app: [frontend.md](./frontend.md)
- Источники данных и импорт: [data-and-imports.md](./data-and-imports.md)
- Снимок текущего статуса: [current-state.md](./current-state.md)
- Карта каталогов и файлов: [project-structure.md](./project-structure.md)

## Где что искать

- REST API, кэш, сервисная логика: `billboardy-map-api/src/Service/`, `billboardy-map-api/src/Rest/`
- Импорт и нормализация данных: `billboardy-map-api/src/Import/`, `billboardy-map-api/src/Repository/`, `billboardy-map-api/src/Domain/`
- Настройки и админка WordPress: `billboardy-map-api/src/Admin/`
- Схема таблицы: `billboardy-map-api/src/Database/`
- Основная карта и клиентская логика: `map-frontend/src/scripts/map.ts`
- Страница Astro: `map-frontend/src/pages/index.astro`
- Стили интерфейса: `map-frontend/src/styles/global.css`

## Принцип использования этой папки

- `README.md` в `docs/` — общий индекс.
- Документы по подсистемам держатся отдельно, чтобы не смешивать backend, frontend и данные.
- `current-state.md` стоит обновлять после заметных архитектурных или поведенческих изменений.
