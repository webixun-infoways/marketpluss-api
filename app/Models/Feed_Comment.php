<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feed_Comment extends Model
{
    use HasFactory;
    protected $table="feed_comments";

   public function replies() {
		return $this->hasMany(Feed_Comment::class, 'parent_id');
   }
}
