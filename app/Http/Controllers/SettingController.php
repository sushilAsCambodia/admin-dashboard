<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingFormRequest;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private SettingService $settingService)
    {
    }

    public function store(SettingFormRequest $request)
    {
        return $this->settingService->store($request->all());
    }

    public function update(SettingFormRequest $request, Setting $setting)
    {
        return $this->settingService->update($setting, $request->all());
    }

    public function delete(Setting $setting)
    {
        return $this->settingService->delete($setting);
    }

    public function get(Setting $setting)
    {
        return response()->json($setting, 200);
    }

    public function getByKey($key)
    {
        return response()->json(Setting::where('key', $key)->first(), 200);
    }

    public function all()
    {
        return response()->json(Setting::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->settingService->paginate($request);
    }
}
