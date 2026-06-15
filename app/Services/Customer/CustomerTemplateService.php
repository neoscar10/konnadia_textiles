<?php

namespace App\Services\Customer;

use App\Models\CustomerLevel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerTemplateService
{
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        // ----------------------------------------------------
        // SHEET 1: Customers
        // ----------------------------------------------------
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customers');

        // Styles
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0F2744'], // Dark Navy
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ];

        $requiredHeaderStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFC89B3C'], // Amber Gold
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ];

        // Headers
        $headers = [
            'Company Name *' => 28,
            'GST Number *' => 22,
            'Contact Person *' => 24,
            'Mobile Number *' => 20,
            'Email Address' => 30,
            'Customer Level *' => 26,
            'Credit Limit' => 18,
            'Allow Credit Beyond Limit' => 26,
            'Billing Address' => 40,
            'Active Status' => 18,
            'Password' => 22,
        ];

        $colIdx = 1;
        foreach ($headers as $header => $width) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getColumnDimension($colLetter)->setWidth($width);
            
            // Highlight required columns in gold, optional in navy
            if (str_contains($header, '*')) {
                $sheet->getStyle($colLetter . '1')->applyFromArray($requiredHeaderStyle);
            } else {
                $sheet->getStyle($colLetter . '1')->applyFromArray($headerStyle);
            }
            $colIdx++;
        }

        $sheet->getRowDimension('1')->setRowHeight(28);
        $sheet->freezePane('A2'); // Freeze header row
        $sheet->setAutoFilter('A1:K1'); // Enable filters

        // Sample Data Row
        $sampleData = [
            ['Acme Wholesalers Ltd', '27AAACA1111A1Z1', 'John Doe', '+919988776655', 'john@acme.com', 'Wholesale Distributor', 500000, 'No', '123 Business Lane, Sector 5, Mumbai', 'Active', 'ManualPass123'],
            ['Retail Express', '27BBBCB2222B2Z2', 'Jane Smith', '+919988776644', 'jane@retail.com', 'Retailer', '', 'Yes', '456 Market Road, Bangalore', 'Inactive', ''],
        ];

        $rowIdx = 2;
        foreach ($sampleData as $rowData) {
            $colIdx = 1;
            foreach ($rowData as $val) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . $rowIdx, $val);
                $colIdx++;
            }
            $rowIdx++;
        }

        // Add dropdowns / data validation where feasible
        // Let's add Yes/No validations for "Allow Credit" and status
        for ($r = 2; $r <= 100; $r++) {
            $validation = $sheet->getCell('H' . $r)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"Yes,No"');

            $validationStatus = $sheet->getCell('J' . $r)->getDataValidation();
            $validationStatus->setType(DataValidation::TYPE_LIST);
            $validationStatus->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validationStatus->setAllowBlank(true);
            $validationStatus->setShowInputMessage(true);
            $validationStatus->setShowErrorMessage(true);
            $validationStatus->setShowDropDown(true);
            $validationStatus->setFormula1('"Active,Inactive"');
        }

        // ----------------------------------------------------
        // SHEET 2: Customer Levels Reference
        // ----------------------------------------------------
        $levelSheet = $spreadsheet->createSheet();
        $levelSheet->setTitle('Customer Levels');

        $levelHeaders = ['Level Name', 'Default Credit Limit', 'Discount %'];
        $colIdx = 1;
        foreach ($levelHeaders as $header) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $levelSheet->setCellValue($colLetter . '1', $header);
            $levelSheet->getColumnDimension($colLetter)->setWidth(25);
            $levelSheet->getStyle($colLetter . '1')->applyFromArray($headerStyle);
            $colIdx++;
        }
        $levelSheet->getRowDimension('1')->setRowHeight(28);

        $levels = CustomerLevel::active()->ordered()->get();
        $rowIdx = 2;
        foreach ($levels as $level) {
            $levelSheet->setCellValue('A' . $rowIdx, $level->name);
            $levelSheet->setCellValue('B' . $rowIdx, $level->default_credit_limit);
            $levelSheet->setCellValue('C' . $rowIdx, $level->discount_percentage . '%');
            $rowIdx++;
        }

        // ----------------------------------------------------
        // SHEET 3: Instructions
        // ----------------------------------------------------
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructionSheet->getColumnDimension('A')->setWidth(35);
        $instructionSheet->getColumnDimension('B')->setWidth(80);

        $instructionSheet->setCellValue('A1', 'Column Name');
        $instructionSheet->setCellValue('B1', 'Rules & Allowed Values');
        $instructionSheet->getStyle('A1:B1')->applyFromArray($headerStyle);
        $instructionSheet->getRowDimension('1')->setRowHeight(28);

        $instructions = [
            ['Company Name *', 'Required. Text representation of the company name. Max 180 chars.'],
            ['GST Number *', 'Required. GSTIN number of the customer. Max 30 chars. Must be unique.'],
            ['Contact Person *', 'Required. Full name of the primary contact person. Max 150 chars.'],
            ['Mobile Number *', 'Required. Format: +919999988888 or 9999988888. Must be unique.'],
            ['Email Address', 'Optional. Must be a valid email format. Unique if provided.'],
            ['Customer Level *', 'Required. Must match one of the level names exactly from the "Customer Levels" tab.'],
            ['Credit Limit', 'Optional. Defaults to the level default if left blank. Numeric value only.'],
            ['Allow Credit Beyond Limit', 'Optional. Allowed: Yes, No. Default: No.'],
            ['Billing Address', 'Optional. Full address details. Max 1000 chars.'],
            ['Active Status', 'Optional. Allowed: Active, Inactive. Default: Active.'],
            ['Password', 'Optional. Manual password (min 8 characters). If left blank, the system auto-generates a secure password.'],
        ];

        $rowIdx = 2;
        foreach ($instructions as $inst) {
            $instructionSheet->setCellValue('A' . $rowIdx, $inst[0]);
            $instructionSheet->setCellValue('B' . $rowIdx, $inst[1]);
            $instructionSheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
            $rowIdx++;
        }

        // Set active sheet back to main Customers sheet
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'kannodia-customer-upload-template.xlsx';
        
        $headersDownload = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ];

        return response()->stream(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, $headersDownload);
    }
}
