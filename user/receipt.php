<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$txn_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$txn_id) {
    die("Invalid transaction ID.");
}

// Ensure FPDF is loaded
require_once '../includes/fpdf/fpdf.php';

// Fetch transaction details and verify ownership
$stmt = mysqli_prepare($conn, "
    SELECT t.*, a.account_number, a.user_id as owner_id, u.full_name as owner_name,
           r_a.account_number as rel_account_number, r_u.full_name as rel_owner_name
    FROM transactions t
    JOIN accounts a ON t.account_id = a.account_id
    JOIN users u ON a.user_id = u.user_id
    LEFT JOIN accounts r_a ON t.related_account_id = r_a.account_id
    LEFT JOIN users r_u ON r_a.user_id = r_u.user_id
    WHERE t.transaction_id = ?
");
mysqli_stmt_bind_param($stmt, 'i', $txn_id);
mysqli_stmt_execute($stmt);
$txn = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$txn) {
    die("Transaction not found.");
}

// Security: User must be the owner of the account OR the related account (if it's a transfer)
$is_owner = ($txn['owner_id'] == $user_id);
$is_related = ($txn['related_account_id'] && r_u_id($conn, $txn['related_account_id']) == $user_id);

function r_u_id($conn, $acc_id) {
    $s = mysqli_prepare($conn, "SELECT user_id FROM accounts WHERE account_id=?");
    mysqli_stmt_bind_param($s, 'i', $acc_id);
    mysqli_stmt_execute($s);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    return $r['user_id'] ?? 0;
}

if (!$is_owner && !$is_related) {
    die("Unauthorized access to this receipt.");
}

// If the user is the related party, we might need to adjust perspective, 
// but it's simpler if they just download their side of the transaction.
// Wait, if A transfers to B, A gets a 'transfer_out' txn, B gets a 'transfer_in' txn.
// The txn ID they get is THEIR side of the transaction!
// So $is_owner is all we strictly need.
if (!$is_owner) {
    die("Unauthorized access to this receipt.");
}

// Build PDF
class ReceiptPDF extends FPDF {
    function Header() {
        // Logo could go here if we had one.
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(0, 102, 204); // Bank accent color
        $this->Cell(0, 15, 'SecureBank', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Transaction Receipt', 0, 1, 'C');
        $this->Ln(10);
        
        // Separator line
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'This is a computer generated document and requires no signature.', 0, 0, 'C');
    }
}

$pdf = new ReceiptPDF();
$pdf->AddPage();

// Transaction Info Section
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 10, 'Transaction Details', 0, 1);
$pdf->SetFont('Arial', '', 11);

// Helpers
function addRow($pdf, $label, $val, $isBold = false) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(50, 8, $label, 0, 0);
    
    $pdf->SetFont('Arial', $isBold ? 'B' : '', 11);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell(140, 8, $val, 0, 1);
}

addRow($pdf, 'Reference Number:', $txn['reference_no']);
addRow($pdf, 'Date & Time:', date('d M Y, h:i A', strtotime($txn['created_at'])));
addRow($pdf, 'Transaction Type:', strtoupper(str_replace('_', ' ', $txn['type'])));
addRow($pdf, 'Status:', 'SUCCESS', true);

$pdf->Ln(5);

// Account Details Section
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 10, 'Account Information', 0, 1);

// Logic based on type
if ($txn['type'] === 'deposit' || $txn['type'] === 'withdraw') {
    addRow($pdf, 'Account Holder:', $txn['owner_name']);
    addRow($pdf, 'Account Number:', 'XXXX' . substr($txn['account_number'], -4));
} elseif ($txn['type'] === 'transfer_out') {
    addRow($pdf, 'Sender Name:', $txn['owner_name']);
    addRow($pdf, 'Sender Account:', 'XXXX' . substr($txn['account_number'], -4));
    addRow($pdf, 'Receiver Name:', $txn['rel_owner_name'] ?? 'Unknown');
    addRow($pdf, 'Receiver Account:', 'XXXX' . substr($txn['rel_account_number'] ?? '0000', -4));
} elseif ($txn['type'] === 'transfer_in') {
    addRow($pdf, 'Sender Name:', $txn['rel_owner_name'] ?? 'Unknown');
    addRow($pdf, 'Sender Account:', 'XXXX' . substr($txn['rel_account_number'] ?? '0000', -4));
    addRow($pdf, 'Receiver Name:', $txn['owner_name']);
    addRow($pdf, 'Receiver Account:', 'XXXX' . substr($txn['account_number'], -4));
}

$pdf->Ln(5);

// Financials Section
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 10, 'Financial Summary', 0, 1);

addRow($pdf, 'Amount:', 'INR ' . number_format($txn['amount'], 2), true);
addRow($pdf, 'Closing Balance:', 'INR ' . number_format($txn['balance_after'], 2));

if ($txn['description']) {
    $pdf->Ln(2);
    addRow($pdf, 'Remarks:', $txn['description']);
}

// Generate PDF
$pdf->Output('I', 'Receipt_' . $txn['reference_no'] . '.pdf');
exit;
