<?php
// app/Models/AppointmentReceipt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppointmentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'receipt_number',
        'pdf_path',
        'qr_code',
        'generated_at',
        'downloaded_by_patient',
        'downloaded_by_doctor',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'downloaded_by_patient' => 'boolean',
        'downloaded_by_doctor' => 'boolean',
    ];

    // Event pour générer automatiquement le numéro de justificatif
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receipt) {
            if (empty($receipt->receipt_number)) {
                $receipt->receipt_number = 'JUST' . date('Ymd') . Str::padLeft(rand(1, 9999), 4, '0');
            }
            $receipt->generated_at = now();
        });
    }

    // Relations
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // Accessors
    public function getPdfUrlAttribute()
    {
        return $this->pdf_path ? asset('storage/' . $this->pdf_path) : null;
    }

    public function getQrCodeUrlAttribute()
    {
        return $this->qr_code ? asset('storage/' . $this->qr_code) : null;
    }
}
