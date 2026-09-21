<?php
// Expects $active to be set by the including page to one of the keys below.
$active = $active ?? '';

$navItems = [
    'dashboard'  => ['dashboard.php',       'fa-chart-pie',            'Dashboard'],
    'transfer'   => ['transfer.php',        'fa-paper-plane',          'Transfer Money'],
    'deposit'    => ['deposit.php',         'fa-wallet',               'Deposit'],
    'withdraw'   => ['withdraw.php',        'fa-money-bill-transfer',  'Withdraw'],
    'statement'  => ['statement.php',       'fa-file-invoice-dollar',  'E-Statement'],
    'beneficiaries'=> ['beneficiaries.php', 'fa-address-book',         'Beneficiaries'],
    'profile'    => ['profile.php',         'fa-user-circle',          'My Profile'],
    'security'   => ['security_center.php', 'fa-shield-halved',        'Security Center'],
];
?>
<aside class="sidebar">
    <div class="brand" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <div><i class="fa-solid fa-building-columns" style="color: var(--primary-glow);"></i> Secure<span>Bank</span></div>
        <div class="notif-bell" style="position: relative;">
            <?php 
                $unread = getUnreadNotificationCount($conn, $_SESSION['user_id'] ?? 0, 'user'); 
            ?>
            <a href="notifications.php" style="color: white; text-decoration: none; position: relative;">
                <i class="fa-solid fa-bell" style="font-size: 18px;"></i>
                <?php if ($unread > 0): ?>
                    <span style="position: absolute; top: -6px; right: -8px; background: #ff3e6c; color: white; font-size: 10px; font-weight: bold; border-radius: 50%; padding: 2px 5px; box-shadow: 0 0 5px rgba(255,62,108,0.5);"><?= $unread ?></span>
                <?php endif; ?>
            </a>
            <!-- Simple Dropdown (Hover) -->
            <style>
                .notif-bell:hover .notif-dropdown { display: block; }
                .notif-dropdown {
                    display: none; position: absolute; top: 100%; left: 100%; min-width: 280px; 
                    background: #1a1a1a; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; 
                    box-shadow: 0 5px 15px rgba(0,0,0,0.5); z-index: 1000; padding: 10px;
                }
                .notif-item { display: block; padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); color: white; text-decoration: none; font-size: 13px; }
                .notif-item:hover { background: rgba(255,255,255,0.05); }
                .notif-item:last-child { border-bottom: none; }
                .notif-title { font-weight: bold; color: var(--accent-blue); margin-bottom: 4px; display: block; }
                .notif-msg { color: var(--text-muted); font-size: 12px; white-space: normal; }
            </style>
            <div class="notif-dropdown">
                <?php 
                    $uid = $_SESSION['user_id'] ?? 0;
                    $ndrop = mysqli_query($conn, "SELECT * FROM notifications WHERE type='user' AND (user_id=$uid OR user_id=0) ORDER BY created_at DESC LIMIT 3");
                    if (mysqli_num_rows($ndrop) > 0) {
                        while ($n = mysqli_fetch_assoc($ndrop)) {
                            $fw = $n['is_read'] ? 'normal' : 'bold';
                            $c = $n['is_read'] ? 'var(--text-muted)' : 'white';
                            echo "<a href='notifications.php' class='notif-item'>
                                    <span class='notif-title'>".htmlspecialchars($n['title'])."</span>
                                    <span class='notif-msg' style='font-weight:$fw; color:$c;'>".htmlspecialchars(substr($n['message'],0,60))."...</span>
                                  </a>";
                        }
                    } else {
                        echo "<div style='color:var(--text-muted); font-size:13px; padding:10px; text-align:center;'>No notifications</div>";
                    }
                ?>
                <a href="notifications.php" style="display: block; text-align: center; font-size: 12px; color: var(--accent-blue); text-decoration: none; margin-top: 10px;">View All Notifications</a>
            </div>
        </div>
    </div>
    <ul class="nav-menu">
        <?php foreach ($navItems as $key => $item): ?>
        <li class="nav-item <?= $active === $key ? 'active' : '' ?>">
            <a href="<?= $item[0] ?>"><i class="fa-solid <?= $item[1] ?>"></i> <?= $item[2] ?></a>
        </li>
        <?php endforeach; ?>
        <li class="nav-item">
            <a href="logout.php" style="color: #ff3e6c;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </li>
    </ul>
</aside>
