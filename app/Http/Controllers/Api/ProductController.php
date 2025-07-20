<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with(['stocks.warehouse'])->get();

        $productsWithStocks = $products->map(function ($product) {
            $stocks = $product->stocks->map(function ($stock) {
                return [
                    'warehouse_id' => $stock->warehouse_id,
                    'warehouse_name' => $stock->warehouse->name,
                    'stock' => $stock->stock,
                ];
            });

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stocks' => $stocks,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $productsWithStocks
        ]);
    }
}
