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
        \Illuminate\Support\Facades\Log::info('Handling WhatsApp Job', ['guest_id' => $this->guest->id]);

        // Construction du lien d'invitation
        $invitationLink = "https://ce.kgslab.com/invitation/" . $this->guest->invitation_token;
        
        $message = "✨ *Invitation : {$event->title}* ✨\n\n";
        $message .= ($event->invitation_text ?? "Vous êtes cordialement invité à notre événement !") . "\n\n";
        $message .= "👉 Voir mon invitation : {$invitationLink}\n\n";
        $message .= "Nous avons hâte de vous voir !";

        \Illuminate\Support\Facades\Log::info('Sending via UltraMsg', ['to' => $this->guest->whatsapp_number]);

        // Envoi via UltraMsg
        $result = $whatsappService->sendMessage(
            $this->guest->whatsapp_number,
            $message
        );

        if ($result) {
            \Illuminate\Support\Facades\Log::info('WhatsApp sent successfully');
            $this->guest->update(['status' => 'sent']);
        } else {
            \Illuminate\Support\Facades\Log::error('WhatsApp failed to send');
        }
    }
}
