<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @group Profile
 * Manage the authenticated user's profile information.
 */
class ProfileController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles', 'permissions');
        return response_success('User data fetched successfully.', $user);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $user = $this->authService->updateProfile($request->user(), $request);
            return response_success('Profile updated successfully.', new UserResource($user));
        } catch (ValidationException $e) {
            return response_error('Validation failed.', $e->errors(), 422);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $this->authService->deleteAccount($request->user());
            return response_success('Account deleted successfully.');
        } catch (\Exception $e) {
            return response_error('Failed to delete account.', ['error' => $e->getMessage()], 500);
        }
    }
}
