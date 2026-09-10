<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Bank
            ['name' => 'BNI 4665',   'account_number' => '0466500123', 'type' => 'bank',      'current_balance' => 0],
            ['name' => 'BRI 0128',   'account_number' => '0128000456', 'type' => 'bank',      'current_balance' => 0],
            ['name' => 'Mandiri',    'account_number' => '1234000789', 'type' => 'bank',      'current_balance' => 0],

            // Cash
            ['name' => 'Kas Tunai',  'account_number' => null,        'type' => 'cash',      'current_balance' => 0],

            // E-Wallet
            ['name' => 'GoPay',      'account_number' => null,        'type' => 'ewallet',   'current_balance' => 0],
            ['name' => 'OVO',        'account_number' => null,        'type' => 'ewallet',   'current_balance' => 0],
            ['name' => 'Dana',       'account_number' => null,        'type' => 'ewallet',   'current_balance' => 0],

            // Aggregator
            ['name' => 'FLIK',       'account_number' => null,        'type' => 'aggregator', 'current_balance' => 0],
            ['name' => 'SiCepat',    'account_number' => null,        'type' => 'aggregator', 'current_balance' => 0],
            ['name' => 'SPX',        'account_number' => null,        'type' => 'aggregator', 'current_balance' => 0],
        ];

        foreach ($accounts as $acct) {
            Account::updateOrCreate(
                ['name' => $acct['name']],
                $acct
            );
        }
    }
}
