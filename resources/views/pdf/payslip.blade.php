<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $staff->name }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; margin: 0; padding: 40px; font-size: 12px; }
        .header { border-bottom: 3px solid #10b981; padding-bottom: 20px; margin-bottom: 30px; }
        .company-name { font-size: 18px; font-weight: bold; color: #059669; text-transform: uppercase; letter-spacing: 2px; }
        .title { font-size: 22px; font-weight: 900; text-align: right; margin-top: -25px; color: #94a3b8; text-transform: uppercase; }
        
        .grid { width: 100%; margin-bottom: 30px; }
        .grid td { vertical-align: top; width: 50%; }
        
        .label { font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .value { font-size: 13px; font-weight: bold; }
        
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th { background: #f8fafc; text-align: left; padding: 10px; font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        table.data td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        
        .summary-box { background: #f0fdf4; border: 1px solid #dcfce7; padding: 15px; border-radius: 8px; margin-top: 30px; }
        .summary-item { margin-bottom: 10px; }
        .summary-label { font-size: 10px; font-weight: bold; color: #166534; text-transform: uppercase; display: inline-block; width: 180px; }
        .summary-value { font-size: 16px; font-weight: 900; color: #064e3b; display: inline-block; }
        
        .footer { margin-top: 60px; font-size: 8px; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $workspace->name }}</div>
        <div class="title">Payslip</div>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="label">Employee</div>
                <div class="value">{{ $staff->name }}</div>
                <div style="margin-top: 8px;">
                    <div class="label">Position</div>
                    <div class="value">{{ $staff->position ?? 'Professional Staff' }}</div>
                </div>
            </td>
            <td style="text-align: right;">
                <div class="label">Pay Period</div>
                <div class="value">{{ \Carbon\Carbon::parse($expense->expense_date)->format('F Y') }}</div>
                <div style="margin-top: 8px;">
                    <div class="label">Social Insurance / Tax ID</div>
                    <div class="value">{{ $staff->si_number ?: '---' }} / {{ $staff->tax_id ?: '---' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Earnings (€)</th>
                <th style="text-align: right;">Deductions (€)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Monthly Salary</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($expense->gross_salary, 2) }}</td>
                <td></td>
            </tr>
            <tr>
                <td>Social Insurance (Employee)</td>
                <td></td>
                <td style="text-align: right; color: #b91c1c;">{{ number_format($expense->si_employee ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>GESY Contribution (Employee)</td>
                <td></td>
                <td style="text-align: right; color: #b91c1c;">{{ number_format($expense->gesi_employee ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Pay As You Earn (Tax)</td>
                <td></td>
                <td style="text-align: right; color: #b91c1c;">{{ number_format($expense->tax_employee ?? 0, 2) }}</td>
            </tr>
            @if(isset($expense->provident_employee) && $expense->provident_employee > 0)
            <tr>
                <td>Provident Fund Deduction</td>
                <td></td>
                <td style="text-align: right; color: #b91c1c;">{{ number_format($expense->provident_employee, 2) }}</td>
            </tr>
            @endif
            @if(isset($expense->union_amount) && $expense->union_amount > 0)
            <tr>
                <td>Trade Union Fee</td>
                <td></td>
                <td style="text-align: right; color: #b91c1c;">{{ number_format($expense->union_amount, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-item">
            <span class="summary-label">Net Salary Distributed</span>
            <span class="summary-value">€{{ number_format($expense->net_payable ?? 0, 2) }}</span>
        </div>
    </div>

    <div style="margin-top: 20px; font-size: 9px; color: #475569;">
        <strong>Payment IBAN:</strong> {{ $staff->iban ?: 'Standard Corporate Account' }}
    </div>

    <div class="footer">
        Generated on {{ date('d/m/Y H:i') }}. This statement is compliant with the Republic of Cyprus labor regulations.
    </div>
</body>
</html>
