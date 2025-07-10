<?php
/**
 * صفحة نموذجية توضح كيفية استخدام النظام المحدث
 * مثال عملي على الميزات الجديدة
 */

// تضمين ملف الترويسة المحدث
require_once 'includes/header.php';

// التحقق من الصلاحية (مثال)
require_permission('admin');

// معالجة النموذج عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF تلقائياً
    verify_post_request();
    
    // تنظيف البيانات المدخلة
    $guest_name = clean_input($_POST['guest_name'], 'name');
    $email = clean_input($_POST['email'], 'email');
    $phone = clean_input($_POST['phone'], 'phone');
    $room_number = clean_input($_POST['room_number'], 'int');
    $amount = clean_input($_POST['amount'], 'float');
    
    // تسجيل الحدث الأمني
    log_security_event('demo_form_submitted', [
        'guest_name' => $guest_name,
        'submitted_by' => $_SESSION['user_id'] ?? 'unknown'
    ]);
    
    // عرض رسالة نجاح
    $success_message = "تم حفظ البيانات بنجاح!";
}
?>

<!-- بداية المحتوى -->
<div class="container-fluid">
    <!-- العنوان الرئيسي -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary">
            <i class="fas fa-vials me-2"></i>
            صفحة نموذجية - عرض الميزات الجديدة
        </h1>
        <div>
            <span class="badge bg-success">النظام v2.0</span>
            <span class="badge bg-info">محدث</span>
        </div>
    </div>

    <!-- عرض رسالة النجاح -->
    <?php if (isset($success_message)): ?>
    <div class="alert alert-success fade-in">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($success_message) ?>
    </div>
    <?php endif; ?>

    <!-- الصف الأول: إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="icon icon-success mb-3">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number">127</div>
                <div class="stat-label">ضيوف نشطين</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="icon icon-warning mb-3">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-number">45</div>
                <div class="stat-label">غرف محجوزة</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="icon icon-danger mb-3">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number">₹85,640</div>
                <div class="stat-label">إيرادات اليوم</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="icon mb-3">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" data-clock></div>
                <div class="stat-label">الوقت الحالي</div>
            </div>
        </div>
    </div>

    <!-- الصف الثاني: النماذج والبيانات -->
    <div class="row">
        <!-- النموذج التفاعلي -->
        <div class="col-lg-8">
            <div class="card shadow slide-in-up">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        نموذج تفاعلي مع الحماية الكاملة
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" data-auto-save id="demoForm">
                        <!-- حقل CSRF التلقائي -->
                        <?= csrf_field() ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user me-1 text-primary"></i>
                                    اسم الضيف
                                </label>
                                <input type="text" class="form-control" name="guest_name" 
                                       placeholder="أدخل اسم الضيف..." required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-envelope me-1 text-primary"></i>
                                    البريد الإلكتروني
                                </label>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="example@domain.com">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-phone me-1 text-primary"></i>
                                    رقم الهاتف
                                </label>
                                <input type="tel" class="form-control" name="phone" 
                                       placeholder="+967 77 123 4567">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-door-open me-1 text-primary"></i>
                                    رقم الغرفة
                                </label>
                                <input type="number" class="form-control" name="room_number" 
                                       placeholder="101" min="1" max="999">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-money-bill-wave me-1 text-primary"></i>
                                    المبلغ
                                </label>
                                <input type="number" class="form-control" name="amount" 
                                       placeholder="0.00" step="0.01" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-calendar me-1 text-primary"></i>
                                    تاريخ الوصول
                                </label>
                                <input type="date" class="form-control" name="arrival_date" 
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-sticky-note me-1 text-primary"></i>
                                ملاحظات
                            </label>
                            <textarea class="form-control" name="notes" rows="3" 
                                      placeholder="ملاحظات إضافية..."></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ البيانات
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-2"></i>إعادة تعيين
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="showFeatures()">
                                <i class="fas fa-lightbulb me-2"></i>عرض الميزات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- الشريط الجانبي -->
        <div class="col-lg-4">
            <!-- معلومات النظام -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات النظام
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>الإصدار:</span>
                        <strong class="text-primary">2.0.0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>الحماية:</span>
                        <span class="badge bg-success">مفعلة</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>CSRF:</span>
                        <span class="badge bg-success">محمي</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>التصميم:</span>
                        <span class="badge bg-info">متجاوب</span>
                    </div>
                </div>
            </div>
            
            <!-- حالة الغرف -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bed me-2"></i>
                        حالة الغرف
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>الغرفة 101</span>
                        <span class="room-status-available">متاحة</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>الغرفة 102</span>
                        <span class="room-status-occupied">محجوزة</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>الغرفة 103</span>
                        <span class="room-status-maintenance">صيانة</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>الدفع:</span>
                        <span class="payment-status-paid">مدفوع</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- الصف الثالث: جدول تفاعلي -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        جدول تفاعلي مع البحث السريع
                    </h5>
                </div>
                <div class="card-body">
                    <!-- البحث السريع سيتم إضافته تلقائياً بواسطة JavaScript -->
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الهاتف</th>
                                <th>الغرفة</th>
                                <th>الحالة</th>
                                <th>المبلغ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>أحمد محمد علي</td>
                                <td>+967 77 123 4567</td>
                                <td>101</td>
                                <td><span class="room-status-occupied">محجوزة</span></td>
                                <td class="payment-status-paid">5,000 ريال</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" data-confirm-delete>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>فاطمة أحمد</td>
                                <td>+967 73 987 6543</td>
                                <td>102</td>
                                <td><span class="room-status-available">متاحة</span></td>
                                <td class="payment-status-pending">3,200 ريال</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" data-confirm-delete>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>محمد عبدالله</td>
                                <td>+967 70 555 1234</td>
                                <td>103</td>
                                <td><span class="room-status-maintenance">صيانة</span></td>
                                <td class="payment-status-overdue">7,800 ريال</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" data-confirm-delete>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- سكريپت مخصص للصفحة -->
<script>
// إضافة تفاعلات مخصصة
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل الحفظ التلقائي للنموذج
    const form = document.getElementById('demoForm');
    if (form) {
        form.setAttribute('data-auto-save', 'true');
    }
    
    // إضافة تأثيرات للبطاقات
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // تحديث الوقت كل ثانية
    updateClock();
    setInterval(updateClock, 1000);
});

// دالة تحديث الساعة
function updateClock() {
    const clockElement = document.querySelector('[data-clock]');
    if (clockElement) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('ar-SA', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        clockElement.textContent = timeString;
    }
}

// دالة عرض الميزات
function showFeatures() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'ميزات النظام الجديدة',
            html: `
                <div class="text-start">
                    <p><i class="fas fa-shield-alt text-success"></i> <strong>حماية CSRF متقدمة</strong></p>
                    <p><i class="fas fa-filter text-info"></i> <strong>فلترة شاملة للمدخلات</strong></p>
                    <p><i class="fas fa-mobile-alt text-primary"></i> <strong>تصميم متجاوب</strong></p>
                    <p><i class="fas fa-search text-warning"></i> <strong>بحث سريع في الجداول</strong></p>
                    <p><i class="fas fa-save text-success"></i> <strong>حفظ تلقائي للنماذج</strong></p>
                    <p><i class="fas fa-keyboard text-secondary"></i> <strong>اختصارات لوحة المفاتيح</strong></p>
                    <p><i class="fas fa-paint-brush text-danger"></i> <strong>تصميم موحد وجميل</strong></p>
                    <p><i class="fas fa-wifi text-muted"></i> <strong>عمل بدون إنترنت</strong></p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'رائع!',
            confirmButtonColor: '#667eea'
        });
    } else {
        alert('ميزات النظام:\n• حماية CSRF\n• فلترة المدخلات\n• تصميم متجاوب\n• بحث سريع\n• حفظ تلقائي');
    }
}

// عرض رسالة ترحيب
setTimeout(() => {
    showNotification('مرحباً بك في النظام المحدث! جرب الميزات الجديدة.', 'info', 5000);
}, 2000);
</script>

<?php
// تضمين ملف التذييل المحدث
require_once 'includes/footer.php';
?>