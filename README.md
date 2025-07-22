
# Микро-CRM для торговли

REST API для управления заказами, товарами и складами.

## Установка и настройка

1. Установите зависимости:
```bash
composer install
```

2. Скопируйте файл конфигурации:
```bash
cp .env.example .env
```

3. Настройте подключение к базе данных в файле `.env`

4. Выполните миграции:
```bash
php artisan migrate
```

5. Заполните базу тестовыми данными:
```bash
php artisan db:seed
```

6. Запустите сервер:
```bash
php artisan serve
```

## API Endpoints

### Склады

#### GET /api/warehouses
Получить список всех складов.

**Ответ:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Склад №1 - Центральный",
            "created_at": "2025-01-01T00:00:00.000000Z",
            "updated_at": "2025-01-01T00:00:00.000000Z"
        }
    ]
}
```

### Товары

#### GET /api/products
Получить список товаров с остатками по складам.

**Ответ:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Ноутбук Dell XPS 13",
            "price": 89999.99,
            "stocks": [
                {
                    "warehouse_id": 1,
                    "warehouse_name": "Склад №1 - Центральный",
                    "stock": 10
                }
            ]
        }
    ]
}
```

### Заказы

#### GET /api/orders
Получить список заказов с фильтрами и пагинацией.

**Параметры:**
- `status` - фильтр по статусу (active, completed, canceled)
- `warehouse_id` - фильтр по складу
- `customer` - поиск по имени клиента
- `per_page` - количество записей на странице (по умолчанию 15)

**Пример:**
```
GET /api/orders?status=active&warehouse_id=1&per_page=10
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
                    "name": "Склад №1 - Центральный"
                },
                "order_items": [
                    {
                        "id": 1,
                        "product_id": 1,
                        "count": 2,
                        "product": {
                            "id": 1,
                            "name": "Ноутбук Dell XPS 13",
                            "price": 89999.99
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

#### POST /api/orders
Создать новый заказ.

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

**Ответ:**
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
            "name": "Склад №1 - Центральный"
        },
        "order_items": [
            {
                "id": 1,
                "product_id": 1,
                "count": 2,
                "product": {
                    "id": 1,
                    "name": "Ноутбук Dell XPS 13",
                    "price": 89999.99
                }
            }
        ]
    }
}
```

#### PUT /api/orders/{id}
Обновить заказ (только активные заказы).

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

#### PATCH /api/orders/{id}/complete
Завершить заказ.

**Ответ:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "status": "completed",
        "completed_at": "2025-01-01T12:00:00.000000Z"
    }
}
```

#### PATCH /api/orders/{id}/cancel
Отменить заказ (товары возвращаются на склад).

**Ответ:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "status": "canceled"
    }
}
```

#### PATCH /api/orders/{id}/resume
Возобновить отмененный заказ (проверяется наличие товаров).

**Ответ:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "status": "active"
    }
}
```

## Обработка ошибок

Все ошибки возвращаются в формате:

```json
{
    "success": false,
    "message": "Описание ошибки"
}
```

или для ошибок валидации:

```json
{
    "success": false,
    "errors": {
        "field": ["Описание ошибки"]
    }
}
```

## Особенности реализации

1. **Управление остатками**: При создании заказа товары автоматически списываются со склада. При отмене - возвращаются.

2. **Проверка доступности**: При возобновлении отмененного заказа проверяется наличие товаров на складе.

3. **Транзакции**: Все операции с остатками выполняются в транзакциях для обеспечения целостности данных.

4. **Валидация**: Все входящие данные валидируются согласно требованиям.

5. **Фильтрация и пагинация**: Поддерживается фильтрация заказов по статусу, складу и клиенту, а также настраиваемая пагинация.

## Тестовые данные

После выполнения `php artisan db:seed` в базе будут созданы:
- 3 склада
- 8 товаров
- Остатки товаров на всех складах
