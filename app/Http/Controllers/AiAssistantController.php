<?php namespace App\Http\Controllers;

use App\Models\AssistentFile;
use App\Services\VisionAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiAssistantController extends Controller {
	
	
	
	public function __construct() {
		
	}
    
	/**
	 * @param 
	 * @return 
	 */
	public function index() {
		$assistentService = app()->make(VisionAssistantService::class);
		$list = $assistentService->listAssistants();

		//toLog($list);

		// Пример 1: Базовое использование
		$assistent = $assistentService->use([
			'assistant_id' => $list['data'][6]['id'],
			'instruction_file' => 'prompts/plan.txt',
			'dir' => 'assistent',
			'file_search' => true,
		]);

		$question = 'сколько опор на схеме ы видишь?';

		$answer = $assistent->ask($question);

		return response()->json(['answer' => $answer]);
	}
	
	
	
	
}