<?php

namespace App\Http\Controllers\Api;

use App\Filters\StockMovementFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovementFilterRequest;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\StockMovementStatisticsResource;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;


class StockMovementController extends Controller
{

    /**
     * Получить список складских перемещений с фильтрацией и пагинацией.
     *
     * @param StockMovementFilterRequest $request Запрос с параметрами фильтра и пагинации.
     * @param StockMovementFilter $filter Применяет фильтры к запросу.
     *
     * @return \Illuminate\Http\JsonResponse JSON с коллекцией складских перемещений и данными пагинации.
     */

    public function index(StockMovementFilterRequest $request, StockMovementFilter $filter): JsonResponse
    {
        $validated = $request->validated();

        $query = StockMovement::with(['product', 'warehouse', 'order']);
        $filter->apply($query, $validated);

        $query->orderBy('created_at', 'desc');

        $perPage = $validated['per_page'] ?? 10;
        $page = $request->get('page', 1);
        $movements = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => StockMovementResource::collection($movements),
            'current_page' => $movements->currentPage(),
            'last_page' => $movements->lastPage(),
            'per_page' => $movements->perPage(),
            'total' => $movements->total(),
        ]);

    }



    /**
     * Получить статистику по складским перемещениям с учетом фильтров.
     *
     * Возвращает количество и суммарные изменения по типам перемещений, а также агрегированные итоги:
     * - общее количество перемещений,
     * - общее количество поступлений,
     * - общее количество списаний.
     *
     * @param StockMovementFilterRequest $request Запрос с параметрами фильтра.
     * @param StockMovementFilter $filter Применяет фильтры к запросу.
     *
     * @return \Illuminate\Http\JsonResponse JSON с агрегированной статистикой складских перемещений.
     */

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
