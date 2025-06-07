<?php namespace App\Http\Controllers\site;

use App\Helpers\DdrDateTime;
use App\Http\Controllers\Controller;
use App\Http\Filters\ContractFilter;
use App\Http\Resources\TimesheetChatResource;
use App\Http\Resources\TimesheetTeamResource;
use App\Models\Contract as ContractModel;
use App\Models\Department;
use App\Models\Staff;
use App\Models\TimesheetContract;
use App\Models\TimesheetTeam;
use App\Services\Business\User as UserService;
use App\Services\Settings;
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
	public function getSlidesData(Request $request) {
		[
			'indexes'	=> $indexes,
		] = $request->validate([
			'indexes'	=> 'required|array',
			'indexes.*' => 'integer',
		]);
		
		$indexes = array_map('intval', $indexes);

		$teams = TimesheetTeam::getByDaysIndexes($indexes)
			->with('profile')
			->with('contracts.contract')
			->with('contracts.chat.profile')
			->get()
			->groupBy(fn($team) => $team->day instanceof Carbon ? $team->day->toDateString() : $team->day);
		
		
		$teams = $teams->map(fn($group) => TimesheetTeamResource::collection($group)->resolve());
		
		$daysData = [];
		foreach ($indexes as $idx) {
			$dateObj = DdrDateTime::getOffsetDate($idx);
			$day = $dateObj->toDateString();
			$weekDayNum = (int)DdrDateTime::numOfWeek($dateObj);
			
			$daysData[] = [
				'index'		=> (int)$idx,
				'weekDay'	=> DdrDateTime::dayOfWeek($dateObj),
				'humanDate'	=> DdrDateTime::dateToHuman($dateObj, 'ru'),
				'day'		=> $day,
				'isWeekEnd'	=> in_array($weekDayNum, [6,7]),
				'isToday'		=> $idx == 0,
				'teams' 	=> $teams[$day] ?? null,
			];
		}
		
		return response()->json($daysData);
	}
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function contractsList(Request $request) {
		[
			'search'	=> $search,
		] = $request->validate([
			'search'	=> 'required|string',
		]);
		
		$queryParams = ['search' => $search];
		$filter = app()->make(ContractFilter::class, compact('queryParams'));
		
		$data = ContractModel::filter($filter)
			->select(['id', 'object_number', 'title', 'titul'])
			->limit(50)
			->get();
		
		
		return response()->json($data);
	}
	
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function addTeam(Request $request) {
		$validFields = $request->validate([
			'staff_id'	=> 'required|integer',
			'day'		=> 'required|date',
		]);
		
		$res = TimesheetTeam::create($validFields);
		
		return response()->json($res->id);
	}
	
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function addContract(Request $request) {
		[
			'contract_id'	=> $contractId,
			'team_id'		=> $teamId,
		] = $request->validate([
			'contract_id'	=> 'required|integer',
			'team_id'		=> 'required|integer',
		]);
		
		$team = TimesheetTeam::find($teamId);
		$contract = $team->contracts()->create(['contract_id' => $contractId]);
		
		return response()->json($contract->id);
	}
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function addComment(Request $request) {
		[
			'timesheet_contract_id'	=> $timesheetContractId,
			'message'				=> $message,
		] = $request->validate([
			'timesheet_contract_id'	=> 'required|integer',
			'message'				=> 'required|string',
		]);
		
		$contract = TimesheetContract::find($timesheetContractId);
		$comment = $contract->chat()->create([
			'from_id' => auth('site')->user()->staff_id,
			'message' => $message,
		]);
		
		$comment->load('profile');
		
		//$comment->created_at = $comment->created_at->translatedFormat('d F Y г. в H:i'),
		
		
		return new TimesheetChatResource($comment);
		
		//return response()->json($comment);
	}
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function getStaff() {
		$depsIds = Department::where('show_in_timesheet', true)->select('id')->pluck('id');
		$staff = Staff::select(['id', 'sname', 'fname', 'mname'])
			->whereRelation('registred', fn($q) => $q->whereIn('department_id', $depsIds))
			->get();
		
		return response()->json($staff);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}	