<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class Section extends Model {
    use HasFactory, Collectionable;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'sections';
	
	
	/**
     * Раздел аутентификации
	 *
     * @var string
     */
	protected $guard = 'site';
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'section',
		'page_title',
		'title',
		'visible',
		'nav'
	];
	
	
	/**
     * учитывать временные поля created_at и updated_at
	 *
     * @var string
     */
	public $timestamps = false;
	
	
	/**
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	protected $casts = [
        'title' => 'array',
        'page_title' => 'array',
    ];
	
	/**
	 * @param string  $value
	 * @return 
	 */
	//public function setTitleAttribute($value) {
	//	$this->attributes['title'] = is_array($value) ? json_encode($value) : $value;
	//}
	
	/**
	 * @param string  $value
	 * @return 
	 */
	//public function setPageTitleAttribute($value) {
	//	$this->attributes['page_title'] = is_array($value) ? json_encode($value) : $value;
	//}
	
	
	
	/**
     * 
     *
     * @param string  $value
     * @return string
     */
    public function getTitleAttribute($value) {
		$value = isJson($value) ? json_decode($value, true) : $value;
		$lang = App::currentLocale();
		return $value[$lang] ?? null;
	}
	
	/**
     * 
     *
     * @param string  $value
     * @return string
     */
    public function getPageTitleAttribute($value) {
		$value = isJson($value) ? json_decode($value, true) : $value;
		$lang = App::currentLocale();
		return $value[$lang] ?? null;
	}
	
	
	
	
	/**
	 * @param string  $value
	 * @return 
	 */
	public function setSettingsAttribute($value) {
		$this->attributes['value'] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
	}
	
	
	/**
     * 
     *
     * @param string  $value
     * @return string
     */
    public function getSettingsAttribute($value) {
		return isJson($value) ? json_decode($value, true) : $value;
	}
	
	
	
	
	
	
	/**
	 * @param string  $activeNav
	 * @return Collection
	 */
	public function getSections($activeNav = false): Collection {
		$navData = collect([]);
		$sections = $this->select('id', 'section', 'title', 'parent_id', 'nav')
						->where('visible', 1)
						->orderBy('_sort', 'ASC')
						->get()
						->filter(function ($item) {
							if (auth('site')->check() && auth('site')->user()->is_main_admin) return true;
							return auth('site')->check() && auth('site')->user()->can('section-'.$item['section'].':site');
						});
			
		if ($sections->isEmpty()) return $navData;
		
		$groupedSections = $sections->groupBy('parent_id');
		
		if ($groupedSections->get(0)->isEmpty()) return $navData;
		
		$navData = $groupedSections->get(0)->each(function ($item, $key) use($groupedSections, $activeNav) {
			$children = $groupedSections->get($item['id']);
			if ($children) $item['children'] = $children->toArray();
			if ($activeNav == $item['section']) $item['active'] = true;
		});
		
		return $navData;
	}
	
	
	
	
	
}