<?php namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class getSiteScreenshotJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
	
	private $url;
    
	/**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($url = null) {
        $this->url = $url;
    }
	
	
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $content = $this->_screen($this->url, "1920x1080", "2000");
		Storage::put('site_parser/'.$this->_generateFilename().'.jpg', $content);
    }
	
	
	
	/**
     * Execute the job.
     *
     * @return void
     */
	private function _screen($url, $razr, $razm, $form = 'jpg') {
    	$toapi = "http://mini.s-shot.ru/".$razr."/".$razm."/".$form."/?".$url;
   	 	$scim = file_get_contents($toapi);
		return $scim;
	}

	
	
	
	private function _generateFilename() {
		$filename = Str::random(40);
		if (Storage::exists($filename)) {
			$filename = $this->_generateFilename();
		}
		return $filename;
	}
	
	
	
}
