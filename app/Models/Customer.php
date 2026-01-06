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
        'last_alerted_at',
    ];

    protected $casts = [
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
}
