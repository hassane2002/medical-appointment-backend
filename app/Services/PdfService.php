<?php
// app/Services/PdfService.php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    /**
     * Générer le justificatif PDF d'un rendez-vous
     */
    public function generateAppointmentReceipt(Appointment $appointment)
    {
        // Charger les relations nécessaires
        $appointment->load(['patient', 'doctor.user', 'doctor.specialty', 'payment']);

        // Vérifier si le justificatif existe déjà
        $receipt = AppointmentReceipt::where('appointment_id', $appointment->id)->first();

        if (!$receipt) {
            // Générer le PDF
            $pdf = Pdf::loadView('receipts.appointment', compact('appointment'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'defaultFont' => 'Arial',
                    'isRemoteEnabled' => false,
                ]);

            // Nom du fichier
            $filename = 'justificatifs/appointment_' . $appointment->id . '_' . time() . '.pdf';
            
            // Sauvegarder le PDF
            Storage::disk('public')->put($filename, $pdf->output());

            // Créer l'enregistrement
            $receipt = AppointmentReceipt::create([
                'appointment_id' => $appointment->id,
                'pdf_path' => $filename,
                'qr_code' => $this->generateQRCode($appointment->reference_number),
                'generated_at' => now(),
            ]);
        }

        return $receipt;
    }

    /**
     * Générer un QR code (simplifié)
     */
    protected function generateQRCode($data)
    {
        // Ici vous pouvez intégrer une librarie QR Code comme SimpleSoftwareIO/simple-qrcode
        // Pour l'instant, on retourne juste les données
        return base64_encode($data);
    }
}
