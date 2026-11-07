<?php
require_once __DIR__ . '/includes/init.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(is_logged_in() ? 'dashboard.php' : 'login.php');
}
csrf_require();
admin_logout();
redirect('login.php?bye=1');
