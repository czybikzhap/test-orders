<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{

    /**
     * Проверяет наличие требуемого количества товаров на складе.
     *
     * @param int $warehouseId ID склада.
     * @param array<int, array{product_id:int, count:int}> $items Массив товаров с их количеством.
     * @return bool true, если все товары доступны в нужном количестве, иначе false.
     */
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


    /**
     * Резервирует товары на складе, уменьшая их количество.
     *
     * @param int $warehouseId ID склада.
     * @param array<int, array{product_id:int, count:int}> $items Массив товаров с их количеством для резервирования.
     * @param int|null $orderId ID заказа, для которого резервируются товары (может быть null).
     * @param string $movementType Тип движения для записи в StockMovement (по умолчанию TYPE_ORDER_CREATED).
     *
     * @throws \Exception Если товар не найден или недостаточно на складе.
     */

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

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'quantity_change' => -$item['count'],
                    'movement_type' => $movementType,
                    'description' => $this->getMovementDescription($movementType, $item['count']),
                    'order_id' => $orderId,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->stock,
                ]);
            }
        });
    }


    /**
     * Возвращает товары на склад, увеличивая их количество.
     *
     * @param int $warehouseId ID склада.
     * @param array<int, array{product_id:int, count:int}> $items Массив товаров с их количеством для возврата.
     * @param int|null $orderId ID заказа (может быть null).
     * @param string $movementType Тип движения для записи в StockMovement (по умолчанию TYPE_ORDER_CANCELED).
     */

    public function returnStock(int $warehouseId, array $items, ?int $orderId = null, string $movementType = StockMovement::TYPE_ORDER_CANCELED): void
    {
        DB::transaction(function () use ($warehouseId, $items, $orderId, $movementType) {
            foreach ($items as $item) {
                $stock = Stock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    $stock = Stock::create([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $item['product_id'],
                        'stock' => 0,
                    ]);
                }

                $stockBefore = $stock->stock;
                $stock->stock += $item['count'];
                $stock->save();


                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'quantity_change' => $item['count'],
                    'movement_type' => $movementType,
                    'description' => $this->getMovementDescription($movementType, $item['count']),
                    'order_id' => $orderId,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->stock,
                ]);
            }
        });
    }

    /**
     * Обновляет остатки товара при изменении заказа.
     *
     * Возвращает старые товары на склад, проверяет наличие новых и резервирует их.
     *
     * @param Order $order Обновляемый заказ.
     * @param array<int, array{product_id:int, count:int}> $oldItems Старые товары заказа.
     * @param array<int, array{product_id:int, count:int}> $newItems Новые товары заказа.
     *
     * @throws \Exception Если недостаточно товаров для обновления.
     */

    public function updateOrderStock(Order $order, array $oldItems, array $newItems): void
    {
        if (!$this->checkAvailability($order->warehouse_id, $newItems)) {
            throw new Exception('Недостаточно товаров на складе для обновления заказа');
        }

        DB::transaction(function () use ($order, $oldItems, $newItems) {
            $this->returnStock($order->warehouse_id, $oldItems, $order->id, StockMovement::TYPE_ORDER_UPDATED);
            $this->reserveStock($order->warehouse_id, $newItems, $order->id, StockMovement::TYPE_ORDER_UPDATED);
        });
    }


    /**
     * Получить описание движения товара для записи в StockMovement.
     *
     * @param string $movementType Тип движения.
     * @param int $quantity Количество товара, участвующее в движении.
     * @return string Текстовое описание движения.
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
