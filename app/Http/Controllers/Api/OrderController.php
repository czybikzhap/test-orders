<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderFilterRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderValidator;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected OrderValidator $validator,
        protected OrderService $orderService
    ) {}


    public function index(OrderFilterRequest $request): JsonResponse
    {
        $query = Order::with(['warehouse', 'orderItems.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('customer')) {
            $query->where('customer', 'like', '%' . $request->customer . '%');
        }

        $perPage = $request->get('per_page', 10);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }


    public function store(StoreOrderRequest $request): JsonResponse
    {

        try {
            $order = $this->orderService->createOrder(
                customer: $request->customer,
                warehouseId: $request->warehouse_id,
                items: $request->items
            );

            return response()->json([
                'success' => true,
                'data' => $order->load(['warehouse', 'orderItems.product']),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->validator->ensureIsActive($order);

        try {
            $updatedOrder = $this->orderService->updateOrder($order, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $updatedOrder->load(['warehouse', 'orderItems.product']),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    public function complete(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeCompleted($order);

        try {
            $completedOrder = $this->orderService->completeOrder($order);

            return response()->json([
                'success' => true,
                'data' => $completedOrder->load(['warehouse', 'orderItems.product']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    public function cancel(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeCanceled($order);

        try {
            $canceledOrder = $this->orderService->cancelOrder($order);

            return response()->json([
                'success' => true,
                'data' => $canceledOrder->load(['warehouse', 'orderItems.product']),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    public function resume(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeResumed($order);

        try {
            $resumedOrder = $this->orderService->resumeOrder($order);

            return response()->json([
                'success' => true,
                'data' => $resumedOrder->load(['warehouse', 'orderItems.product']),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
