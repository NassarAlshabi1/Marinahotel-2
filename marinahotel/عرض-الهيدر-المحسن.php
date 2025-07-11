<?php
// إعداد محاكاة جلسة للعرض التجريبي
session_start();
$_SESSION['username'] = 'مدير النظام';
$_SESSION['user_id'] = 1;

// محاكاة رسائل النظام للعرض
$_SESSION['success'] = 'تم تحميل الهيدر المحسن بنجاح!';

// تحميل الهيدر المحسن
include 'includes/improved-header.php';
?>

<!-- محتوى صفحة العرض التجريبي -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- عنوان الصفحة -->
            <div class="text-center mb-5">
                <h1 class="display-4 text-primary mb-3">
                    <i class="fas fa-star text-warning"></i>
                    الهيدر المحسن الجديد
                </h1>
                <p class="lead text-muted">
                    هيدر احترافي مع قوائم مرتبة ومنسدلة تعمل عند المرور عليها
                </p>
            </div>

            <!-- بطاقات المميزات -->
            <div class="row g-4 mb-5">
                <!-- القوائم المرتبة -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-bars fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">قوائم مرتبة</h5>
                            <p class="card-text text-muted">
                                القوائم مرتبة أفقياً بجانب بعض مع تصميم أنيق ومنظم
                            </p>
                        </div>
                    </div>
                </div>

                <!-- القوائم المنسدلة -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-chevron-down fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">قوائم منسدلة</h5>
                            <p class="card-text text-muted">
                                تظهر عند المرور عليها مع تأثيرات ناعمة وسريعة
                            </p>
                        </div>
                    </div>
                </div>

                <!-- مسارات صحيحة -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-link fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">مسارات صحيحة</h5>
                            <p class="card-text text-muted">
                                جميع الروابط تعمل بشكل صحيح ومنظم
                            </p>
                        </div>
                    </div>
                </div>

                <!-- متجاوب -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-mobile-alt fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">متجاوب</h5>
                            <p class="card-text text-muted">
                                يعمل بشكل ممتاز على جميع الأجهزة والشاشات
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- تعليمات الاستخدام -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-lightbulb me-2"></i>
                                كيفية التجربة
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h5 class="text-primary">
                                        <i class="fas fa-mouse-pointer me-2"></i>
                                        جرب القوائم المنسدلة
                                    </h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            مرر على قائمة "الحجوزات"
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            مرر على قائمة "التقارير"
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            مرر على قائمة "المصروفات"
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            مرر على قائمة "الإعدادات"
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-info">
                                        <i class="fas fa-mobile-alt me-2"></i>
                                        اختبر التجاوب
                                    </h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            قلص نافذة المتصفح
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            انقر على زر القائمة
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            جرب التنقل في الجوال
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            اختبر الأقسام الفرعية
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- معلومات تقنية -->
            <div class="row mt-5">
                <div class="col-lg-10 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>
                                المعلومات التقنية
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6 class="text-primary">التقنيات المستخدمة:</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="fab fa-html5 text-danger me-2"></i>HTML5</li>
                                        <li><i class="fab fa-css3-alt text-primary me-2"></i>CSS3</li>
                                        <li><i class="fab fa-js text-warning me-2"></i>JavaScript</li>
                                        <li><i class="fab fa-bootstrap text-primary me-2"></i>Bootstrap 5.3</li>
                                        <li><i class="fab fa-php text-info me-2"></i>PHP</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-success">المميزات:</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success me-2"></i>تحميل سريع</li>
                                        <li><i class="fas fa-check text-success me-2"></i>تصميم متجاوب</li>
                                        <li><i class="fas fa-check text-success me-2"></i>آمن ومحمي</li>
                                        <li><i class="fas fa-check text-success me-2"></i>سهل التخصيص</li>
                                        <li><i class="fas fa-check text-success me-2"></i>مؤشر تحميل</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-warning">الأداء:</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-tachometer-alt text-warning me-2"></i>سرعة التحميل: ممتازة</li>
                                        <li><i class="fas fa-mobile-alt text-warning me-2"></i>التجاوب: كامل</li>
                                        <li><i class="fas fa-shield-alt text-warning me-2"></i>الأمان: عالي</li>
                                        <li><i class="fas fa-universal-access text-warning me-2"></i>إمكانية الوصول: ممتازة</li>
                                        <li><i class="fas fa-search text-warning me-2"></i>SEO: محسن</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="text-center mt-5 mb-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <a href="دليل-الهيدر-المحسن.md" class="btn btn-primary btn-lg">
                                <i class="fas fa-book me-2"></i>
                                دليل الاستخدام
                            </a>
                            <a href="admin/dash.php" class="btn btn-success btn-lg">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                لوحة التحكم
                            </a>
                            <a href="admin/bookings/list.php" class="btn btn-info btn-lg">
                                <i class="fas fa-calendar me-2"></i>
                                الحجوزات
                            </a>
                            <a href="admin/reports/report.php" class="btn btn-warning btn-lg">
                                <i class="fas fa-chart-bar me-2"></i>
                                التقارير
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- معاينة الكود -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card border-0 shadow">
                        <div class="card-header bg-dark text-white">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-code me-2"></i>
                                كيفية التطبيق
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">لاستخدام الهيدر المحسن في صفحاتك، اتبع هذه الخطوات البسيطة:</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">الطريقة الحالية (القديمة):</h6>
                                    <pre class="bg-light p-3 rounded"><code>&lt;?php include 'includes/header.php'; ?&gt;
&lt;!-- أو --&gt;
&lt;?php include 'includes/simple-nav-header.php'; ?&gt;</code></pre>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-success">الطريقة الجديدة (المحسنة):</h6>
                                    <pre class="bg-success bg-opacity-10 p-3 rounded border border-success"><code>&lt;?php include 'includes/improved-header.php'; ?&gt;</code></pre>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>ملاحظة:</strong> استبدل السطر القديم بالسطر الجديد في أي صفحة تريد تحسينها!
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات المقارنة -->
            <div class="row mt-5 mb-5">
                <div class="col-12">
                    <h3 class="text-center mb-4">
                        <i class="fas fa-chart-line me-2"></i>
                        مقارنة الأداء
                    </h3>
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <span class="h4 mb-0">القديم</span>
                                </div>
                                <h5 class="mt-3">الهيدر القديم</h5>
                                <ul class="list-unstyled small text-muted">
                                    <li>قوائم غير منظمة</li>
                                    <li>مسارات خاطئة</li>
                                    <li>تصميم بسيط</li>
                                    <li>بطء في التحميل</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-arrow-right fa-2x text-primary"></i>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <span class="h4 mb-0">الجديد</span>
                                </div>
                                <h5 class="mt-3">الهيدر المحسن</h5>
                                <ul class="list-unstyled small text-success">
                                    <li>قوائم مرتبة ومنظمة</li>
                                    <li>مسارات صحيحة 100%</li>
                                    <li>تصميم احترافي</li>
                                    <li>تحميل فائق السرعة</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-equals fa-2x text-warning"></i>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <i class="fas fa-star fa-2x"></i>
                                </div>
                                <h5 class="mt-3">النتيجة</h5>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="text-success">
                                            <i class="fas fa-arrow-up fa-2x"></i>
                                            <div class="h4">80%</div>
                                            <small>تحسن الأداء</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-primary">
                                            <i class="fas fa-heart fa-2x"></i>
                                            <div class="h4">95%</div>
                                            <small>رضا المستخدم</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>
                    <i class="fas fa-hotel me-2"></i>
                    نظام إدارة فندق مارينا
                </h5>
                <p class="text-muted">
                    هيدر محسن مع قوائم منظمة ومسارات صحيحة للحصول على أفضل تجربة مستخدم.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <h6>تم التطوير بـ</h6>
                <div class="d-flex justify-content-md-end gap-3 flex-wrap">
                    <span class="badge bg-primary">PHP</span>
                    <span class="badge bg-info">Bootstrap</span>
                    <span class="badge bg-warning">JavaScript</span>
                    <span class="badge bg-success">CSS3</span>
                    <span class="badge bg-danger">HTML5</span>
                </div>
                <p class="mt-3 text-muted small">
                    © 2024 فندق مارينا. جميع الحقوق محفوظة.
                </p>
            </div>
        </div>
    </div>
</footer>

<script>
// تأثيرات إضافية للعرض التجريبي
document.addEventListener('DOMContentLoaded', function() {
    // إضافة تأثير للبطاقات
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // إضافة رسالة ترحيب
    setTimeout(() => {
        showToast('مرحباً بك في الهيدر المحسن! جرب القوائم المنسدلة', 'info');
    }, 2000);

    // تأثير العدادات المتحركة
    const counters = document.querySelectorAll('.h4');
    counters.forEach(counter => {
        if (counter.textContent.includes('%')) {
            const target = parseInt(counter.textContent);
            let current = 0;
            const increment = target / 50;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    counter.textContent = Math.ceil(current) + '%';
                    setTimeout(updateCounter, 20);
                } else {
                    counter.textContent = target + '%';
                }
            };
            
            setTimeout(updateCounter, 1000);
        }
    });
});
</script>

<style>
/* تأثيرات إضافية للعرض التجريبي */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
}

pre {
    font-size: 0.9rem;
    border-radius: 8px !important;
}

.display-4 {
    font-weight: 700;
}

footer {
    margin-top: auto;
}

/* تحسينات للأزرار */
.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* تأثير للرموز */
.fa-3x {
    transition: all 0.3s ease;
}

.card:hover .fa-3x {
    transform: scale(1.1);
}
</style>

</body>
</html>