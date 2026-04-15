<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    private function getWorkspaceId()
    {
        return auth()->user()->currentWorkspaceRecord()->id;
    }

    /**
     * Import multiple phone contacts at once.
     */
    public function syncContacts(Request $request)
    {
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $request->validate([
                'contacts' => 'required|array',
                'contacts.*.name' => 'required|string|max:255',
                'contacts.*.phone' => 'required|string|max:50',
                'contacts.*.email' => 'nullable|string|max:255',
            ]);

            $workspaceId = $this->getWorkspaceId();
            $imported = 0;
            $updated = 0;

            foreach ($request->contacts as $contactData) {
                if (empty($contactData['phone'])) continue;

                $normalizedPhone = preg_replace('/[^0-9]/', '', $contactData['phone']);
                if (strlen($normalizedPhone) < 7) continue;

                $suffix = substr($normalizedPhone, -8);
                $contact = Contact::withTrashed()->where('workspace_id', $workspaceId)
                    ->whereRaw("REPLACE(REPLACE(mobile_number, ' ', ''), '+', '') LIKE ?", ["%{$suffix}"])
                    ->first();

                if ($contact) {
                    if ($contact->trashed()) {
                         // This contact was deleted by the user - EXCLUDE it from sync!
                         continue;
                    }
                    if (str_contains($contact->name, 'Imported Contact') || str_contains($contact->name, 'Unknown Caller') || empty($contact->name)) {
                        $contact->update(['name' => $contactData['name']]);
                        $updated++;
                    }
                } else {
                    $email = (!empty($contactData['email']) && filter_var($contactData['email'], FILTER_VALIDATE_EMAIL)) 
                             ? $contactData['email'] : null;

                    Contact::create([
                        'workspace_id' => $workspaceId,
                        'name' => $contactData['name'],
                        'mobile_number' => $contactData['phone'],
                        'email' => $email,
                        'contact_type' => 'customer'
                    ]);
                    $imported++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Sync completed',
                'imported' => $imported,
                'updated' => $updated
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Sync Contacts Validation Failed: ", $e->errors());
            return response()->json(['status' => 'error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Sync Contacts Failed: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Import multiple call logs at once.
     */
    public function syncCallLogs(Request $request)
    {
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $request->validate([
                'logs' => 'required|array',
                'logs.*.phone' => 'required|string',
                'logs.*.type' => 'nullable|string',
                'logs.*.duration' => 'nullable|integer',
                'logs.*.date' => 'required|date',
                'logs.*.notes' => 'nullable|string',
            ]);

            $workspaceId = $this->getWorkspaceId();
            $processed = 0;
            $skipped = 0;

            $validTypes = ['inbound', 'outbound', 'missed'];
            foreach ($request->logs as $logData) {
                if (empty($logData['phone'])) continue;
                
                $type = strtolower($logData['type'] ?? 'inbound');
                if ($type === 'incoming') $type = 'inbound';
                if ($type === 'outgoing') $type = 'outbound';

                if (!in_array($type, $validTypes)) {
                    $skipped++;
                    continue;
                }

                // Validate and parse date
                $logDate = null;
                try {
                    $inputDate = $logData['date'];
                    \Log::info("Sync Call Log Raw Date: " . $inputDate); // Debug

                    if (is_numeric($inputDate)) {
                        // Handle Unix Timestamps (Seconds vs Milliseconds)
                        if ($inputDate > 10000000000) { // Likely milliseconds
                            $inputDate = (int) ($inputDate / 1000);
                        }
                        $logDate = Carbon::createFromTimestamp($inputDate);
                    } else {
                        $logDate = Carbon::parse($inputDate);
                    }

                    if ($logDate->year < 1970 || $logDate->year > 2100) {
                        $skipped++;
                        continue;
                    }
                } catch (\Exception $e) {
                    $skipped++;
                    continue;
                }

                $normalizedPhone = preg_replace('/[^0-9]/', '', $logData['phone']);
                if (strlen($normalizedPhone) < 7) continue; // Skip very short numbers

                $suffix = substr($normalizedPhone, -8); // Match last 8 digits

                $contact = Contact::withTrashed()->where('workspace_id', $workspaceId)
                    ->whereRaw("REPLACE(REPLACE(mobile_number, ' ', ''), '+', '') LIKE ?", ["%{$suffix}"])
                    ->first();

                if ($contact) {
                    if ($contact->trashed()) {
                        // User deleted this contact - SKIP all its logs
                        continue;
                    }
                } else {
                    $contact = Contact::create([
                        'workspace_id' => $workspaceId,
                        'name' => "Imported Contact (" . $logData['phone'] . ")",
                        'mobile_number' => $logData['phone'],
                        'contact_type' => 'customer'
                    ]);
                }

                $exists = CallLog::where('contact_id', $contact->id)
                    ->where('call_date', $logDate->toDateTimeString())
                    ->where('call_type', $type)
                    ->exists();

                if (!$exists) {
                    CallLog::create([
                        'contact_id' => $contact->id,
                        'call_type' => $type,
                        'call_duration_seconds' => $logData['duration'] ?? 0,
                        'call_date' => $logDate->toDateTimeString(),
                        'call_notes' => $logData['notes'] ?? null,
                    ]);
                    $processed++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Call logs sync completed',
                'processed' => $processed,
                'skipped' => $skipped
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Sync Call Logs Validation Failed: ", $e->errors());
            return response()->json(['status' => 'error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Sync Call Logs Failed: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
