<?php

namespace App\Notifications\TelegramVoteCreated;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\TelegramChannel;
use App\Models\{Vote, User};

class SendMessageWithReviewLink extends Notification
{
    use Queueable;

    private Vote $vote;
    private User $voteCreator;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Vote $vote, User $voteCreator)
    {
        $this->voteCreator = $voteCreator;
    }

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        $url = url('/telegram-dashboard');
        return TelegramMessage::create()
           ->line("  New vote *" .$this->vote->name . "* was created by " . $this->voteCreator->username)
            ->line("  Please review.")
            ->button('View Vote', $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
