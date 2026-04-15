<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #1e293b; background: #f8fafc; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .header { background: #1e293b; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; letter-spacing: 0.1em; text-transform: uppercase; }
        .header p { color: #94a3b8; margin: 6px 0 0; font-size: 13px; }
        .body { padding: 32px; }
        .body p { line-height: 1.7; color: #475569; font-size: 14px; }
        .invoice-box { background: #f1f5f9; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
        .invoice-box table { width: 100%; border-collapse: collapse; }
        .invoice-box td { padding: 6px 0; font-size: 13px; }
        .invoice-box td:last-child { text-align: right; font-weight: 700; }
        .total-row td { font-size: 16px; font-weight: 900; color: #1e293b; border-top: 1px solid #cbd5e1; padding-top: 12px; margin-top: 12px; }
        .footer { text-align: center; padding: 20px 32px; border-top: 1px solid #f1f5f9; color: #94a3b8; font-size: 11px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ $invoice->workspace->company_name ?? 'Invoice' }}</h1>
        <p>Invoice {{ $invoice->invoice_number }}</p>
    </div>
    <div class="body">
        <p>Dear {{ $invoice->contact->name }},</p>
        <p>
            Please find attached your invoice <strong>{{ $invoice->invoice_number }}</strong>
            dated <strong>{{ \Carbon\Carbon::parse($invoice->date)->format('d M Y') }}</strong>.
            Payment is due by <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</strong>.
        </p>

        <div class="invoice-box">
            <table>
                <tr>
                    <td style="color:#64748b">Invoice Number</td>
                    <td>{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="color:#64748b">Invoice Date</td>
                    <td>{{ \Carbon\Carbon::parse($invoice->date)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td style="color:#64748b">Due Date</td>
                    <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td style="color:#64748b">Subtotal (Net)</td>
                    <td>{{ number_format($invoice->subtotal_net, 2) }} {{ $invoice->workspace->currency ?? 'EUR' }}</td>
                </tr>
                <tr>
                    <td style="color:#64748b">VAT</td>
                    <td>{{ number_format($invoice->total_vat_amount, 2) }} {{ $invoice->workspace->currency ?? 'EUR' }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Due</td>
                    <td>{{ number_format($invoice->balance_due, 2) }} {{ $invoice->workspace->currency ?? 'EUR' }}</td>
                </tr>
            </table>
        </div>

        <p>The invoice PDF is attached to this email. If you have any questions, please don't hesitate to contact us.</p>
        <p style="margin-top: 24px;">Kind regards,<br><strong>{{ $invoice->workspace->company_name ?? 'The Team' }}</strong></p>
    </div>
    <div class="footer">
        {{ $invoice->workspace->company_name ?? '' }} &bull;
        {{ $invoice->workspace->address ?? '' }} &bull;
        {{ $invoice->workspace->email ?? '' }}
    </div>
</div>
</body>
</html>
