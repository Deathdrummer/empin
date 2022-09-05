<?php namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreated extends Mailable {
    use Queueable, SerializesModels;
	
	public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user = null) {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
		$this->subject(config('app.name').' Регистрация в системе');
        return $this->view('site.emails.user_created');
    }
}