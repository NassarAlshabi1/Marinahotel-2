<?php
require_once 'auth.php';
require_once __DIR__ . '/config.php';
// تحديد المنطقة الزمنية لعدن (اليمن)
date_default_timezone_set('Asia/Aden');

// تحديد المسار النسبي للأصول حسب موقع الملف بدقة
$current_script = $_SERVER['SCRIPT_NAME'];
$base_admin_path = '/marinahotel/admin/';

// حساب العمق من مجلد admin
if (strpos($current_script, $base_admin_path) !== false) {
    $path_after_admin = substr($current_script, strpos($current_script, $base_admin_path) + strlen($base_admin_path));
    $depth = substr_count($path_after_admin, '/');
} else {
    $depth = 0;
}

// تحديد المسار الصحيح للأصول
$assets_path = str_repeat('../', $depth);
if ($depth === 0) {
    $base_path = '';
} else {
    $base_path = str_repeat('../', $depth);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>نظام إدارة فندق مارينا</title>

    <!-- Bootstrap CSS (Local) -->
    <link href="<?= BASE_URL ?>assets/css/bootstrap-complete.css" rel="stylesheet">
    <!-- Local Fonts -->
    <link href="<?= BASE_URL ?>assets/fonts/fonts.css" rel="stylesheet">
    <!-- Font Awesome (Local) -->
    <link href="<?= BASE_URL ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <!-- Dashboard CSS -->
    <link href="<?= BASE_URL ?>assets/css/dashboard.css" rel="stylesheet">
    <!-- Arabic Support CSS -->
    <link href="<?= BASE_URL ?>assets/css/arabic-support.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding-top: 80px;
            direction: rtl;
            text-align: right;
            min-height: 100vh;
        }

        /* تنسيق عام للعناصر */
        * {
            font-family: 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* تنسيق النافبار */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
            z-index: 1050;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .navbar-brand:hover {
            color: #ffc107 !important;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            font-size: 1rem;
            padding: 0.7rem 1.2rem;
            transition: all 0.3s ease;
            color: rgba(255,255,255,0.9) !important;
            border-radius: 6px;
            margin: 0 2px;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link:focus {
            color: #ffc107 !important;
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.3);
            padding: 0.5rem 0.75rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
        }

        /* إصلاح القوائم المنسدلة */
        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            padding: 10px 0;
            margin-top: 8px;
            min-width: 300px;
            background: white;
            direction: rtl;
            text-align: right;
            position: absolute;
            z-index: 1000;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }

        .dropdown-header {
            color: #667eea !important;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 8px 20px 5px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dropdown-item {
            padding: 10px 20px;
            font-size: 0.95rem;
            color: #495057;
            transition: all 0.3s ease;
            border-radius: 0;
            text-align: right;
            direction: rtl;
            display: block;
            width: 100%;
            clear: both;
            font-weight: 400;
            text-decoration: none;
            white-space: nowrap;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(-5px);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            opacity: 0.7;
            margin-left: 8px;
        }

        .dropdown-item:hover i,
        .dropdown-item:focus i {
            opacity: 1;
        }

        .dropdown-divider {
            margin: 8px 0;
            border-color: #e9ecef;
        }

        .dropdown-toggle::after {
            margin-left: 5px;
        }

        /* تحسين responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }

            .navbar-brand {
                font-size: 1.2rem;
            }

            .navbar-nav .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .dropdown-menu {
                position: static !important;
                transform: none !important;
                box-shadow: none;
                border: 1px solid #dee2e6;
                margin-top: 5px;
                width: 100%;
                min-width: auto;
            }
        }

        /* تنسيق الجداول */
        .table {
            direction: rtl;
            text-align: right;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        /* تنسيق النماذج */
        .form-control, .form-select {
            direction: rtl;
            text-align: right;
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        input[type="number"], input[type="tel"], .number-input {
            direction: ltr;
            text-align: left;
        }

        /* تنسيق الأزرار */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        /* تنسيق التنبيهات */
        .alert {
            font-size: 1rem;
            font-weight: 500;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* تنسيق البطاقات */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            font-weight: 600;
        }

        /* إشعارات النظام */
        .notifications-container {
            position: fixed;
            top: 100px;
            left: 20px;
            z-index: 1050;
            max-width: 350px;
        }

        .notification-item {
            background: white;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease;
        }

        .notification-item.success {
            border-left-color: #28a745;
        }

        .notification-item.error {
            border-left-color: #dc3545;
        }

        .notification-item.warning {
            border-left-color: #ffc107;
        }

        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* إصلاح notification badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg fixed-top" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand" href="<?= $base_path ?>dash.php" title="الصفحة الرئيسية">
                <i class="fas fa-hotel me-2"></i>فندق مارينا
            </a>
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="تبديل التنقل"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_path ?>dash.php">
                            <i class="fas fa-tachometer-alt me-1"></i>لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_path ?>bookings/list.php">
                            <i class="fas fa-calendar-alt me-1"></i>الحجوزات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_path ?>bookings/add2.php">
                            <i class="fas fa-plus-circle me-1"></i>حجز جديد
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-chart-bar me-1"></i>التقارير
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-money-bill-wave me-1"></i>التقارير المالية</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>reports/revenue.php">
                                <i class="fas fa-chart-line me-2"></i>تقارير الإيرادات
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>reports/report.php">
                                <i class="fas fa-chart-pie me-2"></i>التقارير الشاملة
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>reports.php">
                                <i class="fas fa-file-alt me-2"></i>نظام التقارير الجديد
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-users me-1"></i>تقارير الموظفين</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>reports/employee_withdrawals_report.php">
                                <i class="fas fa-hand-holding-usd me-2"></i>سحوبات الموظفين
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-bed me-1"></i>تقارير الإشغال</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>reports/occupancy.php">
                                <i class="fas fa-chart-area me-2"></i>تقرير الإشغال
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-invoice-dollar me-1"></i>المصروفات
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-plus me-1"></i>إضافة مصروفات</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>expenses/expenses.php">
                                <i class="fas fa-plus-circle me-2"></i>إضافة مصروف جديد
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-list me-1"></i>إدارة المصروفات</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>expenses/list.php">
                                <i class="fas fa-list-ul me-2"></i>قائمة المصروفات
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>expenses/categories.php">
                                <i class="fas fa-tags me-2"></i>فئات المصروفات
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-users-cog me-1"></i>مصروفات الموظفين</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>expenses/employee_expenses.php">
                                <i class="fas fa-user-tie me-2"></i>مصروفات الرواتب
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>expenses/salary_withdrawals.php">
                                <i class="fas fa-hand-holding-usd me-2"></i>سحوبات الرواتب
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-chart-bar me-1"></i>تقارير المصروفات</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>expenses/monthly_report.php">
                                <i class="fas fa-calendar-alt me-2"></i>التقرير الشهري
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>expenses/yearly_report.php">
                                <i class="fas fa-calendar me-2"></i>التقرير السنوي
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs me-1"></i>الإدارة
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-comments me-1"></i>واتساب</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>whatsapp_manager.php">
                                <i class="fab fa-whatsapp me-2"></i>إدارة الواتساب
                            </a></li>
                        </ul>
                    </li>
                </ul>

                <!-- معلومات المستخدم -->
                <ul class="navbar-nav">
                    <!-- إشعارات النظام -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell me-1"></i>الإشعارات
                            <span class="notification-badge" id="notificationCount" style="display: none;"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" id="notificationsMenu">
                            <li><h6 class="dropdown-header">الإشعارات الحديثة</h6></li>
                            <li id="noNotifications"><span class="dropdown-item">لا توجد إشعارات جديدة</span></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i>المستخدم
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= $base_path ?>../logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>تسجيل الخروج
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- حاوية الإشعارات -->
    <div class="notifications-container" id="notificationsContainer"></div>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>

    <!-- JavaScript للإشعارات -->
    <script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/bootstrap-full.js"></script>
    <script>
        // إشعارات النظام
        function loadNotifications() {
            fetch('<?= BASE_URL ?>api/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.notifications.length > 0) {
                        updateNotificationUI(data.notifications);
                    }
                })
                .catch(error => console.log('Error loading notifications:', error));
        }

        function updateNotificationUI(notifications) {
            const badge = document.getElementById('notificationCount');
            const menu = document.getElementById('notificationsMenu');
            const noNotifications = document.getElementById('noNotifications');
            
            if (notifications.length > 0) {
                badge.textContent = notifications.length;
                badge.style.display = 'flex';
                noNotifications.style.display = 'none';
                
                let notificationsHTML = '<li><h6 class="dropdown-header">الإشعارات الحديثة</h6></li>';
                notifications.forEach(notification => {
                    notificationsHTML += `
                        <li><a class="dropdown-item" href="#" onclick="markAsRead(${notification.id})">
                            <div><strong>${notification.title}</strong></div>
                            <small>${notification.message}</small>
                        </a></li>
                    `;
                });
                menu.innerHTML = notificationsHTML;
            }
        }

        function markAsRead(notificationId) {
            fetch('<?= BASE_URL ?>api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({id: notificationId})
            })
            .then(() => loadNotifications())
            .catch(error => console.log('Error marking notification as read:', error));
        }

        // تحميل الإشعارات عند بدء الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            // تحديث كل 30 ثانية
            setInterval(loadNotifications, 30000);
        });

        // إصلاح القوائم المنسدلة للأجهزة المحمولة
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.dropdown');
            
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');
                
                if (toggle && menu) {
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // إغلاق جميع القوائم الأخرى
                        dropdowns.forEach(otherDropdown => {
                            if (otherDropdown !== dropdown) {
                                otherDropdown.querySelector('.dropdown-menu')?.classList.remove('show');
                            }
                        });
                        
                        // تبديل حالة القائمة الحالية
                        menu.classList.toggle('show');
                    });
                }
            });
            
            // إغلاق القوائم عند النقر خارجها
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    dropdowns.forEach(dropdown => {
                        dropdown.querySelector('.dropdown-menu')?.classList.remove('show');
                    });
                }
            });
        });
    </script>
</body>
</html>
