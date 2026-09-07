<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'description',
    ];

    /**
     * Standard System Defaults
     */
    public const DEFAULTS = [
        // Store Settings
        'store_name' => 'OmniPOS Superstore',
        'store_logo' => 'images/store-logo.svg',
        'store_address' => '100 Downtown Boulevard, Metropolis',
        'store_phone' => '+1 (555) 019-2831',
        'store_email' => 'contact@omnipos.com',
        'store_website' => 'www.omnipos.com',
        'currency_symbol' => '$',
        'store_tax' => '10',
        'invoice_prefix' => 'INV-',

        // POS Settings
        'pos_default_tax' => '0',
        'pos_default_discount' => '0',
        'receipt_size' => '80mm',
        'enable_barcode' => '1',
        'enable_customer' => '1',
        'enable_sound' => '1',

        // Receipt Footer Note
        'receipt_footer' => 'Thank you for your purchase! Returns accepted within 14 days with original receipt.',
    ];

    /**
     * Retrieve all settings with defaults and persistent cache.
     *
     * @return array<string, mixed>
     */
    public static function getAll(): array
    {
        return Cache::rememberForever('app_settings', function () {
            try {
                $dbSettings = static::pluck('value', 'key')->all();
                return array_merge(static::DEFAULTS, $dbSettings);
            } catch (\Throwable $e) {
                return static::DEFAULTS;
            }
        });
    }

    /**
     * Get a specific setting value with fallback to default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::getAll();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default ?? (static::DEFAULTS[$key] ?? null);
    }

    /**
     * Set a specific setting and clear the cache.
     */
    public static function set(string $key, mixed $value, string $group = 'general', ?string $description = null): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'group' => $group,
                'description' => $description,
            ]
        );

        static::flushCache();

        return $setting;
    }

    /**
     * Batch update multiple settings.
     */
    public static function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $val) {
            static::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($val) ? ($val ? '1' : '0') : (string) $val,
                    'group' => $group,
                ]
            );
        }

        static::flushCache();
    }

    /**
     * Flush settings cache.
     */
    public static function flushCache(): void
    {
        Cache::forget('app_settings');
    }

    /**
     * Get the public URL for the store logo.
     */
    public static function logoUrl(): string
    {
        $logo = static::get('store_logo', 'images/store-logo.svg');

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return asset($logo);
    }
}
