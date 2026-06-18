<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    protected CustomerActivityLogService $activityLogService;

    public function __construct(CustomerActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * List customers with optional filtering and pagination.
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Customer::with(['level', 'user'])->orderBy('id', 'desc');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('customer_number', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('gst_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['level_id'])) {
            $query->where('customer_level_id', $filters['level_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new customer.
     */
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $passwordMode = $data['password_mode'] ?? 'auto';
            if ($passwordMode === 'auto' || empty($data['password'])) {
                $password = $this->generatePassword();
            } else {
                $password = $data['password'];
            }

            // Create user
            $user = $this->createCustomerUser($data, $password);

            $data['user_id'] = $user->id;
            $data['customer_number'] = $this->generateCustomerNumber();
            
            // Format inputs
            $data['company_name'] = trim($data['company_name']);
            $data['contact_person'] = trim($data['contact_person']);
            $data['mobile_number'] = trim($data['mobile_number']);
            $data['email'] = empty($data['email']) ? null : trim($data['email']);
            $data['gst_number'] = trim($data['gst_number']);
            
            $data['outstanding_amount'] = 0;
            $data['overdue_amount'] = 0;
            $data['available_credit'] = $this->calculateAvailableCredit($data['credit_limit'] ?? 0, 0);

            $customer = Customer::create($data);
            
            // Record activity log
            $this->activityLogService->record($customer, 'customer_created', [
                'title' => 'Customer Profile Created',
                'description' => "Customer {$customer->customer_number} ({$customer->company_name}) was created.",
            ]);

            // Pass generated password back in memory
            if ($passwordMode === 'auto' || empty($data['password'])) {
                $customer->generated_password = $password;
            }

            return $customer;
        });
    }

    /**
     * Create customer user account.
     */
    public function createCustomerUser(array $data, string $password): User
    {
        $email = empty($data['email']) ? null : trim($data['email']);
        
        $user = User::create([
            'name' => trim($data['contact_person']),
            'email' => $email,
            'mobile_number' => trim($data['mobile_number']),
            'password' => Hash::make($password),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole('customer');

        return $user;
    }

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            if (isset($data['company_name'])) $data['company_name'] = trim($data['company_name']);
            if (isset($data['contact_person'])) $data['contact_person'] = trim($data['contact_person']);
            if (isset($data['mobile_number'])) $data['mobile_number'] = trim($data['mobile_number']);
            if (array_key_exists('email', $data)) $data['email'] = empty($data['email']) ? null : trim($data['email']);
            if (isset($data['gst_number'])) $data['gst_number'] = trim($data['gst_number']);

            if (isset($data['credit_limit'])) {
                $data['available_credit'] = $this->calculateAvailableCredit(
                    $data['credit_limit'], 
                    $customer->outstanding_amount
                );
            }

            $customer->update($data);

            // Update user record if exists
            if ($customer->user) {
                $userData = [];
                if (isset($data['contact_person'])) $userData['name'] = trim($data['contact_person']);
                if (array_key_exists('email', $data)) $userData['email'] = empty($data['email']) ? null : trim($data['email']);
                if (isset($data['mobile_number'])) $userData['mobile_number'] = trim($data['mobile_number']);
                if (isset($data['is_active'])) $userData['is_active'] = $data['is_active'];

                $customer->user->update($userData);
            }

            // Record activity log
            $this->activityLogService->record($customer, 'customer_updated', [
                'title' => 'Customer Profile Updated',
                'description' => "Customer {$customer->customer_number} ({$customer->company_name}) profile was updated.",
            ]);

            return $customer;
        });
    }

    /**
     * Delete a customer (soft delete).
     */
    public function delete(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            // Record before deleting so we can still associate with customer ID
            $this->activityLogService->record($customer, 'customer_deleted', [
                'title' => 'Customer Profile Deleted',
                'description' => "Customer {$customer->customer_number} ({$customer->company_name}) was deleted.",
            ]);

            $customer->delete();
            // Deactivate linked user
            if ($customer->user) {
                $customer->user->update(['is_active' => false]);
            }
        });
    }

    /**
     * Toggle the active status of a customer.
     */
    public function toggleStatus(Customer $customer): Customer
    {
        return DB::transaction(function () use ($customer) {
            $customer->is_active = !$customer->is_active;
            $customer->save();

            if ($customer->user) {
                $customer->user->is_active = $customer->is_active;
                $customer->user->save();
            }

            $event = $customer->is_active ? 'customer_activated' : 'customer_deactivated';
            $label = $customer->is_active ? 'Activated' : 'Deactivated';
            $this->activityLogService->record($customer, $event, [
                'title' => "Customer {$label}",
                'description' => "Customer {$customer->customer_number} ({$customer->company_name}) was {$label}.",
            ]);

            return $customer;
        });
    }

    /**
     * Generate a unique customer number.
     */
    public function generateCustomerNumber(): string
    {
        $lastCustomer = Customer::withTrashed()->orderBy('id', 'desc')->first();
        $nextId = $lastCustomer ? $lastCustomer->id + 1 : 1;
        
        return 'KT-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate secure random password.
     */
    public function generatePassword(): string
    {
        return Str::random(10);
    }

    /**
     * Calculate available credit based on limit and outstanding amount.
     */
    public function calculateAvailableCredit(float|int|string $creditLimit, float|int|string $outstandingAmount = 0): float
    {
        $available = (float) $creditLimit - (float) $outstandingAmount;
        return max(0, $available);
    }

    /**
     * Export customers to CSV based on filters.
     */
    public function exportCsv(array $filters = [])
    {
        $query = Customer::with(['level', 'user'])->orderBy('id', 'desc');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('customer_number', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('gst_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['level_id'])) {
            $query->where('customer_level_id', $filters['level_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        $customers = $query->get();

        $filename = 'customers-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Customer ID', 'User ID', 'Company Name', 'GST Number', 'Contact Person', 
                'Mobile Number', 'Email', 'Customer Level', 'Credit Limit', 
                'Outstanding Amount', 'Available Credit', 'Overdue Amount', 
                'Allow Credit Beyond Limit', 'Account Status', 'Customer Status', 'Created At'
            ]);

            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->customer_number,
                    $customer->user_id ?? 'N/A',
                    $customer->company_name,
                    $customer->gst_number,
                    $customer->contact_person,
                    $customer->mobile_number,
                    $customer->email ?? 'N/A',
                    $customer->level->name ?? 'N/A',
                    $customer->credit_limit,
                    $customer->outstanding_amount,
                    $customer->available_credit,
                    $customer->overdue_amount,
                    $customer->allow_credit_beyond_limit ? 'Yes' : 'No',
                    ($customer->user && $customer->user->is_active) ? 'Active' : 'Inactive',
                    $customer->is_active ? 'Active' : 'Inactive',
                    $customer->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
