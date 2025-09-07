<?php
// config/medical.php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration des Rendez-vous
    |--------------------------------------------------------------------------
    */
    'appointments' => [
        'default_duration' => env('APPOINTMENT_DEFAULT_DURATION', 30), // en minutes
        'min_booking_advance' => env('APPOINTMENT_MIN_ADVANCE', 2), // heures à l'avance minimum
        'max_booking_advance' => env('APPOINTMENT_MAX_ADVANCE', 90), // jours à l'avance maximum
        'cancellation_deadline' => env('APPOINTMENT_CANCEL_DEADLINE', 24), // heures avant le RDV pour annuler
        'auto_confirm_paid' => env('APPOINTMENT_AUTO_CONFIRM_PAID', true), // Confirmer automatiquement si payé
        'reminder_times' => [
            '24_hours' => env('APPOINTMENT_REMINDER_24H', true),
            '2_hours' => env('APPOINTMENT_REMINDER_2H', true),
        ],
        'allowed_statuses' => ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'],
        'working_hours' => [
            'start' => env('WORKING_HOURS_START', '08:00'),
            'end' => env('WORKING_HOURS_END', '18:00'),
        ],
        'working_days' => [1, 2, 3, 4, 5, 6], // Lundi à Samedi (0 = Dimanche)
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Paiements
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'XOF'),
        'currencies' => [
            'XOF' => 'Franc CFA',
            'EUR' => 'Euro',
            'USD' => 'Dollar US',
        ],
        'simulator' => [
            'enabled' => env('PAYMENT_SIMULATOR_ENABLED', true),
            'success_rate' => env('PAYMENT_SIMULATOR_SUCCESS_RATE', 90), // % de succès
        ],
        'refund' => [
            'deadline_days' => env('PAYMENT_REFUND_DEADLINE', 7), // jours pour demander un remboursement
            'auto_process' => env('PAYMENT_AUTO_PROCESS_REFUND', false),
        ],
        'supported_gateways' => [
            'stripe' => [
                'name' => 'Stripe',
                'enabled' => env('STRIPE_ENABLED', true),
                'test_mode' => env('STRIPE_TEST_MODE', true),
            ],
            'cinetpay' => [
                'name' => 'CinetPay',
                'enabled' => env('CINETPAY_ENABLED', false),
                'test_mode' => env('CINETPAY_TEST_MODE', true),
            ],
            'simulator' => [
                'name' => 'Simulateur',
                'enabled' => env('PAYMENT_SIMULATOR_ENABLED', true),
            ],
        ],
        'fees' => [
            'online_percentage' => env('PAYMENT_ONLINE_FEE_PERCENT', 2.5), // % de frais pour paiement en ligne
            'minimum_amount' => env('PAYMENT_MINIMUM_AMOUNT', 5000), // Montant minimum en FCFA
            'maximum_amount' => env('PAYMENT_MAXIMUM_AMOUNT', 500000), // Montant maximum en FCFA
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'email' => [
            'enabled' => env('NOTIFICATIONS_EMAIL_ENABLED', true),
            'from_name' => env('NOTIFICATIONS_FROM_NAME', 'Medical App'),
            'from_email' => env('NOTIFICATIONS_FROM_EMAIL', 'noreply@medical-app.com'),
        ],
        'sms' => [
            'enabled' => env('NOTIFICATIONS_SMS_ENABLED', false),
            'provider' => env('SMS_PROVIDER', 'twilio'), // twilio, nexmo, etc.
        ],
        'push' => [
            'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', false),
            'firebase_key' => env('FIREBASE_SERVER_KEY'),
        ],
        'types' => [
            'appointment_booked' => [
                'email' => true,
                'sms' => false,
                'push' => true,
            ],
            'appointment_confirmed' => [
                'email' => true,
                'sms' => true,
                'push' => true,
            ],
            'appointment_cancelled' => [
                'email' => true,
                'sms' => false,
                'push' => true,
            ],
            'payment_completed' => [
                'email' => true,
                'sms' => false,
                'push' => true,
            ],
            'appointment_reminder' => [
                'email' => true,
                'sms' => true,
                'push' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration IA Chat
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'enabled' => env('AI_CHAT_ENABLED', true),
        'provider' => env('AI_PROVIDER', 'openai'), // openai, claude, etc.
        'openai' => [
            'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 150),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
            'timeout' => env('OPENAI_TIMEOUT', 30),
        ],
        'conversation' => [
            'max_length' => env('AI_MAX_CONVERSATION_LENGTH', 10),
            'session_timeout' => env('AI_SESSION_TIMEOUT', 3600), // 1 heure
            'rate_limit' => env('AI_RATE_LIMIT_PER_HOUR', 50),
        ],
        'allowed_topics' => [
            'health_general' => true,
            'appointment_booking' => true,
            'platform_usage' => true,
            'payment_info' => true,
            'doctor_search' => true,
        ],
        'restricted_topics' => [
            'medical_diagnosis' => 'Je ne peux pas fournir de diagnostic médical. Consultez un professionnel de santé.',
            'medication_advice' => 'Je ne peux pas donner de conseils sur les médicaments. Consultez votre médecin.',
            'emergency' => 'Pour une urgence médicale, contactez immédiatement les services d\'urgence au 15.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Justificatifs PDF
    |--------------------------------------------------------------------------
    */
    'receipts' => [
        'auto_generate' => env('RECEIPTS_AUTO_GENERATE', true),
        'include_qr_code' => env('RECEIPTS_INCLUDE_QR', true),
        'storage_disk' => env('RECEIPTS_STORAGE_DISK', 'public'),
        'storage_path' => env('RECEIPTS_STORAGE_PATH', 'receipts'),
        'template' => env('RECEIPTS_TEMPLATE', 'receipts.appointment'),
        'logo_path' => env('RECEIPTS_LOGO_PATH', 'images/logo.png'),
        'formats' => ['pdf'], // Possibilité d'ajouter d'autres formats
        'font' => [
            'family' => env('RECEIPTS_FONT_FAMILY', 'Arial'),
            'size' => env('RECEIPTS_FONT_SIZE', 12),
        ],
        'watermark' => [
            'enabled' => env('RECEIPTS_WATERMARK_ENABLED', false),
            'text' => env('RECEIPTS_WATERMARK_TEXT', 'Medical App'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites et Contraintes Système
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'appointments' => [
            'max_per_day_per_patient' => env('MAX_APPOINTMENTS_PER_DAY_PATIENT', 3),
            'max_per_day_per_doctor' => env('MAX_APPOINTMENTS_PER_DAY_DOCTOR', 50),
            'max_concurrent_bookings' => env('MAX_CONCURRENT_BOOKINGS', 1),
        ],
        'search' => [
            'max_results' => env('MAX_SEARCH_RESULTS', 100),
            'cache_duration' => env('SEARCH_CACHE_DURATION', 300), // 5 minutes
        ],
        'uploads' => [
            'max_file_size' => env('MAX_UPLOAD_SIZE', 2048), // KB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        ],
        'api' => [
            'rate_limit_per_minute' => env('API_RATE_LIMIT_PER_MINUTE', 60),
            'auth_rate_limit' => env('AUTH_RATE_LIMIT_PER_MINUTE', 5),
            'payment_rate_limit' => env('PAYMENT_RATE_LIMIT_PER_MINUTE', 10),
        ],
        'session' => [
            'timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 120),
            'max_concurrent_sessions' => env('MAX_CONCURRENT_SESSIONS', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Rôles et Permissions
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'patient' => [
            'display_name' => 'Patient',
            'description' => 'Utilisateur patient qui peut prendre des rendez-vous',
            'permissions' => [
                'appointments.create',
                'appointments.view.own',
                'appointments.cancel.own',
                'payments.create',
                'payments.view.own',
                'ai.chat',
                'profile.update.own',
                'notifications.view.own',
            ],
            'restrictions' => [
                'can_book_multiple_same_day' => false,
                'can_see_other_patients' => false,
            ],
        ],
        'doctor' => [
            'display_name' => 'Médecin',
            'description' => 'Médecin praticien qui peut gérer ses consultations',
            'permissions' => [
                'appointments.view.own',
                'appointments.confirm',
                'appointments.complete',
                'appointments.reject',
                'availabilities.manage',
                'patients.view.basic',
                'receipts.generate',
                'profile.update.own',
                'statistics.view.own',
            ],
            'restrictions' => [
                'requires_verification' => true,
                'can_see_payment_details' => false,
            ],
        ],
        'admin' => [
            'display_name' => 'Administrateur',
            'description' => 'Administrateur système avec tous les privilèges',
            'permissions' => [
                'users.manage',
                'doctors.verify',
                'specialties.manage',
                'appointments.view.all',
                'payments.view.all',
                'payments.refund',
                'statistics.view.all',
                'system.maintenance',
                'notifications.send',
                'reports.generate',
            ],
            'restrictions' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Spécialités Médicales
    |--------------------------------------------------------------------------
    */
    'specialties' => [
        'default_consultation_duration' => 30, // minutes
        'categories' => [
            'general' => [
                'name' => 'Médecine Générale',
                'icon' => 'stethoscope',
                'color' => '#3498db',
            ],
            'cardiology' => [
                'name' => 'Cardiologie',
                'icon' => 'heart',
                'color' => '#e74c3c',
            ],
            'dermatology' => [
                'name' => 'Dermatologie',
                'icon' => 'user',
                'color' => '#f39c12',
            ],
            'pediatrics' => [
                'name' => 'Pédiatrie',
                'icon' => 'child',
                'color' => '#9b59b6',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration de Sécurité
    |--------------------------------------------------------------------------
    */
    'security' => [
        'password' => [
            'min_length' => env('PASSWORD_MIN_LENGTH', 8),
            'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', false),
            'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', false),
            'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', false),
        ],
        'account_lockout' => [
            'max_failed_attempts' => env('MAX_FAILED_LOGIN_ATTEMPTS', 5),
            'lockout_duration' => env('ACCOUNT_LOCKOUT_DURATION', 900), // 15 minutes
        ],
        'two_factor' => [
            'enabled' => env('TWO_FACTOR_ENABLED', false),
            'required_for_admins' => env('TWO_FACTOR_REQUIRED_ADMINS', true),
        ],
        'data_retention' => [
            'appointments_months' => env('DATA_RETENTION_APPOINTMENTS', 60), // 5 ans
            'payments_months' => env('DATA_RETENTION_PAYMENTS', 84), // 7 ans
            'logs_days' => env('DATA_RETENTION_LOGS', 90),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration de l'Interface
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'app_name' => env('APP_NAME', 'Medical Appointment'),
        'theme' => [
            'primary_color' => env('THEME_PRIMARY_COLOR', '#0066cc'),
            'secondary_color' => env('THEME_SECONDARY_COLOR', '#f8f9fa'),
            'success_color' => env('THEME_SUCCESS_COLOR', '#28a745'),
            'warning_color' => env('THEME_WARNING_COLOR', '#ffc107'),
            'danger_color' => env('THEME_DANGER_COLOR', '#dc3545'),
        ],
        'pagination' => [
            'default_per_page' => env('PAGINATION_DEFAULT', 15),
            'max_per_page' => env('PAGINATION_MAX', 100),
        ],
        'date_format' => env('UI_DATE_FORMAT', 'd/m/Y'),
        'time_format' => env('UI_TIME_FORMAT', 'H:i'),
        'datetime_format' => env('UI_DATETIME_FORMAT', 'd/m/Y H:i'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration de Debug et Monitoring
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'log_level' => env('APP_LOG_LEVEL', 'info'),
        'log_channels' => [
            'appointments' => env('LOG_APPOINTMENTS', true),
            'payments' => env('LOG_PAYMENTS', true),
            'auth' => env('LOG_AUTH', true),
            'api' => env('LOG_API_CALLS', false),
        ],
        'metrics' => [
            'enabled' => env('METRICS_ENABLED', false),
            'provider' => env('METRICS_PROVIDER', 'prometheus'),
        ],
        'health_checks' => [
            'database' => true,
            'redis' => false,
            'storage' => true,
            'email' => true,
        ],
    ],

];