<?php

namespace App\Mail;

use App\Models\CustomTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomTicketCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customTicket;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomTicket $customTicket)
    {
        $this->customTicket = $customTicket;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Custom Ticket Created Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.custom_ticket_created',
            with: [
                'ticketReference' => $this->customTicket->reference,
                'ticketSubject' => $this->customTicket->subject,
                'ticketStatus' => $this->customTicket->status,
                'ticketDescription' => $this->extractTextFromJson($this->customTicket->description),
            ],
        );
    }

    protected function extractTextFromJson(string $jsonString): string
    {
        $data = json_decode($jsonString, true);
        $textContent = '';

        if (isset($data['ops']) && is_array($data['ops'])) {
            foreach ($data['ops'] as $op) {
                if (isset($op['insert']) && is_string($op['insert'])) {
                    $textContent .= $op['insert'];
                }
            }
        }

        return trim($textContent);
    }


    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
