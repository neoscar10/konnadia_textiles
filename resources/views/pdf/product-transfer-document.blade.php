<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 14px;
            line-height: 1.4;
            background-color: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            border-bottom: 2px solid #5c44c4;
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand h1 {
            color: #5c44c4;
            margin: 0 0 5px 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .brand p {
            margin: 0;
            color: #666;
            font-size: 12px;
            font-weight: 500;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }
        .doc-title p {
            margin: 5px 0 0 0;
            font-size: 14px;
            font-weight: bold;
            color: #5c44c4;
            font-family: monospace;
        }
        .meta-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            font-size: 13px;
        }
        .meta-card {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        .meta-card h3 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #5c44c4;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 5px;
        }
        .meta-card p {
            margin: 5px 0;
        }
        .meta-card p span {
            font-weight: bold;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 12px;
        }
        th {
            background-color: #5c44c4;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px;
            text-align: left;
            border: 1px solid #5c44c4;
        }
        td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) td {
            background-color: #fcfcfc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .font-mono {
            font-family: monospace;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0edff !important;
        }
        .total-row td {
            border-top: 2px solid #5c44c4;
        }
        .notes-section {
            background-color: #fff9e6;
            border: 1px solid #ffe89e;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 40px;
            font-size: 13px;
        }
        .notes-section h4 {
            margin: 0 0 5px 0;
            color: #b78103;
        }
        .notes-section p {
            margin: 0;
            font-style: italic;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 20px;
        }
        .sig-line {
            width: 200px;
            text-align: center;
        }
        .sig-line div {
            border-top: 1px solid #333;
            margin-bottom: 5px;
            padding-top: 5px;
            font-weight: bold;
        }
        .sig-line p {
            margin: 0;
            font-size: 11px;
            color: #666;
        }
        .footer {
            margin-top: 50px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            text-align: center;
            font-size: 11px;
            color: #888;
        }
        .print-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #5c44c4;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        .btn:hover {
            background-color: #4d37a8;
        }
        .print-header {
            display: none;
        }
        @media print {
            body {
                padding: 0;
                background-color: #fff;
            }
            .container {
                border: none;
                padding: 0;
                box-shadow: none;
                max-width: 100%;
                margin-top: 30px;
            }
            .print-btn-container {
                display: none;
            }
            .print-header {
                display: block;
                position: fixed;
                top: -15px;
                left: 0;
                width: 100%;
                font-size: 10px;
                font-weight: bold;
                color: #5c44c4;
                border-bottom: 1px solid #eee;
                padding-bottom: 5px;
                font-family: monospace;
            }
            .print-header-left {
                float: left;
            }
            .print-header-right {
                float: right;
            }
        }
    </style>
</head>
<body>

    <div class="print-header">
        <div class="print-header-left">Stock Transfer - ID: {{ $transfer->transfer_number }}</div>
        <div class="print-header-right">Destination Shop: {{ $shop->name }} ({{ $shop->shop_code }})</div>
        <div style="clear: both;"></div>
    </div>

    <div class="print-btn-container">
        <button class="btn" onclick="window.print()">Print Transfer / Save PDF</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="brand">
                <h1>Sapnay Lifestyle</h1>
                <p>Internal Stock Logistics & Operations</p>
            </div>
            <div class="doc-title">
                <h2>Stock Transfer Document</h2>
                <p>{{ $transfer->transfer_number }}</p>
            </div>
        </div>

        <div class="meta-section">
            <div class="meta-card">
                <h3>Transfer Meta</h3>
                <p><span>Date:</span> {{ $transfer->transfer_date ? $transfer->transfer_date->format('Y-m-d') : 'N/A' }}</p>
                <p><span>Created By:</span> {{ $creator->name ?? 'System Admin' }}</p>
                <p><span>Stock Deducted:</span> {{ $transfer->stock_deducted ? 'Yes' : 'No' }}</p>
            </div>
            <div class="meta-card">
                <h3>Destination Retail Shop</h3>
                <p><span>Name:</span> {{ $shop->name }} ({{ $shop->shop_code }})</p>
                <p><span>Address:</span> {{ $shop->address }}, {{ $shop->city }}, {{ $shop->state }} - {{ $shop->pincode }}</p>
                <p><span>Contact:</span> {{ $shop->contact_person ?: 'N/A' }} ({{ $shop->contact_phone ?: 'N/A' }})</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">S/N</th>
                    <th style="width: 40%;">Product Description</th>
                    <th style="width: 15%;">SKU / Code</th>
                    <th style="width: 15%;">Variation</th>
                    <th style="width: 10%;">Unit</th>
                    <th style="width: 8%;" class="text-center">Qty</th>
                    <th style="width: 12%;" class="text-center">Base Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_title }}</strong>
                        @if($item->note)
                            <div style="font-size: 10px; color: #0284c7; margin-top: 3px; font-weight: bold; font-style: italic;">Remarks: {{ $item->note }}</div>
                        @endif
                    </td>
                    <td class="font-mono">{{ $item->product_sku }}</td>
                    <td>
                        @if($item->selected_options)
                            @php
                                $opts = [];
                                foreach ($item->selected_options as $k => $v) { $opts[] = "{$k}: {$v}"; }
                                echo implode(', ', $opts);
                            @endphp
                        @else
                            None
                        @endif
                    </td>
                    <td>{{ $item->unit_name ?: 'Piece' }}</td>
                    <td class="text-center font-mono">{{ $item->quantity }}</td>
                    <td class="text-center font-mono font-bold">{{ number_format($item->base_quantity, 2) }}</td>
                </tr>
                @endforeach
                
                <tr class="total-row">
                    <td colspan="5" class="text-right">Totals:</td>
                    <td class="text-center font-mono">{{ $items->sum('quantity') }}</td>
                    <td class="text-center font-mono text-primary">{{ number_format($items->sum('base_quantity'), 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($transfer->notes)
        <div class="notes-section">
            <h4>Transfer Memo / Notes:</h4>
            <p>{{ $transfer->notes }}</p>
        </div>
        @endif

        <div class="signature-section">
            <div class="sig-line">
                <div></div>
                <p>Prepared By Signature</p>
                <p style="font-size: 9px; font-style: italic;">{{ $creator->name ?? 'Operator' }}</p>
            </div>
            <div class="sig-line">
                <div></div>
                <p>Received By Signature</p>
                <p style="font-size: 9px; font-style: italic;">Store Manager</p>
            </div>
        </div>

        <div class="footer">
            This is an internal stock transfer document and not a sale invoice, customer receipt, or commercial order.
            <br>
            Generated on: {{ now()->format('Y-m-d H:i:s') }} UTC.
        </div>
    </div>

    <script>
        // Proactively open browser print dialog on load
        window.addEventListener('DOMContentLoaded', (event) => {
            // Delay print slightly so CSS and document render fully
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
