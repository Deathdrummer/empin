# Timesheet API Documentation

## Для разработчика мобильного приложения

### Документация API

Файл **api-documentation.yaml** содержит полную OpenAPI 3.0 спецификацию.

### Как просмотреть документацию:

**Вариант 1: Swagger Editor (онлайн)**
1. Откройте https://editor.swagger.io/
2. Скопируйте содержимое файла `api-documentation.yaml`
3. Вставьте в левую панель редактора
4. Справа увидите интерактивную документацию

**Вариант 2: Swagger UI (локально)**
1. Установите расширение для VS Code: "Swagger Viewer" или "OpenAPI Preview"
2. Откройте файл `api-documentation.yaml`
3. Используйте команду "Preview Swagger"

**Вариант 3: Postman**
1. Откройте Postman
2. File → Import → выберите `api-documentation.yaml`
3. Получите готовую коллекцию запросов

---

## Базовая информация

### Base URL
```
Production: http://your-domain.com/api
Development: http://localhost/api
```
*(замените на актуальный домен)*

### Авторизация

API использует **Laravel Sanctum** (Bearer Token).

#### Процесс авторизации:

1. **Логин** - получение токена:
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

Ответ:
```json
{
  "token": "1|abcdef123456...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "full_name": "Иванов Иван Иванович",
    "staff_id": 42
  }
}
```

2. **Использование токена** - добавлять в заголовок всех запросов:
```
Authorization: Bearer 1|abcdef123456...
```

3. **Выход** - удаление токена:
```http
POST /api/auth/logout
Authorization: Bearer {your_token}
```

---

## Endpoints

### Авторизация
- `POST /api/auth/login` - вход (без токена)
- `POST /api/auth/logout` - выход (требует токен)
- `GET /api/auth/me` - данные пользователя (требует токен)

### Timesheet (все требуют токен)
- `POST /api/timesheet/slides` - данные по дням
- `GET /api/timesheet/staff` - список сотрудников
- `GET /api/timesheet/contracts/search?search=текст` - поиск контрактов
- `POST /api/timesheet/team` - добавить сотрудника на день
- `DELETE /api/timesheet/team/{id}` - удалить сотрудника
- `POST /api/timesheet/contract` - добавить контракт
- `DELETE /api/timesheet/contract/{id}` - удалить контракт
- `POST /api/timesheet/comment` - добавить комментарий
- `DELETE /api/timesheet/comment/{id}` - удалить комментарий

---

## Примеры запросов

### Получение данных по дням
```http
POST /api/timesheet/slides
Authorization: Bearer {token}
Content-Type: application/json

{
  "indexes": [-1, 0, 1]
}
```
Где `indexes` - массив дней:
- `0` = сегодня
- `-1` = вчера
- `1` = завтра
- `-7` = неделю назад
- и т.д.

Ответ содержит массив дней с командами, контрактами и комментариями.

### Добавление сотрудника на день
```http
POST /api/timesheet/team
Authorization: Bearer {token}
Content-Type: application/json

{
  "staff_id": 42,
  "day": "2025-10-25"
}
```

### Поиск контрактов
```http
GET /api/timesheet/contracts/search?search=объект
Authorization: Bearer {token}
```

---

## Коды ответов

- `200` - успешно
- `401` - не авторизован (неверный/отсутствует токен)
- `404` - не найдено
- `422` - ошибка валидации
- `500` - ошибка сервера

---

## Структуры данных

Все структуры данных (схемы) описаны в разделе `components/schemas` файла `api-documentation.yaml`:

- **Staff** - сотрудник
- **Contract** - контракт
- **DayData** - данные по дню
- **Team** - команда на день
- **TimesheetContract** - контракт в табеле
- **Comment** - комментарий

---

## Тестовые данные

Для тестирования используйте учетные данные, предоставленные отдельно.

---

## Поддержка

При возникновении вопросов или проблем с API обращайтесь к backend-разработчику.
