<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;

class JoinNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $subject)
    {
        $this->user = $user;
        $this->subject = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = cache('config.email.default')->adminEmail;
        $name = cache('config.email.default')->adminEmailName;
        return $this
            ->from($address, $name)
            ->subject($this->subject)
            ->view('mail.default.join_notification')
            ->with('user', $this->user);
    }
}
