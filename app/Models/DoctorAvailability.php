<?php
// app/Models/DoctorAvailability.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    // Relation avec le médecin
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // Constantes pour les jours de la semaine
    const DAYS = [
        0 => 'Dimanche',
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ];

    // Accessor pour le nom du jour
    public function getDayNameAttribute()
    {
        return self::DAYS[$this->day_of_week] ?? 'Inconnu';
    }

    // Scope pour les disponibilités actives
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    // Scope pour un jour spécifique
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    // Méthode pour formater l'horaire
    public function getFormattedScheduleAttribute()
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }
}