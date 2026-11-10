<?php

/*
|--------------------------------------------------------------------------
| Tuition centre settings
|--------------------------------------------------------------------------
| Details printed on receipts and the payment page. Change them in .env
| (TMS_*) instead of editing the views.
*/

return [

    'centre' => [
        'name' => env('TMS_CENTRE_NAME', 'Pusat Tuisyen Anjung Ilmu Pendeta'),
        'address' => env('TMS_CENTRE_ADDRESS', 'Anjung Ilmu, Pusat Bandar Seremban, Negeri Sembilan'),
        'phone' => env('TMS_CENTRE_PHONE', '+60 6-761 0649'),
        'email' => env('TMS_CENTRE_EMAIL', 'pusattuisyenanjungilmupendeta@gmail.com'),
    ],

    'payment' => [
        // One-off fee charged with a student's very first registration.
        'registration_fee' => (float) env('TMS_REGISTRATION_FEE', 15),

        // Shown on the "Pay Now" page.
        'bank_name' => env('TMS_BANK_NAME', 'Bank Islam / DuitNow'),
        'account_name' => env('TMS_ACCOUNT_NAME', 'Pusat Tusyen Anjung Ilmu'),
        'account_number' => env('TMS_ACCOUNT_NUMBER', ''),

        // The text inside the centre's DuitNow QR code (scan the centre's printed QR with any
        // QR reader app to get it). Leave empty to show the bank details only.
        'duitnow_qr' => env('TMS_DUITNOW_QR', ''),
    ],

    // Timetable hours. Classes must start and end inside this window.
    'day_start' => env('TMS_DAY_START', '08:00'),
    'day_end' => env('TMS_DAY_END', '22:00'),
];
