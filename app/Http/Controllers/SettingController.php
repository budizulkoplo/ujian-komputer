<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Settings/Index', [
            'setting' => AppSetting::first() ?? new AppSetting(['cheat_limit' => 3]),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['required', 'string'],
            'cheat_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'school_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $setting = AppSetting::first() ?? new AppSetting();

        if ($request->hasFile('school_logo')) {
            if ($setting->school_logo) {
                Storage::disk('public')->delete($setting->school_logo);
            }

            $validated['school_logo'] = $request->file('school_logo')->store('settings', 'public');
        }

        $setting->fill($validated)->save();

        return back();
    }
}