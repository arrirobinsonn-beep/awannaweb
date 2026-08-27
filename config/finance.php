<?php

return [
    'topup' => [
        'account_id' => env('TOPUP_BANK_TRANSFER_ACCOUNT_ID'),
        'account_name' => env('TOPUP_BANK_TRANSFER_ACCOUNT_NAME'),
        'category_id' => env('TOPUP_BANK_TRANSFER_CATEGORY_ID'),
        'category_name' => env('TOPUP_BANK_TRANSFER_CATEGORY_NAME'),
    ],
];
