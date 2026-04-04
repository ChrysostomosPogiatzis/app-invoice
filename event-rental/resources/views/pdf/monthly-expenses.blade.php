<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Expense Report — {{ $monthLabel }}</title>
    <style>
        @page { margin: 28px 30px; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .report-header {
            width: 100%;
            border-bottom: 3px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .report-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .report-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }

        .company-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-box {
            width: 100%;
            margin-bottom: 24px;
            border-collapse: collapse;
        }

        .summary-cell {
            width: 25%;
            border: 1px solid #e2e8f0;
            padding: 12px 14px;
            vertical-align: top;
        }

        .summary-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background: #0f172a;
            color: #ffffff;
            text-align: left;
            padding: 9px 8px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .items-table td {
            padding: 9px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .category-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .totals-section {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-section td {
            padding: 7px 10px;
            font-size: 11px;
        }

        .totals-section tr.grand td {
            background: #0f172a;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
        }

        .section-divider {
            margin: 24px 0 12px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }

        .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <table class="report-header">
        <tr>
            <td>
                @if($workspace->logo_url)
                    <img src="{{ public_path($workspace->logo_url) }}" style="max-height: 50px; margin-bottom: 8px;"><br>
                @endif
                <div class="company-name">{{ $workspace->company_name }}</div>
                <div class="report-sub">VAT Reg: {{ $workspace->vat_number }} &nbsp;|&nbsp; TIC: {{ $workspace->tax_id }}</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="report-title">Expense Report</div>
                <div class="report-sub">Period: {{ $monthLabel }}</div>
                <div class="report-sub">Generated: {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    {{-- Summary KPIs --}}
    <table class="summary-box">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">Total Records</div>
                <div class="summary-value">{{ $expenses->count() }}</div>
            </td>
            <td class="summary-cell" style="padding-left: 16px;">
                <div class="summary-label">Net Amount</div>
                <div class="summary-value">€{{ number_format($totalNet, 2) }}</div>
            </td>
            <td class="summary-cell" style="padding-left: 16px;">
                <div class="summary-label">Total VAT</div>
                <div class="summary-value">€{{ number_format($totalVat, 2) }}</div>
            </td>
            <td class="summary-cell" style="padding-left: 16px;">
                <div class="summary-label">Total Gross</div>
                <div class="summary-value" style="color: #dc2626;">€{{ number_format($totalGross, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- Expenses Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th width="10%">#</th>
                <th width="11%">Date</th>
                <th width="16%">Category</th>
                <th width="25%">Vendor / Notes</th>
                <th width="13%" class="text-right">Net Amount</th>
                <th width="13%" class="text-right">VAT Amount</th>
                <th width="12%" class="text-right">Gross Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $catColors = [
                    'fuel'        => '#fffbeb;color:#92400e',
                    'staff_wages' => '#f0fdf4;color:#166534',
                    'sub_rental'  => '#eef2ff;color:#3730a3',
                    'marketing'   => '#fff1f2;color:#9f1239',
                    'utility'     => '#f0f9ff;color:#075985',
                    'other'       => '#f8fafc;color:#475569',
                ];
            @endphp
            @forelse($expenses as $index => $expense)
                @php
                    $vatAmt = $expense->vat_amount ?? 0;
                    $gross = $expense->amount + $vatAmt;
                    $catStyle = $catColors[$expense->category] ?? '#f8fafc;color:#475569';
                @endphp
                <tr>
                    <td style="color: #94a3b8;">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}</td>
                    <td>
                        <span class="category-badge" style="background: {{ explode(';', $catStyle)[0] }}; {{ explode(';', $catStyle)[1] ?? '' }}">
                            {{ ucfirst(str_replace('_', ' ', $expense->category)) }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $expense->vendor_name ?: '—' }}</strong>
                        @if($expense->notes)
                            <br><span style="color:#94a3b8; font-size:8px;">{{ Str::limit($expense->notes, 60) }}</span>
                        @endif
                    </td>
                    <td class="text-right">€{{ number_format($expense->amount, 2) }}</td>
                    <td class="text-right" style="{{ $vatAmt > 0 ? 'color:#4f46e5; font-weight:700;' : 'color:#cbd5e1;' }}">
                        {{ $vatAmt > 0 ? '€' . number_format($vatAmt, 2) : '—' }}
                    </td>
                    <td class="text-right"><strong>€{{ number_format($gross, 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #94a3b8; font-style: italic;">
                        No expenses found for this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Category Breakdown --}}
    @if($byCategory->count())
        <div class="section-divider">Breakdown by Category</div>
        <table class="items-table" style="width: 50%; margin-left: auto;">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-right">Net</th>
                    <th class="text-right">VAT</th>
                    <th class="text-right">Gross</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byCategory as $cat)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $cat->category)) }}</td>
                        <td class="text-right">€{{ number_format($cat->net_total, 2) }}</td>
                        <td class="text-right">€{{ number_format($cat->vat_total, 2) }}</td>
                        <td class="text-right"><strong>€{{ number_format($cat->gross_total, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Totals --}}
    <table class="totals-section" style="margin-top: 16px;">
        <tr>
            <td style="color: #64748b;">Total Net</td>
            <td class="text-right">€{{ number_format($totalNet, 2) }}</td>
        </tr>
        <tr>
            <td style="color: #4f46e5; font-weight: 700;">Total VAT</td>
            <td class="text-right" style="color: #4f46e5; font-weight: 700;">€{{ number_format($totalVat, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>GROSS TOTAL</td>
            <td class="text-right">€{{ number_format($totalGross, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ $workspace->company_name }} &mdash; Confidential Accounting Document &mdash; {{ $monthLabel }} Expense Summary
    </div>

</body>
</html>
