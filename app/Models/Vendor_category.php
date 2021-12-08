<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor_category extends Model
{
    use HasFactory;
    protected $table="vendor_categories";
	
	public function products()
    {
        return $this->hasMany(Vendor_Product::class)->whereIn('status',['active','inactive']);
		
    }
}
