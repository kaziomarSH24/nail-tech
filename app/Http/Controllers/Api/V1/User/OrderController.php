<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    //index
    public function index()
    {
        $qc = function ($query) {
            $query->where('user_id', auth()->id());
        };

        $orders = $this->orderService->getAll($qc);
        if ($orders->isEmpty()) {
            return response_error('No orders found', [], 404);
        }
        return OrderResource::collection($orders)->additional([
            'ok' => true,
            'message' => 'Orders retrieved successfully'
        ]);
    }

    public function show($id)
    {
        $order = $this->orderService->getById($id);
        if (!$order || $order->user_id !== auth()->id()) {
            return response_error('Order not found', [], 404);
        }
        return (new OrderResource($order))->additional([
            'ok' => true,
            'message' => 'Order retrieved successfully'
        ]);
    }
}
