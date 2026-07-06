<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Admin\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            if ($categoryName) {
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
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
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

        $taxRate = (float) Setting::getValue('tax_percentage', 0);
        $shippingCost = (float) Setting::getValue('shipping_cost', 0);
        $estimatedTax = ($product->price * $taxRate) / 100;
        $relatedProducts = $this->shopifyService->getRelatedProducts($product);

        //user reward
        $currentPoints = Auth::user() ? Auth::user()->current_points : 0;

        return response()->json([
            'ok' => true,
            'message' => 'Product retrieved successfully',
            'data' => new ProductResource($product),
            'meta' => [
                'current_user_reward_points' => $currentPoints,
                'tax_rate_percentage' => $taxRate,
                'estimated_tax_amount' => round($estimatedTax, 2),
                'shipping_cost' => round($shippingCost, 2),
            ],
            'related_products' => ProductResource::collection($relatedProducts)
        ]);
    }


    //get only all categories
    public function getCategories()
    {
        $categories = Product::select('category')->distinct()->pluck('category')->filter()->values();
        // if ($categories->contains('Uncategorized')) {
        //     $categories = $categories->filter(function ($value) {
        //         return $value !== 'Uncategorized';
        //     })->values();
        // }
        if ($categories->isEmpty()) {
            return response_error('No categories found', [], 404);
        }

        $categories->prepend('All Product');

        return response_success('Categories retrieved successfully', $categories);
    }




    //menual sync
    public function sync()
    {
        $result = $this->shopifyService->syncProducts();
        return response()->json($result);
    }
}
