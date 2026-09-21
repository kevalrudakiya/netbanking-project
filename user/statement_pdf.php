<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$account = validateAccountStatus($conn, $user_id, 'view');

if (!$account) {
    die("No active account found.");
}

require_once '../includes/fpdf/fpdf.php';

// Filters
$f_txn_id = trim($_GET['txn_id'] ?? '');
$f_type = trim($_GET['type'] ?? 'all');
$f_flow = trim($_GET['flow'] ?? '');
$f_min = trim($_GET['min_amount'] ?? '');
$f_max = trim($_GET['max_amount'] ?? '');
$f_start = trim($_GET['start_date'] ?? '');
$f_end = trim($_GET['end_date'] ?? '');
$f_sort = trim($_GET['sort'] ?? 'newest');

$where = ["account_id = ?"];
$params = [$account['account_id']];
$types = "i";

if ($f_txn_id !== '') {
    $where[] = "reference_no LIKE ?";
    $params[] = "%$f_txn_id%"; $types .= "s";
}
if ($f_type !== 'all' && $f_type !== '') {
    $where[] = "type = ?";
    $params[] = $f_type; $types .= "s";
}
if ($f_flow === 'credit') {
    $where[] = "type IN ('deposit', 'transfer_in')";
} elseif ($f_flow === 'debit') {
    $where[] = "type IN ('withdraw', 'transfer_out')";
}
if (is_numeric($f_min)) {
    $where[] = "amount >= ?";
    $params[] = $f_min; $types .= "d";
}
if (is_numeric($f_max)) {
    $where[] = "amount <= ?";
    $params[] = $f_max; $types .= "d";
}
if ($f_start !== '') {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $f_start; $types .= "s";
}
if ($f_end !== '') {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $f_end; $types .= "s";
}

$order_by = "created_at DESC";
if ($f_sort === 'oldest') $order_by = "created_at ASC";
if ($f_sort === 'amt_high') $order_by = "amount DESC";
if ($f_sort === 'amt_low') $order_by = "amount ASC";

$where_clause = implode(" AND ", $where);
$sql = "SELECT * FROM transactions WHERE $where_clause ORDER BY $order_by";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);

// Calculate totals
$total_credit = 0;
$total_debit = 0;
$txns = [];
while ($row = mysqli_fetch_assoc($transactions)) {
    if (in_array($row['type'], ['deposit', 'transfer_in'])) {
        $total_credit += $row['amount'];
    } else {
        $total_debit += $row['amount'];
    }
    $txns[] = $row;
}

// Fetch user for name
$u_stmt = mysqli_prepare($conn, "SELECT full_name FROM users WHERE user_id=?");
mysqli_stmt_bind_param($u_stmt, 'i', $user_id);
mysqli_stmt_execute($u_stmt);
$user_row = mysqli_fetch_assoc(mysqli_stmt_get_result($u_stmt));

class StatementPDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(0, 102, 204);
        $this->Cell(0, 10, 'SecureBank', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Official E-Statement', 0, 1, 'L');
        
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY() + 5, 200, $this->GetY() + 5);
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Page '.$this->PageNo().' / {nb} | Generated on '.date('d-M-Y H:i'), 0, 0, 'C');
    }
}

$pdf = new StatementPDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Account Info
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(40, 6, 'Account Holder:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 6, $user_row['full_name'], 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, 'Account Number:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 6, 'XXXX' . substr($account['account_number'], -4), 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, 'Statement Filter:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$filter_text = [];
if ($f_type !== 'all') $filter_text[] = "Type: ".ucfirst($f_type);
if ($f_flow) $filter_text[] = "Flow: ".ucfirst($f_flow);
if ($f_min) $filter_text[] = "Min: $f_min";
if ($f_max) $filter_text[] = "Max: $f_max";
if ($f_start || $f_end) $filter_text[] = "Date: $f_start to $f_end";
$filter_display = empty($filter_text) ? 'All Transactions' : implode(' | ', $filter_text);
$pdf->Cell(100, 6, substr($filter_display, 0, 65), 0, 1);

$pdf->Ln(5);

// Summary
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(60, 8, 'Total Credits: INR ' . number_format($total_credit, 2), 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Total Debits: INR ' . number_format($total_debit, 2), 1, 0, 'C', true);
$pdf->Cell(70, 8, 'Closing Balance: INR ' . number_format($account['balance'], 2), 1, 1, 'C', true);

$pdf->Ln(5);

// Table Header
$pdf->SetFillColor(0, 102, 204);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(30, 8, 'Date', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Reference', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Type', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Credit', 1, 0, 'R', true);
$pdf->Cell(30, 8, 'Debit', 1, 0, 'R', true);
$pdf->Cell(30, 8, 'Balance', 1, 1, 'R', true);

// Table Body
$pdf->SetTextColor(50, 50, 50);
$pdf->SetFont('Arial', '', 8);

foreach ($txns as $t) {
    $date = date('d/m/Y', strtotime($t['created_at']));
    $type = ucfirst(str_replace('_', ' ', $t['type']));
    
    $is_credit = in_array($t['type'], ['deposit', 'transfer_in']);
    $credit = $is_credit ? number_format($t['amount'], 2) : '-';
    $debit = !$is_credit ? number_format($t['amount'], 2) : '-';
    $bal = number_format($t['balance_after'], 2);
    
    $pdf->Cell(30, 7, $date, 1, 0, 'C');
    $pdf->Cell(35, 7, $t['reference_no'], 1, 0, 'C');
    $pdf->Cell(35, 7, $type, 1, 0, 'C');
    
    if ($is_credit) $pdf->SetTextColor(0, 150, 0);
    $pdf->Cell(30, 7, $credit, 1, 0, 'R');
    $pdf->SetTextColor(50, 50, 50);
    
    if (!$is_credit) $pdf->SetTextColor(200, 0, 0);
    $pdf->Cell(30, 7, $debit, 1, 0, 'R');
    $pdf->SetTextColor(50, 50, 50);
    
    $pdf->Cell(30, 7, $bal, 1, 1, 'R');
}

if (empty($txns)) {
    $pdf->Cell(190, 10, 'No transactions found for this filter.', 1, 1, 'C');
}

$pdf->Output('I', 'Statement_' . $account['account_number'] . '_' . date('Ymd') . '.pdf');
exit;
