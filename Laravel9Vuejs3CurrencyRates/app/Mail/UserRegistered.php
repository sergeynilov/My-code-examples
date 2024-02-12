<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $siteMame;
    public $user;

    public function __construct($siteMame, $user, $confirmationCode)
    {
        $this->siteName = $siteMame;
        $this->user      = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('email.UserRegisteredEmail')
            ->with('siteName', $this->siteName)
            ->with('user', $this->user);
    }
}
