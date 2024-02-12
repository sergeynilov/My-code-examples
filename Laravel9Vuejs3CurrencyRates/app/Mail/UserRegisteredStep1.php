<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegisteredStep1 extends Mailable
{
    use Queueable, SerializesModels;

    public $siteName;
    public $user;
    public $confirmationCode;

    public function __construct($siteName, $user, $confirmationCode)
    {
        $this->siteName          = $siteName;
        $this->user              = $user;
        $this->confirmation_code = $confirmationCode;
    }

    public function build()
    {
        return $this->markdown('email.UserRegisteredEmail')
            ->with('siteName', $this->siteName)
            ->with('user', $this->user)
            ->with('confirmation_code', $this->confirmation_code);
    }
}
