<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use App\Services\ActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display store and POS configuration settings.
     */
    public function index(): View
    {
        $settings = Setting::getAll();

        return view('settings.index', compact('settings'));
    }

    /**
     * Update store and POS configuration settings.
     */
    public function update(SettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $inputs = $validated['settings'];

        // Handle Boolean Toggle Switches (HTML checkboxes omit value when unchecked)
        $inputs['enable_barcode'] = $request->has('settings.enable_barcode') ? '1' : '0';
        $inputs['enable_customer'] = $request->has('settings.enable_customer') ? '1' : '0';
        $inputs['enable_sound'] = $request->has('settings.enable_sound') ? '1' : '0';

        // Handle Store Logo File Upload
        if ($request->hasFile('store_logo_file')) {
            $file = $request->file('store_logo_file');
            $uploadDir = public_path('uploads/settings');

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'logo_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);

            $inputs['store_logo'] = 'uploads/settings/' . $filename;
        }

        // Save all settings in batch
        Setting::setMany($inputs);

        ActivityLoggerService::log('settings.updated', 'Updated store and POS configuration settings');

        return redirect()->route('settings.index')->with('success', 'Store and POS settings updated successfully.');
    }
}
