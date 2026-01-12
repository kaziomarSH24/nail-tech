<?php

namespace App\Services\Admin;

use App\Services\BaseService;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService extends BaseService
{
    protected $baseUrl;
    protected $accessToken;
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Product::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
        $this->baseUrl = "https://" . env('SHOPIFY_STORE_DOMAIN') . "/admin/api/" . env('SHOPIFY_API_VERSION');
        $this->accessToken = env('SHOPIFY_ACCESS_TOKEN');
    }


    // Define allowed filters
    protected function getAllowedFilters(): array
    {
        return [
            'title',
        ];
    }

    // Define allowed includes relationships
    protected function getAllowedIncludes(): array
    {
        return [
            //
        ];
    }

    // Define allowed sorts
    protected function getAllowedSorts(): array
    {
        return [
            'id',
            'created_at',
        ];
    }

    public function syncProducts()
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
            ])->get($this->baseUrl . '/products.json', [
                'limit' => 250,
                'fields' => 'id,title,body_html,images,variants,status'
            ]);

            if ($response->failed()) return ['success' => false, 'message' => 'Shopify Error'];

            $products = $response->json()['products'];
            $count = 0;

            foreach ($products as $item) {
                $this->saveProduct($item);
                $count++;
            }

            return ['success' => true, 'message' => "Synced $count products."];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    //Save/Update Single Product (Common Logic)
    public function saveProduct($shopifyData)
    {
        $image = null;
        if (isset($shopifyData['images']) && count($shopifyData['images']) > 0) {
            $image = $shopifyData['images'][0]['src'];
        } elseif (isset($shopifyData['image']) && isset($shopifyData['image']['src'])) {
            $image = $shopifyData['image']['src'];
        }


        $variant = $shopifyData['variants'][0] ?? [];

        return Product::updateOrCreate(
            ['shopify_product_id' => $shopifyData['id']],
            [
                'title' => $shopifyData['title'],
                'description' => $shopifyData['body_html'] ?? $shopifyData['description'] ?? null,
                'shopify_variant_id' => $variant['id'] ?? null,
                'sku' => $variant['sku'] ?? null,
                'price' => $variant['price'] ?? 0,
                'stock_quantity' => $variant['inventory_quantity'] ?? 0,
                'image_url' => $image,
                'status' => 'synced',
                'last_synced_at' => Carbon::now(),
            ]
        );
    }
}
