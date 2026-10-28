<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Propaganistas\LaravelPhone\PhoneNumber;

class SmbSyncContacts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $organization_id, private $contacts)
    {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->contacts as $contactBag) {
            if($contactBag['type'] != 'contact' || $contactBag['action'] != 'add') continue; // skip any non-contact or delete one
            $contactData = $contactBag['contact'] ?? null;

            $phone = $contactData['phone_number'];

            if (!str_starts_with($phone, '+')) {
                $phone = '+' . $phone;
            }

            $phone = new PhoneNumber($phone);
            $phone = $phone->formatE164();

            // Check if contact exists in the organization
            $contact = Contact::where('organization_id', $this->organization_id)
                ->where('phone', $phone)
                ->whereNull('deleted_at')->first();

            $first_name = $contactData['first_name'] ?? $contactData['full_name'] ?? null;
            $last_name = ($first_name && isset($contactData['first_name']) && isset($contactData['full_name'])) ? trim(substr($contactData['full_name'], strlen($contactData['first_name']))) : null;
            if ($contact === null) {
                // Create a new contact
                Contact::create([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $contactData['email'] ?? null,
                    'phone' => $phone,
                    'organization_id' => $this->organization_id,
                    'created_by' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Update existing contact
                $contact->update([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $contactData['email'] ?? null,
                    'updated_at' => now(),
                    'deleted_at' => null
                ]);
            }
        }
    }
}
