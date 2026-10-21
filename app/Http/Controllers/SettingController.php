<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\Setting\SettingService;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    public function index()
    {
        $this->authorize('setting.view');

        $settings = $this->settingService->getAll(request('group'));

        $groups = Setting::where('is_sensitive', false)
            ->select('group_name')
            ->distinct()
            ->pluck('group_name');

        return view('settings.index', compact('settings', 'groups'));
    }

    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        $this->settingService->updateValue($setting, $request->validated()['value']);

        return back()->with('success', "Configuración '{$setting->key}' actualizada.");
    }
}
