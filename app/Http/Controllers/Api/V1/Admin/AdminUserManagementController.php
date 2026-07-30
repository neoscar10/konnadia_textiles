<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Api\V1\Admin\StoreAdminUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAdminUserRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserManagementController extends Controller
{
    /**
     * Available permission map for admin staff assignment.
     */
    protected array $availablePermissions = [
        'access dashboard' => 'Dashboard',
        'access customers' => 'Customers',
        'access customer-levels' => 'Customer Levels',
        'access products' => 'Products',
        'access design-catalog' => 'Design Catalog',
        'access categories' => 'Categories',
        'access tags' => 'Tags',
        'access inventory' => 'Inventory',
        'access retail-shops' => 'Retail Shops',
        'access product-transfers' => 'Product Transfers',
        'access orders' => 'Orders',
        'access home-content' => 'Home Content',
        'access settings' => 'Settings',
        'access contact-messages' => 'Contact Messages & Support',
    ];

    /**
     * List admin users (excluding super_admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::role('admin')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            })->with('permissions');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->query('status') !== null && $request->query('status') !== '') {
            $query->where('is_active', (bool) $request->query('status'));
        }

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(function ($admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'mobile_number' => $admin->mobile_number,
                    'is_active' => (bool) $admin->is_active,
                    'permissions' => $admin->permissions->pluck('name'),
                    'created_at' => $admin->created_at ? $admin->created_at->format('d-M-Y H:i') : null,
                ];
            }),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ]
        ]);
    }

    /**
     * Get list of available permissions for admin assignment.
     */
    public function permissionsList(): JsonResponse
    {
        $permissions = [];
        foreach ($this->availablePermissions as $key => $label) {
            $permissions[] = [
                'name' => $key,
                'label' => $label,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * Get single admin user details.
     */
    public function show(int $id): JsonResponse
    {
        $admin = User::role('admin')->with('permissions')->findOrFail($id);

        if ($admin->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin details cannot be requested.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'mobile_number' => $admin->mobile_number,
                'is_active' => (bool) $admin->is_active,
                'permissions' => $admin->permissions->pluck('name'),
                'created_at' => $admin->created_at ? $admin->created_at->format('d-M-Y H:i') : null,
            ]
        ]);
    }

    /**
     * Create new admin account.
     */
    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile_number' => $validated['mobile_number'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $adminRoleWeb = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRoleApi = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $user->assignRole([$adminRoleWeb, $adminRoleApi]);

        $permissions = $validated['permissions'] ?? [];
        if (!empty($permissions)) {
            foreach ($permissions as $permissionName) {
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
            }
            $user->syncPermissions($permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin account created successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'is_active' => (bool) $user->is_active,
                'permissions' => $user->permissions->pluck('name'),
            ]
        ], 201);
    }

    /**
     * Update admin account.
     */
    public function update(UpdateAdminUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin accounts cannot be edited.',
            ], 403);
        }

        $validated = $request->validated();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile_number' => $validated['mobile_number'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        $permissions = $validated['permissions'] ?? [];
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }
        $user->syncPermissions($permissions);

        return response()->json([
            'success' => true,
            'message' => 'Admin account updated successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'is_active' => (bool) $user->is_active,
                'permissions' => $user->permissions->pluck('name'),
            ]
        ]);
    }

    /**
     * Toggle status (restrict or enable admin account).
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin accounts cannot be restricted.',
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'Admin account enabled successfully.' : 'Admin account restricted successfully.',
            'data' => [
                'id' => $user->id,
                'is_active' => (bool) $user->is_active,
            ]
        ]);
    }

    /**
     * Delete admin account.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin accounts cannot be deleted.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin account deleted successfully.',
        ]);
    }
}
