<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-readable formatted date: e.g. Sep 05, 2026
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('M d, Y') : '—';
    }

    /**
     * Human-readable formatted time: e.g. 10:45 AM
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('h:i A') : '—';
    }

    /**
     * Scope: Full-text Search
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);
        return $query->where(function (Builder $q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
              ->orWhere('action', 'like', "%{$term}%")
              ->orWhere('module', 'like', "%{$term}%")
              ->orWhere('ip_address', 'like', "%{$term}%")
              ->orWhereHas('user', function (Builder $userQ) use ($term) {
                  $userQ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
              });
        });
    }

    /**
     * Scope: Filter by Module
     */
    public function scopeFilterModule(Builder $query, ?string $module): Builder
    {
        if (!empty($module) && $module !== 'all') {
            return $query->where('module', $module);
        }
        return $query;
    }

    /**
     * Scope: Filter by Action
     */
    public function scopeFilterAction(Builder $query, ?string $action): Builder
    {
        if (!empty($action) && $action !== 'all') {
            return $query->where('action', 'like', "%{$action}%");
        }
        return $query;
    }

    /**
     * Scope: Filter by User
     */
    public function scopeFilterUser(Builder $query, ?string $userId): Builder
    {
        if (!empty($userId) && $userId !== 'all') {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    /**
     * Scope: Filter by Date Range Preset or Custom
     */
    public function scopeFilterDateRange(Builder $query, ?string $preset = null, ?string $startDate = null, ?string $endDate = null): Builder
    {
        if ($preset) {
            switch ($preset) {
                case 'today':
                    return $query->whereDate('created_at', Carbon::today());
                case 'yesterday':
                    return $query->whereDate('created_at', Carbon::yesterday());
                case 'this_week':
                    return $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                case 'this_month':
                    return $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                case 'last_30_days':
                    return $query->where('created_at', '>=', Carbon::now()->subDays(30));
            }
        }

        if ($startDate && $endDate) {
            return $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        } elseif ($startDate) {
            return $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            return $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        return $query;
    }
}
