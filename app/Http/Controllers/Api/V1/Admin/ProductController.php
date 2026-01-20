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
    public function index(Request $request)
    {
        $variants = $request->boolean('variants');
        $groupByCategory = $request->boolean('group_by_category');
        $qc = function ($query) use ($variants) {
            $columns = ['id', 'shopify_product_id', 'title', 'category', 'price'];
            if ($variants) {
                $columns[] = 'variants';
            }
            $columns = array_merge($columns, ['stock_quantity', 'low_stock_threshold', 'last_synced_at', 'created_at', 'updated_at']);
            $query->select($columns);
        };
        $products = $this->shopifyService->getAll($qc);

        if ($products->isEmpty()) {
            return response_error('No products found', [], 404);
        }
        if ($groupByCategory) {

            $groupedProducts = collect($products->items())
                ->map(function ($product) {

                    return new ProductResource($product);
                })
                ->groupBy(function ($item) {
                    return $item->category ?? 'Uncategorized';
                });

            return response()->json([
                'ok' => true,
                'message' => 'Products retrieved and grouped successfully',
                'data' => $groupedProducts,
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                ]
            ]);
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
