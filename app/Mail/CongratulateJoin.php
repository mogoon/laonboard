<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;

class CongratulateJoin extends Mailable
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
        $url = route('emailCertify', [
                'id' => $this->user->id_hashkey,
                'crypt' => $this->user->email_certify2
            ]);
        return $this->subject($this->subject)
                    ->view('mail.congratulate_join')
                    ->with([
                        'user' => $this->user,
                        'url' => $url
                    ]);
    }
}