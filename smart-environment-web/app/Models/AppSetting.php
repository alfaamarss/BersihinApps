<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $fillable = [
        'app_name',
        'app_logo_url',
        'app_banner_url',
        'configured_city_name',
        'configured_city_id',
        'province_id',
        'off_schedule_surcharge',
    ];
}
