<?php

namespace App\Jobs;

use App\Models\Guest;
use App\Models\Event;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable as BusQueueable; // Wait, traits usually handled mostly by Queueable in L11/12? No, standard set.

class SendWhatsAppInvite implements ShouldQueue
{
    use Queueable;

    public $guest;

    /**
     * Create a new job instance.
     */
    public function __construct(Guest $guest)
    {
        $this->guest = $guest;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        $event = $this->guest->event;

        // Construct message components
        $invitationLink = url('/invitation/' . $this->guest->invitation_token); // Or short_link if implemented
        $customMessage = $event->invitation_text ?? 'Vous êtes invité !';

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $event->title],
                    ['type' => 'text', 'text' => $invitationLink],
                    ['type' => 'text', 'text' => $customMessage],
                ]
            ]
        ];

        // Send
        $whatsappService->sendTemplateMessage(
            $this->guest->whatsapp_number,
            'ceremony_invite_v1',
            'fr',
            $components
        );

        $this->guest->update(['status' => 'sent']);
    }
}
