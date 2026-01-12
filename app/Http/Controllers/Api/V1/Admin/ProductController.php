<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\Admin\ShopifyService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ShopifyService $shopifyService;
    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    //list products
    public function index()
    {
        $products = $this->shopifyService->getAll();
        if ($products->isEmpty()) {
            return response_error('No products found', [], 404);
        }
        return ProductResource::collection($products)->additional([
            'ok' => true,
            'message' => 'Products retrieved successfully'
        ]);
    }

    //menual sync
    public function sync()
    {
        $result = $this->shopifyService->syncProducts();
        return response()->json($result);
    }
}
