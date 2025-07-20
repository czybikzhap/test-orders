<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
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

    public function reserveStock(int $warehouseId, array $items): void
    {
        DB::transaction(function () use ($warehouseId, $items) {
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

                $stock->stock -= $item['count'];
                $stock->save();
            }
        });
    }

    public function returnStock(int $warehouseId, array $items): void
    {
        DB::transaction(function () use ($warehouseId, $items) {
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

                $stock->stock += $item['count'];
                $stock->save();
            }
        });
    }

    public function updateOrderStock(Order $order, array $oldItems, array $newItems): void
    {
        DB::transaction(function () use ($order, $oldItems, $newItems) {
            // Возвращаем старые товары
            $this->returnStock($order->warehouse_id, $oldItems);

            // Проверяем доступность новых товаров
            if (!$this->checkAvailability($order->warehouse_id, $newItems)) {
                throw new Exception('Недостаточно товаров на складе для обновления заказа');
            }

            // Списываем новые товары
            $this->reserveStock($order->warehouse_id, $newItems);
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
}
