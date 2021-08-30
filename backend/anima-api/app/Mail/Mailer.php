<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Token;

class Mailer extends Mailable
{
    use Queueable, SerializesModels;

    public $subject = "Token";
    public $token;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.token')->with([
            'token' => $this->token->value,
            'expiration' => $this->token->expiration,
        ]);
    }
}
