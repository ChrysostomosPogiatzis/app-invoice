<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceVatBreakdown;

use Illuminate\Support\Facades\DB;

class FinancialService
{


    protected function generateInvoiceNumber(int $workspaceId, string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 1));
        $count = Invoice::where('workspace_id', $workspaceId)->where('doc_type', $type)->count() + 1;
        return $prefix . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
