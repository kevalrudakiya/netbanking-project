<?php
require 'config/db.php';

// Generate more realistic "real-world" users
$users = [
    [
        'full_name' => 'Michael Johnson',
        'email' => 'mjohnson.professional@gmail.com',
        'phone' => '2125550198',
        'username' => 'mjohnson88',
        'password' => password_hash('securepass#1', PASSWORD_BCRYPT),
        'status' => 'active',
        'accounts' => [
            [
                'account_number' => '1004509123',
                'balance' => 4521.50,
                'pin' => password_hash('8492', PASSWORD_BCRYPT),
                'status' => 'active',
                'account_type' => 'savings',
                'transactions' => [
                    ['type' => 'deposit', 'amount' => 5200.00, 'note' => 'ACH Deposit - TechCorp Inc. Payroll'],
                    ['type' => 'withdraw', 'amount' => 1200.00, 'note' => 'Rent Payment - Skyline Apartments'],
                    ['type' => 'withdraw', 'amount' => 125.50, 'note' => 'POS - Whole Foods Market'],
                    ['type' => 'withdraw', 'amount' => 45.00, 'note' => 'POS - Shell Gas Station'],
                    ['type' => 'deposit', 'amount' => 750.00, 'note' => 'Zelle Transfer from Sarah J.'],
                    ['type' => 'withdraw', 'amount' => 58.00, 'note' => 'POS - Starbucks Coffee']
                ]
            ],
            [
                'account_number' => '1004509124',
                'balance' => 20500.00,
                'pin' => password_hash('8492', PASSWORD_BCRYPT),
                'status' => 'active',
                'account_type' => 'savings', // Acts like an emergency fund
                'transactions' => [
                    ['type' => 'deposit', 'amount' => 20000.00, 'note' => 'Wire Transfer - Fidelity Investments'],
                    ['type' => 'deposit', 'amount' => 500.00, 'note' => 'Interest Payment - Aug 2026']
                ]
            ]
        ]
    ],
    [
        'full_name' => 'Emily Chen',
        'email' => 'echen.design@outlook.com',
        'phone' => '4155550231',
        'username' => 'emilyc_design',
        'password' => password_hash('design!Life9', PASSWORD_BCRYPT),
        'status' => 'active',
        'accounts' => [
            [
                'account_number' => '1008892244',
                'balance' => 12890.75,
                'pin' => password_hash('1045', PASSWORD_BCRYPT),
                'status' => 'active',
                'account_type' => 'current',
                'transactions' => [
                    ['type' => 'deposit', 'amount' => 15000.00, 'note' => 'Invoice Payment - Studio X'],
                    ['type' => 'withdraw', 'amount' => 350.00, 'note' => 'Adobe Creative Cloud Subscription'],
                    ['type' => 'withdraw', 'amount' => 1200.00, 'note' => 'Equipment Purchase - Apple Store'],
                    ['type' => 'withdraw', 'amount' => 85.25, 'note' => 'POS - Blue Bottle Coffee'],
                    ['type' => 'withdraw', 'amount' => 474.00, 'note' => 'Payment - WeWork Co-working space']
                ]
            ]
        ]
    ],
    [
        'full_name' => 'David Martinez',
        'email' => 'dmartinez1975@yahoo.com',
        'phone' => '3055550882',
        'username' => 'davidm_75',
        'password' => password_hash('miamiHeat!', PASSWORD_BCRYPT),
        'status' => 'active',
        'accounts' => [
            [
                'account_number' => '1007739090',
                'balance' => 842.10,
                'pin' => password_hash('7733', PASSWORD_BCRYPT),
                'status' => 'active',
                'account_type' => 'savings',
                'transactions' => [
                    ['type' => 'deposit', 'amount' => 2100.00, 'note' => 'Payroll - Miami Logistics'],
                    ['type' => 'withdraw', 'amount' => 600.00, 'note' => 'Auto Loan Payment - Chase Auto'],
                    ['type' => 'withdraw', 'amount' => 145.20, 'note' => 'Utility Bill - Florida Power'],
                    ['type' => 'withdraw', 'amount' => 89.00, 'note' => 'AT&T Mobile Bill'],
                    ['type' => 'withdraw', 'amount' => 200.00, 'note' => 'ATM Cash Withdrawal'],
                    ['type' => 'withdraw', 'amount' => 223.70, 'note' => 'POS - Target']
                ]
            ]
        ]
    ]
];

foreach ($users as $userData) {
    // Check if user exists first to avoid duplicate usernames in case of re-run
    $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $userData['username']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "User '{$userData['username']}' already exists. Skipping.\n";
        continue;
    }

    // Insert User
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, username, password, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW())");
    $stmt->bind_param("ssssss", $userData['full_name'], $userData['email'], $userData['phone'], $userData['username'], $userData['password'], $userData['status']);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        echo "User '{$userData['username']}' inserted successfully with ID $userId.\n";

        // Insert Accounts for User
        foreach ($userData['accounts'] as $accountData) {
            $stmtAcc = $conn->prepare("INSERT INTO accounts (user_id, account_number, balance, pin, status, account_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW())");
            $stmtAcc->bind_param("isdsss", $userId, $accountData['account_number'], $accountData['balance'], $accountData['pin'], $accountData['status'], $accountData['account_type']);
            
            if ($stmtAcc->execute()) {
                $accountId = $stmtAcc->insert_id;
                echo "  Account '{$accountData['account_number']}' inserted successfully with ID $accountId.\n";

                // Insert Transactions for Account
                $currentBalance = 0;
                $dayOffset = 25; // distribute over the last 25 days
                foreach ($accountData['transactions'] as $txnData) {
                    if ($txnData['type'] == 'deposit') {
                        $currentBalance += $txnData['amount'];
                    } else if ($txnData['type'] == 'withdraw') {
                        $currentBalance -= $txnData['amount'];
                    }

                    $refNo = 'TXN' . date('Ymd', strtotime("-$dayOffset days")) . strtoupper(substr(uniqid(), -6));
                    
                    $stmtTxn = $conn->prepare("INSERT INTO transactions (account_id, type, amount, balance_after, reference_no, note, created_at) VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))");
                    $stmtTxn->bind_param("isddssi", $accountId, $txnData['type'], $txnData['amount'], $currentBalance, $refNo, $txnData['note'], $dayOffset);
                    
                    if ($stmtTxn->execute()) {
                        echo "    Transaction '{$txnData['type']}' of {$txnData['amount']} inserted successfully.\n";
                    } else {
                        echo "    Error inserting transaction: " . $stmtTxn->error . "\n";
                    }
                    $dayOffset = max(0, $dayOffset - rand(1, 4));
                }
                
                // Final update of account balance to reflect transactions exactly
                $updateBal = $conn->prepare("UPDATE accounts SET balance = ? WHERE account_id = ?");
                $updateBal->bind_param("di", $currentBalance, $accountId);
                $updateBal->execute();

            } else {
                echo "  Error inserting account: " . $stmtAcc->error . "\n";
            }
        }
    } else {
        echo "Error inserting user '{$userData['username']}': " . $stmt->error . "\n";
    }
}

echo "\nRealistic data generation complete!\n";
?>
