<?php
// app/Models/Specialty.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'consultation_price',
    ];

    protected $casts = [
        'consultation_price' => 'decimal:2',
    ];

    // Relation avec les médecins
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    // Scope pour les spécialités actives (avec médecins vérifiés)
    public function scopeWithVerifiedDoctors($query)
    {
        return $query->whereHas('doctors', function($q) {
            $q->where('is_verified', true);
        });
    }

    // Accessor pour le prix formaté
    public function getFormattedPriceAttribute()
    {
        return number_format($this->consultation_price, 0, ',', ' ') . ' FCFA';
    }
}