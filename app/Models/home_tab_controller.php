<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class home_tab_controller extends Model
{
    use HasFactory;
    protected $table="home_tab_controllers";
	
	public function fetch_content()
    {
        return $this->hasMany(home_tab_content::class);
    }
}
