<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Http\Resources\Api\V1\AdminUserResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class AdminManagementService
{
    /**
     * Available page & feature permissions map.
     */
    public function getAvailablePermissions(): array
    {
        return [
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
            'manage manufactured orders' => 'Manufactured Orders Scope',
            'manage retail orders' => 'Retail Orders Scope',
            'access home-content' => 'Home Content',
            'access settings' => 'Settings',
        ];
    }

    /**
     * List admins with pagination and filters.
     */
    public function listAdmins(array $filters = [], int $perPage = 15): array
    {
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? null;

        $query = User::role('admin')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', filter_var($status, FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query->orderBy('name')->paginate($perPage);

        return [
            'admins' => AdminUserResource::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ]
        ];
    }

    /**
     * Get single admin user details.
     */
    public function getAdminDetail(int $id): User
    {
        return User::where('id', $id)->firstOrFail();
    }

    /**
     * Create a new Admin user and sync Spatie permissions.
     */
    public function createAdmin(array $data): User
    {
        $selectedPermissions = $data['permissions'] ?? [];

        // Validate Order scope rules if 'access orders' permission selected
        $this->validateOrderPermissions($selectedPermissions);

        // Sync permission records for both web and api guards
        $this->ensurePermissionsExist($selectedPermissions);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'mobile_number' => $data['mobile_number'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole('admin');
        $user->syncPermissions($selectedPermissions);

        return $user;
    }

    /**
     * Update existing Admin user details and permissions.
     */
    public function updateAdmin(int $id, array $data): User
    {
        $user = User::findOrFail($id);

        // Safety check: Cannot edit super_admin via API
        if ($user->hasRole('super_admin')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Unauthorized action: Super Admin accounts cannot be edited.',
                'errors' => new \stdClass()
            ], 403));
        }

        $selectedPermissions = $data['permissions'] ?? $user->getPermissionNames()->toArray();

        $this->validateOrderPermissions($selectedPermissions);
        $this->ensurePermissionsExist($selectedPermissions);

        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => isset($data['email']) ? strtolower(trim($data['email'])) : $user->email,
            'mobile_number' => array_key_exists('mobile_number', $data) ? $data['mobile_number'] : $user->mobile_number,
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : $user->is_active,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        $user->syncPermissions($selectedPermissions);

        return $user;
    }

    /**
     * Toggle status (active/inactive) for an admin.
     */
    public function toggleAdminStatus(int $id): User
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Super Admin accounts cannot be restricted.',
                'errors' => new \stdClass()
            ], 403));
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return $user;
    }

    /**
     * Delete an admin user.
     */
    public function deleteAdmin(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Super Admin accounts cannot be deleted.',
                'errors' => new \stdClass()
            ], 403));
        }

        $user->delete();
    }

    /**
     * Ensure Order permission scopes are selected properly.
     */
    private function validateOrderPermissions(array &$selectedPermissions): void
    {
        if (in_array('access orders', $selectedPermissions)) {
            $hasManufactured = in_array('manage manufactured orders', $selectedPermissions);
            $hasRetail = in_array('manage retail orders', $selectedPermissions);

            if (!$hasManufactured && !$hasRetail) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => 'Please select at least one order scope (manage manufactured orders or manage retail orders) when granting access to orders.',
                    'errors' => [
                        'permissions' => ['Order scope permission required when access orders is selected.']
                    ]
                ], 422));
            }
        } else {
            // Strip scope permissions if access orders is not granted
            $selectedPermissions = array_values(array_diff($selectedPermissions, [
                'manage manufactured orders',
                'manage retail orders'
            ]));
        }
    }

    /**
     * Ensure Spatie permissions exist for both web and api guards.
     */
    private function ensurePermissionsExist(array $permissions): void
    {
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }
    }
}
