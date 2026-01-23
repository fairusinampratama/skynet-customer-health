<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'status',
        'latency_ms',
        'packet_loss',
        'last_seen',
        'last_alerted_at',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'last_alerted_at' => 'datetime',
    ];

    public function healthChecks()
    {
        return $this->hasMany(ServerHealthCheck::class);
    }
}
