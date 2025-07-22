REST API для управления заказами, товарами и складами.

## Установка и настройка

1. Установите зависимости:
```bash
composer install
```

2. Настройте подключение к базе данных в файле `.env`

3. Выполните миграции:
```bash
php artisan migrate
```

4. Заполните базу тестовыми данными:
```bash
php artisan db:seed
```

## Базовый URL
```
http://localhost:86/api
```

## Аутентификация
В текущей версии API не требует аутентификации.

## Общий формат ответов

### Успешный ответ:
```json
{
    "success": true,
    "data": { ... }
}
```

### Ошибка:
```json
{
    "success": false,
    "message": "Описание ошибки"
}
```

### Ошибка валидации:
```json
{
    "success": false,
    "errors": {
        "field": ["Описание ошибки"]
    }
}
```

---

## 1. Склады (Warehouses)

### Получить список всех складов
```http
GET /api/warehouses
```

**Ответ:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Склад №1",
            "created_at": "2025-01-01T00:00:00.000000Z",
            "updated_at": "2025-01-01T00:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "Склад №2",
            "created_at": "2025-01-01T00:00:00.000000Z",
            "updated_at": "2025-01-01T00:00:00.000000Z"
        }
    ]
}
```

---

## 2. Товары (Products)

### Получить список товаров с остатками по складам
```http
GET /api/products
```

**Ответ:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Xiaomi 15 ultra",
            "price": 129999.90,
            "stocks": [
                {
                    "warehouse_id": 1,
                    "warehouse_name": "Склад №1",
                    "stock": 10
                },
                {
                    "warehouse_id": 2,
                    "warehouse_name": "Склад №2",
                    "stock": 5
                }
            ]
        }
    ]
}
```

---

## 3. Заказы (Orders)

### 3.1 Получить список заказов
```http
GET /api/orders
```

**Параметры запроса:**
- `status` (опционально) - фильтр по статусу: `active`, `completed`, `canceled`
- `warehouse_id` (опционально) - фильтр по ID склада
- `customer` (опционально) - поиск по имени клиента (частичное совпадение)
- `per_page` (опционально) - количество записей на странице (по умолчанию 10)

**Примеры запросов:**
```http
GET /api/orders?status=active&warehouse_id=1&per_page=3
GET /api/orders?customer=Иван
GET /api/orders?status=completed
```

**Ответ:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "customer": "Иван Иванов",
                "status": "active",
                "warehouse_id": 1,
                "created_at": "2025-01-01T00:00:00.000000Z",
                "completed_at": null,
                "warehouse": {
                    "id": 1,
                    "name": "Склад №1"
                },
                "order_items": [
                    {
                        "id": 1,
                        "product_id": 1,
                        "count": 2,
                        "product": {
                            "id": 1,
                            "name": "Xiaomi 15 ultra",
                            "price": 129999.90
                        }
                    }
                ]
            }
        ],
        "per_page": 15,
        "total": 1
    }
}
```

### 3.2 Создать заказ
```http
POST /api/orders
```

**Тело запроса:**
```json
{
    "customer": "Иван Иванов",
    "warehouse_id": 1,
    "items": [
        {
            "product_id": 1,
            "count": 2
        },
        {
            "product_id": 3,
            "count": 1
        }
    ]
}
```

**Валидация:**
- `customer` - обязательное поле, строка до 255 символов
- `warehouse_id` - обязательное поле, должен существовать в таблице warehouses
- `items` - обязательный массив, минимум 1 элемент
- `items.*.product_id` - обязательное поле, должен существовать в таблице products
- `items.*.count` - обязательное поле, целое число больше 0

**Ответ (успех):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "customer": "Иван Иванов",
        "status": "active",
        "warehouse_id": 1,
        "created_at": "2025-01-01T00:00:00.000000Z",
        "completed_at": null,
        "warehouse": {
            "id": 1,
            "name": "Склад №1"
        },
        "order_items": [
            {
                "id": 1,
                "product_id": 1,
                "count": 2,
                "product": {
                    "id": 1,
                    "name": "Xiaomi 15 ultra",
                    "price": 129999.90
                }
            }
        ]
    }
}
```

**Ответ (ошибка):**
```json
{
    "success": false,
    "message": "Недостаточно товаров на складе"
}
```

### 3.3 Обновить заказ
```http
PUT /api/orders/{id}
```

**Тело запроса:**
```json
{
    "customer": "Петр Петров",
    "items": [
        {
            "product_id": 2,
            "count": 1
        }
    ]
}
```

**Валидация:**
- Можно обновлять только активные заказы
- `customer` - опциональное поле, строка до 255 символов
- `items` - опциональный массив, минимум 1 элемент
- `items.*.product_id` - обязательное поле при наличии items
- `items.*.count` - обязательное поле при наличии items, целое число больше 0

**Ответ (ошибка):**
```json
{
    "success": false,
    "message": "Можно обновлять только активные заказы",
    "error_code": 400
}
```

### 3.4 Завершить заказ
```http
PATCH /api/orders/{id}/complete
```

**Валидация:**
- Можно завершать только активные заказы

**Ответ:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "customer": "Иван Иванов",
        "status": "completed",
        "completed_at": "2025-01-01T12:00:00.000000Z",
        "warehouse": {
            "id": 1,
            "name": "Склад №1"
        },
        "order_items": [...]
    }
}
```

### 3.5 Отменить заказ
```http
PATCH /api/orders/{id}/cancel
```

**Валидация:**
- Можно отменять только активные заказы
- При отмене товары автоматически возвращаются на склад

**Ответ:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "customer": "Иван Иванов",
        "status": "canceled",
        "warehouse": {
            "id": 1,
            "name": "Склад №1"
        },
        "order_items": [...]
    }
}
```

### 3.6 Возобновить заказ
```http
PATCH /api/orders/{id}/resume
```

**Валидация:**
- Можно возобновлять только отмененные заказы
- Проверяется наличие товаров на складе
- При возобновлении товары снова списываются со склада

**Ответ (успех):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "customer": "Иван Иванов",
        "status": "active",
        "warehouse": {
            "id": 1,
            "name": "Склад №1"
        },
        "order_items": [...]
    }
}
```

**Ответ (ошибка):**
```json
{
    "success": false,
    "message": "Недостаточно товаров на складе для возобновления заказа"
}
```

---

## Примеры использования с curl

### Получить список складов:
```bash
curl http://localhost:86/api/warehouses
```

### Получить товары с остатками:
```bash
curl http://localhost:86/api/products
```

### Получить активные заказы:
```bash
curl http://localhost:86/api/orders?status=active
```

### Создать заказ:
```bash
curl -X POST http://localhost:86/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer": "Иван Иванов",
    "warehouse_id": 1,
    "items": [
      {
        "product_id": 1,
        "count": 2
      }
    ]
  }'
```

### Завершить заказ:
```bash
curl -X PATCH http://localhost:86/api/orders/1/complete
```

### Отменить заказ:
```bash
curl -X PATCH http://localhost:86/api/orders/1/cancel
```

---

## Коды ошибок HTTP

- `200` - Успешный запрос
- `201` - Ресурс создан (при создании заказа)
- `400` - Ошибка валидации или бизнес-логики
- `404` - Ресурс не найден
- `422` - Ошибка валидации данных

---

## Особенности бизнес-логики

1. **Управление остатками:**
   - При создании заказа товары автоматически списываются со склада
   - При отмене заказа товары возвращаются на склад
   - При обновлении заказа старые товары возвращаются, новые списываются
   - При возобновлении отмененного заказа товары снова списываются

2. **Проверки доступности:**
   - При создании заказа проверяется наличие товаров
   - При обновлении заказа проверяется наличие новых товаров
   - При возобновлении заказа проверяется наличие товаров

3. **Статусы заказов:**
   - `active` - активный заказ
   - `completed` - завершенный заказ
   - `canceled` - отмененный заказ

4. **Ограничения операций:**
   - Обновлять можно только активные заказы
   - Завершать можно только активные заказы
   - Отменять можно только активные заказы
   - Возобновлять можно только отмененные заказы

---

## 4. Движения товаров (Stock Movements)

### 4.1 Получить историю движений товаров
```http
GET /api/stock-movements
```

**Параметры запроса:**
- `warehouse_id` (опционально) - фильтр по ID склада
- `product_id` (опционально) - фильтр по ID товара
- `movement_type` (опционально) - фильтр по типу движения: `order_created`, `order_canceled`, `order_updated`, `order_resumed`, `manual`
- `date_from` (опционально) - фильтр по дате начала (формат: YYYY-MM-DD)
- `date_to` (опционально) - фильтр по дате окончания (формат: YYYY-MM-DD)
- `per_page` (опционально) - количество записей на странице (по умолчанию 15)

**Примеры запросов:**
```http
GET /api/stock-movements?warehouse_id=1&product_id=2&per_page=10
GET /api/stock-movements?movement_type=order_created&date_from=2025-01-01
GET /api/stock-movements?date_from=2025-01-01&date_to=2025-08-29
```

**Ответ:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "product": {
                    "id": 1,
                    "name": "Xiaomi 15 ultra",
                    "price": 129999.90
                },
                "warehouse": {
                    "id": 1,
                    "name": "Склад №1"
                },
                "quantity_change": -2,
                "movement_type": "order_created",
                "movement_type_label": "Создание заказа",
                "description": "Списание 2 шт. при создании заказа",
                "stock_before": 10,
                "stock_after": 8,
                "order": {
                    "id": 1,
                    "customer": "Иван Иванов",
                    "status": "active"
                },
                "created_at": "2025-01-01T12:00:00.000000Z",
                "is_incoming": false,
                "is_outgoing": true
            }
        ],
        "per_page": 15,
        "total": 1
    }
}
```

### 4.2 Получить статистику движений
```http
GET /api/stock-movements/statistics
```

**Параметры запроса:** (те же, что и для списка движений, кроме `per_page`)

**Ответ:**
```json
{
    "success": true,
    "data": {
        "total_movements": 5,
        "total_incoming": 15,
        "total_outgoing": 8,
        "net_change": 7,
        "movement_types": {
            "order_created": {
                "count": 2,
                "total_change": -4,
                "label": "Создание заказа"
            },
            "order_canceled": {
                "count": 1,
                "total_change": 2,
                "label": "Отмена заказа"
            }
        }
    }
}
```

---

## Примеры использования с curl

### Получить историю движений:
```bash
curl http://localhost:86/api/stock-movements
```

### Получить статистику движений:
```bash
curl http://localhost:86/api/stock-movements/statistics
```

### Фильтрация движений:
```bash
curl "http://localhost:86/api/stock-movements?warehouse_id=1&movement_type=order_created"
```

---

## Особенности реализации

1. **Управление остатками:**
   - При создании заказа товары автоматически списываются со склада
   - При отмене заказа товары возвращаются на склад
   - При обновлении заказа старые товары возвращаются, новые списываются
   - При возобновлении отмененного заказа товары снова списываются

2. **История движений:**
   - Все изменения остатков товаров автоматически записываются в таблицу движений
   - Каждое движение содержит информацию о товаре, складе, количестве, типе операции
   - Движения связаны с заказами (если операция связана с заказом)

3. **Проверки доступности:**
   - При создании заказа проверяется наличие товаров
   - При обновлении заказа проверяется наличие новых товаров
   - При возобновлении заказа проверяется наличие товаров

4. **Статусы заказов:**
   - `active` - активный заказ
   - `completed` - завершенный заказ
   - `canceled` - отмененный заказ

5. **Типы движений:**
   - `order_created` - списание при создании заказа
   - `order_canceled` - возврат при отмене заказа
   - `order_updated` - изменение при обновлении заказа
   - `order_resumed` - списание при возобновлении заказа
   - `manual` - ручное изменение (для будущего использования)

6. **Ограничения операций:**
   - Обновлять можно только активные заказы
   - Завершать можно только активные заказы
   - Отменять можно только активные заказы
   - Возобновлять можно только отмененные заказы 

## Пагинация заказов

Для получения конкретной страницы используйте параметр `page=N` в запросе.

**Пример:**
```
/api/orders?status=active&warehouse_id=1&per_page=3&page=2
```

- `per_page` — количество элементов на странице
- `page` — номер страницы, которую хотите получить 
