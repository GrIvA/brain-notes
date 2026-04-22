# Brain Notes

Це персональний нотатник з упором на швидкість, приватність та деревоподібну структуру.

## Технологічний стек
- **Backend:** PHP 8.2+ (Slim PHP Framework).
- **Languages:** Багатомовність з підтримкою перекладу через XML-ресурси.
- **Authentication:** JWT (via `lcobucci/jwt`).
- **Templates:** Fenom (Template Engine).
- **Database:** SQLite (via `catfan/medoo`).
- **Frontend:** HTMX (для динаміки) + Alpine.js (для клієнтського стану).
- **Markdown:** `erusev/parsedown`.

## Архітектура додатку
- **Middleware**: Логіка обробки запитів (мова, аліаси, безпека).
- **Services**: Бізнес-логіка (переклади, робота з XML).
- **Models**: Шар доступу до даних (Data Access Layer). Усі запити до Medoo інкапсульовані в класах моделей у `src/Models/`.
- **Notebooks & Sections**: Ієрархічна структура зошитів та розділів з підтримкою деревовидної вкладеності (Recursive Adjacency List).
- **Registry**: Гнучка система метаданих та ієрархічних структур (теги, налаштування, розділи), реалізована через `TagRegistry` та `RegistryModel` (патерн EAV).
- **Authentication**: JWT-базована аутентифікація для API з підтримкою контролю доступу на основі ролей (RBAC) через бітову маску.
- **Entities**: Підтримка об'єктів-сутностей (наприклад, `BaseEntity`, `User`), які інтегрують дані моделей та метадані з реєстру через патерн "Lazy Collection".
- **Controllers**: Тонкі контролери, що координують роботу моделей та сервісів для рендерингу сторінок.

## Архітектура шаблонів
Ми використовуємо структуровану систему шаблонів у `templates/`:
- `layouts/`: Базові макети сторінок (наприклад, `main.tpl`).
- `pages/`: Специфічні шаблони для кожного маршруту.
- `components/`: Багаторазові HTML-фрагменти, готові для використання з HTMX.

## Структура зберігання даних
- `storage/db/`: Містить файл бази даних SQLite (`database.db`). Директорія захищена від прямого доступу та ігнорується системою контролю версій Git.
- `logs/`: Директорія для логів додатку.

## Локальні бібліотеки
Усі фронтенд-бібліотеки (`htmx.min.js`, `alpine.min.js`) розміщені локально в `public/js/` для забезпечення приватності та швидкості.

## Автентифікація
Система підтримує реєстрацію, вхід та вихід користувачів:
- **JWT + TagRegistry**: Використовується гібридний підхід. Кожен токен має унікальний JTI, який реєструється в системі тегів користувача. Це дозволяє миттєво анулювати сесію при виході (Logout).
- **Cookies**: Для веб-інтерфейсу токен зберігається в `httpOnly` кукі.
- **Middleware**: Автоматично ідентифікує користувача за заголовком Authorization або Cookie.

## Корисні команди (Docker)
- Виконання PHP: `docker exec -w /app/blog/html web8 php [args]`
- Запуск тестів:
  - Автентифікація: `docker exec -w /app/blog/html web8 php tests/API/user/test_auth_flow.php`
  - Зошити та Розділи: `docker exec -w /app/blog/html web8 php tests/API/notebook/verify_notebook_api.php`

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

