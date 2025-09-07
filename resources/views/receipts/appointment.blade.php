<!-- resources/views/receipts/appointment.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justificatif de Rendez-vous</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #0066cc;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .info-section h3 {
            background-color: #f0f8ff;
            padding: 8px;
            margin: 0 0 15px 0;
            color: #0066cc;
            border-left: 4px solid #0066cc;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label, .info-value {
            display: table-cell;
            padding: 5px 0;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            width: 30%;
            color: #666;
        }
        .info-value {
            width: 70%;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status.confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status.paid {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
            font-size: 10px;
            color: #999;
        }
        .important-note {
            background-color: #fff9c4;
            border: 1px solid #f0c420;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>JUSTIFICATIF DE RENDEZ-VOUS MÉDICAL</h1>
        <p>Plateforme de Prise de Rendez-vous en ligne</p>
        <p>Date d'émission : {{ now()->format('d/m/Y à H:i') }}</p>
    </div>

    <div class="info-section">
        <h3>Informations du Rendez-vous</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Numéro de référence :</div>
                <div class="info-value"><strong>{{ $appointment->reference_number }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Date du rendez-vous :</div>
                <div class="info-value">{{ $appointment->appointment_date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Heure :</div>
                <div class="info-value">{{ $appointment->appointment_time->format('H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Durée estimée :</div>
                <div class="info-value">{{ $appointment->duration_minutes }} minutes</div>
            </div>
            <div class="info-row">
                <div class="info-label">Statut :</div>
                <div class="info-value">
                    <span class="status {{ strtolower($appointment->status) }}">
                        {{ $appointment->status_label }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="info-section">
        <h3>Informations du Patient</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom complet :</div>
                <div class="info-value">{{ $appointment->patient->full_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email :</div>
                <div class="info-value">{{ $appointment->patient->email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Téléphone :</div>
                <div class="info-value">{{ $appointment->patient->phone ?: 'Non renseigné' }}</div>
            </div>
        </div>
    </div>

    <div class="info-section">
        <h3>Informations du Médecin</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom du médecin :</div>
                <div class="info-value">{{ $appointment->doctor->user->full_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Spécialité :</div>
                <div class="info-value">{{ $appointment->doctor->specialty->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Adresse du cabinet :</div>
                <div class="info-value">{{ $appointment->doctor->cabinet_address }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Téléphone du cabinet :</div>
                <div class="info-value">{{ $appointment->doctor->cabinet_phone ?: 'Non renseigné' }}</div>
            </div>
        </div>
    </div>

    <div class="info-section">
        <h3>Informations de Paiement</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Mode de paiement :</div>
                <div class="info-value">
                    {{ $appointment->payment_method === 'online' ? 'Paiement en ligne' : 'Paiement au cabinet' }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Montant :</div>
                <div class="info-value">{{ $appointment->formatted_amount }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Statut du paiement :</div>
                <div class="info-value">
                    <span class="status {{ strtolower($appointment->payment_status) }}">
                        {{ $appointment->payment_status_label }}
                    </span>
                </div>
            </div>
            @if($appointment->payment_method === 'online' && $appointment->payment)
                <div class="info-row">
                    <div class="info-label">ID Transaction :</div>
                    <div class="info-value">{{ $appointment->payment->gateway_transaction_id }}</div>
                </div>
            @endif
        </div>
    </div>

    @if($appointment->reason)
    <div class="info-section">
        <h3>Motif de la Consultation</h3>
        <p>{{ $appointment->reason }}</p>
    </div>
    @endif

    @if($appointment->notes && auth()->user()->isDoctor())
    <div class="info-section">
        <h3>Notes Médicales</h3>
        <p>{{ $appointment->notes }}</p>
    </div>
    @endif

    <div class="important-note">
        <strong>Important :</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Présentez-vous 15 minutes avant votre rendez-vous</li>
            <li>Munissez-vous de vos documents d'identité et de vos antécédents médicaux</li>
            <li>En cas d'empêchement, merci d'annuler au moins 24h à l'avance</li>
            @if($appointment->payment_method === 'cabinet')
                <li>Le paiement se fera directement au cabinet</li>
            @endif
        </ul>
    </div>

    @if(config('medical.receipts.include_qr_code', true))
    <div class="qr-code">
        <p><strong>Code de vérification</strong></p>
        <!-- QR Code sera généré ici -->
        <div style="width: 100px; height: 100px; border: 1px solid #ccc; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999;">
            QR CODE<br>{{ $appointment->reference_number }}
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Ce document est généré automatiquement par la plateforme Medical Appointment</p>
        <p>Pour toute question, contactez-nous : support@medical-app.com | +221 33 123 45 67</p>
        <p>Document généré le {{ now()->format('d/m/Y à H:i:s') }}</p>
    </div>
</body>
</html>