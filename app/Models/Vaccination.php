<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vaccination extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pet_id',
        'veterinarian_id',
        'medical_record_id',
        'vaccine_name',
        'administered_date',
        'next_due_date',
        'batch_number',
        'manufacturer',
        'notes',
    ];

    protected $casts = [
        'administered_date' => 'date',
        'next_due_date' => 'date',
    ];

    // Relationships
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // Scopes
    public function scopeDueForVaccination($query)
    {
        return $query->where('next_due_date', '<=', now()->addDays(30))
                    ->where('next_due_date', '>=', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('next_due_date', '<', now());
    }
}
