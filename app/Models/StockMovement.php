<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity_change',
        'movement_type',
        'description',
        'order_id',
        'stock_before',
        'stock_after',
    ];

    const TYPE_ORDER_CREATED = 'order_created';
    const TYPE_ORDER_CANCELED = 'order_canceled';
    const TYPE_ORDER_UPDATED = 'order_updated';
    const TYPE_ORDER_RESUMED = 'order_resumed';
    const TYPE_MANUAL = 'manual';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getMovementTypeLabel(): string
    {
        return match($this->movement_type) {
            self::TYPE_ORDER_CREATED => 'Создание заказа',
            self::TYPE_ORDER_CANCELED => 'Отмена заказа',
            self::TYPE_ORDER_UPDATED => 'Обновление заказа',
            self::TYPE_ORDER_RESUMED => 'Возобновление заказа',
            self::TYPE_MANUAL => 'Ручное изменение',
            default => 'Неизвестно'
        };
    }

    /**
     * Проверить, является ли движение приходом
     */
    public function isIncoming(): bool
    {
        return $this->quantity_change > 0;
    }

    /**
     * Проверить, является ли движение расходом
     */
    public function isOutgoing(): bool
    {
        return $this->quantity_change < 0;
    }
}
