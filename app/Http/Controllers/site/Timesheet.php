<?php namespace App\Http\Controllers\site;

use App\Helpers\DdrDateTime;
use App\Http\Controllers\Controller;
use App\Services\Business\User as UserService;

use App\Traits\Renderable;
use App\Traits\Settingable;
use Carbon\Carbon;
use Error;
use Illuminate\Http\Request;
use Tochka\Calendar\WorkCalendar;

class Timesheet extends Controller {
	use Renderable, Settingable;
	
	protected $renderPath = 'site.section.timesheet.render';
	protected $data = [];
	
	//protected $contracts;
	
	
	public function __construct(UserService $user) {
		//$this->department = $department;
		//$this->contract = $contract;
		//$this->user = $user;
	}
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function init(Request $request) {
		/* [
			'views' => $viewsPath,
		] = $request->validate([
			'views'	=> 'string|required',
		]); */
		
		return $this->render('list');
	}
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function get_slides_data(Request $request) {
		//sleep(2);
		[
			'indexes'	=> $indexes,
		] = $request->validate([
			'indexes'	=> 'required|array',
		]);
		
		
		
		
		
		$testData = [];
		foreach ($indexes as $idx) {
			
			
			$dateObj = DdrDateTime::getOffsetDate($idx);
			$date = DdrDateTime::convertDateFormat($dateObj);
			
			
			
			//$dateObj = DdrDateTime::convertDateFormat('2025.04.23');
			
			//$date = DdrDateTime::date($dateObj);
			
			$testData[] = [
				'index' 	=> (int)$idx,
				'content' 	=> "<div><p>loaded: {$date}</p></div>",
			];
		}
		
		return response()->json($testData);
	}
	
	
	
	
}	