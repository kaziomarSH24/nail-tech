<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\User;
use App\Models\UserInventory;
use App\Services\UserInventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class UserInventoryController extends Controller
{
    use AuthorizesRequests;
    protected UserInventoryService $userInventoryService;
    public function __construct(UserInventoryService $userInventoryService)
    {
        $this->userInventoryService = $userInventoryService;
        $this->authorizeResource(UserInventory::class, 'inventory');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventories = $this->userInventoryService->getAll(function ($query) {
            $query->where('user_id', auth()->id());
        });
        if ($inventories->isEmpty()) {
            return response_error('No inventory found.', [], 404);
        }

        return response_success('Inventory retrieved.', $inventories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryRequest $request)
    {
        $data = $request->validated();
        $inventory = $this->userInventoryService->createInventory($data);
        return response_success('Inventory created.', $inventory, 201);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventoryRequest $request, UserInventory $inventory)
    {
        $data = $request->validated();
        $updatedInventory = $this->userInventoryService->updateInventory($inventory->id, $data);
        return response_success('Inventory updated.', $updatedInventory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserInventory $inventory)
    {
        $this->userInventoryService->delete($inventory->id);
        return response_success('Inventory deleted.');
    }

    public function restock($id)
    {
        $inventory = $this->userInventoryService->getById($id);
        $this->authorize('update', $inventory);

        $updated = $this->userInventoryService->restockItem($id);

        return response_success('Item marked as restocked. Reminder reset.', $updated);
    }

    public function consumeItem(Request $request, $id)
    {
        $inventory = $this->userInventoryService->getById($id);
        $this->authorize('update', $inventory);
        $updated = $this->userInventoryService->useItem($id, $request->quantity ?? 1);
    
        $status = $updated->current_quantity <= $updated->reorder_threshold ? 'Low Stock Alert!' : 'Stock updated.';

        return response_success($status, $updated);
    }
}
