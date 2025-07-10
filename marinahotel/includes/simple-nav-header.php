<?php
// بدء الجلسة وتحميل المكونات الأساسية
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

// تحديد المنطقة الزمنية
date_default_timezone_set('Asia/Aden');

// إنشاء CSRF token إذا لم يكن موجوداً
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// تحديد الصفحة الحالية للرابط النشط
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

// دالة للتحقق من الرابط النشط
function is_active($page_name) {
    global $current_page, $current_path;
    return (strpos($current_path, $page_name) !== false || $current_page === $page_name) ? 'active' : '';
}

// تحديد المسار النسبي للأصول
$current_script = $_SERVER['SCRIPT_NAME'];
$base_admin_path = '/marinahotel/admin/';

if (strpos($current_script, $base_admin_path) !== false) {
    $path_after_admin = substr($current_script, strpos($current_script, $base_admin_path) + strlen($base_admin_path));
    $depth = substr_count($path_after_admin, '/');
} else {
    $depth = 0;
}

$base_path = ($depth === 0) ? '' : str_repeat('../', $depth);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="نظام إدارة فندق مارينا - واجهة بسيطة وسهلة الاستخدام">
    <title>نظام إدارة فندق مارينا</title>

    <!-- Bootstrap CSS -->
    <link href="<?= BASE_URL ?>assets/css/bootstrap-complete.css" rel="stylesheet">
    <!-- الخطوط المحلية -->
    <link href="<?= BASE_URL ?>assets/fonts/fonts.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="<?= BASE_URL ?>assets/css/fontawesome.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #06b6d4;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --border-radius: 8px;
            --box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            --transition: all 0.2s ease;
        }

        * {
            font-family: 'Tajawal', 'Segoe UI', sans-serif;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding-top: 70px;
            direction: rtl;
            text-align: right;
            min-height: 100vh;
        }

        /* تصميم شريط التنقل البسيط */
        .simple-navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.8rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1050;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: white !important;
            text-decoration: none;
            transition: var(--transition);
        }

        .navbar-brand:hover {
            color: #fbbf24 !important;
            transform: scale(1.02);
        }

        .navbar-brand i {
            margin-left: 8px;
            color: #fbbf24;
        }

        /* قائمة التنقل البسيطة */
        .simple-nav {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .simple-nav-item {
            position: relative;
        }

        .simple-nav-link {
            color: rgba(255,255,255,0.9) !important;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .simple-nav-link:hover,
        .simple-nav-link.active {
            background-color: rgba(255,255,255,0.15);
            color: #fbbf24 !important;
            transform: translateY(-1px);
        }

        .simple-nav-link i {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* القوائم المنسدلة البسيطة */
        .simple-dropdown {
            position: relative;
        }

        .simple-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 8px 0;
            margin-top: 5px;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition);
            z-index: 1000;
        }

        .simple-dropdown:hover .simple-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .simple-dropdown-item {
            display: block;
            padding: 10px 16px;
            color: #4b5563;
            text-decoration: none;
            transition: var(--transition);
            border-radius: 0;
        }

        .simple-dropdown-item:hover {
            background-color: #f3f4f6;
            color: var(--primary-color);
            padding-right: 20px;
        }

        .simple-dropdown-item i {
            width: 16px;
            margin-left: 8px;
            opacity: 0.7;
        }

        /* قسم معلومات المستخدم */
        .user-section {
            margin-right: auto;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.2);
            color: #fee2e2 !important;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 6px 12px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background: #ef4444;
            color: white !important;
            border-color: #ef4444;
        }

        /* زر القائمة للجوال */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            cursor: pointer;
        }

        /* تصميم متجاوب */
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }

            .simple-navbar {
                padding: 0.6rem 0;
            }

            .navbar-brand {
                font-size: 1.2rem;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .simple-nav {
                position: fixed;
                top: 60px;
                right: -100%;
                width: 280px;
                height: calc(100vh - 60px);
                background: white;
                flex-direction: column;
                align-items: stretch;
                padding: 20px;
                box-shadow: -5px 0 15px rgba(0,0,0,0.1);
                transition: right 0.3s ease;
                overflow-y: auto;
                gap: 5px;
            }

            .simple-nav.open {
                right: 0;
            }

            .simple-nav-link {
                color: #4b5563 !important;
                padding: 12px 16px;
                border-radius: var(--border-radius);
                border: 1px solid #e5e7eb;
            }

            .simple-nav-link:hover,
            .simple-nav-link.active {
                background-color: var(--primary-color);
                color: white !important;
                border-color: var(--primary-color);
                transform: none;
            }

            .simple-dropdown-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
                margin: 5px 0;
                background: #f9fafb;
            }

            .user-section {
                margin-right: 0;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
                margin-top: 20px;
            }
        }

        /* تحسينات الأداء */
        .simple-nav-link,
        .simple-dropdown-item,
        .logout-btn {
            will-change: transform;
        }

        /* حاوية المحتوى */
        .content-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* تنسيق الرسائل */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
        }
    </style>
</head>
<body>
    <!-- شريط التنقل البسيط -->
    <nav class="simple-navbar">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3">
            <!-- شعار النظام -->
            <a class="navbar-brand" href="<?= $base_path ?>dash.php">
                <i class="fas fa-hotel"></i>فندق مارينا
            </a>

            <!-- زر القائمة للجوال -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>

            <!-- قائمة التنقل البسيطة -->
            <ul class="simple-nav" id="simpleNav">
                <!-- لوحة التحكم -->
                <li class="simple-nav-item">
                    <a class="simple-nav-link <?= is_active('dash.php') ?>" href="<?= $base_path ?>dash.php">
                        <i class="fas fa-home"></i>الرئيسية
                    </a>
                </li>

                <!-- الحجوزات -->
                <li class="simple-nav-item simple-dropdown">
                    <a class="simple-nav-link <?= is_active('bookings') ?>" href="#">
                        <i class="fas fa-calendar"></i>الحجوزات
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: 4px;"></i>
                    </a>
                    <div class="simple-dropdown-menu">
                        <a class="simple-dropdown-item" href="<?= $base_path ?>bookings/list.php">
                            <i class="fas fa-list"></i>قائمة الحجوزات
                        </a>
                        <a class="simple-dropdown-item" href="<?= $base_path ?>bookings/add2.php">
                            <i class="fas fa-plus"></i>حجز جديد
                        </a>
                        <a class="simple-dropdown-item" href="<?= $base_path ?>rooms/">
                            <i class="fas fa-bed"></i>إدارة الغرف
                        </a>
                    </div>
                </li>

                <!-- التقارير -->
                <li class="simple-nav-item simple-dropdown">
                    <a class="simple-nav-link <?= is_active('reports') ?>" href="#">
                        <i class="fas fa-chart-bar"></i>التقارير
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: 4px;"></i>
                    </a>
                    <div class="simple-dropdown-menu">
                        <a class="simple-dropdown-item" href="<?= $base_path ?>reports/revenue.php">
                            <i class="fas fa-money-bill"></i>تقارير الإيرادات
                        </a>
                        <a class="simple-dropdown-item" href="<?= $base_path ?>reports/report.php">
                            <i class="fas fa-file-alt"></i>التقارير الشاملة
                        </a>
                        <a class="simple-dropdown-item" href="<?= $base_path ?>reports/occupancy.php">
                            <i class="fas fa-chart-area"></i>تقرير الإشغال
                        </a>
                    </div>
                </li>

                <!-- المصروفات -->
                <li class="simple-nav-item simple-dropdown">
                    <a class="simple-nav-link <?= is_active('expenses') ?>" href="#">
                        <i class="fas fa-receipt"></i>المصروفات
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: 4px;"></i>
                    </a>
                    <div class="simple-dropdown-menu">
                        <a class="simple-dropdown-item" href="<?= $base_path ?>expenses/expenses.php">
                            <i class="fas fa-plus"></i>إضافة مصروف
                        </a>
                        <a class="simple-dropdown-item" href="<?= $base_path ?>expenses/list.php">
                            <i class="fas fa-list"></i>قائمة المصروفات
                        </a>
                        <a class="simple-dropdown-item" href="<?= $base_path ?>expenses/categories.php">
                            <i class="fas fa-tags"></i>فئات المصروفات
                        </a>
                    </div>
                </li>

                <!-- الموظفين -->
                <li class="simple-nav-item">
                    <a class="simple-nav-link <?= is_active('employees') ?>" href="<?= $base_path ?>employees/">
                        <i class="fas fa-users"></i>الموظفين
                    </a>
                </li>

                <!-- الإعدادات -->
                <li class="simple-nav-item">
                    <a class="simple-nav-link <?= is_active('settings') ?>" href="<?= $base_path ?>settings/">
                        <i class="fas fa-cog"></i>الإعدادات
                    </a>
                </li>

                <!-- معلومات المستخدم -->
                <li class="user-section">
                    <span class="user-info">
                        <i class="fas fa-user"></i>
                        <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'المستخدم' ?>
                    </span>
                    <a class="logout-btn" href="<?= $base_path ?>../logout.php" onclick="return confirm('هل تريد تسجيل الخروج؟')">
                        <i class="fas fa-sign-out-alt"></i>خروج
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- حاوية المحتوى -->
    <div class="content-container">
        <!-- عرض الرسائل -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['warning']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($_SESSION['info']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>

    <script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/bootstrap-full.js"></script>
    <script>
        // تبديل القائمة للجوال
        function toggleMobileMenu() {
            const nav = document.getElementById('simpleNav');
            nav.classList.toggle('open');
        }

        // إغلاق القائمة عند النقر خارجها
        document.addEventListener('click', function(e) {
            const nav = document.getElementById('simpleNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('open');
            }
        });

        // تحسين القوائم المنسدلة للجوال
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth <= 768) {
                const dropdowns = document.querySelectorAll('.simple-dropdown');
                dropdowns.forEach(dropdown => {
                    const link = dropdown.querySelector('.simple-nav-link');
                    const menu = dropdown.querySelector('.simple-dropdown-menu');
                    
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // إغلاق القوائم الأخرى
                        dropdowns.forEach(otherDropdown => {
                            if (otherDropdown !== dropdown) {
                                otherDropdown.querySelector('.simple-dropdown-menu').style.display = 'none';
                            }
                        });
                        
                        // تبديل القائمة الحالية
                        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                    });
                });
            }
        });

        // إخفاء التنبيهات تلقائياً
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) {
                        closeBtn.click();
                    }
                }, 5000);
            });
        });
    </script>
</body>
</html>