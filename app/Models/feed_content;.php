<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class feed_content extends Model
{
    use HasFactory;
	protected $table = "feed_contents";
    public $timestamps = false;
    
}
