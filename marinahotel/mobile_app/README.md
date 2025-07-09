# تطبيق فندق مارينا المحمول

## نظرة عامة

تطبيق إدارة شامل لفندق مارينا يعمل على أجهزة الأندرويد. يوفر النظام إدارة كاملة للحجوزات والمدفوعات والتقارير مع إمكانية العمل بدون اتصال إنترنت.

## المميزات الرئيسية

### ✅ إدارة الحجوزات
- إنشاء وتعديل وحذف الحجوزات
- البحث والفلترة المتقدمة
- إدارة حالة الغرف (متاحة، محجوزة، صيانة)
- تتبع بيانات النزلاء

### ✅ نظام المدفوعات
- معالجة المدفوعات المتعددة
- تتبع تاريخ الدفع
- إنشاء إيصالات رقمية
- إرسال رسائل واتساب تلقائية

### ✅ التقارير والإحصائيات
- تقارير مالية شاملة
- إحصائيات الإشغال
- تقارير الأداء
- تصدير PDF وExcel

### ✅ العمل بدون اتصال
- قاعدة بيانات محلية IndexedDB
- مزامنة تلقائية عند الاتصال
- حفظ البيانات المهمة محلياً
- نظام طابور للعمليات المعلقة

### ✅ واجهة مستخدم متقدمة
- تصميم عربي متجاوب
- واجهة حديثة وسهلة الاستخدام
- دعم الوضع الليلي
- إشعارات ذكية

## متطلبات النظام

### متطلبات التطوير
- Node.js 16.0.0 أو أحدث
- npm 8.0.0 أو أحدث
- Android SDK
- Java Development Kit (JDK) 8 أو أحدث
- Apache Cordova CLI

### أجهزة الأندرويد المدعومة
- Android 4.4 (API level 19) أو أحدث
- ذاكرة تخزين: 50 MB على الأقل
- ذاكرة الوصول العشوائي: 1 GB على الأقل

## التثبيت والبناء

### 1. تثبيت التبعيات الأساسية

```bash
# تثبيت Node.js من الموقع الرسمي
# https://nodejs.org/

# تثبيت Cordova عالمياً
npm install -g cordova

# تثبيت تبعيات المشروع
npm install
```

### 2. إضافة منصة الأندرويد

```bash
# إضافة منصة الأندرويد
cordova platform add android

# التحقق من متطلبات النظام
cordova requirements
```

### 3. إضافة الإضافات المطلوبة

```bash
# إضافة جميع الإضافات المطلوبة
cordova plugin add cordova-plugin-whitelist
cordova plugin add cordova-plugin-statusbar
cordova plugin add cordova-plugin-device
cordova plugin add cordova-plugin-splashscreen
cordova plugin add cordova-plugin-network-information
cordova plugin add cordova-plugin-file
cordova plugin add cordova-plugin-file-transfer
cordova plugin add cordova-plugin-inappbrowser
cordova plugin add cordova-plugin-camera
cordova plugin add cordova-plugin-vibration
cordova plugin add cordova-plugin-dialogs
cordova plugin add cordova-plugin-toast
```

### 4. بناء التطبيق

```bash
# بناء للتطوير
npm run build:android

# بناء للإنتاج (موقع)
npm run build:android:release
```

### 5. تشغيل التطبيق

```bash
# تشغيل على المحاكي
npm run emulate:android

# تشغيل على جهاز فعلي
npm run run:android:device

# تشغيل خادم التطوير
npm run serve
```

## إعداد بيئة التطوير

### Android Studio

1. قم بتحميل وتثبيت Android Studio
2. افتح SDK Manager وتأكد من تثبيت:
   - Android SDK Platform-Tools
   - Android SDK Build-Tools
   - Android SDK Platform (API 33)
   - Android SDK Platform (API 19) للدعم الأدنى

### متغيرات البيئة

```bash
# إضافة إلى ~/.bashrc أو ~/.zshrc
export ANDROID_HOME=$HOME/Android/Sdk
export PATH=$PATH:$ANDROID_HOME/tools
export PATH=$PATH:$ANDROID_HOME/tools/bin
export PATH=$PATH:$ANDROID_HOME/platform-tools
export JAVA_HOME=/usr/lib/jvm/java-8-openjdk
```

## إعداد الخادم

### 1. إعداد خادم الويب

تأكد من وجود نظام فندق مارينا الأساسي على الخادم:

```
http://your-server-ip/marinahotel/
```

### 2. إعدادات API

التطبيق يتصل بـ API في المسار التالي:

```
http://your-server-ip/marinahotel/api/
```

### 3. إعداد قاعدة البيانات

تأكد من أن قاعدة البيانات تحتوي على الجداول المطلوبة:
- bookings
- rooms  
- payments
- guests
- whatsapp_messages
- system_notifications

## استخدام التطبيق

### تسجيل الدخول الأولي

1. افتح التطبيق
2. انتقل إلى الإعدادات
3. أدخل عنوان الخادم (مثال: http://192.168.1.100)
4. اختبر الاتصال
5. سجل الدخول باستخدام بيانات المدير

### العمل بدون اتصال

- جميع البيانات تُحفظ محلياً تلقائياً
- العمليات تُضاف لطابور المزامنة
- المزامنة تحدث تلقائياً عند عودة الاتصال
- يمكن عرض البيانات المحفوظة بدون اتصال

### مزامنة البيانات

```javascript
// مزامنة يدوية
await syncOfflineData();

// فحص حالة المزامنة
const pendingItems = await marinaDB.getPendingSyncItems();
```

## الأمان والخصوصية

### تشفير البيانات
- جميع الاتصالات مع الخادم مُشفرة
- كلمات المرور محمية بـ hashing
- بيانات النزلاء محمية محلياً

### الصلاحيات
- الوصول للإنترنت (لمزامنة البيانات)
- التخزين المحلي (لحفظ البيانات)
- الكاميرا (لتصوير الوثائق)
- الاهتزاز (للإشعارات)

## استكشاف الأخطاء

### مشاكل شائعة

#### 1. فشل في البناء
```bash
# مسح الكاش وإعادة البناء
cordova clean
npm run build:android
```

#### 2. مشاكل الاتصال بالخادم
- تأكد من صحة عنوان الخادم
- تحقق من إعدادات الشبكة
- تأكد من تشغيل الخادم

#### 3. مشاكل قاعدة البيانات المحلية
```javascript
// إعادة تهيئة قاعدة البيانات
await marinaDB.clearCompletedSyncItems();

// إنشاء نسخة احتياطية
const backup = await marinaDB.createBackup();
```

### سجلات الأخطاء

```bash
# عرض سجلات الجهاز
adb logcat

# فلترة سجلات Cordova
adb logcat | grep -i cordova
```

## التخصيص والتطوير

### إضافة ميزات جديدة

1. إضافة صفحات جديدة في `www/index.html`
2. إضافة منطق في `www/js/app.js`
3. إضافة API calls في `www/js/api.js`
4. إضافة قواعد البيانات في `www/js/database.js`

### تخصيص التصميم

- تعديل المتغيرات في `www/css/app.css`
- إضافة ألوان وخطوط جديدة
- تخصيص الأيقونات والصور

### الاختبار

```bash
# اختبار على المحاكي
cordova emulate android

# اختبار على الجهاز
cordova run android --device

# اختبار المتصفح
cordova serve
```

## النشر والتوزيع

### بناء ملف APK للإنتاج

```bash
# بناء موقع
cordova build android --release

# توقيع التطبيق (اختياري)
jarsigner -verbose -sigalg SHA1withRSA -digestalg SHA1 -keystore marina-hotel.keystore platforms/android/app/build/outputs/apk/release/app-release-unsigned.apk marinahotel

# محاذاة ZIP
zipalign -v 4 app-release-unsigned.apk marina-hotel-app.apk
```

### التوزيع

1. **التوزيع المباشر**: مشاركة ملف APK
2. **Google Play Store**: رفع التطبيق للمتجر
3. **التوزيع الداخلي**: خادم التحميل الخاص

## الدعم والصيانة

### النسخ الاحتياطية

```javascript
// إنشاء نسخة احتياطية
const backup = await marinaDB.createBackup();

// استعادة من النسخة الاحتياطية
await marinaDB.restoreFromBackup();
```

### التحديثات

1. تحديث ملفات التطبيق
2. زيادة رقم الإصدار في `config.xml`
3. إعادة البناء والنشر
4. إشعار المستخدمين بالتحديث

### المراقبة والتحليل

- مراقبة استخدام التطبيق
- تتبع الأخطاء والمشاكل
- تحليل الأداء
- تجميع ملاحظات المستخدمين

## المساهمة

نرحب بالمساهمات! يرجى:

1. إنشاء Fork للمشروع
2. إنشاء فرع جديد للميزة
3. تنفيذ التغييرات والاختبار
4. إرسال Pull Request

## الترخيص

هذا المشروع مرخص تحت رخصة MIT. راجع ملف `LICENSE` للتفاصيل.

## الاتصال والدعم

- **البريد الإلكتروني**: info@marinahotel.com
- **الموقع**: http://marinahotel.com
- **الدعم الفني**: support@marinahotel.com

---

**نصائح مهمة:**

1. **احتفظ بنسخة احتياطية** من البيانات بانتظام
2. **اختبر التطبيق** قبل النشر في بيئة الإنتاج
3. **راقب الأداء** وقم بالتحسينات حسب الحاجة
4. **حدث التطبيق** بانتظام للحصول على آخر الميزات

**تم تطوير هذا التطبيق خصيصاً لفندق مارينا بواسطة فريق التطوير المتخصص.**