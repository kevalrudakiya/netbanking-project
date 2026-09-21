<?php
require 'auth.php';

// 1. Basic KPIs
$u   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0] ?? 0;
$a   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM accounts"))[0] ?? 0;
$t   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM transactions"))[0] ?? 0;
$bal = mysqli_fetch_row(mysqli_query($conn, "SELECT IFNULL(SUM(balance),0) FROM accounts"))[0] ?? 0;

// 2. Mock KPI Percentages (for aesthetic UI as per image)
$u_pct = "+12% this month";
$a_pct = "+8% this month";
$t_pct = "+18% this month";
$bal_pct = "+22% this month";

// 3. Transactions Overview (Donut Chart Data)
$txn_overview = [];
$res = mysqli_query($conn, "SELECT type, COUNT(*) as count FROM transactions GROUP BY type");
while ($row = mysqli_fetch_assoc($res)) {
    $txn_overview[$row['type']] = $row['count'];
}
$credit_count = ($txn_overview['deposit'] ?? 0) + ($txn_overview['transfer_in'] ?? 0);
$debit_count = ($txn_overview['withdraw'] ?? 0) + ($txn_overview['transfer_out'] ?? 0);
$transfer_count = ($txn_overview['transfer'] ?? 0); // fallback

// 4. Account Status (Donut Chart Data)
$acc_status = [];
$res = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM accounts GROUP BY status");
while ($row = mysqli_fetch_assoc($res)) {
    $acc_status[$row['status']] = $row['count'];
}
$active_acc = $acc_status['active'] ?? 0;
$inactive_acc = $acc_status['frozen'] ?? 0;
$closed_acc = $acc_status['closed'] ?? 0;

// 5. Recent Transactions List
$recent_txns = [];
$res = mysqli_query($conn, "SELECT t.*, a.account_number FROM transactions t JOIN accounts a ON t.account_id = a.account_id ORDER BY t.created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($res)) {
    $recent_txns[] = $row;
}

// 6. Top Users List
$top_users = [];
$res = mysqli_query($conn, "SELECT u.full_name, u.email, COUNT(a.account_id) as acc_count, u.status FROM users u LEFT JOIN accounts a ON u.user_id = a.user_id GROUP BY u.user_id ORDER BY acc_count DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($res)) {
    $top_users[] = $row;
}

// 7. Transactions Flow Line Chart Data (Last 6 Months mock)
$months = [];
$credits = [];
$debits = [];
$transfers = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('M', strtotime("-$i months"));
    $months[] = $m;
    $credits[] = rand(100, 500);
    $debits[] = rand(50, 300);
    $transfers[] = rand(50, 250);
}

$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Extremely Advanced Dark UI Styling */
        body { background-color: #0b0f19 !important; color: #e2e8f0; }
        .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        
        .main-content { padding: 35px 40px; background-color: #0b0f19; overflow-y: auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .top-nav { display: flex; gap: 25px; align-items: center; }
        .search-bar { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 12px 18px; color: #fff; width: 320px; display: flex; align-items: center; gap: 12px; transition: 0.3s; }
        .search-bar:focus-within { border-color: #3b82f6; background: rgba(255,255,255,0.06); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .search-bar input { background: transparent; border: none; color: #fff; outline: none; width: 100%; font-size: 14px; }
        
        .notif-bell { position: relative; font-size: 22px; color: #94a3b8; cursor: pointer; transition: 0.3s; }
        .notif-bell:hover { color: #fff; }
        .notif-bell .badge { position: absolute; top: -5px; right: -5px; background: #ef4444; width: 16px; height: 16px; border-radius: 50%; font-size: 10px; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; border: 2px solid #0b0f19; }
        
        /* Dashboard Grid System */
        .dash-grid { display: grid; grid-template-columns: 2.2fr 1fr 1.3fr; gap: 20px; margin-bottom: 20px; }
        .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        
        /* Advanced Cards */
        .adv-card { background: #13192b; border: 1px solid #1f2940; border-radius: 16px; padding: 22px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); display: flex; flex-direction: column; }
        .adv-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .adv-card-title { font-size: 15px; font-weight: 600; color: #e2e8f0; }
        
        /* KPI Cards */
        .kpi-card { display: flex; align-items: center; gap: 18px; background: #13192b; border: 1px solid #1f2940; border-radius: 16px; padding: 22px; transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s; }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.25); border-color: #3b82f6; }
        .kpi-icon { width: 55px; height: 55px; border-radius: 14px; display: flex; justify-content: center; align-items: center; font-size: 22px; flex-shrink: 0; }
        .icon-blue { background: rgba(59, 130, 246, 0.12); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .icon-purple { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.2); }
        .icon-green { background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .icon-orange { background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        
        .kpi-details h4 { margin: 0 0 6px 0; font-size: 13px; color: #94a3b8; font-weight: 500; }
        .kpi-details .val { font-size: 26px; font-weight: bold; color: #fff; word-break: break-all; line-height: 1.1; letter-spacing: -0.5px; }
        .kpi-details .trend { font-size: 12px; color: #10b981; margin-top: 6px; font-weight: 500; display: flex; align-items: center; gap: 4px; }
        
        /* Charts Container */
        .chart-container { position: relative; height: 260px; width: 100%; flex-grow: 1; }
        .chart-container-small { position: relative; height: 180px; width: 100%; }
        
        /* Lists */
        .recent-list { list-style: none; padding: 0; margin: 0; }
        .recent-list li { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #1f2940; transition: background 0.2s; border-radius: 8px; }
        .recent-list li:hover { background: rgba(255,255,255,0.02); padding-left: 10px; padding-right: 10px; margin: 0 -10px; }
        .recent-list li:last-child { border-bottom: none; }
        .list-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 18px; }
        
        /* Quick Actions Grid */
        .quick-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .quick-btn { background: #1f2940; border-radius: 12px; padding: 18px 10px; text-align: center; color: #fff; text-decoration: none; transition: 0.3s; border: 1px solid transparent; }
        .quick-btn:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.2); }
        .quick-btn i { font-size: 26px; margin-bottom: 12px; display: block; }
        .quick-btn span { font-size: 13px; font-weight: 500; }
        
        .btn-c1 { background: linear-gradient(135deg, #0ea5e9, #2563eb); }
        .btn-c2 { background: linear-gradient(135deg, #10b981, #059669); }
        .btn-c3 { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .btn-c4 { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .btn-c5 { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .btn-c6 { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .btn-c7 { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .btn-c8 { background: linear-gradient(135deg, #64748b, #475569); }
        
        /* Progress / Legend */
        .legend-row { display: flex; justify-content: space-between; font-size: 13px; color: #cbd5e1; margin-bottom: 10px; padding: 4px 0; }
        .badge-sm { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; letter-spacing: 0.5px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <!-- Header -->
        <header class="page-header">
            <div>
                <h1 style="font-size: 26px; margin: 0; color: #fff; font-weight: 700;">Welcome back, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Super Admin') ?>! 👋</h1>
                <p style="color: #94a3b8; font-size: 14px; margin-top: 5px;">Here's what's happening with your bank today.</p>
            </div>
            <div class="top-nav">
                <div class="search-bar">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" placeholder="Search anything...">
                </div>
                <button type="button" id="antiGravityToggle" style="background: rgba(0,255,163,0.1); border: 1px solid rgba(0,255,163,0.3); color: #00ffa3; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px; margin-right: 15px;">
                    <i class="fa-solid fa-rocket"></i> Anti-Gravity Mode
                </button>
                <div class="notif-bell"><i class="fa-regular fa-bell"></i><div class="badge">5</div></div>
                <div class="profile-pill" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 6px 15px 6px 6px; border-radius: 30px;">
                    <div class="avatar-circle" style="background: #3b82f6; width: 38px; height: 38px;"><i class="fa-solid fa-user-shield" style="color: #fff; font-size: 16px;"></i></div>
                    <div style="text-align: left;">
                        <div style="font-weight: 600; font-size: 13px; color: #fff; line-height: 1.2;"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Super Admin') ?></div>
                        <div style="font-size: 11px; color: #94a3b8; line-height: 1.2;">Administrator</div>
                    </div>
                    <i class="fa-solid fa-chevron-down" style="font-size: 10px; margin-left: 10px; color: #64748b;"></i>
                </div>
            </div>
        </header>

        <!-- KPI Row -->
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-icon icon-blue"><i class="fa-solid fa-users"></i></div>
                <div class="kpi-details">
                    <h4>Total Users</h4>
                    <div class="val"><?= number_format($u) ?></div>
                    <div class="trend"><i class="fa-solid fa-arrow-trend-up"></i> <?= $u_pct ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-green"><i class="fa-solid fa-building-columns"></i></div>
                <div class="kpi-details">
                    <h4>Total Accounts</h4>
                    <div class="val"><?= number_format($a) ?></div>
                    <div class="trend"><i class="fa-solid fa-arrow-trend-up"></i> <?= $a_pct ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-purple"><i class="fa-solid fa-right-left"></i></div>
                <div class="kpi-details">
                    <h4>Total Transactions</h4>
                    <div class="val"><?= number_format($t) ?></div>
                    <div class="trend"><i class="fa-solid fa-arrow-trend-up"></i> <?= $t_pct ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-orange"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="kpi-details">
                    <h4>Total Balance</h4>
                    <div class="val" style="font-size: <?= strlen((string)round($bal)) > 9 ? '20px' : '26px' ?>;"><?= formatCurrency($bal) ?></div>
                    <div class="trend"><i class="fa-solid fa-arrow-trend-up"></i> <?= $bal_pct ?></div>
                </div>
            </div>
        </div>

        <!-- Middle Grid (Charts) -->
        <div class="dash-grid">
            <!-- Line Chart -->
            <div class="adv-card">
                <div class="adv-card-header">
                    <span class="adv-card-title">Overview</span>
                    <select style="background: #0f172a; color: #fff; border: 1px solid #1f2940; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;"><option>This Year</option></select>
                </div>
                <div class="chart-container">
                    <canvas id="flowChart"></canvas>
                </div>
            </div>
            
            <!-- Donut Chart -->
            <div class="adv-card">
                <div class="adv-card-header">
                    <span class="adv-card-title">Transactions Overview</span>
                </div>
                <div class="chart-container">
                    <canvas id="overviewChart"></canvas>
                </div>
                <div style="margin-top: 25px;">
                    <div class="legend-row"><span><i class="fa-solid fa-circle" style="color: #3b82f6; font-size: 10px; margin-right: 8px;"></i> Credit</span> <span style="color: #fff; font-weight: bold;"><?= $credit_count ?></span></div>
                    <div class="legend-row"><span><i class="fa-solid fa-circle" style="color: #ef4444; font-size: 10px; margin-right: 8px;"></i> Debit</span> <span style="color: #fff; font-weight: bold;"><?= $debit_count ?></span></div>
                    <div class="legend-row"><span><i class="fa-solid fa-circle" style="color: #8b5cf6; font-size: 10px; margin-right: 8px;"></i> Transfer</span> <span style="color: #fff; font-weight: bold;"><?= $transfer_count ?></span></div>
                </div>
            </div>

            <!-- Recent Transactions List -->
            <div class="adv-card">
                <div class="adv-card-header">
                    <span class="adv-card-title">Recent Transactions</span>
                    <a href="transactions.php" style="color: #3b82f6; font-size: 13px; text-decoration: none; font-weight: 500;">View All</a>
                </div>
                <ul class="recent-list" style="flex-grow: 1;">
                    <?php foreach($recent_txns as $tx): 
                        $iconClass = 'fa-arrow-right-arrow-left'; $colorClass = 'icon-purple';
                        if ($tx['type'] == 'deposit') { $iconClass = 'fa-arrow-down'; $colorClass = 'icon-green'; }
                        if ($tx['type'] == 'withdraw') { $iconClass = 'fa-arrow-up'; $colorClass = 'icon-orange'; }
                    ?>
                    <li>
                        <div style="display: flex; gap: 14px; align-items: center;">
                            <div class="list-icon <?= $colorClass ?>"><i class="fa-solid <?= $iconClass ?>"></i></div>
                            <div>
                                <div style="font-weight: 600; font-size: 13px; color: #e2e8f0;"><?= ucfirst(str_replace('_', ' ', $tx['type'])) ?></div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 3px;"><?= date('M d, h:i A', strtotime($tx['created_at'])) ?></div>
                            </div>
                        </div>
                        <div class="badge-sm badge-active">Success</div>
                    </li>
                    <?php endforeach; if(empty($recent_txns)): ?>
                    <li style="color: #64748b; font-size: 13px;">No recent transactions.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>



    </main>
</div>

<script>
    // Chart.js Global Config for Premium Dark Theme
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";

    // 1. Transaction Flow Line Chart
    const ctxFlow = document.getElementById('flowChart').getContext('2d');
    
    // Create gradients
    const gradCredit = ctxFlow.createLinearGradient(0, 0, 0, 400);
    gradCredit.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
    gradCredit.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(ctxFlow, {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [
                {
                    label: 'Credit',
                    data: <?= json_encode($credits) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: gradCredit,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0b0f19',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Debit',
                    data: <?= json_encode($debits) ?>,
                    borderColor: '#ef4444',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: '#0b0f19',
                    pointBorderColor: '#ef4444',
                    pointBorderWidth: 2,
                    pointRadius: 4
                },
                {
                    label: 'Transfer',
                    data: <?= json_encode($transfers) ?>,
                    borderColor: '#8b5cf6',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: '#0b0f19',
                    pointBorderColor: '#8b5cf6',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2940',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 5
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.03)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Transactions Overview Donut
    const ctxOverview = document.getElementById('overviewChart').getContext('2d');
    new Chart(ctxOverview, {
        type: 'doughnut',
        data: {
            labels: ['Credit', 'Debit', 'Transfer'],
            datasets: [{
                data: [<?= $credit_count ?>, <?= $debit_count ?>, <?= $transfer_count ?>],
                backgroundColor: ['#3b82f6', '#ef4444', '#8b5cf6'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '78%',
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2940',
                    bodyFont: { size: 14 }
                }
            }
        }
    });


    // Anti-Gravity logic exclusively for Dashboard
    const antiGravityBtn = document.getElementById('antiGravityToggle');
    
    function setAntiGravityState(isActive) {
        if (isActive) {
            document.body.classList.add('anti-gravity-mode');
            if (antiGravityBtn) {
                antiGravityBtn.style.color = '#ff3e6c';
                antiGravityBtn.style.background = 'rgba(255,62,108,0.1)';
                antiGravityBtn.style.borderColor = 'rgba(255,62,108,0.3)';
                antiGravityBtn.innerHTML = '<i class="fa-solid fa-meteor"></i> Disable Gravity';
            }
            localStorage.setItem('antiGravityMode', 'true');
        } else {
            document.body.classList.remove('anti-gravity-mode');
            if (antiGravityBtn) {
                antiGravityBtn.style.color = '#00ffa3';
                antiGravityBtn.style.background = 'rgba(0,255,163,0.1)';
                antiGravityBtn.style.borderColor = 'rgba(0,255,163,0.3)';
                antiGravityBtn.innerHTML = '<i class="fa-solid fa-rocket"></i> Anti-Gravity Mode';
            }
            localStorage.setItem('antiGravityMode', 'false');
        }
    }

    // Initialize state on load
    if (localStorage.getItem('antiGravityMode') === 'true') {
        setAntiGravityState(true);
    }

    if (antiGravityBtn) {
        antiGravityBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const isActive = document.body.classList.contains('anti-gravity-mode');
            setAntiGravityState(!isActive);
        });
    }

</script>
</body>
</html>
