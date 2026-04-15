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
            ]);

            $workspaceId = $this->getWorkspaceId();
            
            // Optimization: Pre-fetch all contacts in one go
            $existingContacts = Contact::withTrashed()
                ->where('workspace_id', $workspaceId)
                ->get()
                ->mapWithKeys(function ($c) {
                    $norm = preg_replace('/[^0-9]/', '', $c->mobile_number);
                    return [substr($norm, -8) => $c];
                });

            $imported = 0;
            $updated = 0;

            DB::beginTransaction();

            foreach ($request->contacts as $contactData) {
                if (empty($contactData['phone']) || empty($contactData['name'])) {
                    continue;
                }

                $normalizedPhone = preg_replace('/[^0-9]/', '', $contactData['phone']);
                if (strlen($normalizedPhone) < 7) continue;

                $suffix = substr($normalizedPhone, -8);
                $contact = $existingContacts[$suffix] ?? null;

                if ($contact) {
                    if ($contact->trashed()) {
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

            DB::commit();

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
            ]);

            $workspaceId = $this->getWorkspaceId();

            // Optimization: Pre-fetch all contacts in one go
            $existingContacts = Contact::withTrashed()
                ->where('workspace_id', $workspaceId)
                ->get()
                ->mapWithKeys(function ($c) {
                    $norm = preg_replace('/[^0-9]/', '', $c->mobile_number);
                    return [substr($norm, -8) => $c];
                });

            $processed = 0;
            $skipped = 0;

            DB::beginTransaction();

            $validTypes = ['inbound', 'outbound', 'missed'];
            foreach ($request->logs as $index => $logData) {
                // Modified to skip invalid items instead of failing the whole batch
                if (empty($logData['phone']) || empty($logData['date'])) {
                    $skipped++;
                    continue;
                }
                
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
                    if (is_numeric($inputDate)) {
                        if ($inputDate > 10000000000) { 
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
                if (strlen($normalizedPhone) < 7) {
                    $skipped++;
                    continue;
                }

                $suffix = substr($normalizedPhone, -8); 
                $contact = $existingContacts[$suffix] ?? null;

                if ($contact) {
                    if ($contact->trashed()) {
                        continue;
                    }
                } else {
                    $contact = Contact::create([
                        'workspace_id' => $workspaceId,
                        'name' => "Imported Contact (" . $logData['phone'] . ")",
                        'mobile_number' => $logData['phone'],
                        'contact_type' => 'customer'
                    ]);
                    // Update cache to reflect newly created contact in current batch
                    $existingContacts[$suffix] = $contact;
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

            DB::commit();

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
