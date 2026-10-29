<?php

namespace App\Imports;

use App\Models\Chat;
use App\Models\ChatLog;
use App\Models\Contact;
use App\Models\ContactGroup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Propaganistas\LaravelPhone\PhoneNumber;

class ContactsImport extends \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder implements ToModel, WithHeadingRow, WithCustomValueBinder, WithChunkReading
{
    protected $successfulImports = 0;
    protected $totalImports = 0;
    protected $failedImportsDueToFirstName = 0;
    protected $failedImportsDueToFormat = 0;
    protected $failedImportsDueToDuplicates = 0;

    public function model(array $row)
    {
        try {
            $this->totalImports++;
    
            // Normalize the phone number.
            $phoneNumberValue = trim($row['phone'] ?? '');
            if (!str_starts_with($phoneNumberValue, '+')) {
                $phoneNumberValue = '+' . $phoneNumberValue;
            }
    
            // Basic validation for required fields.
            $validator = Validator::make($row, [
                'first_name' => 'required',
                'phone'      => 'required'
            ], [
                'first_name.required' => 'The first name is required.',
                'phone.required'      => 'The phone is required.'
            ]);
    
            if ($validator->fails()) {
                $this->failedImportsDueToFirstName++;
                return null;
            }
    
            // Validate phone number.
            $phoneNumberObj = new PhoneNumber($phoneNumberValue);
            if (!$phoneNumberObj->isValid()) {
                $this->failedImportsDueToFormat++;
                return null;
            }
    
            // Format phone as E164.
            $phoneNumber = phone($phoneNumberValue)->formatE164();
            $organizationId = session()->get('current_organization');
    
            // 1. Find existing contacts (including soft-deleted ones) with this phone number.
            $existingContacts = Contact::withTrashed()
                ->where('organization_id', $organizationId)
                ->where('phone', $phoneNumber)
                ->get();
    
            // 2. Save their IDs and get latest chat timestamp.
            $oldContactIds = $existingContacts->pluck('id')->toArray();
            $latestChatTimestamp = null;
            if (!empty($oldContactIds)) {
                $latestChatTimestamp = Chat::whereIn('contact_id', $oldContactIds)->max('created_at');
            }
    
            // 3. Handle group association if provided.
            $group = null;
            if (isset($row['group_name'])) {
                $group = ContactGroup::firstOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'name'            => $row['group_name']
                    ],
                    [
                        'created_by'      => auth()->user()->id
                    ]
                );
            }
    
            // 4. Create the new contact.
            $newContact = Contact::create([
                'organization_id'  => $organizationId,
                'first_name'       => $row['first_name'],
                'last_name'        => $row['last_name'] ?? null,
                'phone'            => $phoneNumber,
                'email'            => $row['email'] ?? null,
                'address'          => json_encode([
                    'street'  => $row['street']  ?? null,
                    'city'    => $row['city']    ?? null,
                    'state'   => $row['state']   ?? null,
                    'zip'     => $row['zip']     ?? null,
                    'country' => $row['country'] ?? null,
                ]),
                'contact_group_id' => $group ? $group->id : null,
                'created_by'       => auth()->user()->id,
            ]);
    
            // 5. Reassign chats from old contacts to the new contact.
            if (!empty($oldContactIds)) {
                Chat::whereIn('contact_id', $oldContactIds)
                    ->update(['contact_id' => $newContact->id]);

                ChatLog::whereIn('contact_id', $oldContactIds)
                    ->update(['contact_id' => $newContact->id]);
    
                if ($latestChatTimestamp) {
                    $newContact->update(['latest_chat_created_at' => $latestChatTimestamp]);
                }
            }
    
            // 6. Now delete the old contacts.
            if (!empty($oldContactIds)) {
                Contact::whereIn('id', $oldContactIds)->forceDelete();
            }
    
            $this->successfulImports++;
            return $newContact;
        } catch (\Exception $e) {
            Log::error("Contact Import Error: " . $e->getMessage(), [
                'row'       => $row,
                'exception' => $e,
            ]);
            $this->failedImportsDueToFormat++;
            return null;
        }
    }

    public function getFailedImportsDueToDuplicatesCount()
    {
        return $this->failedImportsDueToDuplicates;
    }

    public function getFailedImportsDueToFirstName()
    {
        return $this->failedImportsDueToFirstName;
    }

    public function getFailedImportsDueToFormat()
    {
        return $this->failedImportsDueToFormat;
    }

    public function getSuccessfulImports()
    {
        return $this->successfulImports;
    }

    public function getTotalImportsCount()
    {
        return $this->totalImports;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}



