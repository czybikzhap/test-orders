<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderFilterRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderValidator;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class OrderController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected OrderValidator $validator
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
            DB::beginTransaction();

            if (!$this->stockService->checkAvailability($request->warehouse_id, $request->items)) {
                throw new Exception('Недостаточно товаров на складе');
            }

            $order = Order::create([
                'customer' => $request->customer,
                'warehouse_id' => $request->warehouse_id,
                'status' => Order::STATUS_ACTIVE,
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'count' => $item['count'],
                ]);
            }

            $this->stockService->reserveStock($request->warehouse_id, $request->items);

            DB::commit();

            $order->load(['warehouse', 'orderItems.product']);

            return response()->json([
                'success' => true,
                'data' => $order
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->validator->ensureIsActive($order);

        try {
            DB::beginTransaction();

            $oldItems = $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                ];
            })->toArray();

            if ($request->has('customer')) {
                $order->customer = $request->customer;
            }

            if ($request->has('items')) {
                $order->orderItems()->delete();

                foreach ($request->items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'count' => $item['count'],
                    ]);
                }

                $this->stockService->updateOrderStock($order, $oldItems, $request->items);
            }

            $order->save();
            DB::commit();

            $order->load(['warehouse', 'orderItems.product']);

            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function complete(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeCompleted($order);

        $order->status = Order::STATUS_COMPLETED;
        $order->completed_at = now();
        $order->save();

        $order->load(['warehouse', 'orderItems.product']);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }


    public function cancel(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeCanceled($order);

        try {
            DB::beginTransaction();

            $items = $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'count' => $item->count,
                ];
            })->toArray();

            $this->stockService->returnStock($order->warehouse_id, $items);

            $order->status = Order::STATUS_CANCELED;
            $order->save();

            DB::commit();

            $order->load(['warehouse', 'orderItems.product']);

            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function resume(Order $order): JsonResponse
    {
        $this->validator->ensureCanBeResumed($order);

        try {
            DB::beginTransaction();

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

            DB::commit();

            $order->load(['warehouse', 'orderItems.product']);

            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
