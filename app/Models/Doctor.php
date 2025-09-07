<?php
// app/Models/Doctor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialty_id',
        'license_number',
        'years_of_experience',
        'consultation_fee',
        'cabinet_address',
        'cabinet_phone',
        'bio',
        'is_verified',
    ];

    protected $casts = [
        'consultation_fee' => 'decimal:2',
        'is_verified' => 'boolean',
        'years_of_experience' => 'integer',
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec la spécialité
    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    // Relation avec les disponibilités
    public function availabilities()
    {
        return $this->hasMany(DoctorAvailability::class);
    }

    // Relation avec les rendez-vous
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // Rendez-vous confirmés
    public function confirmedAppointments()
    {
        return $this->hasMany(Appointment::class)->where('status', 'confirmed');
    }

    // Rendez-vous complétés
    public function completedAppointments()
    {
        return $this->hasMany(Appointment::class)->where('status', 'completed');
    }

    // Scope pour les médecins vérifiés
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Scope pour recherche par spécialité
    public function scopeBySpecialty($query, $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }

    // Scope pour recherche par ville
    public function scopeByCity($query, $city)
    {
        return $query->whereHas('user', function($q) use ($city) {
            $q->where('city', 'like', "%{$city}%");
        });
    }

    // Accessor pour le nom complet
    public function getFullNameAttribute()
    {
        return $this->user->full_name;
    }

    // Accessor pour le prix de consultation formaté
    public function getFormattedFeeAttribute()
    {
        return number_format($this->consultation_fee, 0, ',', ' ') . ' FCFA';
    }

    // Méthode pour vérifier si le médecin est disponible à une date/heure donnée
    public function isAvailableAt($date, $time)
    {
        $dayOfWeek = date('w', strtotime($date));
        
        return $this->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->where('is_available', true)
            ->exists();
    }

    // Méthode pour obtenir les créneaux disponibles
    public function getAvailableSlots($date)
    {
        $dayOfWeek = date('w', strtotime($date));
        
        $availabilities = $this->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->get();

        $slots = [];
        foreach ($availabilities as $availability) {
            $start = strtotime($availability->start_time);
            $end = strtotime($availability->end_time);
            
            // Générer des créneaux de 30 minutes
            for ($time = $start; $time < $end; $time += 1800) { // 30 min = 1800 sec
                $timeSlot = date('H:i', $time);
                
                // Vérifier si ce créneau n'est pas déjà pris
                $isBooked = $this->appointments()
                    ->where('appointment_date', $date)
                    ->where('appointment_time', $timeSlot)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();
                
                if (!$isBooked) {
                    $slots[] = $timeSlot;
                }
            }
        }
        
        return $slots;
    }
}