<?php namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminUserCreated extends Mailable {
    use Queueable, SerializesModels;
	
	
	
	public $adminUser;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($adminUser = null) {
        $this->adminUser = $adminUser;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->view('admin.emails.admin_user_created');
    }
}