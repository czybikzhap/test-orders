<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with(['stocks.warehouse'])->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }
}
