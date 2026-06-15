<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerBulkUploadService
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Parse uploaded CSV or Excel file.
     */
    public function parseUploadedFile(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            return [];
        }

        // The first row contains the headers
        $headerRow = array_map(function ($val) {
            return trim($val);
        }, array_shift($rows));

        // Map from header column text to normalized field name
        $headerMap = [
            'Company Name *' => 'company_name',
            'Company Name' => 'company_name',
            'GST Number *' => 'gst_number',
            'GST Number' => 'gst_number',
            'Contact Person *' => 'contact_person',
            'Contact Person' => 'contact_person',
            'Mobile Number *' => 'mobile_number',
            'Mobile Number' => 'mobile_number',
            'Email Address' => 'email',
            'Email' => 'email',
            'Customer Level *' => 'customer_level_name',
            'Customer Level' => 'customer_level_name',
            'Credit Limit' => 'credit_limit',
            'Allow Credit Beyond Limit' => 'allow_credit_beyond_limit',
            'Billing Address' => 'billing_address',
            'Active Status' => 'active_status',
            'Password' => 'password',
        ];

        $fieldMap = [];
        foreach ($headerRow as $colLetter => $headerText) {
            if (isset($headerMap[$headerText])) {
                $fieldMap[$colLetter] = $headerMap[$headerText];
            }
        }

        $parsedRows = [];
        $tempId = 1;
        foreach ($rows as $row) {
            $isEmpty = true;
            foreach ($row as $val) {
                if ($val !== null && trim($val) !== '') {
                    $isEmpty = false;
                    break;
                }
            }

            if ($isEmpty) {
                continue;
            }

            $parsedRow = [
                'temp_id' => $tempId++,
                'company_name' => '',
                'gst_number' => '',
                'contact_person' => '',
                'mobile_number' => '',
                'email' => '',
                'customer_level_name' => '',
                'credit_limit' => '',
                'allow_credit_beyond_limit' => 'No',
                'billing_address' => '',
                'active_status' => 'Active',
                'password' => '',
            ];

            foreach ($row as $colLetter => $val) {
                if (isset($fieldMap[$colLetter])) {
                    $parsedRow[$fieldMap[$colLetter]] = $val !== null ? trim($val) : '';
                }
            }

            $parsedRows[] = $parsedRow;
        }

        return $parsedRows;
    }

    /**
     * Validate all rows.
     */
    public function validateRows(array $rows): array
    {
        $validatedRows = [];
        foreach ($rows as $row) {
            $validatedRows[] = $this->revalidateRow($row, $rows);
        }
        return $validatedRows;
    }

    /**
     * Revalidate a single row.
     */
    public function revalidateRow(array $row, array $allRows = []): array
    {
        $errors = [];
        $warnings = [];

        if (empty($row['company_name'])) {
            $errors[] = 'Company Name is required.';
        }
        if (empty($row['gst_number'])) {
            $errors[] = 'GST Number is required.';
        }
        if (empty($row['contact_person'])) {
            $errors[] = 'Contact Person is required.';
        }
        if (empty($row['mobile_number'])) {
            $errors[] = 'Mobile Number is required.';
        }
        if (empty($row['customer_level_name'])) {
            $errors[] = 'Customer Level is required.';
        }

        if (!empty($row['password']) && strlen($row['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if (!empty($row['gst_number'])) {
            $gstExists = Customer::where('gst_number', $row['gst_number'])->exists();
            if ($gstExists) {
                $errors[] = 'GST Number already exists in database.';
            }
        }

        if (!empty($row['mobile_number'])) {
            $mobileExists = User::where('mobile_number', $row['mobile_number'])->exists();
            if (!$mobileExists) {
                $mobileExists = Customer::where('mobile_number', $row['mobile_number'])->exists();
            }
            if ($mobileExists) {
                $errors[] = 'Mobile Number already exists in database.';
            }
        }

        if (!empty($row['email'])) {
            $emailExists = User::where('email', $row['email'])->exists();
            if ($emailExists) {
                $errors[] = 'Email Address already exists in database.';
            }
        }

        $level = null;
        if (!empty($row['customer_level_name'])) {
            $level = $this->resolveCustomerLevel($row['customer_level_name']);
            if (!$level) {
                $errors[] = 'Customer Level "' . $row['customer_level_name'] . '" was not found.';
            }
        }

        $gstDupes = 0;
        $mobileDupes = 0;
        $emailDupes = 0;
        foreach ($allRows as $r) {
            if ($r['temp_id'] !== $row['temp_id']) {
                if (!empty($row['gst_number']) && strtolower($r['gst_number']) === strtolower($row['gst_number'])) {
                    $gstDupes++;
                }
                if (!empty($row['mobile_number']) && $r['mobile_number'] === $row['mobile_number']) {
                    $mobileDupes++;
                }
                if (!empty($row['email']) && strtolower($r['email']) === strtolower($row['email'])) {
                    $emailDupes++;
                }
            }
        }

        if ($gstDupes > 0) {
            $errors[] = 'Duplicate GST Number in uploaded file.';
        }
        if ($mobileDupes > 0) {
            $errors[] = 'Duplicate Mobile Number in uploaded file.';
        }
        if ($emailDupes > 0) {
            $errors[] = 'Duplicate Email Address in uploaded file.';
        }

        if (empty($row['email'])) {
            $warnings[] = 'Email is blank. Customer can still be created.';
        }
        if (empty($row['credit_limit'])) {
            if ($level) {
                $warnings[] = 'Credit Limit is blank. Default level credit limit (₹' . number_format($level->default_credit_limit, 2) . ') will be used.';
            } else {
                $warnings[] = 'Credit Limit is blank. Level limit will apply.';
            }
        }
        if (empty($row['password'])) {
            $warnings[] = 'Password is blank. System will generate one.';
        }

        $row['errors'] = $errors;
        $row['warnings'] = $warnings;
        $row['is_valid'] = empty($errors);

        return $row;
    }

    /**
     * Import multiple rows.
     */
    public function importRows(array $rows): array
    {
        $report = [];

        foreach ($rows as $row) {
            $validated = $this->revalidateRow($row, $rows);

            if (!$validated['is_valid']) {
                $report[] = [
                    'company_name' => $row['company_name'],
                    'contact_person' => $row['contact_person'],
                    'mobile_number' => $row['mobile_number'],
                    'email' => $row['email'],
                    'status' => 'Failed',
                    'message' => implode(' ', $validated['errors']),
                    'customer_id' => 'N/A',
                    'generated_password' => '',
                ];
                continue;
            }

            try {
                $level = $this->resolveCustomerLevel($row['customer_level_name']);
                
                $limit = trim($row['credit_limit']);
                if ($limit === '') {
                    $limit = $level->default_credit_limit;
                }

                $allowCredit = $this->normalizeBoolean($row['allow_credit_beyond_limit'], false);
                $isActive = $this->normalizeBoolean($row['active_status'], true);

                $data = [
                    'company_name' => $row['company_name'],
                    'gst_number' => $row['gst_number'],
                    'contact_person' => $row['contact_person'],
                    'mobile_number' => $row['mobile_number'],
                    'email' => $row['email'],
                    'customer_level_id' => $level->id,
                    'credit_limit' => $limit,
                    'allow_credit_beyond_limit' => $allowCredit,
                    'billing_address' => $row['billing_address'],
                    'is_active' => $isActive,
                ];

                if (!empty($row['password'])) {
                    $data['password_mode'] = 'manual';
                    $data['password'] = $row['password'];
                } else {
                    $data['password_mode'] = 'auto';
                }

                $customer = $this->customerService->create($data);

                $report[] = [
                    'company_name' => $row['company_name'],
                    'contact_person' => $row['contact_person'],
                    'mobile_number' => $row['mobile_number'],
                    'email' => $row['email'],
                    'status' => 'Success',
                    'message' => 'Imported successfully.',
                    'customer_id' => $customer->customer_number,
                    'generated_password' => $customer->generated_password ?? 'Provided by admin',
                ];

            } catch (\Exception $e) {
                $report[] = [
                    'company_name' => $row['company_name'],
                    'contact_person' => $row['contact_person'],
                    'mobile_number' => $row['mobile_number'],
                    'email' => $row['email'],
                    'status' => 'Failed',
                    'message' => 'System error: ' . $e->getMessage(),
                    'customer_id' => 'N/A',
                    'generated_password' => '',
                ];
            }
        }

        return $report;
    }

    /**
     * Resolve boolean value representation.
     */
    public function normalizeBoolean($value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $val = strtolower(trim($value));
        if (in_array($val, ['yes', 'active', 'true', '1', 't', 'y'])) {
            return true;
        }
        if (in_array($val, ['no', 'inactive', 'false', '0', 'f', 'n'])) {
            return false;
        }

        return $default;
    }

    /**
     * Resolve Customer Level by Name.
     */
    public function resolveCustomerLevel(string $name): ?CustomerLevel
    {
        return CustomerLevel::where('name', trim($name))->first();
    }
}
