<?php
// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // Relation avec les utilisateurs
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Constantes pour les rôles
    const PATIENT = 'patient';
    const DOCTOR = 'doctor';
    const ADMIN = 'admin';

    // Méthodes utilitaires
    public static function getPatientRole()
    {
        return self::where('name', self::PATIENT)->first();
    }

    public static function getDoctorRole()
    {
        return self::where('name', self::DOCTOR)->first();
    }

    public static function getAdminRole()
    {
        return self::where('name', self::ADMIN)->first();
    }
}