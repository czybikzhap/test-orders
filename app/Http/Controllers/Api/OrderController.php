<?php

namespace App\Http\Controllers\Api;

use App\Filters\OrderFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderFilterRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Exception;

class OrderController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected OrderService $orderService
    ) {}


    /**
     * Получить список заказов с фильтрацией и пагинацией.
     *
     * @param OrderFilterRequest $request Запрос с параметрами фильтрации и пагинации.
     * @param OrderFilter $filter Сервис для применения фильтров к запросу.
     *
     * @return \Illuminate\Http\JsonResponse JSON-ответ с данными заказов и информацией о пагинации.
     */

    public function index(OrderFilterRequest $request, OrderFilter $filter): JsonResponse
    {
        $validated = $request->validated();

        $query = Order::with(['warehouse', 'orderItems.product']);
        $filter->apply($query, $validated);

        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $orders = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ]);
    }


    /**
     * Создать новый заказ.
     *
     * @param StoreOrderRequest $request Запрос с данными для создания заказа.
     *
     * @return \Illuminate\Http\JsonResponse JSON-ответ с данными созданного заказа и HTTP статусом 201.
     */

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            customer: $request->customer,
            warehouseId: $request->warehouse_id,
            items: $request->items
        );

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ], 201);

    }


    /**
     * Обновить существующий заказ.
     *
     * @param UpdateOrderRequest $request Запрос с данными для обновления заказа.
     * @param Order $order Модель заказа, который необходимо обновить.
     *
     * @return \Illuminate\Http\JsonResponse JSON-ответ с обновленными данными заказа.
     */

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->orderService->updateOrder($order, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ]);

    }


    /**
     * Завершить заказ.
     *
     * @param Order $order Модель заказа, который необходимо завершить.
     *
     * @return \Illuminate\Http\JsonResponse JSON-ответ с данными завершенного заказа.
     */

    public function complete(Order $order): JsonResponse
    {
        $this->orderService->completeOrder($order);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ]);

    }


    /**
     * Отменить заказ.
     *
     * @param Order $order Модель заказа, который необходимо отменить.
     *
     * @return \Illuminate\Http\JsonResponse JSON-ответ с данными отмененного заказа.
     */

    public function cancel(Order $order): JsonResponse
    {
        $this->orderService->cancelOrder($order);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ]);

    }


    /**
     * Возобновить заказ.
     *
     * @param Order $order Модель заказа, который необходимо возобновить.
     *
     * @return \Illuminate\Http\JsonResponse JSON-ответ с данными возобновленного заказа.
     */


    public function resume(Order $order): JsonResponse
    {
        $this->orderService->resumeOrder($order);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ]);

    }
}
