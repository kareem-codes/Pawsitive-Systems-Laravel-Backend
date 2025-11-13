<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_id',
        'weight',
        'unit',
        'measured_at',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'measured_at' => 'date',
        'weight' => 'decimal:2',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Convert weight to kg if stored in lb
    public function getWeightInKgAttribute(): float
    {
        return $this->unit === 'lb' ? round($this->weight * 0.453592, 2) : $this->weight;
    }

    // Convert weight to lb if stored in kg
    public function getWeightInLbAttribute(): float
    {
        return $this->unit === 'kg' ? round($this->weight * 2.20462, 2) : $this->weight;
    }
}
