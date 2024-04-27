<?php namespace App\Models;

use App\Models\Traits\Collectionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class act extends Model {
    use HasFactory, Collectionable;
	
	protected $table = 'acts_templates';
	
	protected $guarded = false;
	
	
	
	
	
}
