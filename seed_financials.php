<?php

use App\Modules\Billing\Models\ChartOfAccount;
use App\Modules\Billing\Models\JournalEntry;
use App\Modules\Billing\Models\JournalLine;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Billing\Models\Invoice;
use Faker\Factory as Faker;

$faker = Faker::create();
$tenant = Tenant::first();

// 1. Create Chart of Accounts
$accountsData = [
    ['code' => '1000', 'name' => 'Cash', 'type' => 'asset'],
    ['code' => '4000', 'name' => 'Internet Service Revenue', 'type' => 'revenue'],
    ['code' => '4100', 'name' => 'Installation Fee Revenue', 'type' => 'revenue'],
    ['code' => '5000', 'name' => 'Bandwidth Cost', 'type' => 'expense'],
    ['code' => '5100', 'name' => 'Salary Expense', 'type' => 'expense'],
    ['code' => '5200', 'name' => 'Office Rent', 'type' => 'expense'],
];

$accounts = [];
foreach ($accountsData as $data) {
    $accounts[$data['code']] = ChartOfAccount::firstOrCreate(
        ['tenant_id' => $tenant->id ?? null, 'account_code' => $data['code']],
        ['account_name' => $data['name'], 'account_type' => $data['type'], 'is_active' => true]
    );
}

// 2. Create Journal Entries
$transactions = [
    // Revenue 1
    [
        'desc' => 'Monthly Internet Bill Collection',
        'lines' => [
            ['account' => '1000', 'debit' => 150000, 'credit' => 0],
            ['account' => '4000', 'debit' => 0, 'credit' => 150000],
        ]
    ],
    // Revenue 2
    [
        'desc' => 'New Connection Installation Fees',
        'lines' => [
            ['account' => '1000', 'debit' => 25000, 'credit' => 0],
            ['account' => '4100', 'debit' => 0, 'credit' => 25000],
        ]
    ],
    // Expense 1
    [
        'desc' => 'Paid Upstream Bandwidth Provider',
        'lines' => [
            ['account' => '5000', 'debit' => 45000, 'credit' => 0],
            ['account' => '1000', 'debit' => 0, 'credit' => 45000],
        ]
    ],
    // Expense 2
    [
        'desc' => 'Staff Salaries',
        'lines' => [
            ['account' => '5100', 'debit' => 30000, 'credit' => 0],
            ['account' => '1000', 'debit' => 0, 'credit' => 30000],
        ]
    ],
    // Expense 3
    [
        'desc' => 'Office Rent for the month',
        'lines' => [
            ['account' => '5200', 'debit' => 15000, 'credit' => 0],
            ['account' => '1000', 'debit' => 0, 'credit' => 15000],
        ]
    ]
];

$date = now()->format('Y-m-d');
$invoice = Invoice::first();

foreach ($transactions as $index => $trx) {
    $entry = JournalEntry::create([
        'tenant_id' => $tenant->id ?? null,
        'entry_date' => clone now()->subDays(rand(1, 15)),
        'reference_number' => 'JRN-' . date('Ym') . '-' . str_pad((string)($index + 1), 4, '0', STR_PAD_LEFT),
        'source_type' => $invoice ? get_class($invoice) : 'App\Modules\Billing\Models\Invoice',
        'source_id' => $invoice ? $invoice->id : 1,
        'description' => $trx['desc'],
        'status' => 'posted',
    ]);

    foreach ($trx['lines'] as $lineData) {
        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'chart_of_account_id' => $accounts[$lineData['account']]->id,
            'debit' => $lineData['debit'],
            'credit' => $lineData['credit'],
            'memo' => $trx['desc'],
        ]);
    }
}

echo "Financial dummy data (Chart of Accounts & Journal Entries) generated successfully.\n";
