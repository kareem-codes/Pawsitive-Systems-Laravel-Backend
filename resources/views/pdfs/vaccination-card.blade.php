<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Card - {{ $pet->name }}</title>
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
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 4px solid #28a745;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #28a745;
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .header h2 {
            color: #666;
            font-size: 18px;
            font-weight: normal;
        }
        
        .clinic-info {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .clinic-info p {
            margin: 2px 0;
        }
        
        .pet-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #28a745;
        }
        
        .pet-details h3 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .detail-grid {
            display: table;
            width: 100%;
        }
        
        .detail-row {
            display: table-row;
        }
        
        .detail-label {
            display: table-cell;
            width: 30%;
            padding: 8px 0;
            font-weight: bold;
            color: #555;
        }
        
        .detail-value {
            display: table-cell;
            padding: 8px 0;
        }
        
        .vaccinations-section {
            margin-top: 30px;
        }
        
        .vaccinations-section h3 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 8px;
        }
        
        .vaccinations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .vaccinations-table thead {
            background-color: #28a745;
            color: white;
        }
        
        .vaccinations-table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        
        .vaccinations-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }
        
        .vaccinations-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .status-completed {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-scheduled {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-overdue {
            color: #dc3545;
            font-weight: bold;
        }
        
        .no-records {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
        
        .next-due {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin-top: 20px;
        }
        
        .next-due h4 {
            color: #856404;
            margin-bottom: 8px;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .signature-section {
            margin-top: 30px;
        }
        
        .signature-box {
            float: left;
            width: 45%;
            margin-right: 5%;
        }
        
        .signature-box:last-child {
            margin-right: 0;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 10px;
            color: #666;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .footer-text {
            text-align: center;
            color: #666;
            font-size: 10px;
            margin-top: 20px;
        }
        
        .important-note {
            background-color: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #2196f3;
            margin-top: 20px;
        }
        
        .important-note h4 {
            color: #0d47a1;
            margin-bottom: 8px;
        }
        
        .important-note p {
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🐾 VACCINATION CARD 🐾</h1>
            <h2>Official Pet Vaccination Record</h2>
        </div>
        
        <!-- Clinic Info -->
        <div class="clinic-info">
            <p><strong>Pawsitive Systems Veterinary Clinic</strong></p>
            <p>123 Veterinary Street, Pet City, PC 12345</p>
            <p>Phone: (555) 123-4567 | Email: info@pawsitive.com</p>
        </div>
        
        <!-- Pet Details -->
        <div class="pet-details">
            <h3>Pet Information</h3>
            <div class="detail-grid">
                <div class="detail-row">
                    <div class="detail-label">Pet Name:</div>
                    <div class="detail-value"><strong>{{ $pet->name }}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Species:</div>
                    <div class="detail-value">{{ ucfirst($pet->species) }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Breed:</div>
                    <div class="detail-value">{{ $pet->breed }}</div>
                </div>
                @if($pet->color)
                <div class="detail-row">
                    <div class="detail-label">Color:</div>
                    <div class="detail-value">{{ $pet->color }}</div>
                </div>
                @endif
                @if($pet->date_of_birth)
                <div class="detail-row">
                    <div class="detail-label">Date of Birth:</div>
                    <div class="detail-value">{{ \Carbon\Carbon::parse($pet->date_of_birth)->format('M d, Y') }} ({{ \Carbon\Carbon::parse($pet->date_of_birth)->age }} years old)</div>
                </div>
                @endif
                @if($pet->microchip_number)
                <div class="detail-row">
                    <div class="detail-label">Microchip #:</div>
                    <div class="detail-value">{{ $pet->microchip_number }}</div>
                </div>
                @endif
                <div class="detail-row">
                    <div class="detail-label">Owner:</div>
                    <div class="detail-value">{{ $pet->owner->name }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Contact:</div>
                    <div class="detail-value">{{ $pet->owner->email }} @if($pet->owner->phone) | {{ $pet->owner->phone }}@endif</div>
                </div>
            </div>
        </div>
        
        <!-- Vaccinations Section -->
        <div class="vaccinations-section">
            <h3>Vaccination History</h3>
            
            @if($pet->vaccinations->count() > 0)
                <table class="vaccinations-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Vaccine Name</th>
                            <th style="width: 15%;">Date Given</th>
                            <th style="width: 15%;">Next Due</th>
                            <th style="width: 12%;">Batch Number</th>
                            <th style="width: 20%;">Administered By</th>
                            <th style="width: 18%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pet->vaccinations as $vaccination)
                        <tr>
                            <td><strong>{{ $vaccination->vaccine_name }}</strong></td>
                            <td>{{ \Carbon\Carbon::parse($vaccination->administered_date)->format('M d, Y') }}</td>
                            <td>
                                @if($vaccination->next_due_date)
                                    {{ \Carbon\Carbon::parse($vaccination->next_due_date)->format('M d, Y') }}
                                    @if(\Carbon\Carbon::parse($vaccination->next_due_date)->isPast())
                                        <span class="status-overdue">(Overdue)</span>
                                    @elseif(\Carbon\Carbon::parse($vaccination->next_due_date)->diffInDays(now()) <= 30)
                                        <span class="status-scheduled">(Due Soon)</span>
                                    @endif
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $vaccination->batch_number ?? 'N/A' }}</td>
                            <td>
                                @if($vaccination->administeredBy)
                                    Dr. {{ $vaccination->administeredBy->name }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td style="font-size: 10px;">{{ $vaccination->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                @php
                    $upcomingVaccinations = $pet->vaccinations->filter(function($v) {
                        return $v->next_due_date && \Carbon\Carbon::parse($v->next_due_date)->isFuture();
                    })->sortBy('next_due_date');
                @endphp
                
                @if($upcomingVaccinations->count() > 0)
                <div class="next-due">
                    <h4>⚠️ Upcoming Vaccinations</h4>
                    @foreach($upcomingVaccinations->take(3) as $vaccination)
                        <p><strong>{{ $vaccination->vaccine_name }}</strong> - Due: {{ \Carbon\Carbon::parse($vaccination->next_due_date)->format('M d, Y') }}</p>
                    @endforeach
                </div>
                @endif
            @else
                <div class="no-records">
                    <p>No vaccination records available for this pet.</p>
                </div>
            @endif
        </div>
        
        <!-- Important Note -->
        <div class="important-note">
            <h4>Important Information</h4>
            <p>This vaccination card is an official record of all vaccinations administered to the above-named pet. Please keep this card in a safe place and bring it to all veterinary appointments. Some vaccinations require booster shots at regular intervals. Please consult with your veterinarian about your pet's vaccination schedule.</p>
        </div>
        
        <!-- Footer with Signatures -->
        <div class="footer">
            <div class="signature-section clearfix">
                <div class="signature-box">
                    <div class="signature-line">
                        Veterinarian Signature
                    </div>
                </div>
                <div class="signature-box" style="float: right; margin-right: 0;">
                    <div class="signature-line">
                        Date: {{ now()->format('M d, Y') }}
                    </div>
                </div>
            </div>
            
            <div class="footer-text">
                <p>This document was generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
                <p style="margin-top: 5px;">Pawsitive Systems - Caring for your pets since 2025</p>
            </div>
        </div>
    </div>
</body>
</html>
