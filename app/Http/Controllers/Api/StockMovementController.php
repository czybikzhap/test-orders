<?php

namespace App\Http\Controllers\Api;

use App\Filters\StockMovementFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovementFilterRequest;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\StockMovementStatisticsResource;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockMovementController extends Controller
{

    public function index(StockMovementFilterRequest $request, StockMovementFilter $filter): JsonResponse
    {
        $validated = $request->validated();

        $query = StockMovement::with(['product', 'warehouse', 'order']);
        $filter->apply($query, $validated);

        $query->orderBy('created_at', 'desc');

        $perPage = $validated['per_page'] ?? 10;
        $movements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => StockMovementResource::collection($movements),
        ]);

    }


    public function statistics(StockMovementFilterRequest $request, StockMovementFilter $filter): JsonResponse
    {
        $validated = $request->validated();
        $query = StockMovement::query();
        $filter->apply($query, $validated);

        $movementTypesStats = $query->selectRaw('movement_type, COUNT(*) as count, SUM(quantity_change) as total_change')
            ->groupBy('movement_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->movement_type => [
                    'count' => $item->count,
                    'total_change' => $item->total_change,
                    'label' => (new StockMovement())->getMovementTypeLabel(),
                ]];
            });

        $totalIncoming = $query->where('quantity_change', '>', 0)->sum('quantity_change');
        $totalOutgoing = abs($query->where('quantity_change', '<', 0)->sum('quantity_change'));
        $totalMovements = $query->count();

        $data = [
            'total_movements' => $totalMovements,
            'total_incoming' => $totalIncoming,
            'total_outgoing' => $totalOutgoing,
            'movement_types' => $movementTypesStats,
        ];

        return response()->json([
            'success' => true,
            'data' => new StockMovementStatisticsResource($data)
        ]);
    }

}
