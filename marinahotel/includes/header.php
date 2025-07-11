<?php
// بدء الجلسة وتحميل المكونات الأساسية
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

// تحديد المنطقة الزمنية
date_default_timezone_set('Asia/Aden');

// إنشاء CSRF token إذا لم يكن موجوداً
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// تحديد الصفحة الحالية للرابط النشط
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

// دالة لإنشاء CSRF token
function csrf_token() {
    return $_SESSION['csrf_token'];
}

// دالة للتحقق من الرابط النشط
function is_active($page_name) {
    global $current_page, $current_path;
    return (strpos($current_path, $page_name) !== false || $current_page === $page_name) ? 'active' : '';
}

// تحديد المسار النسبي للأصول حسب موقع الملف
$current_script = $_SERVER['SCRIPT_NAME'];
$base_admin_path = '/marinahotel/admin/';

if (strpos($current_script, $base_admin_path) !== false) {
    $path_after_admin = substr($current_script, strpos($current_script, $base_admin_path) + strlen($base_admin_path));
    $depth = substr_count($path_after_admin, '/');
} else {
    $depth = 0;
}

$assets_path = str_repeat('../', $depth);
$base_path = ($depth === 0) ? '' : str_repeat('../', $depth);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="نظام إدارة فندق مارينا - نظام متكامل لإدارة الحجوزات والضيوف">
    <meta name="author" content="فريق التطوير المحلي">
    <title>نظام إدارة فندق مارينا</title>

    <!-- Bootstrap CSS (محلي) -->
    <link href="<?= BASE_URL ?>assets/css/bootstrap-complete.css" rel="stylesheet">
    <!-- الخطوط المحلية -->
    <link href="<?= BASE_URL ?>assets/fonts/fonts.css" rel="stylesheet">
    <!-- Font Awesome (محلي) -->
    <link href="<?= BASE_URL ?>assets/css/fontawesome.min.css" rel="stylesheet">
    <!-- تصميم لوحة التحكم -->
    <link href="<?= BASE_URL ?>assets/css/dashboard.css" rel="stylesheet">
    <!-- دعم اللغة العربية -->
    <link href="<?= BASE_URL ?>assets/css/arabic-support.css" rel="stylesheet">
    <!-- ثيم مارينا المحسن -->
    <link href="<?= BASE_URL ?>assets/css/marina-theme.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 12px;
            --box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Tajawal', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding-top: 80px;
            direction: rtl;
            text-align: right;
            min-height: 100vh;
            position: relative;
        }

        /* إعدادات عامة */
        * {
            font-family: 'Tajawal', 'Segoe UI', sans-serif;
        }

        /* تصميم شريط التنقل */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%) !important;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 1rem 0;
            z-index: 1050;
            position: fixed;
            top: 0;
            width: 100%;
            transition: var(--transition);
        }

        .navbar.scrolled {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%) !important;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            transition: var(--transition);
        }

        .navbar-brand:hover {
            color: var(--warning-color) !important;
            transform: scale(1.05);
        }

        .navbar-brand i {
            margin-left: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* روابط التنقل */
        .navbar-nav .nav-link {
            font-weight: 500;
            font-size: 1rem;
            padding: 0.8rem 1.2rem;
            transition: var(--transition);
            color: rgba(255,255,255,0.9) !important;
            border-radius: 8px;
            margin: 0 3px;
            position: relative;
            overflow: hidden;
        }

        .navbar-nav .nav-link:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }

        .navbar-nav .nav-link:hover:before {
            left: 100%;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link:focus,
        .navbar-nav .nav-link.active {
            color: var(--warning-color) !important;
            background-color: rgba(255,255,255,0.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .navbar-nav .nav-link.active {
            background-color: rgba(255,193,7,0.2);
            font-weight: 600;
        }

        /* القوائم المنسدلة */
        .dropdown-menu {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 12px 0;
            margin-top: 8px;
            min-width: 320px;
            background: white;
            direction: rtl;
            text-align: right;
            animation: dropdownFadeIn 0.3s ease;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-header {
            color: var(--primary-color) !important;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 10px 20px 8px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
        }

        .dropdown-item {
            padding: 12px 20px;
            font-size: 0.95rem;
            color: #495057;
            transition: var(--transition);
            border-radius: 0;
            text-align: right;
            direction: rtl;
            position: relative;
            overflow: hidden;
        }

        .dropdown-item:before {
            content: '';
            position: absolute;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            transition: var(--transition);
            z-index: -1;
        }

        .dropdown-item:hover:before {
            right: 0;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: white;
            background: transparent;
            transform: translateX(-8px);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            opacity: 0.7;
            margin-left: 10px;
            transition: var(--transition);
        }

        .dropdown-item:hover i {
            opacity: 1;
            transform: scale(1.1);
        }

        .dropdown-divider {
            margin: 8px 0;
            border-color: #e9ecef;
        }

        /* زر التبديل للجوال */
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.3);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
        }

        /* شارة الإشعارات */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% { transform: translateY(0); }
            40%, 43% { transform: translateY(-8px); }
            70% { transform: translateY(-4px); }
        }

        /* تصميم متجاوب */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }

            .navbar-brand {
                font-size: 1.3rem;
            }

            .navbar-nav .nav-link {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
                margin: 2px 0;
            }

            .dropdown-menu {
                position: static !important;
                transform: none !important;
                box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
                border: 1px solid #dee2e6;
                margin-top: 5px;
                width: 100%;
                min-width: auto;
                animation: none;
            }

            .dropdown-item {
                padding: 10px 15px;
            }
        }

        /* تحسينات إضافية */
        .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
        }

        /* تأثيرات التحميل */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* تنسيق الجداول */
        .table {
            direction: rtl;
            text-align: right;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        /* تنسيق الأزرار */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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

        /* تنسيق البطاقات */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            font-weight: 600;
        }

        /* حاوية الإشعارات */
        .notifications-container {
            position: fixed;
            top: 100px;
            left: 20px;
            z-index: 1060;
            max-width: 350px;
        }

        .notification-item {
            background: white;
            border-right: 4px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification-item.success {
            border-right-color: var(--success-color);
        }

        .notification-item.error {
            border-right-color: var(--danger-color);
        }

        .notification-item.warning {
            border-right-color: var(--warning-color);
        }

        /* تحسينات الأداء */
        .nav-link, .dropdown-item, .btn {
            will-change: transform;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل الرئيسي -->
    <nav class="navbar navbar-expand-lg fixed-top" aria-label="الشريط الرئيسي للتنقل">
        <div class="container-fluid">
            <!-- شعار النظام -->
            <a class="navbar-brand" href="<?= $base_path ?>dash.php" title="الصفحة الرئيسية">
                <i class="fas fa-hotel"></i>فندق مارينا
            </a>

            <!-- زر التبديل للجوال -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="تبديل التنقل"
            >
                <i class="fas fa-bars text-white"></i>
            </button>

            <!-- عناصر التنقل -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- لوحة التحكم -->
                    <li class="nav-item">
                        <a class="nav-link <?= is_active('dash.php') ?>" href="<?= $base_path ?>dash.php">
                            <i class="fas fa-tachometer-alt me-1"></i>لوحة التحكم
                        </a>
                    </li>

                    <!-- الحجوزات -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= is_active('bookings') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-calendar-check me-1"></i>الحجوزات
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-list me-1"></i>إدارة الحجوزات</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>bookings/list.php">
                                <i class="fas fa-list-ul me-2"></i>قائمة الحجوزات
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>bookings/add2.php">
                                <i class="fas fa-plus-circle me-2"></i>حجز جديد
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-bed me-1"></i>الغرف</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>rooms/">
                                <i class="fas fa-door-open me-2"></i>إدارة الغرف
                            </a></li>
                        </ul>
                    </li>

                    <!-- التقارير -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= is_active('reports') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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

                    <!-- المصروفات -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= is_active('expenses') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                        </ul>
                    </li>

                    <!-- الموظفين -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= is_active('employees') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users me-1"></i>الموظفين
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-user-plus me-1"></i>إدارة الموظفين</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>employees/">
                                <i class="fas fa-users-cog me-2"></i>قائمة الموظفين
                            </a></li>
                        </ul>
                    </li>
                </ul>

                <!-- معلومات المستخدم والإعدادات -->
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

                    <!-- قائمة المستخدم -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'المستخدم' ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><i class="fas fa-cog me-1"></i>الإعدادات</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>settings/">
                                <i class="fas fa-user-cog me-2"></i>إعدادات الحساب
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>settings/system.php">
                                <i class="fas fa-cogs me-2"></i>إعدادات النظام
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-tools me-1"></i>أدوات النظام</h6></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>whatsapp_manager.php">
                                <i class="fab fa-whatsapp me-2"></i>إدارة الواتساب
                            </a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>system_tools/">
                                <i class="fas fa-wrench me-2"></i>أدوات متقدمة
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_path ?>../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- حاوية الإشعارات -->
    <div class="notifications-container" id="notificationsContainer"></div>

    <!-- حاوية المحتوى الرئيسي -->
    <div class="container-fluid mt-4">
        <!-- CSRF Token للنماذج -->
        <script>
            window.csrfToken = '<?= csrf_token() ?>';
        </script>

        <!-- تأثير شريط التنقل عند التمرير -->
        <script>
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // تحسين الأداء للقوائم المنسدلة
            document.addEventListener('DOMContentLoaded', function() {
                const dropdowns = document.querySelectorAll('.dropdown');
                dropdowns.forEach(dropdown => {
                    const dropdownMenu = dropdown.querySelector('.dropdown-menu');
                    
                    dropdown.addEventListener('mouseenter', function() {
                        dropdownMenu.style.display = 'block';
                        setTimeout(() => {
                            dropdownMenu.classList.add('show');
                        }, 10);
                    });
                    
                    dropdown.addEventListener('mouseleave', function() {
                        dropdownMenu.classList.remove('show');
                        setTimeout(() => {
                            if (!dropdownMenu.classList.contains('show')) {
                                dropdownMenu.style.display = 'none';
                            }
                        }, 150);
                    });
                });
            });
        </script>

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
