<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function createOrder(string $customer, int $warehouseId, array $items): Order
    {
        return DB::transaction(function () use ($customer, $warehouseId, $items) {
            if (!$this->stockService->checkAvailability($warehouseId, $items)) {
                throw new Exception('Недостаточно товаров на складе');
            }

            $order = Order::create([
                'customer' => $customer,
                'warehouse_id' => $warehouseId,
                'status' => Order::STATUS_ACTIVE,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'count' => $item['count'],
                ]);
            }

            $this->stockService->reserveStock($warehouseId, $items);

            return $order;
        });
    }

    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $oldItems = $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                ];
            })->toArray();

            if (isset($data['customer'])) {
                $order->customer = $data['customer'];
            }

            if (isset($data['items'])) {
                $order->orderItems()->delete();

                foreach ($data['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'count' => $item['count'],
                    ]);
                }

                $this->stockService->updateOrderStock($order, $oldItems, $data['items']);
            }

            $order->save();

            return $order;
        });
    }


    public function completeOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->status = Order::STATUS_COMPLETED;
            $order->completed_at = now();
            $order->save();

            return $order;
        });
    }

    public function cancelOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $items = $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                ];
            })->toArray();

            $this->stockService->returnStock($order->warehouse_id, $items);

            $order->status = Order::STATUS_CANCELED;
            $order->save();

            return $order;
        });
    }

    public function resumeOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $items = $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                ];
            })->toArray();

            if (!$this->stockService->checkAvailability($order->warehouse_id, $items)) {
                throw new Exception('Недостаточно товаров на складе для возобновления заказа');
            }

            $this->stockService->reserveStock($order->warehouse_id, $items);

            $order->status = Order::STATUS_ACTIVE;
            $order->save();

            return $order;
        });
    }

}
