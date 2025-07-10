</div>

<footer class="footer mt-5 py-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="footer-brand">
                    <i class="fas fa-hotel text-primary me-2"></i>
                    <strong>نظام إدارة فندق مارينا</strong>
                </div>
                <p class="footer-text mb-0">
                    نظام متكامل لإدارة الحجوزات والضيوف والخدمات الفندقية
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="footer-info">
                    <p class="mb-1">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        &copy; <?= date('Y') ?> جميع الحقوق محفوظة
                    </p>
                    <small class="text-muted">
                        <i class="fas fa-code text-primary me-1"></i>
                        تم التطوير بواسطة فريق التطوير المحلي
                    </small>
                </div>
            </div>
        </div>
        
        <!-- معلومات النظام -->
        <div class="row mt-3 pt-3 border-top">
            <div class="col-md-4">
                <small class="text-muted">
                    <i class="fas fa-server text-success me-1"></i>
                    حالة الخادم: <span class="text-success">متصل</span>
                </small>
            </div>
            <div class="col-md-4 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt text-info me-1"></i>
                    الحماية: مفعلة
                </small>
            </div>
            <div class="col-md-4 text-md-end">
                <small class="text-muted">
                    <i class="fas fa-clock text-warning me-1"></i>
                    آخر تحديث: <?= date('Y-m-d H:i', filemtime(__FILE__)) ?>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- تحميل مكتبات JavaScript المحلية -->
<!-- Bootstrap JS Bundle (محلي) -->
<script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (محلي) -->
<script src="<?= BASE_URL ?>assets/js/jquery.min.js"></script>

<!-- SweetAlert2 (محلي) -->
<script src="<?= BASE_URL ?>assets/js/sweetalert2.min.js"></script>

<!-- Chart.js للرسوم البيانية (محلي) -->
<script src="<?= BASE_URL ?>assets/js/chart.min.js"></script>

<!-- سكريبت النظام الرئيسي -->
<script>
// إعدادات النظام الأساسية
window.systemConfig = {
    baseUrl: '<?= BASE_URL ?>',
    csrfToken: '<?= csrf_token() ?>',
    language: 'ar',
    theme: 'light',
    timezone: 'Asia/Aden'
};

// التحقق من تحميل المكتبات الأساسية
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 تم تحميل نظام إدارة فندق مارينا');
    
    // التحقق من Bootstrap
    if (typeof bootstrap !== 'undefined') {
        console.log('✅ Bootstrap محمل محلياً');
        initializeBootstrapComponents();
    } else {
        console.warn('❌ Bootstrap غير محمل');
        showFallbackMessage('Bootstrap غير متاح');
    }
    
    // التحقق من jQuery
    if (typeof $ !== 'undefined') {
        console.log('✅ jQuery محمل محلياً');
        initializeJQueryComponents();
    } else {
        console.warn('❌ jQuery غير محمل');
    }
    
    // التحقق من SweetAlert2
    if (typeof Swal !== 'undefined') {
        console.log('✅ SweetAlert2 محمل محلياً');
        initializeSweetAlert();
    } else {
        console.warn('❌ SweetAlert2 غير محمل');
    }
    
    // تهيئة المكونات العامة
    initializeSystemComponents();
});

// تهيئة مكونات Bootstrap
function initializeBootstrapComponents() {
    // تفعيل Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => 
        new bootstrap.Tooltip(tooltipTriggerEl)
    );
    
    // تفعيل Popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => 
        new bootstrap.Popover(popoverTriggerEl)
    );
    
    // تحسين القوائم المنسدلة
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.nextElementSibling;
            if (menu) {
                menu.classList.toggle('show');
            }
        });
    });
}

// تهيئة مكونات jQuery
function initializeJQueryComponents() {
    // تحسين النماذج
    $('form').on('submit', function(e) {
        const form = this;
        
        // التحقق من وجود CSRF token
        if (!$(form).find('input[name="csrf_token"]').length) {
            $(form).append('<input type="hidden" name="csrf_token" value="' + window.systemConfig.csrfToken + '">');
        }
        
        // منع الإرسال المتعدد
        const submitBtn = $(form).find('button[type="submit"]');
        if (submitBtn.prop('disabled')) {
            e.preventDefault();
            return false;
        }
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري المعالجة...');
        
        // إعادة تفعيل الزر بعد 3 ثوان
        setTimeout(() => {
            submitBtn.prop('disabled', false).html(submitBtn.data('original-text') || 'حفظ');
        }, 3000);
    });
    
    // تحسين الجداول
    $('table.table').each(function() {
        const table = $(this);
        
        // إضافة فلترة سريعة
        if (!table.prev('.table-search').length) {
            table.before(`
                <div class="table-search mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="البحث في الجدول..." data-table-search>
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
            `);
        }
    });
    
    // تفعيل البحث في الجداول
    $('[data-table-search]').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        const table = $(this).closest('.table-search').next('table');
        
        table.find('tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.includes(searchText));
        });
    });
}

// تهيئة SweetAlert
function initializeSweetAlert() {
    // إعداد SweetAlert2 للغة العربية
    Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-success me-2',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        confirmButtonText: 'موافق',
        cancelButtonText: 'إلغاء',
        showCancelButton: true
    });
    
    // تأكيد الحذف
    $('[data-confirm-delete]').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href') || $(this).data('url');
        const message = $(this).data('message') || 'هل أنت متأكد من الحذف؟';
        
        Swal.fire({
            title: 'تأكيد الحذف',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                if (url) {
                    window.location.href = url;
                } else {
                    $(this).closest('form').submit();
                }
            }
        });
    });
}

// تهيئة مكونات النظام العامة
function initializeSystemComponents() {
    // تحديث الوقت المباشر
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
    // تحسين التنقل
    improveNavigation();
    
    // تحسين النماذج
    enhanceForms();
    
    // تحميل الإشعارات
    loadNotifications();
    
    // تفعيل الاختصارات
    enableKeyboardShortcuts();
    
    // مراقبة حالة الاتصال
    monitorConnectionStatus();
}

// تحديث التاريخ والوقت
function updateDateTime() {
    const now = new Date();
    const options = {
        timeZone: 'Asia/Aden',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    
    const dateTimeString = now.toLocaleString('ar-SA', options);
    const clockElements = document.querySelectorAll('[data-clock]');
    clockElements.forEach(el => {
        el.textContent = dateTimeString;
    });
}

// تحسين التنقل
function improveNavigation() {
    // تمييز الصفحة النشطة
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.split('/').pop())) {
            link.classList.add('active');
        }
    });
    
    // تحسين القوائم المنسدلة للجوال
    if (window.innerWidth <= 768) {
        const dropdownToggles = document.querySelectorAll('.navbar .dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const menu = this.nextElementSibling;
                if (menu) {
                    menu.classList.toggle('show');
                }
            });
        });
    }
}

// تحسين النماذج
function enhanceForms() {
    // تحسين حقول الإدخال
    const inputs = document.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        // إضافة تأثيرات التركيز
        input.addEventListener('focus', function() {
            this.closest('.form-group, .mb-3, .col-md-6, .col-12')?.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.closest('.form-group, .mb-3, .col-md-6, .col-12')?.classList.remove('focused');
        });
        
        // تحسين التحقق
        input.addEventListener('input', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });
    
    // حفظ البيانات محلياً
    const forms = document.querySelectorAll('form[data-auto-save]');
    forms.forEach(form => {
        const formId = form.id || 'form_' + Date.now();
        
        // استرجاع البيانات المحفوظة
        const savedData = localStorage.getItem('form_data_' + formId);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input && input.type !== 'password') {
                        input.value = data[key];
                    }
                });
            } catch (e) {
                console.warn('خطأ في استرجاع البيانات المحفوظة:', e);
            }
        }
        
        // حفظ البيانات عند التغيير
        form.addEventListener('input', function() {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                if (key !== 'csrf_token' && !key.includes('password')) {
                    data[key] = value;
                }
            });
            localStorage.setItem('form_data_' + formId, JSON.stringify(data));
        });
        
        // مسح البيانات المحفوظة عند الإرسال الناجح
        form.addEventListener('submit', function() {
            setTimeout(() => {
                localStorage.removeItem('form_data_' + formId);
            }, 1000);
        });
    });
}

// تحميل الإشعارات
function loadNotifications() {
    // محاكاة تحميل الإشعارات
    const notifications = [
        {
            type: 'info',
            message: 'النظام يعمل بشكل طبيعي',
            time: new Date().toLocaleTimeString('ar-SA')
        }
    ];
    
    updateNotificationBadge(notifications.length);
    
    // تحديث قائمة الإشعارات كل 30 ثانية
    setInterval(loadNotifications, 30000);
}

// تحديث شارة الإشعارات
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationCount');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// تفعيل اختصارات لوحة المفاتيح
function enableKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S للحفظ
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const activeForm = document.querySelector('form:focus-within');
            if (activeForm) {
                const submitBtn = activeForm.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.click();
                }
            }
        }
        
        // Escape لإغلاق المودال
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.show');
            if (activeModal && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(activeModal);
                if (modal) modal.hide();
            }
        }
        
        // F5 لتحديث الصفحة (مع تأكيد)
        if (e.key === 'F5' && !e.ctrlKey) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تحديث الصفحة',
                    text: 'هل تريد تحديث الصفحة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'نعم',
                    cancelButtonText: 'لا'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
            } else {
                if (confirm('هل تريد تحديث الصفحة؟')) {
                    window.location.reload();
                }
            }
        }
    });
}

// مراقبة حالة الاتصال
function monitorConnectionStatus() {
    function updateConnectionStatus() {
        const statusElement = document.querySelector('.text-success');
        if (navigator.onLine) {
            if (statusElement) {
                statusElement.textContent = 'متصل';
                statusElement.className = 'text-success';
            }
        } else {
            if (statusElement) {
                statusElement.textContent = 'غير متصل';
                statusElement.className = 'text-danger';
            }
            showNotification('تحذير: فقدان الاتصال بالإنترنت', 'warning');
        }
    }
    
    window.addEventListener('online', updateConnectionStatus);
    window.addEventListener('offline', updateConnectionStatus);
    updateConnectionStatus();
}

// عرض الإشعارات
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notificationsContainer') || 
                    document.querySelector('.notifications-container');
    
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification-item ${type}`;
    notification.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <i class="fas fa-${getIconForType(type)} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-sm" aria-label="إغلاق"></button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // إغلاق تلقائي
    setTimeout(() => {
        notification.remove();
    }, duration);
    
    // إغلاق يدوي
    notification.querySelector('.btn-close').addEventListener('click', () => {
        notification.remove();
    });
}

// الحصول على أيقونة حسب نوع الإشعار
function getIconForType(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// رسالة بديلة عند عدم توفر المكتبات
function showFallbackMessage(libraryName) {
    console.warn(`تحذير: ${libraryName} غير متاح. بعض الميزات قد لا تعمل بشكل صحيح.`);
}

// تحسينات الأداء
function optimizePerformance() {
    // تحسين الصور المُحملة ببطء
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
    
    // تحسين الأداء للجداول الكبيرة
    const largeTables = document.querySelectorAll('table tbody');
    largeTables.forEach(tbody => {
        if (tbody.children.length > 100) {
            // إخفاء الصفوف الزائدة وإظهارها عند الحاجة
            Array.from(tbody.children).slice(50).forEach(row => {
                row.style.display = 'none';
                row.classList.add('hidden-row');
            });
            
            // إضافة زر "عرض المزيد"
            const showMoreBtn = document.createElement('button');
            showMoreBtn.className = 'btn btn-outline-primary btn-sm mt-2';
            showMoreBtn.innerHTML = '<i class="fas fa-plus me-1"></i>عرض المزيد';
            showMoreBtn.addEventListener('click', () => {
                const hiddenRows = tbody.querySelectorAll('.hidden-row');
                Array.from(hiddenRows).slice(0, 50).forEach(row => {
                    row.style.display = '';
                    row.classList.remove('hidden-row');
                });
                
                if (tbody.querySelectorAll('.hidden-row').length === 0) {
                    showMoreBtn.remove();
                }
            });
            
            tbody.parentNode.appendChild(showMoreBtn);
        }
    });
}

// تشغيل تحسينات الأداء
setTimeout(optimizePerformance, 1000);

// معالجة الأخطاء العامة
window.addEventListener('error', function(e) {
    console.error('خطأ في النظام:', e.error);
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'حدث خطأ',
            text: 'حدث خطأ غير متوقع. يرجى تحديث الصفحة والمحاولة مرة أخرى.',
            icon: 'error',
            confirmButtonText: 'موافق'
        });
    }
});

// إضافة معلومات التصحيح في وضع التطوير
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    console.log('%c🏨 نظام إدارة فندق مارينا', 'color: #667eea; font-size: 16px; font-weight: bold;');
    console.log('وضع التطوير مفعل');
    console.log('إصدار النظام: 2.0.0');
    console.log('آخر تحديث: <?= date('Y-m-d H:i:s') ?>');
}
</script>

<!-- تنسيقات CSS إضافية للتذييل -->
<style>
.footer {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid #dee2e6;
    margin-top: auto;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.footer-brand {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 8px;
}

.footer-text {
    color: #6c757d;
    font-size: 0.9rem;
}

.footer-info {
    text-align: right;
}

@media (max-width: 768px) {
    .footer-info {
        text-align: center;
        margin-top: 15px;
    }
}

/* تحسينات الفلترة */
.table-search .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
}

/* تحسينات النماذج المحسنة */
.focused {
    transform: scale(1.02);
    transition: transform 0.2s ease;
}

.form-control.is-valid {
    border-color: var(--success-color);
}

.form-control.is-invalid {
    border-color: var(--danger-color);
}

/* تأثيرات الإشعارات المحسنة */
.notification-item {
    transform: translateX(-100%);
    animation: slideInRight 0.3s ease forwards;
}

.notification-item:hover {
    transform: translateX(0) scale(1.02);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

/* تحسينات الأداء */
.hidden-row {
    opacity: 0;
    transition: opacity 0.3s ease;
}

/* مؤشر التحميل المحسن */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
}

.loading-spinner-large {
    width: 3rem;
    height: 3rem;
    border: 0.3rem solid rgba(102, 126, 234, 0.3);
    border-radius: 50%;
    border-top-color: var(--primary-color);
    animation: spin 1s ease-in-out infinite;
}
</style>

</body>
</html>
