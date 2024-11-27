<?php

namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectionSort extends Model{
	use HasFactory, Collectionable;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'selection_sort';
	
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'account_id',
		'selection_id',
		'sort',
	];
	
    // Если хотите разрешить массовое заполнение всех полей (не рекомендуется)
    // protected $guarded = [];

	
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
        'account_id' => 'integer',
		'selection_id'=> 'integer',
		'sort'=> 'integer',
    ];
}
