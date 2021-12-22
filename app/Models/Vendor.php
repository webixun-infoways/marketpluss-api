<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Vendor extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

      /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
	
	public function covers()
    {
        return $this->hasMany(Vendor_cover::class);
    }
	
	public function categories()
    {
        return $this->hasMany(Vendor_category::class);
    }
	
	public function offers(){
		return $this->hasOne(Vendor_Offer::class)->where('status','active')->latest();
	}
	
	public function timings(){
		return $this->hasMany(vendor_timing::class)->where('day_status','1');
	}
	
	public function today_timing(){
		
		$day=date("l");
		return $this->hasMany(vendor_timing::class)->where('open_timing','<', NOW())->where('close_timing','>', NOW())->where('day_name',$day)->where('day_status','1');
	}
	
}
