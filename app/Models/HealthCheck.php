<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HealthCheck extends Model
{
    use HasFactory;

    public $timestamps = false; // IMPORTANT

    protected $fillable = [
        'customer_id',
        'status',
        'latency_ms',
        'packet_loss',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
