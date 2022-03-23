<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    use HasFactory;
    protected $table="feeds";
	
	 public function feed_content()
    {
        return $this->hasMany(feed_content::class);
    }
	
	 public function feed_like()
    {
        return $this->hasMany(Feed_like::class);
    }
	public function feed_comment()
    {
        return $this->hasMany(Feed_Comment::class);
    }
	
}
