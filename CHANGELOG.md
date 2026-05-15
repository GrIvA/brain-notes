# Changelog

## beta_v4
Release beta_v4: Drag-and-Drop Reorganization & Auth Monitoring

Key Changes:
1. Drag-and-Drop System:
   - Implemented branch-to-branch reorganization for the section tree in the sidebar.
   - Implemented note movement by dragging the edit button onto any section in the sidebar.
   - Enhanced Aimara.js to support persistent DOM structures for collapsed nodes during DnD.
   - Added visual feedback for drag operations (ghost elements, drop-zone highlighting).
2. UI & UX Refinements:
   - Sections in the sidebar are now collapsed by default upon page load for better focus.
   - Removed the legacy "Move" button and modal UI in favor of the new DnD workflow.
   - Added automatic page refresh for note views after successful movement to update breadcrumbs.
   - Improved breadcrumb navigation: removed redundant note title and added links to return to home with active node selection.
3. Security & Monitoring:
   - Added logging for expired JWT tokens to diagnose premature session terminations.
   - Fixed TypeErrors in AuthMiddleware by aligning constructor arguments with the container.
   - Defined proper PAGE_NOTE (4) constant for accurate page state handling.
4. Infrastructure:
   - Locally hosted Sortable.js (v1.15.2) for privacy and offline support.
   - Improved API communication using native fetch to avoid HTMX conflicts during complex async operations.

## beta_v3
Release beta_v3: Documentation & UI Refinements

Key Changes:
1. Documentation:
   - Added a detailed "Installation" section to README.md.
   - Synchronized English and Ukrainian documentation for consistency.
   - Updated "Tag Management" description to reflect global state synchronization.
2. UI & Navigation:
   - Fixed sidebar section tree navigation and state flow.
   - Improved tag filtering logic and global state synchronization between components.
   - Refined template architecture for better performance and state management.
3. Bug Fixes:
   - Resolved issues in the tag processing flow.
   - Minor controller and template refinements.

## beta_v2
Release beta_v2: Notebook Management & Security Enhancements

Key Changes:
1. Structure Management (UI):
   - Implemented full notebook management: creation, renaming, deletion, and setting a default notebook via a new modal window ("⚙️" icon).
   - Added the ability to quickly create sections and subsections directly in the tree ("+" icon).
2. Inline Note Editing:
   - Implemented the ability to edit note content directly on the view page without unnecessary navigation (Inline Markdown Editor).
   - Added a quick note creation button for specific sections ("📄" icon).
3. Security & Encryption:
   - Implemented server-side encryption for note content (AES-256).
   - Implemented on-the-fly decryption UI via password.
4. Technical Updates:
   - Complete transition from jQuery to HTMX and Alpine.js.
   - Replaced the heavy jsTree with the lightweight Aimara.js with custom extensions.
   - Updated documentation (bilingual README) and prepared a clean database template (database_example.db).

---

# Історія змін

## beta_v4
Реліз beta_v4: Реорганізація Drag-and-Drop та моніторинг авторизації

Ключові зміни:
1. Система Drag-and-Drop:
   - Впроваджено можливість реорганізації гілок дерева розділів у сайдбарі.
   - Впроваджено переміщення нотатки шляхом перетягування кнопки редагування на будь-який розділ у сайдбарі.
   - Модернізовано Aimara.js для підтримки стабільної структури DOM згорнутих вузлів під час DnD.
   - Додано візуальний відгук (ghost-елементи, підсвічування зон скидання).
2. Вдосконалення UI та UX:
   - Розділи в сайдбарі тепер згорнуті за замовчуванням при завантаженні для кращого фокусування.
   - Видалено застарілу кнопку «Перенести» та старе модальне вікно на користь нового DnD процесу.
   - Додано автоматичне оновлення сторінки перегляду нотатки після переміщення для актуалізації «хлібних крихт».
3. Безпека та моніторинг:
   - Додано логування прострочених JWT токенів для діагностики передчасного завершення сесій.
   - Виправлено TypeError в AuthMiddleware (синхронізація аргументів конструктора з контейнером).
   - Впроваджено константу PAGE_NOTE (4) для чіткої ідентифікації типу сторінки.
4. Інфраструктура:
   - Локальне підключення бібліотеки Sortable.js (v1.15.2) для швидкості та приватності.
   - Покращено взаємодію з API через нативний fetch для уникнення конфліктів HTMX при складних асинхронних діях.

## beta_v3
Реліз beta_v3: Документація та вдосконалення UI

Ключові зміни:
1. Документація:
   - Додано детальний розділ «Встановлення» до README.md.
   - Синхронізовано англійську та українську версії документації для повної відповідності.
   - Оновлено опис «Керування тегами» з урахуванням синхронізації глобального стану.
2. UI та навігація:
   - Виправлено логіку навігації та стан дерева розділів у сайдбарі.
   - Покращено логіку фільтрації тегів та синхронізацію стану між компонентами.
   - Оптимізовано архітектуру шаблонів для кращої продуктивності та керування станом.
3. Виправлення помилок:
   - Усунено помилки у процесі обробки тегів.
   - Дрібні виправлення в контролерах та шаблонах.

## beta_v2
Реліз beta_v2: Керування зошитами та посилення безпеки

Ключові зміни:
1. Керування структурою (UI):
   - Впроваджено повноцінне керування зошитами: створення, редагування назв, видалення та встановлення зошита за замовчуванням через нове модальне вікно (іконка «⚙️»).
   - Додано можливість швидкого створення розділів та підрозділів прямо в дереві (іконка «+»).
2. Inline-редагування нотаток:
   - Реалізовано можливість редагувати вміст нотатки прямо на сторінці перегляду без зайвих переходів (Inline Markdown Editor).
   - Додано кнопку швидкого створення нотатки для конкретного розділу (іконка «📄»).
3. Безпека та шифрування:
   - Впроваджено механізм шифрування вмісту нотаток на стороні сервера (AES-256).
   - Реалізовано UI для дешифрування «на льоту» через пароль.
4. Технічне оновлення:
   - Повна відмова від jQuery на користь HTMX та Alpine.js.
   - Заміна важкого jsTree на легкий Aimara.js з кастомними розширеннями.
   - Оновлення документації (двомовний README) та підготовка шаблону чистої бази даних (database_example.db).
