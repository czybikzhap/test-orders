<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    public function checkAvailability(int $warehouseId, array $items): bool
    {
        foreach ($items as $item) {
            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $item['product_id'])
                ->first();

            $availableStock = $stock ? $stock->stock : 0;

            if ($availableStock < $item['count']) {
                return false;
            }
        }

        return true;
    }

    public function reserveStock(int $warehouseId, array $items, ?int $orderId = null, string $movementType = StockMovement::TYPE_ORDER_CREATED): void
    {
        DB::transaction(function () use ($warehouseId, $items, $orderId, $movementType) {
            foreach ($items as $item) {
                $stock = Stock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    throw new Exception("Товар с ID {$item['product_id']} не найден на складе");
                }

                if ($stock->stock < $item['count']) {
                    throw new Exception("Недостаточно товара с ID {$item['product_id']} на складе");
                }

                $stockBefore = $stock->stock;
                $stock->stock -= $item['count'];
                $stock->save();

                // Записываем движение товара
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'quantity_change' => -$item['count'], // отрицательное значение - расход
                    'movement_type' => $movementType,
                    'description' => $this->getMovementDescription($movementType, $item['count']),
                    'order_id' => $orderId,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->stock,
                ]);
            }
        });
    }

    public function returnStock(int $warehouseId, array $items, ?int $orderId = null, string $movementType = StockMovement::TYPE_ORDER_CANCELED): void
    {
        DB::transaction(function () use ($warehouseId, $items, $orderId, $movementType) {
            foreach ($items as $item) {
                $stock = Stock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    // Создаем запись о товаре на складе, если её нет
                    $stock = Stock::create([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $item['product_id'],
                        'stock' => 0,
                    ]);
                }

                $stockBefore = $stock->stock;
                $stock->stock += $item['count'];
                $stock->save();

                // Записываем движение товара
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'quantity_change' => $item['count'], // положительное значение - приход
                    'movement_type' => $movementType,
                    'description' => $this->getMovementDescription($movementType, $item['count']),
                    'order_id' => $orderId,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->stock,
                ]);
            }
        });
    }

    public function updateOrderStock(Order $order, array $oldItems, array $newItems): void
    {
        DB::transaction(function () use ($order, $oldItems, $newItems) {
            // Возвращаем старые товары
            $this->returnStock($order->warehouse_id, $oldItems, $order->id, StockMovement::TYPE_ORDER_UPDATED);

            // Проверяем доступность новых товаров
            if (!$this->checkAvailability($order->warehouse_id, $newItems)) {
                throw new Exception('Недостаточно товаров на складе для обновления заказа');
            }

            // Списываем новые товары
            $this->reserveStock($order->warehouse_id, $newItems, $order->id, StockMovement::TYPE_ORDER_UPDATED);
        });
    }

    public function getStocksWithWarehouses()
    {
        return Stock::with(['product', 'warehouse'])
            ->get()
            ->groupBy('warehouse_id')
            ->map(function ($stocks) {
                return $stocks->map(function ($stock) {
                    return [
                        'product_id' => $stock->product_id,
                        'product_name' => $stock->product->name,
                        'product_price' => $stock->product->price,
                        'stock' => $stock->stock,
                    ];
                });
            });
    }

    /**
     * Получить описание движения товара
     */
    private function getMovementDescription(string $movementType, int $quantity): string
    {
        return match($movementType) {
            StockMovement::TYPE_ORDER_CREATED => "Списание {$quantity} шт. при создании заказа",
            StockMovement::TYPE_ORDER_CANCELED => "Возврат {$quantity} шт. при отмене заказа",
            StockMovement::TYPE_ORDER_UPDATED => "Изменение остатка на {$quantity} шт. при обновлении заказа",
            StockMovement::TYPE_ORDER_RESUMED => "Списание {$quantity} шт. при возобновлении заказа",
            StockMovement::TYPE_MANUAL => "Ручное изменение на {$quantity} шт.",
            default => "Изменение остатка на {$quantity} шт."
        };
    }
}
