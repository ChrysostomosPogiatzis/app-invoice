<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\StaffLeaveRequest;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $workspaceId = $request->user()->currentWorkspaceRecord()?->id
            ?? $request->user()->workspaces()->first()->id;

        $staff = StaffMember::where('workspace_id', $workspaceId)
            ->withCount('leaveRequests')
            ->orderBy('name')
            ->get()
            ->map(fn($s) => [
                'id'                  => $s->id,
                'name'                => $s->name,
                'email'               => $s->email,
                'phone'               => $s->phone,
                'position'            => $s->position,
                'base_salary'         => (float) $s->base_salary,
                'joining_date'        => $s->joining_date,
                'annual_leave_total'  => $s->annual_leave_total,
                'leave_balance'       => $s->leave_balance,
                'leave_requests_count'=> $s->leave_requests_count,
            ]);

        return response()->json(['status' => 'success', 'data' => $staff]);
    }

    public function show(Request $request, $id)
    {
        $workspaceId = $request->user()->currentWorkspaceRecord()?->id
            ?? $request->user()->workspaces()->first()->id;

        $staff = StaffMember::where('workspace_id', $workspaceId)
            ->with([
                'leaveRequests' => fn($q) => $q->orderByDesc('start_date')->limit(10),
                'documents',
                'expenses' => fn($q) => $q->where('is_payroll', true)->orderByDesc('expense_date')->limit(12),
            ])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                    => $staff->id,
                'name'                  => $staff->name,
                'email'                 => $staff->email,
                'phone'                 => $staff->phone,
                'position'              => $staff->position,
                'base_salary'           => (float) $staff->base_salary,
                'id_number'             => $staff->id_number,
                'si_number'             => $staff->si_number,
                'tax_id'                => $staff->tax_id,
                'iban'                  => $staff->iban,
                'joining_date'          => $staff->joining_date,
                'annual_leave_total'    => $staff->annual_leave_total,
                'leave_balance'         => $staff->leave_balance,
                'emergency_contact_name'  => $staff->emergency_contact_name,
                'emergency_contact_phone' => $staff->emergency_contact_phone,
                'leave_requests'        => $staff->leaveRequests,
                'documents'             => $staff->documents->map(fn($d) => [
                    'id'        => $d->id,
                    'name'      => $d->name,
                    'type'      => $d->type,
                    'file_path' => $d->file_path,
                    'mime_type' => $d->mime_type ?? null,
                    'size_bytes'=> $d->size_bytes ?? null,
                ]),
                'payroll_history'       => $staff->expenses->map(fn($e) => [
                    'id'           => $e->id,
                    'period'       => $e->expense_date,
                    'gross_salary' => (float) ($e->gross_salary ?? $e->amount),
                    'net_payable'  => (float) ($e->net_payable ?? $e->amount),
                    'amount'       => (float) $e->amount,
                ]),
            ],
        ]);
    }

    public function leaveRequests(Request $request, $id)
    {
        $workspaceId = $request->user()->currentWorkspaceRecord()?->id
            ?? $request->user()->workspaces()->first()->id;

        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($id);

        $leaves = StaffLeaveRequest::where('staff_member_id', $staff->id)
            ->orderByDesc('start_date')
            ->get();

        return response()->json(['status' => 'success', 'data' => $leaves]);
    }

    public function storeLeave(Request $request, $id)
    {
        $workspaceId = $request->user()->currentWorkspaceRecord()?->id
            ?? $request->user()->workspaces()->first()->id;

        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'type'       => 'required|string',
            'reason'     => 'nullable|string',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end   = Carbon::parse($validated['end_date']);
        $days  = $start->diffInDaysFiltered(fn($d) => !$d->isWeekend(), $end) + 1;

        $leave = $staff->leaveRequests()->create([
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'type'       => $validated['type'],
            'reason'     => $validated['reason'] ?? null,
            'status'     => 'pending',
            'days_count' => $days,
        ]);

        return response()->json(['status' => 'success', 'data' => $leave], 201);
    }

    public function onLeaveToday(Request $request)
    {
        $workspaceId = $request->user()->currentWorkspaceRecord()?->id
            ?? $request->user()->workspaces()->first()->id;

        $today = now()->toDateString();

        $leaves = StaffLeaveRequest::whereHas('staff', fn($q) => $q->where('workspace_id', $workspaceId))
            ->where('status', '!=', 'rejected')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with('staff:id,name,position,phone')
            ->get()
            ->map(fn($l) => [
                'staff_name'     => $l->staff->name ?? 'Unknown',
                'staff_position' => $l->staff->position ?? '',
                'staff_phone'    => $l->staff->phone ?? '',
                'leave_type'     => $l->type,
                'status'         => $l->status,
                'start_date'     => $l->start_date,
                'end_date'       => $l->end_date,
            ]);

        return response()->json(['status' => 'success', 'date' => $today, 'data' => $leaves]);
    }
}
