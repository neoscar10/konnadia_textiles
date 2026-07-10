<?php

namespace App\Livewire\Admin\Admins;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

#[Layout('components.admin.layout')]
class AdminIndexPage extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $editingId = null;

    public function mount()
    {
        $this->search = '';
        $this->status = '';

        if (request()->has('search') || request()->has('status')) {
            return redirect()->route('admin.admins.index');
        }
    }

    public $form = [
        'name' => '',
        'email' => '',
        'mobile_number' => '',
        'password' => '',
        'password_confirmation' => '',
        'is_active' => true,
    ];

    public $selectedPermissions = [];

    public $deleteId = null;

    // Available page permissions
    public $availablePermissions = [
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
    ];

    protected function rules()
    {
        return [
            'form.name' => ['required', 'string', 'max:150'],
            'form.email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($this->editingId)
            ],
            'form.mobile_number' => ['nullable', 'string', 'max:20'],
            'form.password' => $this->editingId ? ['nullable', 'string', 'min:6', 'same:form.password_confirmation'] : ['required', 'string', 'min:6', 'same:form.password_confirmation'],
            'form.password_confirmation' => ['nullable'],
            'form.is_active' => ['boolean'],
            'selectedPermissions' => ['array'],
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'add-admin');
    }

    public function edit(User $user)
    {
        // Safety guard: Super Admin cannot be edited
        if ($user->hasRole('super_admin')) {
            $this->dispatch('toast', message: 'Unauthorized action on Super Admin.', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->editingId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => $user->is_active,
        ];
        
        $this->selectedPermissions = $user->permissions->pluck('name')->toArray();
        $this->dispatch('open-modal', 'add-admin');
    }

    public function save()
    {
        $this->validate();

        if (in_array('access orders', $this->selectedPermissions)) {
            $hasManufactured = in_array('manage manufactured orders', $this->selectedPermissions);
            $hasRetail = in_array('manage retail orders', $this->selectedPermissions);
            if (!$hasManufactured && !$hasRetail) {
                $this->addError('orderScope', 'Please select at least one product type scope (Manufactured or Retail/Bought) for Orders access.');
                return;
            }
        } else {
            // Remove permissions if access orders is not granted
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, [
                'manage manufactured orders',
                'manage retail orders'
            ]));
        }

        // Ensure all selected permissions exist in the database for both guards to prevent Spatie errors
        foreach ($this->selectedPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            
            // Safety guard: Super Admin cannot be edited
            if ($user->hasRole('super_admin')) {
                $this->dispatch('toast', message: 'Unauthorized action on Super Admin.', type: 'error');
                return;
            }

            $updateData = [
                'name' => $this->form['name'],
                'email' => $this->form['email'],
                'mobile_number' => $this->form['mobile_number'],
                'is_active' => $this->form['is_active'],
            ];

            if (!empty($this->form['password'])) {
                $updateData['password'] = Hash::make($this->form['password']);
            }

            $user->update($updateData);
            $user->syncPermissions($this->selectedPermissions);

            $this->dispatch('toast', message: 'Admin account updated successfully.', type: 'success');
        } else {
            $user = User::create([
                'name' => $this->form['name'],
                'email' => $this->form['email'],
                'mobile_number' => $this->form['mobile_number'],
                'password' => Hash::make($this->form['password']),
                'is_active' => $this->form['is_active'],
            ]);

            $user->assignRole('admin');
            $user->syncPermissions($this->selectedPermissions);

            $this->dispatch('toast', message: 'Admin account created successfully.', type: 'success');
        }

        $this->dispatch('close-modal', 'add-admin');
        $this->resetForm();
    }

    public function toggleStatus(User $user)
    {
        // Safety guard: Super Admin cannot be restricted
        if ($user->hasRole('super_admin')) {
            $this->dispatch('toast', message: 'Super Admin accounts cannot be restricted.', type: 'error');
            return;
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $message = $user->is_active ? 'Admin account enabled successfully.' : 'Admin account restricted successfully.';
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function confirmDelete($id)
    {
        $user = User::findOrFail($id);
        if ($user->hasRole('super_admin')) {
            $this->dispatch('toast', message: 'Super Admin accounts cannot be deleted.', type: 'error');
            return;
        }

        $this->deleteId = $id;
        $this->dispatch('open-modal', 'delete-admin');
    }

    public function delete()
    {
        if ($this->deleteId) {
            $user = User::findOrFail($this->deleteId);
            
            // Safety guard: Super Admin cannot be deleted
            if ($user->hasRole('super_admin')) {
                $this->dispatch('toast', message: 'Super Admin accounts cannot be deleted.', type: 'error');
                return;
            }

            $user->delete();
            $this->dispatch('toast', message: 'Admin account deleted successfully.', type: 'success');
            $this->dispatch('close-modal', 'delete-admin');
            $this->deleteId = null;
        }
    }

    private function resetForm()
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'email' => '',
            'mobile_number' => '',
            'password' => '',
            'password_confirmation' => '',
            'is_active' => true,
        ];
        $this->selectedPermissions = [];
    }

    public function render()
    {
        // Fetch only admins, excluding any super_admins
        $query = User::role('admin')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('mobile_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->status !== '') {
            $query->where('is_active', (bool)$this->status);
        }

        $admins = $query->orderBy('name')->paginate(10);

        return view('livewire.admin.admins.admin-index-page', [
            'admins' => $admins
        ])->title('Admins Management');
    }
}
