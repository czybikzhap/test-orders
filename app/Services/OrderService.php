<?php

namespace App\Services;


use App\Exceptions\OrderNotActiveException;
use App\Exceptions\OrderStatusException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

use App\Exceptions\OrderCannotBeCompletedException;
use App\Exceptions\OrderCannotBeCanceledException;
use App\Exceptions\OrderCannotBeResumedException;

class OrderService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Создаёт новый заказ и резервирует товар на складе.
     *
     * Проверяет наличие товара на складе, создаёт заказ и позиции,
     * затем резервирует необходимое количество товаров.
     *
     * @param string $customer Имя или идентификатор покупателя.
     * @param int $warehouseId ID склада.
     * @param array<int, array{product_id: int, count: int}> $items Список товаров с количеством.
     *
     * @return Order Созданный заказ.
     *
     * @throws \Exception Если недостаточно товара на складе.
     */

    public function createOrder(string $customer, int $warehouseId, array $items): Order
    {
        if (!$this->stockService->checkAvailability($warehouseId, $items)) {
            throw new Exception('Недостаточно товаров на складе');
        }

        return DB::transaction(function () use ($customer, $warehouseId, $items) {
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

            $this->stockService->reserveStock(
                $warehouseId,
                $items,
                $order->id
            );

            return $order;
        });
    }


    /**
     * Обновляет заказ и связанные с ним позиции.
     *
     * Проверяет, что заказ активен, затем в транзакции обновляет данные заказа,
     * удаляет старые позиции и создаёт новые. Обновляет складские остатки через StockService.
     *
     * @param Order $order Заказ для обновления.
     * @param array{
     *     customer?: string,
     *     items?: array<int, array{product_id: int, count: int}>
     * } $data Массив с данными для обновления заказа.
     *
     * @return Order Обновлённый экземпляр заказа.
     *
     * @throws OrderNotActiveException Если заказ не в статусе активен.
     * @throws \Exception Возможные исключения при работе с базой и сервисами.
     */

    public function updateOrder(Order $order, array $data): Order
    {

        if ($order->status !== Order::STATUS_ACTIVE) {
            throw new OrderNotActiveException();
        }

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


    /**
     * Завершает заказ, меняя его статус и дату завершения.
     *
     * Проверяет, что заказ можно завершить, иначе выбрасывает исключение.
     *
     * @param Order $order Заказ для завершения.
     *
     * @return Order Обновлённый заказ со статусом "завершён".
     *
     * @throws OrderCannotBeCompletedException Если заказ нельзя завершить.
     * @throws \Exception Возможные ошибки при сохранении в базе.
     */

    public function completeOrder(Order $order): Order
    {
        if (!$order->canBeCompleted()) {
            throw new OrderCannotBeCompletedException();
        }

        $order->status = Order::STATUS_COMPLETED;
        $order->completed_at = now();
        $order->save();

        return $order;
    }


    /**
     * Отменяет заказ и возвращает товары на склад.
     *
     * Проверяет, что заказ можно отменить, иначе выбрасывает исключение.
     * Выполняет транзакцию для возврата товаров на склад и обновления статуса заказа.
     *
     * @param Order $order Заказ для отмены.
     * @return Order Обновлённый заказ со статусом "отменён".
     *
     * @throws OrderCannotBeCanceledException Если заказ нельзя отменить.
     * @throws \Exception При ошибках в процессе возврата товара на склад или сохранения заказа.
     */

    public function cancelOrder(Order $order): Order
    {
        if (!$order->canBeCanceled()) {
            throw new OrderCannotBeCanceledException();
        }

        return DB::transaction(function () use ($order) {
            $items = $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                ];
            })->toArray();

            $this->stockService->returnStock($order->warehouse_id, $items, $order->id);

            $order->status = Order::STATUS_CANCELED;
            $order->save();

            return $order;
        });
    }

    /**
     * Возобновляет ранее отменённый заказ.
     *
     * Проверяет, что заказ можно возобновить, иначе выбрасывает исключение.
     * Проверяет наличие товаров на складе до начала транзакции.
     * Выполняет транзакцию резервирования товара на складе и обновления статуса заказа.
     *
     * @param Order $order Заказ для возобновления.
     * @return Order Обновлённый заказ со статусом "активен".
     *
     * @throws OrderCannotBeResumedException Если заказ нельзя возобновить.
     * @throws \Exception Если недостаточно товаров на складе или ошибки при резервировании/сохранении.
     */

    public function resumeOrder(Order $order): Order
    {
        if (!$order->canBeResumed()) {
            throw new OrderCannotBeResumedException();
        }

        $items = $order->orderItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'count' => $item->count,
            ];
        })->toArray();

        if (!$this->stockService->checkAvailability($order->warehouse_id, $items)) {
            throw new Exception('Недостаточно товаров на складе для возобновления заказа');
        }

        return DB::transaction(function () use ($order, $items) {
            $this->stockService->reserveStock(
                $order->warehouse_id,
                $items,
                $order->id,
                StockMovement::TYPE_ORDER_RESUMED
            );

            $order->status = Order::STATUS_ACTIVE;
            $order->save();

            return $order;
        });

    }

}
