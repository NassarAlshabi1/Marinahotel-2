<?php
// بدء الجلسة وتحميل المكونات الأساسية
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// تحديد المنطقة الزمنية
date_default_timezone_set('Asia/Aden');

// إنشاء CSRF token إذا لم يكن موجوداً
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// تحديد الصفحة الحالية للرابط النشط
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = dirname($_SERVER['REQUEST_URI']);

// دالة للتحقق من الرابط النشط
function is_menu_active($menu_name) {
    global $current_page, $current_dir;
    
    // إذا كان في مجلد admin أو فرعي منه
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        if (strpos($current_dir, $menu_name) !== false || strpos($current_page, $menu_name) !== false) {
            return 'active';
        }
    }
    return '';
}

// تحديد المسار الأساسي للنظام
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']);

// تنظيف مسار النظام
$system_path = '/marinahotel';
if (strpos($script_dir, 'marinahotel') !== false) {
    $system_path = substr($script_dir, 0, strpos($script_dir, 'marinahotel') + strlen('marinahotel'));
}

$base_url = $protocol . $host . $system_path;

// تحديد إذا كان المستخدم في مجلد admin
$in_admin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
$admin_prefix = $in_admin ? '' : 'admin/';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="نظام إدارة فندق مارينا - واجهة احترافية">
    <title>نظام إدارة فندق مارينا</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- خط عربي -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --info-color: #0891b2;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --gradient-bg: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #06b6d4 100%);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --border-radius: 0.5rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            font-family: 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            margin: 0;
            padding: 0;
            direction: rtl;
            text-align: right;
            min-height: 100vh;
            font-size: 14px;
        }

        /* شريط التنقل الرئيسي */
        .main-navbar {
            background: var(--gradient-bg);
            box-shadow: var(--shadow-lg);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }

        /* الشعار */
        .brand-logo {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .brand-logo:hover {
            color: #fbbf24;
            transform: scale(1.05);
        }

        .brand-logo i {
            margin-left: 0.75rem;
            font-size: 1.8rem;
            color: #fbbf24;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        /* القائمة الرئيسية */
        .main-menu {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 0.5rem;
        }

        .menu-item {
            position: relative;
        }

        .menu-link {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            position: relative;
            white-space: nowrap;
            gap: 0.5rem;
        }

        .menu-link:hover,
        .menu-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: #fbbf24;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .menu-link.active {
            background: rgba(251, 191, 36, 0.2);
            font-weight: 600;
        }

        .menu-link i {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .menu-link:hover i {
            opacity: 1;
            transform: scale(1.1);
        }

        /* القوائم المنسدلة */
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            min-width: 280px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px) scale(0.95);
            transition: var(--transition);
            z-index: 1100;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .menu-item:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: #374151;
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: var(--primary-color);
            padding-right: 1.5rem;
            font-weight: 500;
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            opacity: 0.7;
            transition: var(--transition);
        }

        .dropdown-item:hover i {
            opacity: 1;
            color: var(--primary-color);
            transform: scale(1.1);
        }

        /* قسم المستخدم */
        .user-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            font-weight: 500;
            gap: 0.5rem;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .logout-button {
            background: rgba(220, 38, 38, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, 0.3);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-button:hover {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* زر القائمة للجوال */
        .mobile-toggle {
            display: none;
            background: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .mobile-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* تصميم متجاوب */
        @media (max-width: 768px) {
            .navbar-container {
                height: 60px;
                padding: 0 0.75rem;
            }

            .brand-logo {
                font-size: 1.2rem;
            }

            .mobile-toggle {
                display: block;
            }

            .main-menu {
                position: fixed;
                top: 60px;
                right: -100%;
                width: 300px;
                height: calc(100vh - 60px);
                background: white;
                flex-direction: column;
                align-items: stretch;
                padding: 1.5rem;
                box-shadow: var(--shadow-lg);
                transition: right 0.3s ease;
                overflow-y: auto;
                gap: 0;
            }

            .main-menu.open {
                right: 0;
            }

            .menu-link {
                color: #374151;
                padding: 1rem 1.25rem;
                border-radius: var(--border-radius);
                border: 1px solid #e5e7eb;
                margin-bottom: 0.5rem;
                justify-content: flex-start;
            }

            .menu-link:hover,
            .menu-link.active {
                background: var(--primary-color);
                color: white;
                border-color: var(--primary-color);
                transform: none;
            }

            .dropdown-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
                margin: 0.5rem 0 1rem 0;
                background: #f9fafb;
                border: 1px solid #e5e7eb;
            }

            .user-section {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
                padding-top: 1.5rem;
                border-top: 1px solid #e5e7eb;
                margin-top: 1rem;
            }

            .user-info {
                color: #374151;
                justify-content: center;
                padding: 1rem;
                background: #f3f4f6;
                border-radius: var(--border-radius);
            }

            .logout-button {
                justify-content: center;
                background: #dc2626;
                color: white;
                border-color: #dc2626;
            }
        }

        /* حاوية المحتوى */
        .content-wrapper {
            margin-top: 70px;
            padding: 2rem 1rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin-top: 60px;
                padding: 1rem 0.75rem;
            }
        }

        /* تحسينات الرسائل */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-right: 4px solid #059669;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-right: 4px solid #dc2626;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-right: 4px solid #d97706;
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-right: 4px solid #2563eb;
        }

        /* تأثيرات خاصة */
        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: #fbbf24;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .menu-item:hover::before,
        .menu-item.active::before {
            transform: scaleX(1);
        }

        /* تحسينات الأداء */
        .menu-link,
        .dropdown-item,
        .logout-button {
            will-change: transform;
        }

        /* مؤشر التحميل */
        .loading-indicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            display: none;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- مؤشر التحميل -->
    <div class="loading-indicator" id="loadingIndicator">
        <div class="spinner"></div>
    </div>

    <!-- شريط التنقل الرئيسي -->
    <nav class="main-navbar">
        <div class="navbar-container">
            <!-- الشعار -->
            <a href="<?= $base_url ?>/admin/dash.php" class="brand-logo">
                <i class="fas fa-hotel"></i>
                فندق مارينا
            </a>

            <!-- زر القائمة للجوال -->
            <button class="mobile-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>

            <!-- القائمة الرئيسية -->
            <ul class="main-menu" id="mainMenu">
                <!-- لوحة التحكم -->
                <li class="menu-item">
                    <a href="<?= $base_url ?>/admin/dash.php" class="menu-link <?= is_menu_active('dash') ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        لوحة التحكم
                    </a>
                </li>

                <!-- الحجوزات -->
                <li class="menu-item">
                    <a href="#" class="menu-link <?= is_menu_active('bookings') ?>">
                        <i class="fas fa-calendar-check"></i>
                        الحجوزات
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: auto;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?= $base_url ?>/admin/bookings/list.php" class="dropdown-item">
                            <i class="fas fa-list-ul"></i>
                            قائمة الحجوزات
                        </a>
                        <a href="<?= $base_url ?>/admin/bookings/add2.php" class="dropdown-item">
                            <i class="fas fa-plus-circle"></i>
                            حجز جديد
                        </a>
                        <a href="<?= $base_url ?>/admin/rooms/" class="dropdown-item">
                            <i class="fas fa-bed"></i>
                            إدارة الغرف
                        </a>
                        <a href="<?= $base_url ?>/admin/bookings/calendar.php" class="dropdown-item">
                            <i class="fas fa-calendar-alt"></i>
                            تقويم الحجوزات
                        </a>
                    </div>
                </li>

                <!-- التقارير -->
                <li class="menu-item">
                    <a href="#" class="menu-link <?= is_menu_active('reports') ?>">
                        <i class="fas fa-chart-line"></i>
                        التقارير
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: auto;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?= $base_url ?>/admin/reports/revenue.php" class="dropdown-item">
                            <i class="fas fa-chart-bar"></i>
                            تقارير الإيرادات
                        </a>
                        <a href="<?= $base_url ?>/admin/reports/report.php" class="dropdown-item">
                            <i class="fas fa-file-chart-line"></i>
                            التقارير الشاملة
                        </a>
                        <a href="<?= $base_url ?>/admin/reports/occupancy.php" class="dropdown-item">
                            <i class="fas fa-chart-area"></i>
                            تقرير الإشغال
                        </a>
                        <a href="<?= $base_url ?>/admin/reports/financial.php" class="dropdown-item">
                            <i class="fas fa-coins"></i>
                            التقارير المالية
                        </a>
                    </div>
                </li>

                <!-- المصروفات -->
                <li class="menu-item">
                    <a href="#" class="menu-link <?= is_menu_active('expenses') ?>">
                        <i class="fas fa-file-invoice-dollar"></i>
                        المصروفات
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: auto;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?= $base_url ?>/admin/expenses/expenses.php" class="dropdown-item">
                            <i class="fas fa-plus-square"></i>
                            إضافة مصروف
                        </a>
                        <a href="<?= $base_url ?>/admin/expenses/list.php" class="dropdown-item">
                            <i class="fas fa-list-alt"></i>
                            قائمة المصروفات
                        </a>
                        <a href="<?= $base_url ?>/admin/expenses/categories.php" class="dropdown-item">
                            <i class="fas fa-tags"></i>
                            فئات المصروفات
                        </a>
                        <a href="<?= $base_url ?>/admin/expenses/salary_withdrawals.php" class="dropdown-item">
                            <i class="fas fa-hand-holding-usd"></i>
                            سحوبات الرواتب
                        </a>
                    </div>
                </li>

                <!-- الموظفين -->
                <li class="menu-item">
                    <a href="<?= $base_url ?>/admin/employees/" class="menu-link <?= is_menu_active('employees') ?>">
                        <i class="fas fa-users"></i>
                        الموظفين
                    </a>
                </li>

                <!-- الإعدادات -->
                <li class="menu-item">
                    <a href="#" class="menu-link <?= is_menu_active('settings') ?>">
                        <i class="fas fa-cogs"></i>
                        الإعدادات
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: auto;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?= $base_url ?>/admin/settings/" class="dropdown-item">
                            <i class="fas fa-sliders-h"></i>
                            إعدادات النظام
                        </a>
                        <a href="<?= $base_url ?>/admin/settings/users.php" class="dropdown-item">
                            <i class="fas fa-user-cog"></i>
                            إدارة المستخدمين
                        </a>
                        <a href="<?= $base_url ?>/admin/settings/backup.php" class="dropdown-item">
                            <i class="fas fa-database"></i>
                            النسخ الاحتياطي
                        </a>
                    </div>
                </li>

                <!-- قسم المستخدم -->
                <li class="user-section">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'المستخدم' ?></span>
                    </div>
                    <a href="<?= $base_url ?>/logout.php" class="logout-button" onclick="return confirm('هل تريد تسجيل الخروج؟')">
                        <i class="fas fa-sign-out-alt"></i>
                        خروج
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- حاوية المحتوى -->
    <div class="content-wrapper">
        <!-- عرض الرسائل -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['warning']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['info']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>

    <!-- الاسكريبتات -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تبديل القائمة للجوال
        function toggleMobileMenu() {
            const menu = document.getElementById('mainMenu');
            menu.classList.toggle('open');
        }

        // إغلاق القائمة عند النقر خارجها
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('mainMenu');
            const toggle = document.querySelector('.mobile-toggle');
            
            if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                menu.classList.remove('open');
            }
        });

        // تحسين القوائم المنسدلة للجوال
        document.addEventListener('DOMContentLoaded', function() {
            // إضافة تفاعل للجوال
            if (window.innerWidth <= 768) {
                const dropdownItems = document.querySelectorAll('.menu-item');
                dropdownItems.forEach(item => {
                    const link = item.querySelector('.menu-link');
                    const menu = item.querySelector('.dropdown-menu');
                    
                    if (menu) {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            // إغلاق القوائم الأخرى
                            dropdownItems.forEach(otherItem => {
                                if (otherItem !== item) {
                                    const otherMenu = otherItem.querySelector('.dropdown-menu');
                                    if (otherMenu) {
                                        otherMenu.style.display = 'none';
                                    }
                                }
                            });
                            
                            // تبديل القائمة الحالية
                            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                        });
                    }
                });
            }

            // إخفاء التنبيهات تلقائياً
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) {
                        closeBtn.click();
                    }
                }, 5000);
            });

            // إضافة تأثير التحميل للروابط
            const links = document.querySelectorAll('a[href]:not([href="#"]):not([href^="javascript:"])');
            links.forEach(link => {
                link.addEventListener('click', function() {
                    showLoadingIndicator();
                    
                    // إخفاء مؤشر التحميل بعد 3 ثوان كحد أقصى
                    setTimeout(() => {
                        hideLoadingIndicator();
                    }, 3000);
                });
            });
        });

        // إظهار مؤشر التحميل
        function showLoadingIndicator() {
            document.getElementById('loadingIndicator').style.display = 'block';
        }

        // إخفاء مؤشر التحميل
        function hideLoadingIndicator() {
            document.getElementById('loadingIndicator').style.display = 'none';
        }

        // إخفاء مؤشر التحميل عند تحميل الصفحة
        window.addEventListener('load', function() {
            hideLoadingIndicator();
        });

        // تحسين تجربة المستخدم
        window.addEventListener('beforeunload', function() {
            showLoadingIndicator();
        });

        // دالة للتحقق من صحة النماذج
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        }

        // دالة لعرض الرسائل المؤقتة
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed`;
            toast.style.cssText = `
                top: 90px;
                left: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: slideInLeft 0.3s ease;
            `;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        }
    </script>

    <style>
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</body>
</html>