<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserPublicResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $users = User::paginate($perPage);

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => UserPublicResource::collection($users)->response()->getData()
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => new UserPublicResource($user)
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updateData = $request->only(['name', 'email', 'phone', 'address']);

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($user->fresh())
        ]);
    }

    /**
     * Remove the specified user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // Prevent users from deleting themselves
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}
