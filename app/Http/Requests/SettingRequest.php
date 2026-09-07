<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            // Store Settings
            'settings.store_name' => ['required', 'string', 'max:255'],
            'settings.store_address' => ['nullable', 'string', 'max:500'],
            'settings.store_phone' => ['nullable', 'string', 'max:50'],
            'settings.store_email' => ['nullable', 'email', 'max:255'],
            'settings.store_website' => ['nullable', 'string', 'max:255'],
            'settings.currency_symbol' => ['required', 'string', 'max:10'],
            'settings.store_tax' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings.invoice_prefix' => ['required', 'string', 'max:20'],

            // POS Settings
            'settings.pos_default_tax' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings.pos_default_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings.receipt_size' => ['required', 'in:58mm,80mm,a4'],
            'settings.enable_barcode' => ['nullable'],
            'settings.enable_customer' => ['nullable'],
            'settings.enable_sound' => ['nullable'],
            'settings.receipt_footer' => ['nullable', 'string', 'max:500'],

            // Logo File Upload
            'store_logo_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.store_name.required' => 'The store name is required.',
            'settings.currency_symbol.required' => 'The currency symbol is required.',
            'settings.invoice_prefix.required' => 'The invoice prefix is required.',
            'settings.receipt_size.in' => 'The receipt size must be 58mm, 80mm, or a4.',
            'store_logo_file.max' => 'The store logo may not be greater than 2MB.',
        ];
    }
}
