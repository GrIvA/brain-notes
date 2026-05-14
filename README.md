# Brain Notes

A personal notebook with a focus on speed, privacy, and tree structure.

## Tech Stack
- **Backend:** PHP 8.2+ (Slim PHP Framework).
- **Languages:** Multi-language support with translation via XML resources.
- **Authentication:** JWT (via `lcobucci/jwt`).
- **Templates:** Fenom (Template Engine).
- **Database:** SQLite (via `catfan/medoo`).
- **Frontend**: HTMX (for dynamics) + Alpine.js (for client state).
- **Theme Management**: Support for dark/light themes with synchronization via Cookie and prevention of Flash of Unstyled Content (FOUC) using Server-Side Rendering (SSR) of the `data-theme` attribute.
- **Markdown**: `erusev/parsedown`.

## Installation
1. Clone the repository:
   ```bash
   git clone <repository_url>
   cd brain-notes
   ```
2. Switch to the `releases` branch:
   ```bash
   git checkout releases
   ```
3. Prepare the environment configuration:
   ```bash
   cp config/env/env_example.php config/env/env.php
   ```
   *Edit `config/env/env.php` to set your variables if necessary.*
4. Prepare the database:
   ```bash
   cp storage/db/database_example.db storage/db/database.db
   # Set write permissions for your web server
   chmod 666 storage/db/database.db
   ```
5. Create the `tmp/` directory and set permissions:
   ```bash
   mkdir -p tmp
   chmod 775 tmp
   # Ensure the web server can write to this directory
   ```
6. Install dependencies:
   ```bash
   composer install
   ```

## App Architecture
- **Middleware**: Request processing logic.
    - `IpSecurityMiddleware`: Blocks suspicious IP addresses (first in the chain).
    - `AuthMiddleware`: Global authentication.
    - `LanguageMiddleware`: Language detection.
    - `PageAliasMiddleware`: Handles page aliases.
- **Services**: Business logic (translations, XML processing).
- **Models**: Data Access Layer. All Medoo queries are encapsulated in model classes in `src/Models/`.
- **Notebooks & Sections**: Hierarchical structure of notebooks and sections with support for recursive nesting (Recursive Adjacency List). Management of notebooks (create, edit, delete) and adding sections/notes are available in the sidebar (creation actions are restricted to the Homepage for consistency).
- **Registry**: Flexible system for metadata and hierarchical structures (tags, settings, sections), implemented via `TagRegistry` and `RegistryModel` (EAV pattern).
- **Authentication**: JWT-based authentication for API with support for Role-Based Access Control (RBAC) via bitmask.
- **Access Control**: Strict ownership verification at the controller level. Users can only see their own data.
- **Error Handling**: Centralized error handling system.
    - `ErrorPage`: Controller for rendering themed error pages (404, 403, 500) within the site design.
    - `ExceptionHandlingMiddleware`: Intercepts exceptions and automatically selects the response format (HTML for browser or JSON for API).
    - Automatic redirect of unauthenticated users to the login page when attempting to access protected resources.
- **Entities**: Support for entity objects (e.g., `BaseEntity`, `User`) that integrate model data and registry metadata via the "Lazy Collection" pattern.
- **Controllers**: Thin controllers coordinating models and services for page rendering.

## Template Architecture
We use a structured template system in `templates/`:
- `layouts/`: Base page layouts (e.g., `main.tpl`). Contains global containers (e.g., `#modal-container`, `.toast-container`) and global JS variables for state synchronization.
- `pages/`: Specific templates for each route.
- `components/`: Reusable HTML fragments ready for use with HTMX (including `tag_block`, `note_list`, `tag_autocomplete`).

## Data Storage Structure
- `storage/db/`: Contains the SQLite database file (`database.db`). The directory is protected from direct access and ignored by the Git version control system.
- `logs/`: Application logs directory.

## Features
- **Hierarchy:** Tree-like structure of notebooks and sections.
- **Management:** Convenient notebook management interface via the "⚙️" button, contextual addition of sections/notes via icons in the tree (on Homepage), as well as **inline editing of note content** directly on the view page.
- **Tag Management:** Dynamic note filtering by tags using global state (synchronization between the sidebar and the note footer) and a built-in autocomplete for fast tagging.
- **Interactivity:** Note control panel with HTMX support for instant updates.

## Local Libraries
All frontend libraries (`htmx.min.js`, `alpine.min.js`, `tag-autocomplete.js`) are hosted locally in `public/js/` for privacy and speed. FontAwesome 6.5.1 fonts are located in `public/webfonts/`.

## Authentication
The system supports user registration, login, and logout:
- **JWT + TagRegistry**: A hybrid approach is used. Each token has a unique JTI registered in the user's tag system. This allows for instant session invalidation upon Logout.
- **Cookies**: For the web interface, the token is stored in an `httpOnly` cookie.
- **Middleware**: Automatically identifies the user by the Authorization header or Cookie.

## Useful Commands (Docker)
- PHP Execution: `docker exec -w /app/blog/html web8 php [args]`
- Running Tests:
  - Authentication: `docker exec -w /app/blog/html web8 php tests/Site/user/test_auth_flow.php`
  - Note Viewing: `docker exec -w /app/blog/html web8 php tests/Site/user/test_note_view.php`
  - Tag Filtering: `docker exec -w /app/blog/html web8 php tests/Site/user/test_tag_filtering.php`

## API Usage (cURL examples)

### User Authentication
To work with the API, you need to obtain an authorization token.

**Registration:**
```bash
curl -X POST http://blog.test:88/register \
     -H "Content-Type: application/json" \
     -d '{"email": "user@example.com", "password": "password123", "name": "User Name"}' \
     -c cookies.txt
```

**Login:**
```bash
curl -X POST http://blog.test:88/login \
     -H "Content-Type: application/json" \
     -d '{"email": "user@example.com", "password": "password123"}' \
     -c cookies.txt
```

### Sliding Session (Automatic Token Refresh)
The system automatically refreshes the token if more than 2/3 of its lifetime has passed. `curl` automatically updates the session if you use `-c cookies.txt`.

**Refresh Check (look for Set-Cookie header in response):**
```bash
curl -X GET http://blog.test:88/api/v1/notebooks \
     -b cookies.txt \
     -c cookies.txt \
     -v
```

**Create Notebook:**
```bash
curl -X POST http://blog.test:88/api/v1/notebooks \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"title": "My working notebook", "attributes": 1}'
```

**Get Notebook List:**
```bash
curl -X GET http://blog.test:88/api/v1/notebooks -b cookies.txt
```

**Update Notebook (set default flag):**
```bash
curl -X PATCH http://blog.test:88/api/v1/notebooks/1 \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"attributes": 1}'
```

**Add Section (root):**
```bash
curl -X POST http://blog.test:88/api/v1/sections \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"notebook_id": 1, "title": "Section Title", "parent_id": null}'
```

**Get Notebook Section Tree:**
```bash
curl -X GET http://blog.test:88/api/v1/notebooks/1/tree -b cookies.txt
```

**Move Section (change parent_id):**
```bash
curl -X PATCH http://blog.test:88/api/v1/sections/2/move \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"parent_id": 3}'
```

### Notes Management
Notes always belong to a specific section.

**Create Note:**
```bash
curl -X POST http://blog.test:88/api/v1/notes \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"section_id": 1, "title": "First note", "content": "# Hello\nThis is md.", "attributes": 0}'
```

**Get Note:**
```bash
curl -X GET http://blog.test:88/api/v1/notes/1 -b cookies.txt
```

**Bulk Note Move by ID list:**
```bash
curl -X PATCH http://blog.test:88/api/v1/notes/move \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"target_section_id": 2, "note_ids": [1, 2, 3]}'
```

**Bulk Migration of all notes from section to section:**
```bash
curl -X PATCH http://blog.test:88/api/v1/notes/move \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"target_section_id": 2, "source_section_id": 1}'
```

### Tags Management
The system supports global tags within the user scope with automatic normalization.

**Get All Tags (dictionary):**
```bash
curl -X GET http://blog.test:88/api/v1/tags -b cookies.txt
```

**Link Tags to a Note:**
```bash
curl -X POST http://blog.test:88/api/v1/notes/1/tags \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"tags": ["PHP", "web", "Backend"]}'
```

**Search Notes by Multiple Tags (AND by default):**
```bash
curl -X GET "http://blog.test:88/api/v1/notes/search-by-tags?tag_ids[]=1&tag_ids[]=2" -b cookies.txt
```

**Search Tags in OR mode (at least one):**
```bash
curl -X GET "http://blog.test:88/api/v1/notes/search-by-tags?tag_ids[]=1&tag_ids[]=2&mode=OR" -b cookies.txt
```

### IP Security
Get all IPs:
```bash
curl -X GET http://blog.test:88/api/v1/security/ips \
     -H "Content-Type: application/json" \
     -b cookies.txt
```

Change IP Status (normal, allow, disabled):
```bash
curl -X PATCH http://blog.test:88/api/v1/security/ips/1.2.3.4 \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"status": "allow"}'
```

---

# Brain Notes

Це персональний нотатник з упором на швидкість, приватність та деревоподібну структуру.

## Технологічний стек
- **Backend:** PHP 8.2+ (Slim PHP Framework).
- **Languages:** Багатомовність з підтримкою перекладу через XML-ресурси.
- **Authentication:** JWT (via `lcobucci/jwt`).
- **Templates:** Fenom (Template Engine).
- **Database:** SQLite (via `catfan/medoo`).
- **Frontend**: HTMX (для динаміки) + Alpine.js (для клієнтського стану).
- **Theme Management**: Підтримка темної/світлої теми з синхронізацією через Cookie та запобіганням спалаху темної теми (FOUC) за допомогою Server-Side Rendering (SSR) атрибута `data-theme`.
- **Markdown**: `erusev/parsedown`.

## Встановлення
1. Склонуйте репозиторій:
   ```bash
   git clone <repository_url>
   cd brain-notes
   ```
2. Перейдіть на гілку `releases`:
   ```bash
   git checkout releases
   ```
3. Підготуйте конфігурацію середовища:
   ```bash
   cp config/env/env_example.php config/env/env.php
   ```
   *Відредагуйте `config/env/env.php`, щоб додати власні змінні за потреби.*
4. Підготуйте базу даних:
   ```bash
   cp storage/db/database_example.db storage/db/database.db
   # Надайте права на запис для вашого веб-сервера
   chmod 666 storage/db/database.db
   ```
5. Створіть папку `tmp/` та налаштуйте права доступу:
   ```bash
   mkdir -p tmp
   chmod 775 tmp
   # Переконайтеся, що веб-сервер має права на запис у цю директорію
   ```
6. Встановіть необхідні залежності:
   ```bash
   composer install
   ```

## Архітектура додатку
- **Middleware**: Логіка обробки запитів.
    - `IpSecurityMiddleware`: Блокування підозрілих IP адрес (перший у ланцюжку).
    - `AuthMiddleware`: Глобальна автентифікація.
    - `LanguageMiddleware`: Визначення мови.
    - `PageAliasMiddleware`: Робота з аліасами сторінок.
- **Services**: Бізнес-логіка (переклади, робота з XML).
- **Models**: Шар доступу до даних (Data Access Layer). Усі запити до Medoo інкапсульовані в класах моделей у `src/Models/`.
- **Notebooks & Sections**: Ієрархічна структура зошитів та розділів з підтримкою деревовидної вкладеності (Recursive Adjacency List). Керування зошитами (створення, редагування, видалення) та додавання розділів/нотаток доступні у сайдбарі (дії створення обмежені Головною сторінкою для забезпечення цілісності інтерфейсу).
- **Registry**: Гнучка система метаданих та ієрархічних структур (теги, налаштування, розділи), реалізована через `TagRegistry` та `RegistryModel` (патерн EAV).
- **Authentication**: JWT-базована аутентифікація для API з підтримкою контролю доступу на основі ролей (RBAC) через бітову маску.
- **Access Control**: Сувора перевірка прав власності на рівні контролерів. Користувачі можуть бачити лише власні дані.
- **Error Handling**: Централізована система обробки помилок.
    - `ErrorPage`: Контролер для рендерингу тематичних сторінок помилок (404, 403, 500) у дизайні сайту.
    - `ExceptionHandlingMiddleware`: Перехоплює винятки та автоматично вибирає формат відповіді (HTML для браузера або JSON для API).
    - Автоматичний редирект неавторизованих користувачів на сторінку входу при спробі доступу до захищених ресурсів.
- **Entities**: Підтримка об'єктів-сутностей (наприклад, `BaseEntity`, `User`), які інтегрують дані моделей та метадані з реєстру через патерн "Lazy Collection".
- **Controllers**: Тонкі контролери, що координують роботу моделей та сервісів для рендерингу сторінок.

## Архітектура шаблонів
Ми використовуємо структуровану систему шаблонів у `templates/`:
- `layouts/`: Базові макети сторінок (наприклад, `main.tpl`). Містить глобальні контейнери (наприклад, `#modal-container`, `.toast-container`) та глобальні JS-змінні для синхронізації стану.
- `pages/`: Специфічні шаблони для кожного маршруту.
- `components/`: Багаторазові HTML-фрагменти, готові для використання з HTMX (включаючи `tag_block`, `note_list`, `tag_autocomplete`).

## Структура зберігання даних
- `storage/db/`: Містить файл бази даних SQLite (`database.db`). Директорія захищена від прямого доступу та ігнорується системою контролю версій Git.
- `logs/`: Директорія для логів додатку.

## Особливості
- **Ієрархія:** Деревоподібна структура зошитів та розділів.
- **Management:** Зручний інтерфейс для створення та редагування зошитів через кнопку «⚙️», контекстне додавання розділів/нотаток через іконки у дереві (на Головній сторінці), **Drag-and-Drop реорганізація розділів та переміщення нотаток**, а також **inline-редагування вмісту нотаток** прямо на сторінці перегляду.
- **Керування тегами:** Динамічна фільтрація нотаток за тегами з використанням глобального стану (синхронізація між сайдбаром та футером нотатки) та вбудований автокомпліт для швидкого тегування.
- **Інтерактивність:** Панель керування нотатками з підтримкою HTMX для миттєвого оновлення.

## Локальні бібліотеки
Усі фронтенд-бібліотеки (`htmx.min.js`, `alpine.min.js`, `tag-autocomplete.js`, `sortable.min.js`) розміщені локально в `public/js/` для забезпечення приватності та швидкості. Шрифти FontAwesome 6.5.1 знаходяться у `public/webfonts/`.

## Автентифікація
Система підтримує реєстрацію, вхід та вихід користувачів:
- **JWT + TagRegistry**: Використовується гібридний підхід. Кожен токен має унікальний JTI, який реєструється в системі тегів користувача. Це дозволяє миттєво анулювати сесію при виході (Logout).
- **Cookies**: Для веб-інтерфейсу токен зберігається в `httpOnly` кукі.
- **Middleware**: Автоматично ідентифікує користувача за заголовком Authorization або Cookie.

## Корисні команди (Docker)
- Виконання PHP: `docker exec -w /app/blog/html web8 php [args]`
- Запуск тестів:
  - Автентифікація: `docker exec -w /app/blog/html web8 php tests/Site/user/test_auth_flow.php`
  - Перегляд нотаток: `docker exec -w /app/blog/html web8 php tests/Site/user/test_note_view.php`
  - Фільтрація тегами: `docker exec -w /app/blog/html web8 php tests/Site/user/test_tag_filtering.php`

## API Usage (cURL examples)

### User Authentication
Для роботи з API необхідно отримати токен авторизації.

**Реєстрація:**
```bash
curl -X POST http://blog.test:88/register \
     -H "Content-Type: application/json" \
     -d '{"email": "user@example.com", "password": "password123", "name": "User Name"}' \
     -c cookies.txt
```

**Вхід (Login):**
```bash
curl -X POST http://blog.test:88/login \
     -H "Content-Type: application/json" \
     -d '{"email": "user@example.com", "password": "password123"}' \
     -c cookies.txt
```

### Sliding Session (Automatic Token Refresh)
Система автоматично оновлює токен, якщо пройшло більше 2/3 його часу життя. `curl` автоматично оновлює сесію, якщо ви використовуєте `-c cookies.txt`.

**Перевірка оновлення (дивіться заголовок Set-Cookie у відповіді):**
```bash
curl -X GET http://blog.test:88/api/v1/notebooks \
     -b cookies.txt \
     -c cookies.txt \
     -v
```

**Створення зошита:**
```bash
curl -X POST http://blog.test:88/api/v1/notebooks \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"title": "Мій робочий зошит", "attributes": 1}'
```

**Отримання списку зошитів:**
```bash
curl -X GET http://blog.test:88/api/v1/notebooks -b cookies.txt
```

**Оновлення зошита (встановлення прапора за замовчуванням):**
```bash
curl -X PATCH http://blog.test:88/api/v1/notebooks/1 \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"attributes": 1}'
```

**Додавання розділу (кореневого):**
```bash
curl -X POST http://blog.test:88/api/v1/sections \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"notebook_id": 1, "title": "Назва розділу", "parent_id": null}'
```

**Отримання дерева розділів зошита:**
```bash
curl -X GET http://blog.test:88/api/v1/notebooks/1/tree -b cookies.txt
```

**Переміщення розділу (зміна parent_id):**
```bash
curl -X PATCH http://blog.test:88/api/v1/sections/2/move \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"parent_id": 3}'
```

### Notes Management
Нотатки завжди належать до певного розділу.

**Створення нотатки:**
```bash
curl -X POST http://blog.test:88/api/v1/notes \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"section_id": 1, "title": "Перша нотатка", "content": "# Hello\nThis is md.", "attributes": 0}'
```

**Отримання нотатки:**
```bash
curl -X GET http://blog.test:88/api/v1/notes/1 -b cookies.txt
```

**Масове перенесення нотаток за списком ID:**
```bash
curl -X PATCH http://blog.test:88/api/v1/notes/move \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"target_section_id": 2, "note_ids": [1, 2, 3]}'
```

**Масова міграція всіх нотаток з розділу в розділ:**
```bash
curl -X PATCH http://blog.test:88/api/v1/notes/move \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"target_section_id": 2, "source_section_id": 1}'
```

### Tags Management
Система підтримує глобальні теги в межах користувача з автоматичним нормуванням.

**Отримання списку всіх тегів (словник):**
```bash
curl -X GET http://blog.test:88/api/v1/tags -b cookies.txt
```

**Прив'язка тегів до нотатки:**
```bash
curl -X POST http://blog.test:88/api/v1/notes/1/tags \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"tags": ["PHP", "web", "Backend"]}'
```

**Пошук нотаток за декількома тегами (AND за замовчуванням):**
```bash
curl -X GET "http://blog.test:88/api/v1/notes/search-by-tags?tag_ids[]=1&tag_ids[]=2" -b cookies.txt
```

**Пошук за тегами у режимі OR (хоча б один):**
```bash
curl -X GET "http://blog.test:88/api/v1/notes/search-by-tags?tag_ids[]=1&tag_ids[]=2&mode=OR" -b cookies.txt
```

### IP Security
Отримання списку всіх IPs:
```bash
curl -X GET http://blog.test:88/api/v1/security/ips \
     -H "Content-Type: application/json" \
     -b cookies.txt
```

Зміна статусу IP (normal, allow, disabled):
```bash
curl -X PATCH http://blog.test:88/api/v1/security/ips/1.2.3.4 \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"status": "allow"}'
```
     -b cookies.txt \
     -d '{"status": "allow"}'
```
     -d '{"status": "allow"}'
```
