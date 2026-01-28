<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
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
        $categoryName = $request->query('category_name');
        $qc = function ($query) use ($variants, $categoryName) {
            $columns = ['id', 'shopify_product_id', 'shopify_variant_id', 'title', 'category', 'price', 'image_url', 'sku'];
            if ($variants) {
                $columns[] = 'variants';
            }
            $columns = array_merge($columns, ['stock_quantity', 'low_stock_threshold', 'last_synced_at', 'status', 'created_at', 'updated_at']);
            if($categoryName){
                $query->where('category', $categoryName);
            }
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

    //show product details
    public function show($id)
    {
        $product = $this->shopifyService->getById($id);
        if (!$product) {
            return response_error('Product not found', [], 404);
        }
        return response()->json([
            'ok' => true,
            'message' => 'Product retrieved successfully',
            'data' => new ProductResource($product)
        ]);
    }


    //get only all categories
    public function getCategories()
    {
        $categories = Product::select('category')->distinct()->pluck('category')->filter()->values();
        if($categories->contains('Uncategorized')){
            $categories = $categories->filter(function ($value) {
                return $value !== 'Uncategorized';
            })->values();
        }
        if ($categories->isEmpty()) {
            return response_error('No categories found', [], 404);
        }
        return response_success('Categories retrieved successfully', $categories);
    }




    //menual sync
    public function sync()
    {
        $result = $this->shopifyService->syncProducts();
        return response()->json($result);
    }
}
