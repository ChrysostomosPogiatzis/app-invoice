<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice Report — {{ $monthLabel }}</title>
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
            border-radius: 4px;
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

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-unpaid { background: #fef3c7; color: #92400e; }
        .badge-partial { background: #dbeafe; color: #1e40af; }
        .badge-overdue { background: #fee2e2; color: #991b1b; }

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
                <div class="report-sub">VAT Reg: {{ $workspace->vat_number }} &nbsp;|&nbsp; TIC: {{ $workspace->tic_number }}</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="report-title">Invoice Report</div>
                <div class="report-sub">Period: {{ $monthLabel }}</div>
                <div class="report-sub">Generated: {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    {{-- Summary KPIs --}}
    <table class="summary-box">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">Total Invoices</div>
                <div class="summary-value">{{ $invoices->count() }}</div>
            </td>
            <td class="summary-cell" style="padding-left: 16px;">
                <div class="summary-label">Total Net</div>
                <div class="summary-value">€{{ number_format($totalNet, 2) }}</div>
            </td>
            <td class="summary-cell" style="padding-left: 16px;">
                <div class="summary-label">Total VAT</div>
                <div class="summary-value">€{{ number_format($totalVat, 2) }}</div>
            </td>
            <td class="summary-cell" style="padding-left: 16px;">
                <div class="summary-label">Total Gross</div>
                <div class="summary-value" style="color: #4f46e5;">€{{ number_format($totalGross, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- Invoices Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th width="12%">Invoice #</th>
                <th width="10%">Date</th>
                <th width="22%">Client</th>
                <th width="10%" class="text-right">Net</th>
                <th width="10%" class="text-right">VAT</th>
                <th width="12%" class="text-right">Gross</th>
                <th width="10%" class="text-right">Paid</th>
                <th width="10%" class="text-right">Balance</th>
                <th width="8%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ \Carbon\Carbon::parse($invoice->date)->format('d/m/Y') }}</td>
                    <td>
                        {{ $invoice->contact->name ?? '—' }}
                        @if($invoice->contact?->company_name)
                            <br><span style="color:#94a3b8; font-size:8px;">{{ $invoice->contact->company_name }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($invoice->subtotal_net, 2) }}</td>
                    <td class="text-right">{{ number_format($invoice->total_vat_amount, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($invoice->grand_total_gross, 2) }}</strong></td>
                    <td class="text-right" style="color: #16a34a;">{{ number_format($invoice->amount_paid, 2) }}</td>
                    <td class="text-right" style="color: #dc2626; font-weight:700;">{{ number_format($invoice->balance_due, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 30px; color: #94a3b8; font-style: italic;">
                        No invoices found for this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Status Breakdown --}}
    @if($byStatus->count())
        <div class="section-divider">Breakdown by Status</div>
        <table class="items-table" style="width: 50%; margin-left: auto;">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Net</th>
                    <th class="text-right">VAT</th>
                    <th class="text-right">Gross</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byStatus as $st)
                    <tr>
                        <td>{{ strtoupper($st->status) }}</td>
                        <td class="text-right">{{ $st->count }}</td>
                        <td class="text-right">€{{ number_format($st->net_total, 2) }}</td>
                        <td class="text-right">€{{ number_format($st->vat_total, 2) }}</td>
                        <td class="text-right"><strong>€{{ number_format($st->gross_total, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Totals --}}
    <table class="totals-section">
        <tr>
            <td style="color: #64748b;">Total Net</td>
            <td class="text-right">€{{ number_format($totalNet, 2) }}</td>
        </tr>
        <tr>
            <td style="color: #64748b;">Total VAT</td>
            <td class="text-right">€{{ number_format($totalVat, 2) }}</td>
        </tr>
        <tr>
            <td style="color: #16a34a; font-weight: 700;">Total Collected</td>
            <td class="text-right" style="color: #16a34a; font-weight: 700;">€{{ number_format($totalPaid, 2) }}</td>
        </tr>
        <tr>
            <td style="color: #dc2626; font-weight: 700;">Outstanding</td>
            <td class="text-right" style="color: #dc2626; font-weight: 700;">€{{ number_format($totalBalance, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>GROSS TOTAL</td>
            <td class="text-right">€{{ number_format($totalGross, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ $workspace->company_name }} &mdash; Confidential Accounting Document &mdash; {{ $monthLabel }} Invoice Summary
    </div>

</body>
</html>
