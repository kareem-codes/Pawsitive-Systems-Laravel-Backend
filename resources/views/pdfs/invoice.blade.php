<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            padding: 40px;
        }
        
        .header {
            margin-bottom: 40px;
            border-bottom: 3px solid #4a90e2;
            padding-bottom: 20px;
        }
        
        .company-info {
            float: left;
            width: 50%;
        }
        
        .company-info h1 {
            color: #4a90e2;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .company-info p {
            margin: 2px 0;
            color: #666;
        }
        
        .invoice-info {
            float: right;
            width: 45%;
            text-align: right;
        }
        
        .invoice-info h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .invoice-info p {
            margin: 5px 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-overdue { background-color: #f8d7da; color: #721c24; }
        .status-draft { background-color: #e2e3e5; color: #383d41; }
        .status-partially_paid { background-color: #cce5ff; color: #004085; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .billing-info {
            margin: 30px 0;
        }
        
        .billing-section {
            float: left;
            width: 48%;
        }
        
        .billing-section h3 {
            font-size: 14px;
            color: #4a90e2;
            margin-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 5px;
        }
        
        .billing-section p {
            margin: 3px 0;
        }
        
        .pet-info {
            float: right;
            width: 48%;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        
        .items-table thead {
            background-color: #4a90e2;
            color: white;
        }
        
        .items-table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .items-table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .totals .label {
            text-align: right;
            font-weight: normal;
            color: #666;
        }
        
        .totals .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .totals .grand-total {
            background-color: #4a90e2;
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        
        .notes {
            clear: both;
            margin-top: 40px;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #4a90e2;
        }
        
        .notes h4 {
            margin-bottom: 8px;
            color: #4a90e2;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header clearfix">
            <div class="company-info">
                <h1>Pawsitive Systems</h1>
                <p>123 Veterinary Street</p>
                <p>Pet City, PC 12345</p>
                <p>Phone: (555) 123-4567</p>
                <p>Email: info@pawsitive.com</p>
            </div>
            
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $invoice->invoice_date->format('M d, Y') }}</p>
                @if($invoice->due_date)
                    <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
                @endif
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                </span>
            </div>
        </div>
        
        <!-- Billing Information -->
        <div class="billing-info clearfix">
            <div class="billing-section">
                <h3>Bill To</h3>
                <p><strong>{{ $invoice->owner->name }}</strong></p>
                <p>{{ $invoice->owner->email }}</p>
                @if($invoice->owner->phone)
                    <p>{{ $invoice->owner->phone }}</p>
                @endif
                @if($invoice->owner->address)
                    <p>{{ $invoice->owner->address }}</p>
                @endif
            </div>
            
            @if($invoice->pet)
            <div class="pet-info">
                <h3>Pet Information</h3>
                <p><strong>Name:</strong> {{ $invoice->pet->name }}</p>
                <p><strong>Species:</strong> {{ ucfirst($invoice->pet->species) }}</p>
                <p><strong>Breed:</strong> {{ $invoice->pet->breed }}</p>
                @if($invoice->pet->date_of_birth)
                    <p><strong>Age:</strong> {{ \Carbon\Carbon::parse($invoice->pet->date_of_birth)->age }} years</p>
                @endif
            </div>
            @endif
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 15%;" class="text-right">Unit Price</th>
                    <th style="width: 15%;" class="text-right">Tax</th>
                    <th style="width: 15%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->description)
                            <br><small style="color: #666;">{{ $item->description }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->tax_amount, 2) }}</td>
                    <td class="text-right">${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="amount">${{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td class="label">Discount:</td>
                    <td class="amount" style="color: #28a745;">-${{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Tax:</td>
                    <td class="amount">${{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td style="padding: 12px;">TOTAL:</td>
                    <td style="padding: 12px;">${{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                @if($invoice->paid_amount > 0)
                <tr>
                    <td class="label">Paid:</td>
                    <td class="amount" style="color: #28a745;">${{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr style="font-size: 14px;">
                    <td class="label"><strong>Balance Due:</strong></td>
                    <td class="amount" style="color: #dc3545;"><strong>${{ number_format($invoice->balance_due, 2) }}</strong></td>
                </tr>
                @endif
            </table>
        </div>
        
        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This invoice was generated on {{ now()->format('M d, Y \a\t h:i A') }}</p>
        </div>
    </div>
</body>
</html>
