<?php

return [
    'stale_customer_days' => env('FIELD_STALE_CUSTOMER_DAYS', 30),

    'deposit_alerts' => [
        'unreconciled_count' => env('FIELD_DEPOSIT_ALERT_UNRECONCILED_COUNT', 5),
        'disputed_count' => env('FIELD_DEPOSIT_ALERT_DISPUTED_COUNT', 1),
        'outstanding_value' => env('FIELD_DEPOSIT_ALERT_OUTSTANDING_VALUE', 500000),
    ],
];
