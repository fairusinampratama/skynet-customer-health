<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerHealthCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'status',
        'latency_ms',
        'packet_loss',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
