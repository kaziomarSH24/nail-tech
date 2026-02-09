<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Faq;
use App\Services\UserService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    //get users notification settings
    public function getNotificationSettings()
    {
        $user = auth()->user();
        $notification = $user->notification_settings;
        return response_success('Notification settings retrieved successfully.', $notification);
    }

    //update users notification settings
    public function updateNotificationSettings(UpdateSettingRequest $request, UserService $userService)
    {
        $data = $request->validated();
        $notification = $userService->updateSettings($data);
        return response_success('Notification settings updated successfully.', $notification);
    }

    //get faqs
    public function getFaqs()
    {
        $faqs = Faq::all();
        if ($faqs->isEmpty()) {
            return response_error('No FAQs found.', [], 404);
        }
        return response_success('FAQs retrieved successfully.', $faqs);
    }
}
