<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'name',
        'ip_address',
        'status',
        'latency_ms',
        'packet_loss',
        'is_isolated',
        'last_alerted_at',
    ];

    protected $casts = [
        'is_isolated' => 'boolean',
        'last_alerted_at' => 'datetime',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function healthChecks()
    {
        return $this->hasMany(HealthCheck::class);
    }

    public function latestHealth()
    {
        return $this->hasOne(HealthCheck::class)
            ->latest('checked_at');
    }

    /**
     * Scope a query to only include customers with issues on a specific date.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon|string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithIssuesOn($query, $date)
    {
        return $query->whereIn('status', ['offline', 'down', 'unstable'])
            ->where('is_isolated', false)
            ->with(['area', 'healthChecks' => function ($q) {
                $q->latest('checked_at')->limit(1);
            }])
            ->withCount(['healthChecks' => function ($q) use ($date) {
                $q->whereDate('checked_at', $date)
                  ->where('status', 'down');
            }]);
    }
}
