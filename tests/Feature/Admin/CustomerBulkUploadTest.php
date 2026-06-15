<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\User;
use App\Services\Customer\CustomerBulkUploadService;
use App\Services\Customer\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class CustomerBulkUploadTest extends TestCase
{
    use RefreshDatabase;

    private CustomerLevel $level;
    private CustomerBulkUploadService $uploadService;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        $this->level = CustomerLevel::create([
            'name' => 'Wholesale Distributor',
            'discount_percentage' => 10,
            'default_credit_limit' => 500000,
            'is_active' => true,
        ]);

        $this->uploadService = app(CustomerBulkUploadService::class);
    }

    public function test_bulk_row_normalization_and_warnings()
    {
        $rows = [
            [
                'temp_id' => 1,
                'company_name' => 'Bulk Company 1',
                'gst_number' => '12ABCDE3456F7GZ',
                'contact_person' => 'Raj Kumar',
                'mobile_number' => '9988776655',
                'email' => '', // blank email (warning)
                'customer_level_name' => 'Wholesale Distributor',
                'credit_limit' => '', // blank limit (warning)
                'allow_credit_beyond_limit' => 'No',
                'billing_address' => 'Mumbai Office',
                'active_status' => 'Active',
                'password' => '', // blank password (warning)
            ]
        ];

        $validated = $this->uploadService->validateRows($rows);

        $this->assertTrue($validated[0]['is_valid']);
        $this->assertCount(0, $validated[0]['errors']);
        $this->assertCount(3, $validated[0]['warnings']); // email blank, limit blank, password blank
    }

    public function test_bulk_row_validation_detects_missing_required_fields()
    {
        $rows = [
            [
                'temp_id' => 1,
                'company_name' => '', // error
                'gst_number' => '', // error
                'contact_person' => 'Raj Kumar',
                'mobile_number' => '', // error
                'email' => 'raj@test.com',
                'customer_level_name' => 'Invalid Level', // error
                'credit_limit' => '1000',
                'allow_credit_beyond_limit' => 'No',
                'billing_address' => '',
                'active_status' => 'Active',
                'password' => '',
            ]
        ];

        $validated = $this->uploadService->validateRows($rows);

        $this->assertFalse($validated[0]['is_valid']);
        $this->assertContains('Company Name is required.', $validated[0]['errors']);
        $this->assertContains('GST Number is required.', $validated[0]['errors']);
        $this->assertContains('Mobile Number is required.', $validated[0]['errors']);
        $this->assertContains('Customer Level "Invalid Level" was not found.', $validated[0]['errors']);
    }

    public function test_bulk_row_validation_detects_duplicates_in_uploaded_file()
    {
        $rows = [
            [
                'temp_id' => 1,
                'company_name' => 'Company A',
                'gst_number' => 'DUPE-GST',
                'contact_person' => 'Raj',
                'mobile_number' => '9988776655',
                'email' => 'dupe@email.com',
                'customer_level_name' => 'Wholesale Distributor',
                'credit_limit' => '50000',
                'allow_credit_beyond_limit' => 'No',
                'billing_address' => '',
                'active_status' => 'Active',
                'password' => '',
            ],
            [
                'temp_id' => 2,
                'company_name' => 'Company B',
                'gst_number' => 'DUPE-GST', // duplicate
                'contact_person' => 'Sam',
                'mobile_number' => '9988776655', // duplicate
                'email' => 'dupe@email.com', // duplicate
                'customer_level_name' => 'Wholesale Distributor',
                'credit_limit' => '50000',
                'allow_credit_beyond_limit' => 'No',
                'billing_address' => '',
                'active_status' => 'Active',
                'password' => '',
            ]
        ];

        $validated = $this->uploadService->validateRows($rows);

        $this->assertFalse($validated[0]['is_valid']);
        $this->assertFalse($validated[1]['is_valid']);

        $this->assertContains('Duplicate GST Number in uploaded file.', $validated[1]['errors']);
        $this->assertContains('Duplicate Mobile Number in uploaded file.', $validated[1]['errors']);
        $this->assertContains('Duplicate Email Address in uploaded file.', $validated[1]['errors']);
    }

    public function test_bulk_import_saves_valid_records_to_database_with_level_defaults()
    {
        $rows = [
            [
                'temp_id' => 1,
                'company_name' => 'Success Company',
                'gst_number' => '12ABCDE3456F7GZ',
                'contact_person' => 'Raj Kumar',
                'mobile_number' => '9988776655',
                'email' => 'raj@success.com',
                'customer_level_name' => 'Wholesale Distributor',
                'credit_limit' => '', // blank to test prefilling from Wholesale Distributor default (500000)
                'allow_credit_beyond_limit' => 'Yes',
                'billing_address' => 'Mumbai Office',
                'active_status' => 'Active',
                'password' => 'CustomPassword123',
            ]
        ];

        $report = $this->uploadService->importRows($rows);

        $this->assertEquals('Success', $report[0]['status']);
        $this->assertDatabaseHas('customers', [
            'company_name' => 'Success Company',
            'gst_number' => '12ABCDE3456F7GZ',
            'credit_limit' => 500000, // resolved from Level default
            'allow_credit_beyond_limit' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Raj Kumar',
            'email' => 'raj@success.com',
            'mobile_number' => '9988776655',
        ]);

        $user = User::where('mobile_number', '9988776655')->first();
        $this->assertTrue($user->hasRole('customer'));
    }
}
