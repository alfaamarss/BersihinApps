<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Area;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AppSettingsController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request)
    {
        $setting = AppSetting::query()->latest()->first();

        if (! $setting) {
            return $this->ok(null);
        }

        $city = null;

        if ($setting->configured_city_id) {
            $cityArea = Area::find($setting->configured_city_id);

            if ($cityArea) {
                $city = [
                    'id' => (string) $cityArea->id,
                    'name' => $cityArea->name,
                    'level' => $cityArea->level,
                ];
            }
        }

        if (! $city && $setting->configured_city_name) {
            $city = [
                'id' => (string) ($setting->configured_city_id ?? $setting->configured_city_name),
                'name' => $setting->configured_city_name,
                'level' => 'City',
            ];
        }

        $province = null;
        if ($setting->province_id) {
            $province = [
                'id' => (string) $setting->province_id,
                'name' => $setting->province_id,
            ];
        }

        return $this->ok([
            'province' => $province,
            'city' => $city,
        ]);
    }
}
