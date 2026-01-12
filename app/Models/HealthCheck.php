<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;

class HealthCheck extends Model
{
    use HasFactory, Prunable;

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

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('checked_at', '<=', now()->subDays(7));
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
