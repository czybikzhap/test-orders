<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
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
