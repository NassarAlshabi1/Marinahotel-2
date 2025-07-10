# 🏨 دليل نظام إدارة فندق مارينا - الإصدار 2.0

## 📋 نظرة عامة

تم تطوير نظام إدارة فندق مارينا ليكون حلاً شاملاً ومتكاملاً لإدارة العمليات الفندقية بكفاءة عالية وحماية أمنية متقدمة.

### ✨ الميزات الجديدة في الإصدار 2.0

- **🎨 تصميم موحد وجميل**: واجهة مستخدم حديثة مع ثيم مخصص
- **🛡️ حماية أمنية شاملة**: حماية CSRF وفلترة المدخلات
- **📱 تصميم متجاوب**: يعمل بشكل مثالي على جميع الأجهزة
- **🔍 رابط نشط ذكي**: تمييز الصفحة النشطة تلقائياً
- **⚡ أداء محسن**: تحميل سريع وتفاعل سلس
- **🌐 عمل بدون إنترنت**: جميع المكتبات محلية

---

## 🚀 البدء السريع

### 1. متطلبات النظام

- **خادم ويب**: Apache/Nginx
- **PHP**: الإصدار 7.4 أو أحدث
- **قاعدة البيانات**: MySQL 5.7 أو أحدث
- **ذاكرة**: 512 ميجابايت RAM على الأقل
- **مساحة التخزين**: 100 ميجابايت على الأقل

### 2. الملفات الرئيسية المحدثة

```
marinahotel/
├── includes/
│   ├── header.php          # ✅ محدث - شريط تنقل محسن
│   ├── footer.php          # ✅ محدث - تذييل تفاعلي
│   └── security.php        # 🆕 جديد - نظام حماية شامل
├── assets/
│   └── css/
│       └── marina-theme.css # 🆕 جديد - ثيم موحد
└── SYSTEM_GUIDE.md        # 📖 هذا الدليل
```

---

## 🎨 نظام التصميم الموحد

### الألوان الأساسية
```css
--primary-color: #667eea     /* الأزرق الأساسي */
--secondary-color: #764ba2   /* البنفسجي الثانوي */
--success-color: #56ab2f     /* الأخضر للنجاح */
--warning-color: #f5a623     /* البرتقالي للتحذير */
--danger-color: #e74c3c      /* الأحمر للخطر */
```

### استخدام الألوان
```html
<!-- أزرار ملونة -->
<button class="btn btn-primary">زر أساسي</button>
<button class="btn btn-success">زر نجاح</button>
<button class="btn btn-warning">زر تحذير</button>
<button class="btn btn-danger">زر خطر</button>

<!-- بطاقات إحصائية -->
<div class="stat-card">
    <div class="stat-number">150</div>
    <div class="stat-label">إجمالي الحجوزات</div>
</div>
```

---

## 🛡️ نظام الحماية والأمان

### 1. حماية CSRF

```php
// في النماذج - إضافة تلقائية
echo csrf_field();

// للتحقق من الطلبات
verify_post_request();
```

### 2. فلترة المدخلات

```php
// تنظيف البيانات
$clean_name = clean_input($_POST['name'], 'name');
$clean_email = clean_input($_POST['email'], 'email');
$clean_phone = clean_input($_POST['phone'], 'phone');
$clean_number = clean_input($_POST['amount'], 'float');

// أنواع التنظيف المتاحة
'string'   // نص عام
'email'    // بريد إلكتروني
'phone'    // رقم هاتف
'int'      // رقم صحيح
'float'    // رقم عشري
'name'     // أسماء (عربي/إنجليزي)
'date'     // تاريخ
'sql'      // حماية SQL
```

### 3. التحقق من الصلاحيات

```php
// التحقق من تسجيل الدخول
require_login();

// التحقق من صلاحية محددة
require_permission('admin');
require_permission('bookings.create');

// فحص الصلاحية دون إيقاف التنفيذ
if (has_permission('reports.view')) {
    // عرض التقارير
}
```

### 4. تسجيل الأحداث الأمنية

```php
// تسجيل حدث أمني
log_security_event('login_success', [
    'user_id' => $user_id,
    'ip' => $_SERVER['REMOTE_ADDR']
]);
```

---

## 📱 شريط التنقل المحسن

### الميزات الجديدة

1. **تمييز الرابط النشط**: يتم تمييز الصفحة الحالية تلقائياً
2. **قوائم منسدلة محسنة**: تصميم أنيق مع تأثيرات حركية
3. **إشعارات ذكية**: نظام إشعارات متقدم
4. **تصميم متجاوب**: يتكيف مع جميع أحجام الشاشات

### استخدام الشريط

```php
// تضمين الشريط في صفحاتك
<?php include 'includes/header.php'; ?>

// سيتم إضافة الشريط تلقائياً مع:
// - شعار النظام
// - قوائم التنقل
// - إشعارات النظام
// - معلومات المستخدم
```

---

## 🎛️ المكونات التفاعلية

### 1. البطاقات المحسنة

```html
<div class="card">
    <div class="card-header">
        <h5>عنوان البطاقة</h5>
    </div>
    <div class="card-body">
        <p>محتوى البطاقة</p>
    </div>
</div>
```

### 2. النماذج الذكية

```html
<form data-auto-save>
    <?= csrf_field() ?>
    
    <div class="mb-3">
        <label class="form-label">اسم الضيف</label>
        <input type="text" class="form-control" name="guest_name" required>
    </div>
    
    <button type="submit" class="btn btn-primary">حفظ</button>
</form>
```

### 3. الجداول التفاعلية

```html
<table class="table">
    <thead>
        <tr>
            <th>الاسم</th>
            <th>الهاتف</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>أحمد محمد</td>
            <td>+967 77 123 4567</td>
            <td><span class="room-status-available">متاح</span></td>
        </tr>
    </tbody>
</table>
```

### 4. التنبيهات الذكية

```html
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    تم حفظ البيانات بنجاح
</div>

<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    يرجى مراجعة البيانات المدخلة
</div>
```

---

## ⚡ تحسينات الأداء

### 1. التحميل السريع

- **ضغط CSS**: ملفات مضغوطة لتحميل أسرع
- **الخطوط المحلية**: عدم الحاجة للإنترنت
- **تحسين الصور**: ضغط تلقائي للصور المرفوعة

### 2. التفاعل السلس

```javascript
// تأثيرات تلقائية للعناصر
document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-4px)';
    });
});
```

### 3. البحث السريع

```html
<!-- بحث تلقائي في الجداول -->
<input type="text" class="form-control" data-table-search 
       placeholder="البحث في الجدول...">
```

---

## 🔧 إعدادات النظام

### 1. متغيرات البيئة

```php
// ملف config.php
define('SYSTEM_VERSION', '2.0.0');
define('SYSTEM_NAME', 'نظام إدارة فندق مارينا');
define('SESSION_TIMEOUT', 3600); // ساعة واحدة
define('MAX_LOGIN_ATTEMPTS', 5);
```

### 2. إعدادات الحماية

```php
// ملف security.php
$security_settings = [
    'csrf_protection' => true,
    'input_filtering' => true,
    'session_security' => true,
    'brute_force_protection' => true
];
```

---

## 📊 حالات الغرف والحجوزات

### أكواد حالة الغرف

```html
<!-- غرفة متاحة -->
<span class="room-status-available">متاحة</span>

<!-- غرفة محجوزة -->
<span class="room-status-occupied">محجوزة</span>

<!-- غرفة قيد الصيانة -->
<span class="room-status-maintenance">صيانة</span>
```

### حالات الدفع

```html
<!-- مدفوع -->
<span class="payment-status-paid">مدفوع</span>

<!-- معلق -->
<span class="payment-status-pending">معلق</span>

<!-- متأخر -->
<span class="payment-status-overdue">متأخر</span>
```

---

## 🛠️ استكشاف الأخطاء وإصلاحها

### 1. مشاكل شائعة

#### مشكلة: لا تظهر الأيقونات
```html
<!-- تأكد من تحميل Font Awesome -->
<link href="assets/css/fontawesome.min.css" rel="stylesheet">
```

#### مشكلة: لا تعمل القوائم المنسدلة
```javascript
// تأكد من تحميل Bootstrap JS
<script src="assets/js/bootstrap.bundle.min.js"></script>
```

#### مشكلة: خطأ CSRF Token
```php
// تأكد من تضمين security.php
require_once 'includes/security.php';

// إضافة CSRF field في النماذج
echo csrf_field();
```

### 2. سجلات الأخطاء

```bash
# موقع ملفات السجلات
logs/
├── security.log      # سجل أحداث الأمان
├── error.log         # سجل أخطاء النظام
└── access.log        # سجل عمليات الوصول
```

### 3. وضع التصحيح

```php
// تفعيل وضع التصحيح
ini_set('display_errors', 1);
error_reporting(E_ALL);

// في المتصفح - افتح Developer Tools
// وابحث عن رسائل في Console
```

---

## 📱 الاستجابة للأجهزة المختلفة

### نقاط الكسر المدعومة

```css
/* هواتف صغيرة */
@media (max-width: 576px) { ... }

/* هواتف كبيرة */
@media (max-width: 768px) { ... }

/* أجهزة لوحية */
@media (max-width: 992px) { ... }

/* شاشات كبيرة */
@media (min-width: 1200px) { ... }
```

### تحسينات للجوال

```html
<!-- أزرار بعرض كامل للجوال -->
<button class="btn btn-primary btn-block d-md-inline-block">
    حفظ البيانات
</button>

<!-- جداول متجاوبة -->
<div class="table-responsive">
    <table class="table">...</table>
</div>
```

---

## 🚀 أفضل الممارسات

### 1. في التطوير

```php
// استخدم دائماً فلترة المدخلات
$clean_data = clean_input($_POST['data'], 'string');

// تحقق من الصلاحيات قبل العمليات الحساسة
require_permission('admin');

// سجل الأحداث المهمة
log_security_event('booking_created', ['booking_id' => $id]);
```

### 2. في التصميم

```html
<!-- استخدم الفئات المحددة مسبقاً -->
<div class="card shadow">
    <div class="card-header bg-primary">
        <h5 class="text-white mb-0">العنوان</h5>
    </div>
    <div class="card-body">
        المحتوى
    </div>
</div>
```

### 3. في الأمان

```php
// تحقق دائماً من CSRF في النماذج
verify_post_request();

// استخدم HTTPS في الإنتاج
if (!isset($_SERVER['HTTPS'])) {
    // إعادة توجيه إلى HTTPS
}
```

---

## 📞 الدعم الفني

### للحصول على المساعدة:

1. **مراجعة هذا الدليل** أولاً
2. **فحص ملفات السجلات** في مجلد `logs/`
3. **التأكد من الصلاحيات** للملفات والمجلدات
4. **مراجعة إعدادات PHP** والخادم

### معلومات النظام

```php
// عرض معلومات النظام
echo "إصدار النظام: " . SYSTEM_VERSION;
echo "إصدار PHP: " . PHP_VERSION;
echo "الذاكرة المتاحة: " . ini_get('memory_limit');
```

---

## 🔄 التحديثات المستقبلية

### الميزات المخططة:

- 📊 **تقارير متقدمة**: رسوم بيانية تفاعلية
- 🔔 **إشعارات فورية**: تنبيهات في الوقت الفعلي
- 📱 **تطبيق جوال**: تطبيق مخصص للهواتف
- 🌍 **دعم متعدد اللغات**: لغات إضافية
- ☁️ **النسخ الاحتياطي التلقائي**: حفظ تلقائي للبيانات

---

## ✅ قائمة التحقق للتشغيل

- [ ] تحميل جميع الملفات على الخادم
- [ ] إعداد قاعدة البيانات
- [ ] تعديل ملف `config.php`
- [ ] تعيين صلاحيات المجلدات (755 للمجلدات، 644 للملفات)
- [ ] إنشاء مجلد `logs/` وتعيين صلاحيات الكتابة
- [ ] إنشاء مجلد `uploads/` وتعيين صلاحيات الكتابة
- [ ] اختبار تسجيل الدخول
- [ ] اختبار النماذج والحماية
- [ ] اختبار التصميم على أجهزة مختلفة

---

**تم إنشاء هذا الدليل بواسطة فريق التطوير المحلي**  
**آخر تحديث: {{ date('Y-m-d') }}**

---

> 💡 **نصيحة**: احتفظ بنسخة احتياطية من النظام قبل أي تحديثات مستقبلية