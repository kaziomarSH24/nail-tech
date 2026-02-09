<?php

namespace App\Services\Admin;

use App\Services\BaseService;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;
use App\Models\Order;
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
            'category',
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
                'fields' => 'id,title,body_html,images,variants,status,product_type'
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
        // dd($shopifyData);
        $image = null;
        if (isset($shopifyData['images']) && count($shopifyData['images']) > 0) {
            $image = $shopifyData['images'][0]['src'];
        } elseif (isset($shopifyData['image']) && isset($shopifyData['image']['src'])) {
            $image = $shopifyData['image']['src'];
        }


        $variant = $shopifyData['variants'][0] ?? [];
        $variants = $shopifyData['variants'] ?? [];
        $category = !empty($shopifyData['product_type']) ? $shopifyData['product_type'] : 'Uncategorized';

        return Product::updateOrCreate(
            ['shopify_product_id' => $shopifyData['id']],
            [
                'title' => $shopifyData['title'],
                'description' => $shopifyData['body_html'] ?? $shopifyData['description'] ?? null,
                'shopify_variant_id' => $variant['id'] ?? null,
                'category' => $category,
                'variants' => $variants,
                'sku' => $variant['sku'] ?? null,
                'price' => $variant['price'] ?? 0,
                'stock_quantity' => $variant['inventory_quantity'] ?? 0,
                'image_url' => $image,
                'status' => 'synced',
                'last_synced_at' => Carbon::now(),
            ]
        );
    }


    public function syncOrderToShopify(Order $order)
    {
        $shippingData = $order->shipping_address ?? [];
        $addressDetails = $shippingData['address'] ?? [];
        $shopifyAddress = [
            'first_name' => $shippingData['first_name'] ?? 'Customer',
            'last_name'  => $shippingData['last_name'] ?? '',
            'phone'      => $shippingData['phone'] ?? '',
            'address1'   => $addressDetails['address1'] ?? '',
            'address2'   => $addressDetails['address2'] ?? '',
            'city'       => $addressDetails['city'] ?? '',
            'zip'        => $addressDetails['zip'] ?? '',
            'country'    => $addressDetails['country'] ?? 'United Kingdom',
            'province'   => $addressDetails['province'] ?? '',
        ];

        
        $lineItems = collect($order->items)->map(function ($item) {
            return [
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity']
            ];
        })->toArray();


        $payload = [
            'order' => [
                'email' => $shippingData['email'] ?? $order->user->email ?? 'customer@example.com',
                'financial_status' => 'paid',
                'line_items' => $lineItems,
                'shipping_address' => $shopifyAddress,
                'billing_address' => $shopifyAddress,
                'note' => "App Order ID: #{$order->id}",
                'tags' => 'App Purchase'
            ]
        ];

        // ৩. API Call
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrl}/orders.json", $payload);

        if ($response->successful()) {
            $shopifyData = $response->json();


            $order->update([
                'shopify_order_id' => $shopifyData['order']['id'],
                'delivery_status' => 'unfulfilled'
            ]);

            return $shopifyData;
        } else {
            Log::error('Shopify Order Sync Failed', ['error' => $response->body()]);
            return null;
        }
    }

    public function getRelatedProducts($product, $limit = 3)
    {
        $related = Product::where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->take($limit)
            ->get();

        if ($related->count() < $limit) {

            $needed = $limit - $related->count();

            $fallback = Product::where('id', '!=', $product->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->inRandomOrder()
                ->take($needed)
                ->get();

            $related = $related->merge($fallback);
        }

        return $related;
    }
}
