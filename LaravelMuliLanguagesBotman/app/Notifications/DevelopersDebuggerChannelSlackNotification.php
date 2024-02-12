<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class DevelopersDebuggerChannelSlackNotification extends Notification
{

    private string $errorMsg;
    private string $exceptionClass;
    private string $file;
    private int $line;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        string $errorMsg,
        string $exceptionClass,
        string $file,
        int $line
    ) {
        $this->errorMsg = $errorMsg;
        $this->exceptionClass = $exceptionClass;
        $this->file = $file;
        $this->line = $line;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        $contentText = 'Error message " ' . $this->errorMsg . '" with "' . $this->exceptionClass .
                       '" exception, in file "' . $this->file . '" at line "' . $this->line . "'";

        $slackMessage = (new SlackMessage)
            ->from('Errors debugger')
            ->content($contentText . ' => ' . getOSInfo(false));
        return $slackMessage;
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
