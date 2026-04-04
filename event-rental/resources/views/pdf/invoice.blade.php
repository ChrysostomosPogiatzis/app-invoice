<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Tax Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 30px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Legal Header */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-brand {
            vertical-align: top;
        }

        .invoice-type {
            text-align: right;
            vertical-align: top;
        }

        .title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }

        /* Address Grid */
        .info-grid {
            width: 100%;
            margin-bottom: 40px;
            border-collapse: collapse;
        }

        .info-col {
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }

        .label {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
            display: block;
        }

        /* Table Design */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background: #f8fafc;
            text-align: left;
            padding: 12px 8px;
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 2px solid #cbd5e1;
        }

        .items-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Totals Block */
        .totals-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .totals-table td {
            padding: 8px;
            font-size: 12px;
        }

        .settlement-block {
            margin-top: 28px;
            margin-bottom: 8px;
        }

        .settlement-title {
            font-size: 10px;
            font-weight: 700;
            color: #059669;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        .settlement-table {
            width: 100%;
            border-collapse: collapse;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        .settlement-table td {
            padding: 9px 10px;
            font-size: 10px;
            border-bottom: 1px solid #dcfce7;
        }

        .settlement-table tr:last-child td {
            border-bottom: none;
        }

        .grand-total {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px !important;
        }

        /* Footer & Compliance */
        .compliance-note {
            font-size: 9px;
            color: #64748b;
            font-style: italic;
            margin-top: 40px;
        }

        .signature-section {
            margin-top: 60px;
            width: 100%;
        }

        .sig-box {
            border-top: 1px solid #94a3b8;
            width: 200px;
            padding-top: 8px;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td class="company-brand">
                @if($invoice->workspace->logo_url)
                    <img src="{{ public_path($invoice->workspace->logo_url) }}"
                        style="max-height: 60px; margin-bottom: 10px;">
                @endif
                <div class="company-name">{{ $invoice->workspace->company_name }}</div>
                <div style="font-size: 10px; color: #475569;">
                    {{ $invoice->workspace->address }}<br> Tel: {{ $invoice->workspace->phone_number }} | Email:
                    {{ $invoice->workspace->email }}
                </div>
            </td>
            <td class="invoice-type">
                <h1 class="title">Tax Invoice</h1>
                <div style="margin-top: 15px;">
                    <strong>No:</strong> {{ $invoice->invoice_number }}<br> <strong>Date:</strong>
                    {{ \Carbon\Carbon::parse($invoice->date)->format('d/m/Y') }}<br> <strong>V.A.T. Reg:</strong>
                    {{ $invoice->workspace->vat_number }}<br> <strong>T.I.C:</strong> {{ $invoice->workspace->tax_id }}
                </div>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td class="info-col">
                <span class="label">Billed To</span>
                <div style="font-size: 14px; font-weight: 700;">{{ $invoice->contact->name }}</div>
                @if($invoice->contact->address)
                    <div>{{ $invoice->contact->address }}</div>
                @endif
                @if($invoice->contact->vat_number)
                    <div>VAT: {{ $invoice->contact->vat_number }}</div>
                @endif
            </td>
            <td class="info-col">
                <span class="label">Payment Details</span>
                <div><strong>Bank:</strong> IBAN {{ $invoice->workspace->iban }}</div>
                <div><strong>BIC/SWIFT:</strong> {{ $invoice->workspace->bic }}</div> @if($invoice->notes)
                <div style="margin-top: 10px; color: #64748b;">Ref: {{ $invoice->notes }}</div> @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">#</th>
                <th width="40%">Description</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="12%" class="text-right">Unit Price (Net)</th>
                <th width="11%" class="text-right">Unit Price (Gross)</th>
                <th width="10%" class="text-center">VAT</th>
                <th width="12%" class="text-right">Total (Gross)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-center" style="color: #94a3b8;">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->description }}</strong>
                        @if($item->notes)
                            <div style="font-size: 9px; color: #64748b;">{{ $item->notes }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price_net, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price_net * (1 + $item->vat_rate / 100), 2) }}</td>
                    <td class="text-center">{{ $item->vat_rate }}%</td>
                    <td class="text-right"><strong>{{ number_format($item->total_gross, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($invoice->payments->count())
        <div class="settlement-block">
            <div class="settlement-title">Settlement Log</div>
            <table class="settlement-table">
                @foreach($invoice->payments as $payment)
                    <tr>
                        <td width="26%" style="font-weight: 700; color: #065f46;">
                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}
                        </td>
                        <td width="42%" style="color: #047857; text-transform: uppercase; font-weight: 700;">
                            {{ $payment->payment_method }}
                        </td>
                        <td width="32%" class="text-right" style="font-weight: 700; color: #064e3b;">
                            +€{{ number_format($payment->amount, 2) }}
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <table class="totals-table">
        <tr>
            <td style="color: #64748b;">Total Net</td>
            <td class="text-right">{{ number_format($invoice->subtotal_net, 2) }}</td>
        </tr>
        <tr>
            <td style="color: #64748b;">V.A.T. Amount</td>
            <td class="text-right">{{ number_format($invoice->grand_total_gross - $invoice->subtotal_net, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Grand Total (EUR)</td>
            <td class="text-right">€{{ number_format($invoice->grand_total_gross, 2) }}</td>
        </tr>
        @if($invoice->amount_paid > 0)
            <tr>
                <td style="color: #10b981; font-weight: 700;">Amount Paid</td>
                <td class="text-right" style="color: #10b981;">-{{ number_format($invoice->amount_paid, 2) }}</td>
            </tr>
        @endif
        <tr style="border-top: 2px solid #0f172a;">
            <td style="font-weight: 700;">Balance Due</td>
            <td class="text-right" style="font-weight: 700;">€{{ number_format($invoice->balance_due, 2) }}</td>
        </tr>
    </table>

    @if($invoice->notes)
    <div style="margin-top: 30px; border-left: 3px solid #cbd5e1; padding-left: 15px;">
        <span class="label">Invoice Notes</span>
        <div style="font-size: 10px; color: #1e293b;">{!! nl2br(e($invoice->notes)) !!}</div>
    </div>
    @endif

    <div class="compliance-note">
        Amount in words: {{ $amountInWords }}
    </div>

    @if($invoice->terms)
    <div style="margin-top: 20px; font-size: 8px; color: #64748b; line-height: 1.4; border-top: 1px solid #f1f5f9; padding-top: 10px;">
        <strong>Terms & Conditions:</strong><br>
        {!! nl2br(e($invoice->terms)) !!}
    </div>
    @endif

    <table class="signature-section">
        <tr>
            <td>
                <div class="sig-box">Seller Signature & Stamp</div>
            </td>
            <td class="text-right">
                <div class="sig-box" style="margin-left: auto;">Buyer Signature: {{ $invoice->contact->name }}</div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 50px; font-size: 8px; color: #cbd5e1;">
        This document is a Tax Invoice in accordance with Cyprus VAT Legislation. </div>

</body>

</html>
