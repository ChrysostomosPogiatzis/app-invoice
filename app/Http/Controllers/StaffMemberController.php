<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStoredFile;
use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StaffMemberController extends Controller
{
    use ResolvesWorkspace, ResolvesStoredFile;

    public function index()
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)->get();

        return Inertia::render('Finance/Staff/Index', [
            'staff' => $staff
        ]);
    }

    public function show($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)
            ->with(['documents', 'leaveRequests', 'expenses' => function($q) {
                $q->where('is_payroll', true)->orderBy('expense_date', 'desc');
            }])
            ->findOrFail($id);

        $staff->documents->each(function ($document) use ($staff) {
            $document->download_url = route('staff-members.documents.download', [
                'staffId' => $staff->id,
                'docId' => $document->id,
            ]);
        });

        return Inertia::render('Finance/Staff/Show', [
            'member' => $staff
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->currentWorkspace();
        if (!$workspace) {
            return back()->with('error', 'No active workspace found.');
        }

        // Tier Enforcement
        if (!\App\Services\TierService::canAddStaff($workspace)) {
            $limits = \App\Services\TierService::getLimits($workspace->tier);
            return back()->with('error', "Staff limit reached correctly for your {$workspace->tier} plan (Max: {$limits['max_staff']}). Please upgrade to add more personnel.");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'id_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'base_salary' => 'nullable|numeric|min:0',
            'si_number' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:20',
            'iban' => 'nullable|string|max:40',
            'provident_employee_rate' => 'nullable|numeric|min:0|max:100',
            'provident_employer_rate' => 'nullable|numeric|min:0|max:100',
            'union_rate' => 'nullable|numeric|min:0|max:100',
            'union_type' => 'nullable|string',
            'use_holiday_fund' => 'nullable|boolean',
            'holiday_rate' => 'nullable|numeric|min:0|max:100',
            'joining_date' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'annual_leave_total' => 'nullable|integer|min:0',
            'leave_balance' => 'nullable|numeric'
        ]);

        StaffMember::create(array_merge($validated, ['workspace_id' => $workspace->id]));

        return back()->with('success', 'Staff member added successfully within plan limits.');
    }

    public function update(Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'id_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'base_salary' => 'nullable|numeric|min:0',
            'si_number' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:20',
            'iban' => 'nullable|string|max:40',
            'provident_employee_rate' => 'nullable|numeric|min:0|max:100',
            'provident_employer_rate' => 'nullable|numeric|min:0|max:100',
            'union_rate' => 'nullable|numeric|min:0|max:100',
            'union_type' => 'nullable|string',
            'use_holiday_fund' => 'nullable|boolean',
            'holiday_rate' => 'nullable|numeric|min:0|max:100',
            'joining_date' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'annual_leave_total' => 'nullable|integer|min:0',
            'leave_balance' => 'nullable|numeric'
        ]);

        $staff->update($validated);

        return back()->with('success', 'Staff member updated.');
    }

    public function destroy($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($id);
        $staff->delete();

        return back()->with('success', 'Staff member removed.');
    }

    public function uploadDocument(Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($id);

        $request->validate([
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png|max:10240',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
        ]);

        $path = $request->file('document_file')->store('staff_documents', 'local');

        $staff->documents()->create([
            'name' => $request->name,
            'type' => $request->type ?? 'General',
            'file_path' => $path,
            'size_bytes' => $request->file('document_file')->getSize(),
            'mime_type' => $request->file('document_file')->getMimeType(),
        ]);

        return back()->with('success', 'Document securely uploaded to staff profile.');
    }

    public function storeLeave(Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($id);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $start = \Carbon\Carbon::parse($request->start_date);
        $end = \Carbon\Carbon::parse($request->end_date);
        $daysCount = $start->diffInDaysFiltered(function (\Carbon\Carbon $date) {
            return !$date->isWeekend();
        }, $end) + 1;

        $staff->leaveRequests()->create([
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'type'       => $request->type,
            'reason'     => $request->reason,
            'status'     => 'pending',
            'days_count' => $daysCount,
        ]);

        return back()->with('success', 'Leave request submitted and awaiting approval.');
    }

    public function approveLeave($staffId, $leaveId)
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($staffId);
        $leave = \App\Models\StaffLeaveRequest::where('staff_member_id', $staff->id)->findOrFail($leaveId);

        if ($leave->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $leave->update(['status' => 'approved']);

        if ($leave->type === 'annual') {
            $staff->decrement('leave_balance', $leave->days_count);
        }

        return back()->with('success', 'Leave approved and balance updated.');
    }

    public function rejectLeave($staffId, $leaveId)
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($staffId);
        $leave = \App\Models\StaffLeaveRequest::where('staff_member_id', $staff->id)->findOrFail($leaveId);

        if ($leave->status === 'approved' && $leave->type === 'annual') {
            $staff->increment('leave_balance', $leave->days_count);
        }

        $leave->update(['status' => 'rejected']);

        return back()->with('success', 'Leave request rejected.');
    }

    public function destroyDocument($staffId, $docId)
    {
        $workspaceId = $this->currentWorkspaceId();
        $staff = StaffMember::where('workspace_id', $workspaceId)->findOrFail($staffId);
        $doc = \App\Models\StaffDocument::where('staff_member_id', $staff->id)->findOrFail($docId);

        $disk = $this->storedFileDisk($doc->file_path);
        $relativePath = $this->normalizeStoredPath($doc->file_path);
        if ($disk && $relativePath) {
            Storage::disk($disk)->delete($relativePath);
        }
        $doc->delete();

        return back()->with('success', 'Document deleted.');
    }

    public function downloadDocument($staffId, $docId)
    {
        $staff = StaffMember::where('workspace_id', $this->currentWorkspaceId())->findOrFail($staffId);
        $doc = \App\Models\StaffDocument::where('staff_member_id', $staff->id)->findOrFail($docId);
        $fullPath = $this->storedFileAbsolutePath($doc->file_path);

        abort_unless($fullPath && file_exists($fullPath), 404, 'Document file not found.');

        return response()->download($fullPath, $doc->name);
    }
}
