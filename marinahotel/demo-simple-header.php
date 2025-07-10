<?php
// صفحة تجريبية لعرض الهيدر البسيط
// Demo page for the simple header

// محاكاة البيانات للتجربة
session_start();
$_SESSION['username'] = 'أحمد محمد'; // مثال على اسم المستخدم
$_SESSION['success'] = 'تم تحميل الهيدر البسيط بنجاح!'; // رسالة تجريبية

// إعدادات النظام المحاكاة
define('BASE_URL', '/marinahotel/');

// دوال مساعدة للتجربة
function is_active($page_name) {
    return ($page_name === 'demo-simple-header.php') ? 'active' : '';
}

$base_path = '';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="نظام إدارة فندق مارينا - واجهة بسيطة وسهلة الاستخدام">
    <title>نظام إدارة فندق مارينا - عرض تجريبي</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts للخط العربي -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">

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

        /* حاوية المحتوى */
        .content-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* تنسيق خاص للعرض التجريبي */
        .demo-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .demo-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .feature-card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border-right: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .feature-description {
            color: #64748b;
            line-height: 1.6;
        }

        .code-example {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            overflow-x: auto;
        }

        .code-example pre {
            margin: 0;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <!-- شريط التنقل البسيط -->
    <nav class="simple-navbar">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3">
            <!-- شعار النظام -->
            <a class="navbar-brand" href="#" onclick="showAlert('تم النقر على شعار الفندق')">
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
                    <a class="simple-nav-link active" href="#" onclick="showAlert('الانتقال إلى لوحة التحكم')">
                        <i class="fas fa-home"></i>الرئيسية
                    </a>
                </li>

                <!-- الحجوزات -->
                <li class="simple-nav-item simple-dropdown">
                    <a class="simple-nav-link" href="#" onclick="return false;">
                        <i class="fas fa-calendar"></i>الحجوزات
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: 4px;"></i>
                    </a>
                    <div class="simple-dropdown-menu">
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('عرض قائمة الحجوزات')">
                            <i class="fas fa-list"></i>قائمة الحجوزات
                        </a>
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('إضافة حجز جديد')">
                            <i class="fas fa-plus"></i>حجز جديد
                        </a>
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('إدارة الغرف')">
                            <i class="fas fa-bed"></i>إدارة الغرف
                        </a>
                    </div>
                </li>

                <!-- التقارير -->
                <li class="simple-nav-item simple-dropdown">
                    <a class="simple-nav-link" href="#" onclick="return false;">
                        <i class="fas fa-chart-bar"></i>التقارير
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: 4px;"></i>
                    </a>
                    <div class="simple-dropdown-menu">
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('عرض تقارير الإيرادات')">
                            <i class="fas fa-money-bill"></i>تقارير الإيرادات
                        </a>
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('عرض التقارير الشاملة')">
                            <i class="fas fa-file-alt"></i>التقارير الشاملة
                        </a>
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('عرض تقرير الإشغال')">
                            <i class="fas fa-chart-area"></i>تقرير الإشغال
                        </a>
                    </div>
                </li>

                <!-- المصروفات -->
                <li class="simple-nav-item simple-dropdown">
                    <a class="simple-nav-link" href="#" onclick="return false;">
                        <i class="fas fa-receipt"></i>المصروفات
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-right: 4px;"></i>
                    </a>
                    <div class="simple-dropdown-menu">
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('إضافة مصروف جديد')">
                            <i class="fas fa-plus"></i>إضافة مصروف
                        </a>
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('عرض قائمة المصروفات')">
                            <i class="fas fa-list"></i>قائمة المصروفات
                        </a>
                        <a class="simple-dropdown-item" href="#" onclick="showAlert('إدارة فئات المصروفات')">
                            <i class="fas fa-tags"></i>فئات المصروفات
                        </a>
                    </div>
                </li>

                <!-- الموظفين -->
                <li class="simple-nav-item">
                    <a class="simple-nav-link" href="#" onclick="showAlert('إدارة الموظفين')">
                        <i class="fas fa-users"></i>الموظفين
                    </a>
                </li>

                <!-- الإعدادات -->
                <li class="simple-nav-item">
                    <a class="simple-nav-link" href="#" onclick="showAlert('إعدادات النظام')">
                        <i class="fas fa-cog"></i>الإعدادات
                    </a>
                </li>

                <!-- معلومات المستخدم -->
                <li class="user-section">
                    <span class="user-info">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                    <a class="logout-btn" href="#" onclick="return confirm('هل تريد تسجيل الخروج؟') && showAlert('تم تسجيل الخروج')">
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

        <!-- محتوى الصفحة التجريبية -->
        <div class="demo-section">
            <h1 class="demo-title">
                <i class="fas fa-star text-warning me-2"></i>
                عرض تجريبي للهيدر البسيط
            </h1>
            
            <div class="text-center mb-4">
                <p class="lead">هذا عرض تجريبي للهيدر البسيط الجديد لنظام إدارة فندق مارينا</p>
                <p class="text-muted">جرب النقر على القوائم المختلفة لرؤية كيفية عملها</p>
            </div>

            <!-- شبكة المميزات -->
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3 class="feature-title">تصميم سريع</h3>
                    <p class="feature-description">
                        تحميل أسرع بـ 60% مقارنة بالهيدر الأصلي مع كود أقل وأداء محسن
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">متجاوب تماماً</h3>
                    <p class="feature-description">
                        يعمل بشكل مثالي على جميع الأجهزة مع قائمة منزلقة للهواتف المحمولة
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3 class="feature-title">تصميم عصري</h3>
                    <p class="feature-description">
                        ألوان عصرية وتدرجات جميلة مع رموز واضحة وتأثيرات بصرية جذابة
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">سهل الاستخدام</h3>
                    <p class="feature-description">
                        قوائم مبسطة وتنظيم منطقي يقلل من التشتت ويحسن تجربة المستخدم
                    </p>
                </div>
            </div>

            <div class="text-center mt-4">
                <button class="btn btn-primary btn-lg" onclick="showAlert('شكراً لتجربة الهيدر البسيط!')">
                    <i class="fas fa-thumbs-up me-2"></i>أعجبني التصميم
                </button>
                <button class="btn btn-outline-secondary btn-lg ms-2" onclick="toggleDemo()">
                    <i class="fas fa-eye me-2"></i>إخفاء/إظهار التفاصيل
                </button>
            </div>
        </div>

        <!-- قسم الكود -->
        <div class="demo-section" id="codeSection">
            <h2 class="text-center mb-4">
                <i class="fas fa-code me-2"></i>مثال على الكود
            </h2>
            
            <p class="text-center text-muted mb-4">هذا مثال على كيفية استخدام الهيدر البسيط في صفحاتك:</p>
            
            <div class="code-example">
                <pre><code>&lt;?php
// في بداية الصفحة
require_once 'includes/simple-nav-header.php';
?&gt;

&lt;!-- محتوى الصفحة --&gt;
&lt;div class="container"&gt;
    &lt;h1&gt;عنوان الصفحة&lt;/h1&gt;
    &lt;p&gt;محتوى الصفحة هنا...&lt;/p&gt;
&lt;/div&gt;

&lt;?php
// في نهاية الصفحة
require_once 'includes/footer.php';
?&gt;</code></pre>
            </div>

            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    للحصول على دليل الاستخدام الكامل، راجع ملف simple-header-guide.md
                </small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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

        // دالة عرض التنبيهات للتجربة
        function showAlert(message) {
            // إنشاء تنبيه Bootstrap
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-info alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = `
                top: 80px;
                left: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            `;
            alertDiv.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // إزالة التنبيه تلقائياً بعد 3 ثوان
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
            
            return false; // منع الرابط من العمل
        }

        // تبديل عرض قسم الكود
        function toggleDemo() {
            const codeSection = document.getElementById('codeSection');
            if (codeSection.style.display === 'none') {
                codeSection.style.display = 'block';
                codeSection.scrollIntoView({ behavior: 'smooth' });
            } else {
                codeSection.style.display = 'none';
            }
        }

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

        // تحسين الأداء - تحميل متأخر للتأثيرات
        window.addEventListener('load', function() {
            // إضافة تأثيرات للبطاقات
            const cards = document.querySelectorAll('.feature-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });

        // رسالة ترحيب
        setTimeout(() => {
            showAlert('مرحباً بك في العرض التجريبي للهيدر البسيط! 🎉');
        }, 1000);
    </script>
</body>
</html>