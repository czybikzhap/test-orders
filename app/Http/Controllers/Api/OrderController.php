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
use App\Services\OrderValidator;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Exception;

class OrderController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected OrderValidator $validator,
        protected OrderService $orderService
    ) {}


    public function index(OrderFilterRequest $request,  OrderFilter $filter): JsonResponse
    {
        $validated = $request->validated();

        $query = Order::with(['warehouse', 'orderItems.product']);
        $filter->apply($query, $validated);


        $perPage = $request->get('per_page', 10);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
        ]);
    }


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


    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->validator->ensureIsActive($order);

        $this->orderService->updateOrder($order, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ]);

    }


    public function complete(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeCompleted($order);

        $this->orderService->completeOrder($order);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ]);

    }


    public function cancel(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeCanceled($order);

        $this->orderService->cancelOrder($order);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ]);

    }


    public function resume(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeResumed($order);

        $this->orderService->resumeOrder($order);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['warehouse', 'orderItems.product']))
        ]);

    }
}
