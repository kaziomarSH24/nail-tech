<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RestockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StockController extends Controller
{
    public function index(Request $request)
    {

        $products = Cache::tags('products')->remember('stock_overview_' . request()->fullUrl(), 60 * 5, function () use ($request) {

            $query = Product::query();

            if ($request->status === 'low_stock') {
                $query->lowStock();
            } elseif ($request->status === 'out_of_stock') {
                $query->outOfStock();
            }

            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }
            return $query->select('id', 'title', 'sku', 'stock_quantity', 'low_stock_threshold', 'image_url', 'status')
                ->latest()
                ->paginate(10);
        });

        return response_success('Stock overview retrieved successfully', $products);
    }


    public function restockRequests(Request $request)
    {
        $requests = RestockRequest::with(['user:id,name,email', 'product:id,title,image_url'])
            ->when($request->status, function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    // 3. Update Restock Request Status (Approve/Reject)
    public function updateRestockStatus(Request $request, $id)
    {
        $restockRequest = RestockRequest::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $restockRequest->update([
            'status' => $request->status,
            'admin_note' => $request->note ?? null
        ]);

        return response()->json(['message' => 'Restock request updated successfully']);
    }
}
