<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\UserInventory;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\Auth;

class UserInventoryService extends BaseService
{
    use FileUploadTrait;
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = UserInventory::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }


    // Define allowed filters
    protected function getAllowedFilters(): array
    {
        return [
            'item_name',
            AllowedFilter::exact('source'),
            AllowedFilter::scope('low_stock'),
        ];
    }

    // Define allowed includes relationships
    protected function getAllowedIncludes(): array
    {
        return ['linkedProduct'];
    }

    // Define allowed sorts
    protected function getAllowedSorts(): array
    {
        return ['id', 'current_quantity', 'created_at', 'item_name'];
    }

    /**
     * Override create to set user_id and reminder dates
     */
    public function createInventory(array $data)
    {
        $data['user_id'] = Auth::id();
        $data['last_restocked_at'] = now();
        $request = request();

        if ($request->hasFile('image')) {
            $data['image'] = $this->handleFileUpload($request, 'image', 'user_inventories', null, null, 90, true);
        }

        // Calculate next reminder
        if (isset($data['reminder_frequency_days'])) {
            $data['next_reminder_at'] = now()->addDays((int)$data['reminder_frequency_days']);
        }

        return $this->create($data);
    }

    /**
     * Update Inventory with Image Replacement
     */
    public function updateInventory($id, array $data)
    {
        $inventory = $this->getById($id);
        $request = request();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($inventory->image) {
                $this->deleteFile($inventory->image);
            }
            // Upload new
            $data['image'] = $this->handleFileUpload($request, 'image', 'user_inventories', null, null, 90, true);
        }

        // Recalculate reminder if frequency changed
        if (isset($data['reminder_frequency_days'])) {
            $data['next_reminder_at'] = now()->addDays((int)$data['reminder_frequency_days']);
        }

        return $this->update($id, $data);
    }

    /**
     * Mark item as Restocked (The Toggle Feature)
     */
    public function restockItem($id)
    {
        $inventory = $this->getById($id);

        $updateData = [
            'last_restocked_at' => now(),
        ];

        // Update next reminder date
        if ($inventory->reminder_frequency_days) {
            $updateData['next_reminder_at'] = now()->addDays($inventory->reminder_frequency_days);
        }

        return $this->update($id, $updateData);
    }

    public function useItem($id, $quantityUsed = 1)
    {
        $inventory = $this->getById($id);
        // dd($inventory->current_quantity);
        $newQuantity = max(0, $inventory->current_quantity - $quantityUsed);
        // dd($newQuantity);
        return $this->update($id, ['current_quantity' => $newQuantity]);
    }
}
