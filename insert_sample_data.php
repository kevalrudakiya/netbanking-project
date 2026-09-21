<?php
require 'config/db.php';

// Generate some sample users
$users = [
    [
        'full_name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '5551234567',
        'username' => 'johndoe',
        'password' => password_hash('password123', PASSWORD_BCRYPT),
        'status' => 'active',
        'accounts' => [
            [
                'account_number' => '1001002001',
                'balance' => 15000.00,
                'pin' => password_hash('1234', PASSWORD_BCRYPT),
                'status' => 'active',
                'account_type' => 'savings',
                'transactions' => [
                    ['type' => 'deposit', 'amount' => 20000.00, 'note' => 'Initial deposit'],
                    ['type' => 'withdraw', 'amount' => 5000.00, 'note' => 'ATM Withdrawal']
                ]
            ],
            [
                'account_number' => '1001002002',
                'balance' => 5000.00,
                'pin' => password_hash('1234', PASSWORD_BCRYPT),
                'status' => 'active',
                'account_type' => 'current',
                'transactions' => [
                    ['type' => 'deposit', 'amount' => 5000.00, 'note' => 'Initial deposit']
                ]
            ]
        ]
    ],
    [
        'full_name' => 'Jane Smith',
        'email' => 'jane.smith@example.com',
        'phone' => '5559876543',
        'username' => 'janesmith',
        'password' => password_hash('password123', PASSWORD_BCRYPT),
        'status' => 'active',
        'accounts' => [
            [
                'account_number' => '1002003001',
                'balance' => 75000.50,
                'pin' => password_hash('4321', PASSWORD_BCRYPT),
                'status' => 'active',
                'account_type' => 'savings',
                'transactions' => [
                    ['type' => 'deposit', 'amount' => 80000.00, 'note' => 'Salary'],
                    ['type' => 'withdraw', 'amount' => 4999.50, 'note' => 'Rent Payment']
                ]
            ]
        ]
    ],
    [
        'full_name' => 'Demo User',
        'email' => 'demo@example.com',
        'phone' => '5550000000',
        'username' => 'demo',
        'password' => password_hash('demo123', PASSWORD_BCRYPT),
        'status' => 'active',
        'accounts' => [
            [
                'account_number' => '1009998888',
                'balance' => 1000.00,
                'pin' => password_hash('0000', PASSWORD_BCRYPT),
                'status' => 'active',
                'account_type' => 'savings',
                'transactions' => [
                    ['type' => 'deposit', 'amount' => 1000.00, 'note' => 'Demo deposit']
                ]
            ]
        ]
    ]
];

foreach ($users as $userData) {
    // Insert User
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, username, password, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("ssssss", $userData['full_name'], $userData['email'], $userData['phone'], $userData['username'], $userData['password'], $userData['status']);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        echo "User '{$userData['username']}' inserted successfully with ID $userId.\n";

        // Insert Accounts for User
        foreach ($userData['accounts'] as $accountData) {
            $stmtAcc = $conn->prepare("INSERT INTO accounts (user_id, account_number, balance, pin, status, account_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmtAcc->bind_param("isdsss", $userId, $accountData['account_number'], $accountData['balance'], $accountData['pin'], $accountData['status'], $accountData['account_type']);
            
            if ($stmtAcc->execute()) {
                $accountId = $stmtAcc->insert_id;
                echo "  Account '{$accountData['account_number']}' inserted successfully with ID $accountId.\n";

                // Insert Transactions for Account
                $currentBalance = 0;
                foreach ($accountData['transactions'] as $txnData) {
                    if ($txnData['type'] == 'deposit') {
                        $currentBalance += $txnData['amount'];
                    } else if ($txnData['type'] == 'withdraw') {
                        $currentBalance -= $txnData['amount'];
                    }

                    $refNo = 'TXN' . date('Ymd') . strtoupper(substr(uniqid(), -6));
                    
                    $stmtTxn = $conn->prepare("INSERT INTO transactions (account_id, type, amount, balance_after, reference_no, note, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmtTxn->bind_param("isddss", $accountId, $txnData['type'], $txnData['amount'], $currentBalance, $refNo, $txnData['note']);
                    
                    if ($stmtTxn->execute()) {
                        echo "    Transaction '{$txnData['type']}' of {$txnData['amount']} inserted successfully.\n";
                    } else {
                        echo "    Error inserting transaction: " . $stmtTxn->error . "\n";
                    }
                }
            } else {
                echo "  Error inserting account: " . $stmtAcc->error . "\n";
            }
        }
    } else {
        echo "Error inserting user '{$userData['username']}': " . $stmt->error . "\n";
    }
}

echo "\nSample data generation complete!\n";
?>
