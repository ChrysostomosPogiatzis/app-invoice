<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Payroll Liability Report</title>
    <style>
        /* DejaVu Sans is generally bundled with DomPDF and supports Greek characters */
        body { font-family: 'DejaVu Sans', sans-serif; color: #334155; margin: 0; padding: 0; font-size: 9px; }
        .header { background: #1e293b; color: white; padding: 25px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0 0; opacity: 0.7; font-size: 9px; }
        .section { padding: 20px; }
        .summary-grid { display: block; margin-bottom: 25px; }
        .summary-card { 
            display: inline-block; 
            width: 17%; 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            padding: 10px; 
            border-radius: 6px; 
            margin-right: 1.5%;
        }
        .summary-card h3 { margin: 0; color: #64748b; text-transform: uppercase; font-size: 7px; opacity: 0.8; }
        .summary-card div { font-size: 14px; font-weight: bold; margin-top: 4px; color: #0f172a; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f1f5f9; text-align: left; padding: 8px; border-bottom: 2px solid #e2e8f0; font-size: 7px; text-transform: uppercase; color: #475569; }
        td { padding: 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .footer { position: fixed; bottom: 20px; width: 100%; text-align: center; color: #94a3b8; font-size: 7px; }
        .total-row { background: #f8fafc; font-weight: bold; border-top: 2px solid #e2e8f0; }
        .highlight-blue { color: #2563eb; }
        .highlight-emerald { color: #059669; font-weight: bold; }
        .subtext { font-size: 7px; color: #64748b; margin-top: 2px; }
        
        .routing-table tr:nth-child(even) { background-color: #f8fafc; }
        .routing-header { background: #334155 !important; color: white !important; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payroll Liability Summary</h1>
        <p>{{ $workspace->name }} &bull; {{ $monthLabel }}</p>
    </div>

    <div class="section">
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Social Insurance</h3>
                <div>€{{ number_format($siTotal, 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>GESY (Health)</h3>
                <div>€{{ number_format($gesiTotal, 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Provident Fund</h3>
                <div>€{{ number_format($providentTotal, 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Other ER Funds</h3>
                <div>€{{ number_format($otherTotal, 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Income Tax</h3>
                <div>€{{ number_format($taxTotal, 2) }}</div>
            </div>
            <div class="summary-card" style="margin-right: 0;">
                <h3>Union Fees</h3>
                <div>€{{ number_format($unionTotal, 2) }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Staff Member</th>
                    <th>Gross Salary</th>
                    <th>Soc. Insurance</th>
                    <th>GESY</th>
                    <th>Provident</th>
                    <th>Union / Other</th>
                    <th>Income Tax</th>
                    <th>Net Payable</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $exp)
                    <tr>
                        <td><strong>{{ $exp->vendor_name }}</strong></td>
                        <td>€{{ number_format($exp->gross_salary, 2) }}</td>
                        <td>
                        <td>
                            €{{ number_format(($exp->si_employee ?? 0) + ($exp->si_employer ?? 0), 2) }}
                            <div class="subtext">EE: {{ number_format($exp->si_employee ?? 0, 2) }} | ER: {{ number_format($exp->si_employer ?? 0, 2) }}</div>
                        </td>
                        <td>
                            €{{ number_format(($exp->gesi_employee ?? 0) + ($exp->gesi_employer ?? 0), 2) }}
                            <div class="subtext">EE: {{ number_format($exp->gesi_employee ?? 0, 2) }} | ER: {{ number_format($exp->gesi_employer ?? 0, 2) }}</div>
                        </td>
                        <td>
                            €{{ number_format(($exp->provident_employee ?? 0) + ($exp->provident_employer ?? 0), 2) }}
                            <div class="subtext">EE: {{ number_format($exp->provident_employee ?? 0, 2) }} | ER: {{ number_format($exp->provident_employer ?? 0, 2) }}</div>
                        </td>
                        <td>
                            €{{ number_format(($exp->cohesion_amount ?? 0) + ($exp->redundancy_amount ?? 0) + ($exp->training_amount ?? 0) + ($exp->holiday_amount ?? 0) + ($exp->union_amount ?? 0), 2) }}
                            <div class="subtext">Red: {{ number_format($exp->redundancy_amount ?? 0, 2) }} | Tr: {{ number_format($exp->training_amount ?? 0, 2) }}</div>
                        </td>
                        <td class="highlight-blue">€{{ number_format($exp->tax_employee ?? 0, 2) }}</td>
                        <td class="highlight-emerald">€{{ number_format($exp->net_payable ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>TOTALS</td>
                    <td>€{{ number_format($expenses->sum('gross_salary'), 2) }}</td>
                    <td>€{{ number_format($siTotal, 2) }}</td>
                    <td>€{{ number_format($gesiTotal, 2) }}</td>
                    <td>€{{ number_format($providentTotal, 2) }}</td>
                    <td>€{{ number_format($otherTotal + $unionTotal, 2) }}</td>
                    <td>€{{ number_format($taxTotal, 2) }}</td>
                    <td>€{{ number_format($expenses->sum('net_payable'), 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 35px; padding: 15px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px;">
            <h4 style="margin: 0; color: #92400e; font-size: 9px; text-transform: uppercase;">Payment Obligations</h4>
            <p style="margin: 5px 0 0; color: #b45309; line-height: 1.4;">
                Total remittance required: <strong>€{{ number_format($siTotal + $gesiTotal + $taxTotal + $providentTotal + $otherTotal + $unionTotal, 2) }}</strong>.
                Remittances should be settled with respective departments by the end of next month.
            </p>
        </div>

        <div style="margin-top: 30px;">
            <h4 style="font-size: 9px; text-transform: uppercase; color: #1e293b; margin-bottom: 10px; letter-spacing: 1px;">Where Each Payment Must Go</h4>
            <table class="routing-table" style="font-size: 8px;">
                <thead>
                    <tr class="routing-header">
                        <th style="padding: 10px;">Contribution</th>
                        <th style="padding: 10px;">Pay To (Authority)</th>
                        <th style="padding: 10px;">Portal / Method</th>
                        <th style="padding: 10px;">Deadline</th>
                        <th style="padding: 10px; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Social Insurance (EE + ER)</td>
                        <td><strong>Social Insurance Services (YKA / ΥΚΑ)</strong></td>
                        <td>ysis.mlsi.gov.cy</td>
                        <td>End of following month</td>
                        <td style="text-align: right; font-weight: bold;">€{{ number_format($expenses->sum(fn($e) => ($e->si_employee??0)+($e->si_employer??0)), 2) }}</td>
                    </tr>
                    <tr>
                        <td>Redundancy & Training (ER)</td>
                        <td><strong>Social Insurance Services (YKA / ΥΚΑ)</strong></td>
                        <td>Paid together with SI</td>
                        <td>End of following month</td>
                        <td style="text-align: right; font-weight: bold;">€{{ number_format($expenses->sum(fn($e) => ($e->redundancy_amount ?? 0) + ($e->training_amount ?? 0)), 2) }}</td>
                    </tr>
                    <tr>
                        <td>Social Cohesion Fund (ER)</td>
                        <td><strong>Social Insurance Services (YKA / ΥΚΑ)</strong></td>
                        <td>Paid together with SI</td>
                        <td>End of following month</td>
                        <td style="text-align: right; font-weight: bold;">€{{ number_format($expenses->sum(fn($e) => $e->cohesion_amount ?? 0), 2) }}</td>
                    </tr>
                    <tr>
                        <td>GESY / Health (EE + ER)</td>
                        <td><strong>HIO — Health Insurance Org (OAY / ΟΑΥ)</strong></td>
                        <td>hio.org.cy</td>
                        <td>End of following month</td>
                        <td style="text-align: right; font-weight: bold;">€{{ number_format($gesiTotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Income Tax / PAYE (EE)</td>
                        <td><strong>Tax Department (Τμήμα Φορολογίας)</strong></td>
                        <td>taxisnet.mof.gov.cy</td>
                        <td>End of following month</td>
                        <td style="text-align: right; font-weight: bold;">€{{ number_format($taxTotal, 2) }}</td>
                    </tr>
                    @if($providentTotal > 0)
                    <tr>
                        <td>Provident Fund (EE + ER)</td>
                        <td><strong>Provident Fund Administrator</strong></td>
                        <td>Per agreement</td>
                        <td>Monthly</td>
                        <td style="text-align: right; font-weight: bold;">€{{ number_format($providentTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($holidayTotal > 0)
                    <tr>
                        <td>Holiday Fund (ER)</td>
                        <td><strong>Sector Holiday Fund</strong></td>
                        <td>Sector specific</td>
                        <td>Monthly</td>
                        <td style="text-align: right; font-weight: bold;">€{{ number_format($holidayTotal, 2) }}</td>
                    </tr>
                    @endif
                    @if($unionTotal > 0)
                    <tr>
                        <td>Trade Union Fees (PEO / SEK)</td>
                        <td><strong>Respective Trade Union</strong></td>
                        <td>Direct Remittance</td>
                        <td>Monthly</td>
                        <td style="text-align: right; font-weight: bold;">€{{ number_format($unionTotal, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row" style="background: #1e293b; color: white;">
                        <td colspan="4" style="padding: 10px; text-transform: uppercase;">Total Monthly Remittances</td>
                        <td style="padding: 10px; text-align: right; font-size: 11px;">€{{ number_format($siTotal + $gesiTotal + $taxTotal + $providentTotal + $otherTotal + $unionTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        Generated on {{ now()->format('d/m/Y H:i') }} &bull; Chrysler Payroll Engine &bull; {{ $workspace->name }}
    </div>
</body>
</html>
