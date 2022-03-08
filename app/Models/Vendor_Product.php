<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor_Product extends Model
{
    use HasFactory;
    protected $table="vendor_products";
	
	public function favourite(){
		return $this->hasOne(user_product_saves::class,'product_id');
	}
}
