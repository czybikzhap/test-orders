<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    /**
     * Просмотр списка складов
     */
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::all();
        
        return response()->json([
            'success' => true,
            'data' => $warehouses
        ]);
    }
} 