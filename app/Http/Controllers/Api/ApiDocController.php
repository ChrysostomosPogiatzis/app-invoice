<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="Witbo Business API",
 *     version="1.0.0",
 *     description="REST API for the Witbo multi-tenant business management platform. All endpoints (except /login) require a Bearer token obtained via POST /api/login. All data is automatically scoped to the authenticated user's active workspace.",
 *     @OA\Contact(email="support@witbo.com.cy"),
 *     @OA\License(name="Proprietary")
 * )
 *
 * @OA\Server(url="https://call.witbo.com.cy", description="Production")
 * @OA\Server(url="http://localhost:8000", description="Local Development")
 *
 * @OA\SecurityScheme(
 *     securityScheme="BearerToken",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="token",
 *     description="Sanctum token from POST /api/login"
 * )
 *
 * @OA\Tag(name="Auth", description="Authentication")
 * @OA\Tag(name="Contacts", description="CRM contact management")
 * @OA\Tag(name="Invoices", description="Invoicing")
 * @OA\Tag(name="Quotes", description="Quotations")
 * @OA\Tag(name="Expenses", description="Expenses & payroll records")
 * @OA\Tag(name="Products", description="Product & inventory management")
 * @OA\Tag(name="Staff", description="Staff members & HR")
 * @OA\Tag(name="Reminders", description="Follow-up reminders")
 * @OA\Tag(name="Dashboard", description="Summary & calendar data")
 * @OA\Tag(name="Sync", description="Bulk mobile data synchronisation")
 * @OA\Tag(name="Banking", description="Bank connection sync")
 *
 * ---------------------------------------------------------------------------
 * Reusable Schemas
 * ---------------------------------------------------------------------------
 *
 * @OA\Schema(schema="PaginationMeta",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="per_page", type="integer", example=30),
 *     @OA\Property(property="total", type="integer", example=142)
 * )
 *
 * @OA\Schema(schema="Contact",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Nikos Papadopoulos"),
 *     @OA\Property(property="company_name", type="string", nullable=true, example="Acme Ltd"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="mobile_number", type="string", example="+35799123456"),
 *     @OA\Property(property="contact_type", type="string", enum={"customer","supplier","lead"}, example="customer"),
 *     @OA\Property(property="vat_number", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(schema="InvoiceItem",
 *     @OA\Property(property="description", type="string", example="Web design services"),
 *     @OA\Property(property="quantity", type="number", format="float", example=1),
 *     @OA\Property(property="unit_price_net", type="number", format="float", example=200.00),
 *     @OA\Property(property="vat_rate", type="number", format="float", example=19),
 *     @OA\Property(property="product_id", type="integer", nullable=true)
 * )
 *
 * @OA\Schema(schema="Invoice",
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="invoice_number", type="string", example="INV-0042"),
 *     @OA\Property(property="status", type="string", enum={"draft","unpaid","partial","paid","void"}, example="unpaid"),
 *     @OA\Property(property="doc_type", type="string", enum={"invoice","credit_note"}, example="invoice"),
 *     @OA\Property(property="date", type="string", format="date", example="2026-04-15"),
 *     @OA\Property(property="due_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="grand_total_gross", type="number", format="float", example=238.00),
 *     @OA\Property(property="balance_due", type="number", format="float", example=238.00),
 *     @OA\Property(property="amount_paid", type="number", format="float", example=0),
 *     @OA\Property(property="contact", ref="#/components/schemas/Contact"),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/InvoiceItem"))
 * )
 *
 * @OA\Schema(schema="Product",
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="name", type="string", example="Office Chair"),
 *     @OA\Property(property="sku", type="string", nullable=true, example="CHR-001"),
 *     @OA\Property(property="product_type", type="string", enum={"physical","service","digital"}, example="physical"),
 *     @OA\Property(property="unit_price_net", type="number", format="float", example=150.00),
 *     @OA\Property(property="unit_price_gross", type="number", format="float", example=178.50),
 *     @OA\Property(property="vat_rate", type="number", format="float", example=19),
 *     @OA\Property(property="current_stock", type="number", format="float", example=12)
 * )
 *
 * @OA\Schema(schema="StaffMember",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Maria Constantinou"),
 *     @OA\Property(property="position", type="string", nullable=true, example="Cashier"),
 *     @OA\Property(property="base_salary", type="number", format="float", example=1200.00),
 *     @OA\Property(property="leave_balance", type="number", format="float", example=18),
 *     @OA\Property(property="joining_date", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Schema(schema="Expense",
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="description", type="string", example="Office supplies"),
 *     @OA\Property(property="amount", type="number", format="float", example=85.00),
 *     @OA\Property(property="expense_date", type="string", format="date", example="2026-04-01"),
 *     @OA\Property(property="category", type="string", nullable=true, example="Office"),
 *     @OA\Property(property="is_payroll", type="boolean", example=false)
 * )
 *
 * @OA\Schema(schema="ErrorValidation",
 *     @OA\Property(property="message", type="string", example="The email field is required."),
 *     @OA\Property(property="errors", type="object", example={"email": {"The email field is required."}})
 * )
 *
 * @OA\Schema(schema="Error",
 *     @OA\Property(property="message", type="string", example="Unauthenticated.")
 * )
 */
class ApiDocController extends Controller
{
    // This controller exists only to hold OpenAPI root annotations.
    // It has no routes.
}
