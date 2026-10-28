<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Helpers\UnreadCountHelper;

class BackfillUnreadCount extends Command
{
    protected $signature = 'contacts:backfill-unread-count';
    protected $description = 'Backfill unread_count for all existing contacts';

    public function handle()
    {
        $this->info('Starting to backfill unread_count...');

        $totalContacts = Contact::count();
        $progressBar = $this->output->createProgressBar($totalContacts);

        Contact::chunk(500, function ($contacts) use ($progressBar) {
            foreach ($contacts as $contact) {
                UnreadCountHelper::recalculateUnreadCount($contact->id);
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->info("\n" . 'Unread count backfill completed successfully!');
    }
}
