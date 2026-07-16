<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Customer\CustomerTemplateService;
use App\Services\Customer\CustomerBulkUploadService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Validation\Rule;

#[Layout('components.admin.layout')]
class CustomerIndexPage extends Component
{
    use WithPagination, WithFileUploads;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $level_id = '';

    #[Url(history: true)]
    public $status = '';

    public $editingId = null;
    public $deleteId = null;
    public ?Customer $selectedCustomer = null;

    // Password Setup for Single Creation
    public $form = [
        'customer_level_id' => '',
        'company_name' => '',
        'gst_number' => '',
        'contact_person' => '',
        'mobile_number' => '',
        'email' => '',
        'credit_limit' => 0,
        'allow_credit_beyond_limit' => false,
        'billing_address' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'pincode' => '',
        'is_active' => true,
        'password_mode' => 'auto',
        'password' => '',
        'password_confirmation' => '',
    ];

    public $singleCreatedPassword = null; // Display generated password on success

    public $resetPasswordCustomerId = null;
    public $resetForm = [
        'password' => '',
        'password_confirmation' => '',
    ];

    // Bulk Upload State
    public $bulkFile;
    public $bulkStep = 1; // 1 = Upload, 2 = Review, 3 = Report
    public $parsedRows = [];
    public $validatedRows = [];
    public $importReport = [];

    // Edit row inside bulk upload review
    public $selectedRowToEdit = null;
    public $editingRow = [];

    public $levels = [];

    public function mount()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        } catch (\Exception $e) {
            // Silence exceptions if DB is not ready yet
        }
        $this->levels = CustomerLevel::active()->ordered()->get();
    }

    protected function rules()
    {
        $rules = [
            'form.customer_level_id' => ['required', 'exists:customer_levels,id'],
            'form.company_name' => ['required', 'string', 'max:180'],
            'form.gst_number' => [
                'required', 
                'string', 
                'max:30', 
                Rule::unique('customers', 'gst_number')->ignore($this->editingId)
            ],
            'form.contact_person' => ['required', 'string', 'max:150'],
            'form.mobile_number' => [
                'required', 
                'string', 
                'max:30', 
                Rule::unique('customers', 'mobile_number')->ignore($this->editingId),
                Rule::unique('users', 'mobile_number')->ignore($this->editingId ? Customer::find($this->editingId)?->user_id : null)
            ],
            'form.email' => [
                'nullable', 
                'email', 
                'max:150',
                Rule::unique('customers', 'email')->ignore($this->editingId),
                Rule::unique('users', 'email')->ignore($this->editingId ? Customer::find($this->editingId)?->user_id : null)
            ],
            'form.address' => ['nullable', 'string', 'max:500'],
            'form.city' => ['nullable', 'string', 'max:100'],
            'form.state' => ['nullable', 'string', 'max:100'],
            'form.pincode' => ['nullable', 'string', 'max:20'],
            'form.is_active' => ['boolean'],
        ];

        // Conditional password validation on single creation
        if (!$this->editingId && $this->form['password_mode'] === 'manual') {
            $rules['form.password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedLevelId()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedFormCustomerLevelId($value)
    {
        if ($value && !$this->editingId) {
            $level = CustomerLevel::find($value);
            if ($level) {
                $this->form['credit_limit'] = $level->default_credit_limit;
            }
        }
    }

    public function showAddChoice()
    {
        $this->dispatch('open-modal', 'add-choice');
    }

    public function startSingleCreation()
    {
        $this->dispatch('close-modal', 'add-choice');
        $this->resetForm();
        $this->dispatch('open-modal', 'add-customer');
    }

    public function startBulkUpload()
    {
        $this->dispatch('close-modal', 'add-choice');
        $this->bulkFile = null;
        $this->bulkStep = 1;
        $this->parsedRows = [];
        $this->validatedRows = [];
        $this->importReport = [];
        $this->dispatch('open-modal', 'bulk-upload');
    }

    public function downloadTemplate(CustomerTemplateService $templateService)
    {
        return $templateService->downloadTemplate();
    }

    public function uploadBulkFile(CustomerBulkUploadService $bulkUploadService)
    {
        $this->validate([
            'bulkFile' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $this->parsedRows = $bulkUploadService->parseUploadedFile($this->bulkFile);
        
        if (empty($this->parsedRows)) {
            $this->addError('bulkFile', 'The file is empty or does not contain valid headers.');
            return;
        }

        $this->validatedRows = $bulkUploadService->validateRows($this->parsedRows);
        $this->bulkStep = 2; // Transition to review step
    }

    public function removeBulkRow($tempId, CustomerBulkUploadService $bulkUploadService)
    {
        $this->parsedRows = array_values(array_filter($this->parsedRows, function ($row) use ($tempId) {
            return $row['temp_id'] !== $tempId;
        }));
        
        $this->validatedRows = $bulkUploadService->validateRows($this->parsedRows);
    }

    public function editBulkRow($tempId)
    {
        $row = collect($this->validatedRows)->firstWhere('temp_id', $tempId);
        if ($row) {
            $this->selectedRowToEdit = $tempId;
            $this->editingRow = $row;
            $this->dispatch('open-modal', 'edit-bulk-row');
        }
    }

    public function saveBulkRow(CustomerBulkUploadService $bulkUploadService)
    {
        $this->validate([
            'editingRow.company_name' => ['required', 'string', 'max:180'],
            'editingRow.gst_number' => ['required', 'string', 'max:30'],
            'editingRow.contact_person' => ['required', 'string', 'max:150'],
            'editingRow.mobile_number' => ['required', 'string', 'max:30'],
            'editingRow.email' => ['nullable', 'email', 'max:150'],
            'editingRow.customer_level_name' => ['required', 'string'],
            'editingRow.credit_limit' => ['nullable', 'numeric', 'min:0'],
            'editingRow.password' => ['nullable', 'string', 'min:8'],
        ]);

        $this->parsedRows = array_map(function ($row) {
            if ($row['temp_id'] === $this->selectedRowToEdit) {
                return array_merge($row, [
                    'company_name' => $this->editingRow['company_name'],
                    'gst_number' => $this->editingRow['gst_number'],
                    'contact_person' => $this->editingRow['contact_person'],
                    'mobile_number' => $this->editingRow['mobile_number'],
                    'email' => $this->editingRow['email'],
                    'customer_level_name' => $this->editingRow['customer_level_name'],
                    'credit_limit' => $this->editingRow['credit_limit'],
                    'allow_credit_beyond_limit' => $this->editingRow['allow_credit_beyond_limit'],
                    'billing_address' => $this->editingRow['billing_address'],
                    'active_status' => $this->editingRow['active_status'],
                    'password' => $this->editingRow['password'],
                ]);
            }
            return $row;
        }, $this->parsedRows);

        $this->validatedRows = $bulkUploadService->validateRows($this->parsedRows);
        $this->dispatch('close-modal', 'edit-bulk-row');
        $this->selectedRowToEdit = null;
    }

    public function importBulkRows(CustomerBulkUploadService $bulkUploadService)
    {
        // Import only valid rows
        $validRows = array_filter($this->validatedRows, function ($row) {
            return $row['is_valid'];
        });

        if (empty($validRows)) {
            $this->dispatch('toast', message: 'No valid rows found to import.', type: 'error');
            return;
        }

        $this->importReport = $bulkUploadService->importRows($validRows);
        $this->bulkStep = 3; // Transition to report step
    }

    public function downloadImportReport()
    {
        if (empty($this->importReport)) {
            return;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Report');

        $headers = [
            'Customer ID', 'Company Name', 'Contact Person', 'Mobile Number', 
            'Email', 'Status', 'Message', 'Generated Password / Info'
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0F2744'],
            ]
        ];

        $colIdx = 1;
        foreach ($headers as $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getColumnDimension($colLetter)->setWidth(25);
            $sheet->getStyle($colLetter . '1')->applyFromArray($headerStyle);
            $colIdx++;
        }

        $rowIdx = 2;
        foreach ($this->importReport as $row) {
            $sheet->setCellValue('A' . $rowIdx, $row['customer_id']);
            $sheet->setCellValue('B' . $rowIdx, $row['company_name']);
            $sheet->setCellValue('C' . $rowIdx, $row['contact_person']);
            $sheet->setCellValue('D' . $rowIdx, $row['mobile_number']);
            $sheet->setCellValue('E' . $rowIdx, $row['email']);
            $sheet->setCellValue('F' . $rowIdx, $row['status']);
            $sheet->setCellValue('G' . $rowIdx, $row['message']);
            $sheet->setCellValue('H' . $rowIdx, $row['generated_password']);
            $rowIdx++;
        }

        $filename = 'customer-import-report-' . date('Y-m-d-His') . '.xlsx';
        
        $headersDownload = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ];

        return response()->stream(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, $headersDownload);
    }

    public function create()
    {
        $this->showAddChoice();
    }

    public function showDetails($id)
    {
        $this->selectedCustomer = Customer::with(['level', 'user', 'creditHoldBy'])->findOrFail($id);
        $this->dispatch('open-modal', 'customer-details');
    }

    public function edit(Customer $customer)
    {
        $this->dispatch('close-modal', 'customer-details');
        $this->resetValidation();
        $this->editingId = $customer->id;
        $this->form = [
            'customer_level_id' => $customer->customer_level_id,
            'company_name' => $customer->company_name,
            'gst_number' => $customer->gst_number,
            'contact_person' => $customer->contact_person,
            'mobile_number' => $customer->mobile_number,
            'email' => $customer->email,
            'credit_limit' => $customer->credit_limit,
            'allow_credit_beyond_limit' => $customer->allow_credit_beyond_limit,
            'billing_address' => $customer->billing_address,
            'address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'pincode' => $customer->pincode,
            'is_active' => $customer->is_active,
            'password_mode' => 'auto',
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->dispatch('open-modal', 'add-customer');
    }

    public function save(CustomerService $service)
    {
        $this->validate();

        // Compile billing address from details
        $this->form['billing_address'] = trim(
            ($this->form['address'] ?? '') . "\n" . 
            ($this->form['city'] ?? '') . ", " . 
            ($this->form['state'] ?? '') . " - " . 
            ($this->form['pincode'] ?? '')
        );

        if ($this->editingId) {
            $customer = Customer::findOrFail($this->editingId);
            $service->update($customer, $this->form);
            $this->dispatch('toast', message: 'Customer updated successfully.', type: 'success');
            $this->dispatch('close-modal', 'add-customer');
            $this->resetForm();
        } else {
            $customer = $service->create($this->form);
            
            if (isset($customer->generated_password)) {
                $this->singleCreatedPassword = $customer->generated_password;
                $this->dispatch('close-modal', 'add-customer');
                $this->dispatch('open-modal', 'single-creation-success');
            } else {
                $this->dispatch('toast', message: 'Customer created successfully.', type: 'success');
                $this->dispatch('close-modal', 'add-customer');
                $this->resetForm();
            }
        }
    }

    public function closeSuccessModal()
    {
        $this->dispatch('close-modal', 'single-creation-success');
        $this->resetForm();
    }

    public function startResetPassword($customerId)
    {
        $this->resetPasswordCustomerId = $customerId;
        $this->resetForm = [
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->resetValidation();
        $this->dispatch('open-modal', 'reset-password-modal');
    }

    public function resetPassword()
    {
        $this->validate([
            'resetForm.password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::findOrFail($this->resetPasswordCustomerId);
        $user = $customer->user;
        if ($user) {
            $user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($this->resetForm['password'])
            ]);
            $this->dispatch('toast', message: 'Password updated successfully.', type: 'success');
        } else {
            $this->dispatch('toast', message: 'User account not found for this customer.', type: 'error');
        }

        $this->dispatch('close-modal', 'reset-password-modal');
        $this->reset(['resetPasswordCustomerId', 'resetForm']);
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('open-modal', 'delete-customer');
    }

    public function delete(CustomerService $service)
    {
        if ($this->deleteId) {
            $customer = Customer::findOrFail($this->deleteId);
            $service->delete($customer);
            $this->dispatch('toast', message: 'Customer deleted successfully.', type: 'success');
            $this->dispatch('close-modal', 'delete-customer');
            $this->deleteId = null;
        }
    }

    public function toggleStatus(Customer $customer, CustomerService $service)
    {
        $service->toggleStatus($customer);
        $message = $customer->is_active ? 'Customer activated successfully.' : 'Customer deactivated successfully.';
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function export(CustomerService $service)
    {
        return response()->streamDownload(function () use ($service) {
            $response = $service->exportCsv([
                'search' => $this->search,
                'level_id' => $this->level_id,
                'status' => $this->status,
            ]);
            $response->sendContent();
        }, 'customers-' . date('Y-m-d') . '.csv');
    }

    private function resetForm()
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->singleCreatedPassword = null;
        $this->form = [
            'customer_level_id' => '',
            'company_name' => '',
            'gst_number' => '',
            'contact_person' => '',
            'mobile_number' => '',
            'email' => '',
            'credit_limit' => 0,
            'allow_credit_beyond_limit' => false,
            'billing_address' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'pincode' => '',
            'is_active' => true,
            'password_mode' => 'auto',
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    public function render(CustomerService $service)
    {
        $customers = $service->list([
            'search' => $this->search,
            'level_id' => $this->level_id,
            'status' => $this->status,
        ], 10);

        return view('livewire.admin.customers.customer-index-page', [
            'customers' => $customers,
        ]);
    }
}
