<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'date_of_birth',
        'address',
        'city',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    // Relation avec les rôles
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Relation avec le profil médecin
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    // Rendez-vous en tant que patient
    public function patientAppointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    // Rendez-vous en tant que médecin (via la relation doctor)
    public function doctorAppointments()
    {
        return $this->hasManyThrough(Appointment::class, Doctor::class, 'user_id', 'doctor_id');
    }

    // Paiements effectués
    public function payments()
    {
        return $this->hasMany(Payment::class, 'patient_id');
    }

    // Notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Conversations IA
    public function aiConversations()
    {
        return $this->hasMany(AiConversation::class);
    }

    // Méthodes utilitaires
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isPatient()
    {
        return $this->role->name === 'patient';
    }

    public function isDoctor()
    {
        return $this->role->name === 'doctor';
    }

    public function isAdmin()
    {
        return $this->role->name === 'admin';
    }

    // Scope pour les utilisateurs actifs
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope par rôle
    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }
}