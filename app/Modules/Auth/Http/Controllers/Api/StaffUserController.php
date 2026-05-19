<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\StaffUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class StaffUserController extends Controller
{
    /**
     * Display a listing of the staff users.
     */
    public function index(Request $request): JsonResponse
    {
        // Enforce RBAC policy
        if (!Gate::allows('viewAny', StaffUser::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: Insufficient permissions.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Fetch paginated staff users (automatically isolated by TenantScope!)
        $staff = StaffUser::paginate(15);

        return response()->json([
            'success' => true,
            'data' => $staff->items(),
            'meta' => [
                'current_page' => $staff->currentPage(),
                'last_page' => $staff->lastPage(),
                'total' => $staff->total(),
            ]
        ]);
    }

    /**
     * Store a newly created staff user.
     */
    public function store(Request $request): JsonResponse
    {
        // Enforce RBAC policy
        if (!Gate::allows('create', StaffUser::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: Insufficient permissions.'
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8',
        ]);

        // Create user (tenant_id is automatically injected by BelongsToTenant!)
        $staffUser = StaffUser::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Staff user created successfully.',
            'data' => $staffUser
        ], Response::HTTP_CREATED);
    }
}
