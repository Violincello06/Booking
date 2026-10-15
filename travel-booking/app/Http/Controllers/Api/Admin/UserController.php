<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->userService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar user berhasil diambil',
            'data' => UserResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat',
            'data' => new UserResource($user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $loaded = $this->userService->find($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail user berhasil diambil',
            'data' => new UserResource($loaded),
        ], 200);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updated = $this->userService->update($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui',
            'data' => new UserResource($updated),
        ], 200);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus',
        ], 200);
    }
}
