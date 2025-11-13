<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

    /**
     * The guard name for Spatie Permission
     *
     * @var string
     */
    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'phone_secondary',
        'user_type',
        'is_active',
        'profile_photo',
        // Owner-specific fields
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    
    // For Owners (customers)
    public function pets()
    {
        return $this->hasMany(Pet::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // For Staff (veterinarians)
    public function veterinarianAppointments()
    {
        return $this->hasMany(Appointment::class, 'veterinarian_id');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'veterinarian_id');
    }

    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class, 'veterinarian_id');
    }

    public function createdInvoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function receivedPayments()
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    // Scopes
    public function scopeOwners($query)
    {
        return $query->where('user_type', 'owner');
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('user_type', ['admin', 'vet', 'receptionist', 'cashier']);
    }

    public function scopeVeterinarians($query)
    {
        return $query->where('user_type', 'vet');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function isOwner(): bool
    {
        return $this->user_type === 'owner';
    }

    public function isStaff(): bool
    {
        return in_array($this->user_type, ['admin', 'vet', 'receptionist', 'cashier']);
    }

    public function isVeterinarian(): bool
    {
        return $this->user_type === 'vet';
    }
}
