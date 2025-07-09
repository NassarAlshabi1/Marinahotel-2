# تحويل نظام إدارة فندق مارينا بلازا إلى ملف تنفيذي
# Marina Plaza Hotel Management System - Conversion to Executable

## ملخص العملية / Process Summary

### العربية

تم بنجاح تحويل نظام إدارة فندق مارينا بلازا من تطبيق ويب PHP إلى تطبيق مكتبي مستقل يعمل كملف تنفيذي (.exe) على نظام Windows.

### المخرجات النهائية:

#### 1. ملف مضغوط للتوزيع
- **اسم الملف**: `Marina-Hotel-Management-System.zip`
- **حجم الملف**: 70 ميجابايت
- **المحتويات**: نظام كامل ومستقل لإدارة الفندق

#### 2. مجلد التطبيق المستقل
- **اسم المجلد**: `Marina-Hotel-Management-System-exe/`
- **الملف التنفيذي الرئيسي**: `phpdesktop-chrome.exe`
- **ملف التشغيل السريع**: `تشغيل النظام - Start System.bat`

### التقنية المستخدمة

تم استخدام **PHP Desktop Chrome 57** مع PHP 7.1.3 لتحويل تطبيق الويب إلى تطبيق مكتبي. هذه التقنية تدمج:
- متصفح Chrome المدمج
- خادم ويب محلي متعدد الخيوط
- مفسر PHP 7.1.3
- جميع المكتبات والإضافات المطلوبة

### الميزات الرئيسية للنظام المحول

1. **تطبيق مستقل**: لا يحتاج إلى تثبيت خادم ويب أو قاعدة بيانات منفصلة
2. **سهولة التشغيل**: انقر مرتين على الملف التنفيذي وابدأ العمل
3. **محمول**: يمكن نسخ المجلد إلى أي جهاز Windows وسيعمل فوراً
4. **آمن**: جميع الملفات محفوظة محلياً
5. **واجهة عربية**: دعم كامل للغة العربية مع خطوط Tajawal

### كيفية الاستخدام

#### للمستخدم النهائي:
1. استخرج ملف `Marina-Hotel-Management-System.zip`
2. شغل `تشغيل النظام - Start System.bat` أو `phpdesktop-chrome.exe`
3. استخدم بيانات تسجيل الدخول:
   - اسم المستخدم: `admin`
   - كلمة المرور: `1234`

#### للمطور:
- جميع ملفات PHP في مجلد `www/`
- إعدادات التطبيق في `settings.json`
- قاعدة البيانات في `www/database.sql`

---

## English

Successfully converted the Marina Plaza Hotel Management System from a PHP web application to a standalone desktop application that runs as an executable (.exe) file on Windows.

### Final Deliverables:

#### 1. Distribution Archive
- **File Name**: `Marina-Hotel-Management-System.zip`
- **File Size**: 70 MB
- **Contents**: Complete standalone hotel management system

#### 2. Standalone Application Folder
- **Folder Name**: `Marina-Hotel-Management-System-exe/`
- **Main Executable**: `phpdesktop-chrome.exe`
- **Quick Launcher**: `تشغيل النظام - Start System.bat`

### Technology Used

Used **PHP Desktop Chrome 57** with PHP 7.1.3 to convert the web application to desktop application. This technology integrates:
- Embedded Chrome browser
- Local multi-threaded web server
- PHP 7.1.3 interpreter
- All required libraries and extensions

### Key Features of the Converted System

1. **Standalone Application**: No need to install separate web server or database
2. **Easy to Run**: Double-click the executable file and start working
3. **Portable**: Can copy the folder to any Windows machine and it will work immediately
4. **Secure**: All files stored locally
5. **Arabic Interface**: Full Arabic language support with Tajawal fonts

### How to Use

#### For End User:
1. Extract `Marina-Hotel-Management-System.zip`
2. Run `تشغيل النظام - Start System.bat` or `phpdesktop-chrome.exe`
3. Use login credentials:
   - Username: `admin`
   - Password: `1234`

#### For Developer:
- All PHP files in `www/` folder
- Application settings in `settings.json`
- Database in `www/database.sql`

---

## التخصيصات المطبقة / Applied Customizations

### إعدادات النافذة / Window Settings
- العنوان: "نظام إدارة فندق مارينا بلازا - Marina Plaza Hotel Management System"
- الحجم الافتراضي: 1200x800 بكسل
- تشغيل بوضع ملء الشاشة: نعم
- تصغير إلى علبة النظام: نعم

### إعدادات الأمان / Security Settings
- إزالة أدوات المطور: نعم
- منع التنقل الخارجي: نعم
- إخفاء ملفات التطوير والاختبار: نعم
- تعطيل قائمة السياق: جزئياً (الطباعة فقط)

### التحسينات / Optimizations
- حجم النافذة الأمثل للفندق
- إعداد صفحة البداية كصفحة تسجيل الدخول
- رسائل خطأ مخصصة
- دعم كامل للعربية

---

## متطلبات النظام / System Requirements

### الحد الأدنى / Minimum Requirements
- نظام التشغيل: Windows 7 أو أحدث
- المعالج: Intel/AMD متوافق
- الذاكرة: 2 جيجا بايت RAM
- مساحة القرص: 500 ميجا بايت
- **مطلوب**: Visual C++ Redistributable for Visual Studio 2015

### الموصى به / Recommended
- نظام التشغيل: Windows 10/11
- الذاكرة: 4 جيجا بايت RAM أو أكثر
- مساحة القرص: 1 جيجا بايت أو أكثر

---

## الدعم والصيانة / Support & Maintenance

### تحديثات النظام / System Updates
- لتحديث النظام: استبدل ملفات `www/` بالإصدار الجديد
- لإضافة ميزات: عدل ملفات PHP في `www/`
- لتغيير الإعدادات: عدل `settings.json`

### النسخ الاحتياطي / Backup
- انسخ مجلد `Marina-Hotel-Management-System-exe/` كاملاً
- أهم الملفات: `www/database.sql` و `www/`

### استكشاف الأخطاء / Troubleshooting
- إذا لم يبدأ النظام: تحقق من وجود Visual C++ Redistributable
- إذا ظهرت أخطاء: راجع ملف `debug.log`
- للمساعدة: راجع ملف `README.md` في المجلد

---

## ملاحظة هامة / Important Note

هذا النظام جاهز للاستخدام المباشر ولا يحتاج إلى أي تثبيت إضافي عدا Visual C++ Redistributable. يمكن توزيعه على أي عدد من أجهزة Windows ويعمل بنفس الكفاءة.

This system is ready for immediate use and requires no additional installation except Visual C++ Redistributable. It can be distributed to any number of Windows machines and works with the same efficiency.

---

**تم إنجاز المشروع بنجاح في:** 8 يوليو 2025  
**Project Successfully Completed on:** July 8, 2025

**نوع التحويل:** PHP Web Application → Windows Desktop Executable  
**Conversion Type:** PHP Web Application → Windows Desktop Executable

**الحجم النهائي:** 70 ميجابايت (مضغوط)  
**Final Size:** 70 MB (compressed)