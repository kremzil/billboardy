# Frontend

Frontend находится в `map-frontend/` и реализован как отдельное Astro приложение.

## Задача frontend-части

- отрисовать карту;
- загрузить данные только через backend API;
- показать фильтры, кластеры, список объектов и popup карточки;
- оставаться отзывчивым при большом числе точек.

## Основные зоны кода

### Страница

- `map-frontend/src/pages/index.astro`
- `map-frontend/src/pages/typ/[slug].astro`
- `map-frontend/src/pages/reklama-na-mhd-kosice.astro`
- `map-frontend/src/data/site.ts`
- `map-frontend/src/pages/robots.txt.ts`
- `map-frontend/src/pages/llms.txt.ts`
- `map-frontend/src/pages/sitemap.xml.ts`

`index.astro` — основная страница приложения. На главной подключаются hero, блок цен и секция карты.

`typ/[slug].astro` — статически генерируемые страницы типов носителей (`billboard`, `citylight`, `bigboard`, `most`, `plachta`, `fasada`, `mega-plocha`). Страница использует тот же компонент карты, но передаёт фиксированный тип носителя, чтобы карта и список `Výber v aktuálnej oblasti` грузили только релевантные данные.

`reklama-na-mhd-kosice.astro` — статически генерируемая sales/catalog страница `/reklama-na-mhd-kosice/` для рекламы на košických автобусах, трамваях, SIT и VLAJKA на опорах verejného osvetlenia.

### Компоненты

- `map-frontend/src/components/MapSection.astro`
- `map-frontend/src/components/InquiryWidget.astro`
- `map-frontend/src/components/MhdOfferCard.astro`

Это Astro-shell секции карты для главной страницы и страниц типов носителей. В компоненте находится разметка заголовка, быстрых фильтров по типу, лениво отображаемого блока `Výber v aktuálnej oblasti` и контейнера Google Maps. Расширенная панель `Filtre` в секции карты не используется: выбор края приходит из hero/footer, а тип можно менять через быстрые кнопки. Компонент принимает стартовый `media_type` и может заблокировать выбор типа для страниц форматов. Клиентская логика остаётся в `src/scripts/map.ts`.

`InquiryWidget.astro` — глобальный dopyt-виджет. Он показывает bubble под header после выбора плоскости на карте, хранит выбранные точки в `sessionStorage` и открывает modal с формой отправки.

`MhdOfferCard.astro` — карточка позиции каталога для страницы рекламы na MHD v Košiciach.

- `map-frontend/src/components/mainHero.astro`

Hero содержит стартовый поиск по краю (`region`) и основным типам рекламной плоскости (`media_type`) без варианта `Ostatné plochy`. При отправке формы страница скроллится к секции карты, а карта применяет выбранный тип и перестраивает viewport под выбранный словацкий край. Региональный выбор работает через bounds карты, а не через отдельное поле таблицы, потому что `region` в данных заполнен не у всех источников.

- `map-frontend/src/components/footer.astro`

Footer содержит ссылки на словацкие kraje вместо городов. Если карта уже есть на текущей странице, клик применяет выбранный `region` к карте без перезагрузки; иначе ссылка ведёт на главную карту с `region` в query string.

### Данные страниц типов

- `map-frontend/src/data/adTypes.ts`
- `map-frontend/src/data/mhdAdvertising.ts`

Здесь хранится контент страниц типов носителей, slug-и, публичные названия и соответствие frontend-страниц значениям `media_type`, которые отправляются в backend API.

`mhdAdvertising.ts` хранит официальные исходные цены DPMK на 2026 год и вычисляет отображаемые суммы с округлением до центов. Все показанные цены указаны без 23% DPH; временный polep в карточках автобусов и электричек стоит 100 € без DPH. В таблицах SIT/VLAJKA эта строка не отображается.

MHD-страница имеет собственные сущности `Service`, `OfferCatalog` и `BreadcrumbList` в JSON-LD. Видимый ценовой блок фиксирует дату статической сборки и сообщает, что доступность подтверждается индивидуально. Контекстные ссылки на страницу размещены в главном меню, отдельном промоблоке главной страницы и footer.

После hero и красной информационной полосы страница MHD использует тот же обзорный шаблон, что и `/typ/[slug]`: слева расположены описание типа, `Kľúčové výhody` и три карточки-перехода к разделам `Autobusy`, `Električky`, `Verejné osvetlenie`; справа — липкая карточка `Technické parametre` и CTA с телефоном и переходом на форму заявки.

В блоке `Verejné osvetlenie` заголовок и вводный текст вынесены отдельно над контентом. Ниже в две колонки выровнены таблицы SIT/VLAJKA и интерактивная карточка официальной категоризации улиц DPMK; на десктопе её высота синхронизируется с суммарной высотой двух таблиц. Заголовки карточек используют светлый `brand/10` вместо насыщенной красной заливки. Табы A, B и C переключают прокручиваемые списки: активный таб расширяется и остаётся белым с нейтральной рамкой, а при наведении используется такой же `shadow-md`, как у карточек форматов. На мобильных устройствах панель переносится под таблицы и получает собственную минимальную высоту.

Локальная навигация `Autobusy / Električky / Verejné osvetlenie` не занимает место в обычном потоке страницы. Она скрыта, пока пользователь просматривает обзорный блок, и плавно появляется как фиксированная полоса под шапкой после прокрутки обзора. Полоса использует цвет `bb-control` и светлые контрастные чипы.

Временные локальные assets страницы лежат в `public/assets/mhd-kosice/`: это схемы транспортных средств, производные от PDF, а также связанные локальные изображения. `ATTRIBUTION.md` фиксирует источники Wikimedia для hero с košickým трамваем и DPMK для материалов страницы.

### Карта и клиентская логика

- `map-frontend/src/scripts/map.ts`
- `map-frontend/src/scripts/inquiry.ts`

Это главный файл frontend-логики. В нём сосредоточены:

- загрузка Google Maps;
- запросы к backend API;
- быстрые фильтры по типу и применение фильтров из hero/footer;
- работа с маркерами и кластерами;
- popup и список объектов;
- переключение в Street View через ближайшую панораму Google к центру карты или выбранной точке; в этом режиме список `Výber v aktuálnej oblasti` скрывается, а выбранная плоскость показывается отдельной подсказкой;
- поиск по карте через control с иконкой лупы (`TOP_RIGHT`, рядом с кнопкой Street View): кнопка разворачивает инпут влево, запрос идёт в `/ad-spaces` с параметром `search` без bounds (по коду или локации/городу); один результат открывается через `focusAdSpace`, несколько — карта подгоняется под их bounds;
- взаимная подсветка карточки и маркера при наведении (`is-hover`/`is-highlight`): наведение на карточку списка подсвечивает соответствующий маркер (через `state.markerById`), наведение на маркер подсвечивает связанные карточки (через `marker.__billboardyPointIds`);
- активное состояние открытого маркера (`is-active`): `setActiveMarker` выделяет точку при открытии popup и сбрасывает выделение по `closeclick` InfoWindow;
- полезные пустые/ошибочные состояния: к статусу добавляются кнопки действий (`Skúsiť znova` при ошибке; `Zrušiť filtre` и `Oddialiť mapu` при пустом результате) через `renderStatusActions`;
- skeleton-карточки (`skeletonCards`) вместо текста «Načítavam...» во время загрузки `Výber v aktuálnej oblasti`; анимация отключается при `prefers-reduced-motion`;
- автоматический выход из Street View при смене типа носителя или региона: тип отдаляет текущий viewport до базового zoom, регион перестраивает карту по bounds выбранного края;
- кнопка возврата из детального списка уменьшает zoom ниже порога `Výber v aktuálnej oblasti`, сохраняя текущий центр карты;
- на мобильных список работает как нижняя сворачиваемая панель: в компактном состоянии оставляет карту видимой и показывает первую карточку, полный список раскрывается отдельным шевроном;
- клиентский cache map payload'ов;
- diff-обновление маркеров;
- отмена устаревших `/map-points` запросов при активном pan/zoom/search.
- ленивую загрузку `Výber v aktuálnej oblasti` через `/ad-spaces` с `per_page=10`, текущими bounds и пагинацией только на близком зуме.
- передачу точки из popup карты в dopyt-виджет через событие `billboardy:inquiry-add`.

`inquiry.ts` управляет sessionStorage-выборкой точек, bubble-счётчиком, modal-формой и POST-запросом на `/inquiries`. Modal-форма имеет focus-trap (циклический Tab внутри диалога), закрытие по Escape и возврат фокуса на элемент-инициатор при закрытии; счётчик выбранных плоскостей объявляется screen reader'ам через `aria-live="polite"`.

### Стили

- `map-frontend/src/styles/global.css`

Тут лежит базовый внешний вид карты, popup, sidebar и элементов управления.

### Конфигурация

- `map-frontend/.env.example`
- `map-frontend/astro.config.mjs`

Тут определяются runtime/build настройки, которые не стоит зашивать в код. `PUBLIC_GTM_ID` задаёт Google Tag Manager container для конкретного окружения; если переменная пустая, GTM не рендерится.

### SEO

- `src/layouts/layout.astro` формирует базовые meta tags: `title`, `description`, `robots`, canonical URL, Open Graph, Twitter Card и JSON-LD `WebSite`.
- JSON-LD в `src/layouts/layout.astro` построен как `@graph`: `Organization`, `WebSite` и `Service` с `OfferCatalog` основных рекламных форматов.
- Layout также публикует ссылку на agent-context файл через `<link rel="alternate" type="text/markdown" href="/llms.txt">`.
- `src/layouts/layout.astro` подключает Google Tag Manager глобально для всех страниц, если задан `PUBLIC_GTM_ID`: скрипт в `<head>` и `noscript` iframe сразу в `<body>`.
- `src/data/site.ts` хранит site origin, дефолтные SEO-тексты, общие контактные данные, `PUBLIC_GTM_ID` и helpers для абсолютных URL с учётом Astro `base: "/mapa/"`.
- `src/pages/robots.txt.ts` генерирует `robots.txt` при static build и объявляет Content Signals для AI-использования: `ai-train=no, search=yes, ai-input=yes`.
- `src/pages/llms.txt.ts` генерирует `/llms.txt` в формате `text/markdown`: краткое описание сайта для агентов, важные страницы, публичные GET endpoints карты и ограничения по не-публичным административным маршрутам.
- `src/pages/sitemap.xml.ts` генерирует sitemap для главной страницы, контакта, GDPR/Cookies и страниц типов носителей.
- Для production-домена используется `PUBLIC_SITE_URL`; по умолчанию он равен `https://www.billboardy.sk`.
- Страница `/reklama-na-mhd-kosice/` добавлена в desktop/mobile навигацию `reklamné plochy` и в sitemap.

### Шрифт

Основной интерфейсный шрифт — Plus Jakarta Sans, подключённый через Google Fonts в `src/layouts/layout.astro`. CSS token `--font-bb-sans` в `src/styles/global.css` содержит системные fallback-шрифты.

## Практические команды

Установка и запуск:

```powershell
cd map-frontend
npm install
npm run dev
```

Проверка и сборка:

```powershell
npm run check
npm run build
```

Node-тесты `map-frontend/src/data/mhdAdvertising.test.ts` и `map-frontend/src/data/mhdAdvertisingPage.test.ts` проверяют соответственно расчёт цен каталога и контракт MHD-страницы. Запускайте их вместе с остальными тестами командой `npm test`.

## Build-артефакты

Результат сборки пишется в:

- `map-frontend/dist/`

Этот каталог используется как артефакт для выкладки frontend на сервер.

## Когда идти во frontend

Идите сюда, если нужно:

- менять поведение карты;
- улучшать производительность рендера;
- менять popup, список объектов, фильтры;
- править UI copy;
- менять env/config интеграции.

## Текущие performance-заметки

- Клиент использует diff-обновление маркеров вместо полного teardown/rebuild на каждый `idle`.
- Устаревшие `/map-points` запросы теперь abort'ятся, а не просто игнорируются после ответа.
- Клик по крупному server-side cluster теперь форсирует более глубокий drill-down zoom, чтобы быстрее раскрывать подгруппы и точки.
- Список `Výber v aktuálnej oblasti` не строится из полного map payload'а: он скрыт на дальнем зуме и запрашивает по 10 объектов через paginated `/ad-spaces` только при приближении к уровню одиночных точек.
- Основной выигрыш frontend сейчас зависит не столько от размера bundle, сколько от частоты запросов карты и объёма backend payload'ов.
