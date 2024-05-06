<?php namespace App\Http\Controllers;

use App\Jobs\getSiteScreenshotJob;
use App\Models\SiteParser;
use App\Models\SiteParserSubject;
use App\Traits\Renderable;
use App\Traits\Settingable;
use Illuminate\Http\Request;

class SiteParserController extends Controller {
	use Renderable, Settingable;
	
	protected $renderPath = 'admin.section.site-parser.render';
	protected $limit = 50;
	protected $subjectsLimit = 100;
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function list(Request $request) {
		[
			'offset'	=> $offset,
			'sortField'	=> $sortField,
			'sortOrder'	=> $sortOrder,
			'stat'		=> $stat
		] = $request->validate([
			'offset'	=> 'required|numeric',
			'sortField'	=> 'required|string',
			'sortOrder'	=> 'required|string',
			'stat'		=> 'nullable|string'
		]);
		
		
		$subjectsIds = $request->input('subjectsIds');
		
		
		
		$list = SiteParser::with('subject')
			->limit($this->limit)
			->offset($offset * $this->limit)
			->when($subjectsIds, function($query) use($subjectsIds) {
				$query->whereIn('subject_id', $subjectsIds);
			})
			->when($stat, function($query) use($stat) {
				$query->where($stat, true);
			}, function($query) {
				$query->whereNull('banned');
				$query->whereNull('valid');
			})
			->orderBy($sortField, $sortOrder)
			->get();
		
		return $this->render('list', compact('list', 'stat'), headers: ['x-count-rows' => $list->count()]);
	}
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function get_subjects(Request $request) {
		$choosed = $request->input('choosedSubjects');
		$offset = $request->input('offset', 0);
		$letter = $request->input('letter', null);
		
		toLog($letter);
		
		$subjects = SiteParserSubject::orderBy('subject', 'asc')
			->when($letter, function($query) use($letter) {
				$query->where('subject', 'LIKE', "$letter%");
			})
			->limit($this->subjectsLimit)
			->offset($offset * $this->subjectsLimit)
			->get()
			->map(function($item) use($choosed) {
				$item['selected'] = in_array($item['id'], $choosed ?? []);
			
				return $item;
			});
		
		$part = $this->subjectsLimit;
		
		return $this->render('subjects', compact('subjects', 'offset', 'part', 'letter'));
	}
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function import_form() {
		getSiteScreenshotJob::dispatch('https://yandex.ru');
		return $this->render('import_form');
	}
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function import_data(Request $request) {
		$data = $request->input('importdata');
		$file = $request->file('importfile');
		$colums = $request->input('colums');
		$required = $request->input('required');
		
		$content = $file?->path() ? file_get_contents($file->path()) : $data;
		
		if (!$content) return response()->json(false);
		
		//$titles = $this->_getTitles($data, $colums);
		
		$rows = $this->_parseData($content, $colums, $required);
		
		$stat = $this->_insertRows($rows->toArray());
		
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function set_stat(Request $request) {
		[
			'id'	=> $id,
			'stat'	=> $stat
		] = $request->validate([
			'id'	=> 'required|numeric',
			'stat'	=> 'required|string'
		]);
		
		
		$row = SiteParser::find($id);
		$row->banned = null;
		$row->valid = null;
		$row->{$stat} = true;
		
		$res = $row->save();
		
		return response()->json($res);
	}
	
	
	
	
	
	
	
	#-----------------------------------------------------------------------------------------------------------------------------------------
	
	
	

	/**
	* 
	* @param 
	* @return 
	*/
	/* private function _getTitles($data = null) {
		if (!$data) return false;

		$titlesStr = substr($data, 0, strpos($data, "\n") ?: null);
		
		$titles = splitString($titlesStr, ";");
		
		return collect($titles);
	} */
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _parseData($data = null, $colums = null, $required = null) {
		if (!$data || !$colums) return false;
		
		$rows = splitString($data, "\n");

		array_splice($rows, 0, 1);
		
		$existingSubjects = $this->_getExistingSubjects();
		
		return collect($rows)->filter(function($item) use($colums, $required) {
			if (empty($required)) return true;
			
			$row = splitString($item, '|');
			
			$filterStat = true;
			foreach ($required as $col => $stat) {
				if ($stat == 0) continue;
				if (empty($row[$colums[$col]]))$filterStat = false;
			}
			
			return $filterStat; 
		})->map(function($item) use($colums, &$existingSubjects) {
			$row = splitString($item, '|');
			
			$buildedRow = [];
			foreach ($colums as $col => $pos) {
				if ($col == 'subject') {
					if (isset($row[$pos]) && in_array($row[$pos], array_keys($existingSubjects))) {
						$buildedRow['subject_id'] = $existingSubjects[$row[$pos]] ?? null;
					} elseif (isset($row[$pos])) {
						$savedSubjectId = $this->_saveSubject($row[$pos]);
						
						$existingSubjects[$row[$pos]] = $savedSubjectId;
						
						$buildedRow['subject_id'] = $savedSubjectId;
					}
				} else {
					$buildedRow[$col] = $row[$pos] ?? null;
				}
			}
			
			return $buildedRow;
		});
	}
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	/* private function _parseSubjects($data = null) {
		if (!$data) return false;
		
		return array_unique(array_column($data, ' '));
	} */
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _saveSubject($subjectName = null) {
		$savedSubject = SiteParserSubject::create([
			'subject' => $subjectName,
		]);
		return $savedSubject?->id;
	}
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _insertRows($rows = null) {
		if (!$rows) return false;
		
		$rowsCollection = collect($rows);

		$chunks = $rowsCollection->chunk(1000);
		
		$stat = true;
		$chunks->each(function($part, $k) use(&$stat) {
			if (!SiteParser::insert($part->toArray())) {
				$stat = false;
			}
		});
		
		return $stat;
	}
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _getExistingSubjects() {
		$subjects = SiteParserSubject::all();
		return $subjects->mapWithKeys(fn(SiteParserSubject $item) => [$item['subject'] => $item['id']])->toArray();
	}
	
	
	
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	private function _multiple() {
		$urls = array(
			"https://mini.s-shot.ru/1920x1080/2000/jpg/?https://unu.im",
			"https://mini.s-shot.ru/1920x1080/2000/jpg/?https://podcast.adobe.com",
			"https://mini.s-shot.ru/1920x1080/2000/jpg/?https://whatsmaster.ru",
			"https://mini.s-shot.ru/1920x1080/2000/jpg/?https://tenchat.ru",
			"https://mini.s-shot.ru/1920x1080/2000/jpg/?https://online-video-cutter.com",
			"https://mini.s-shot.ru/1920x1080/2000/jpg/?https://www.avito.ru",
		);

		$multi = curl_multi_init();
		$channels = array();

		foreach ($urls as $url) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt($ch, CURLOPT_PROXY, $proxy);
		
			curl_multi_add_handle($multi, $ch);

			$channels[$url] = $ch;
		}

		$active = null;
		do {
			$mrc = curl_multi_exec($multi, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($multi) == -1) {
				continue;
			}

			do {
				$mrc = curl_multi_exec($multi, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}

		foreach ($channels as $channel) {
			echo '1';
			file_put_contents('storage/test/'.rand(0, 9999).'_screen.jpg', curl_multi_getcontent($channel));
			curl_multi_remove_handle($multi, $channel);
		}

		curl_multi_close($multi);
	}
	
	
	
	
	
	
	
}