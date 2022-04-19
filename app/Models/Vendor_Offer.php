<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor_Offer extends Model
{
    use HasFactory;
    protected $table="vendor_offers";
	

    public function vendor(){
	    return $this->belongsTo(Vendor::class);
	}
	
}

