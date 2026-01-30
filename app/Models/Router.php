<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'username',
        'password',
        'port',
        'is_active',
        'status',
        'cpu_load',
        'temperature',
        'free_memory',
        'total_memory',
        'disk_usage',
        'last_seen',
        'last_alerted_at',
        'last_alert_values',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen' => 'datetime',
        'last_alerted_at' => 'datetime',
        'last_alert_values' => 'array',
        'password' => 'encrypted',
    ];
}
