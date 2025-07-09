@echo off
chcp 65001 > nul
echo ========================================
echo       اختبار تطبيق فندق مارينا
echo ========================================
echo.

echo التحقق من الاتصال بالخادم...
echo رابط الاختبار: http://10.0.0.57/marina hotel/admin/
echo.

REM اختبار الاتصال بالخادم
ping -n 1 10.0.0.57 > nul
if %errorlevel% == 0 (
    echo ✓ الخادم متاح
) else (
    echo ❌ لا يمكن الوصول للخادم
    echo تأكد من:
    echo - تشغيل XAMPP
    echo - أن الكمبيوتر متصل بالشبكة
    echo - أن عنوان IP صحيح
    pause
    exit /b 1
)

echo.
echo فتح الرابط في المتصفح للاختبار...
start http://10.0.0.57/marina hotel/admin/

echo.
echo إذا فتح الموقع في المتصفح بنجاح، فالتطبيق سيعمل أيضاً
echo.

echo التحقق من ملف APK...
if exist "app\build\outputs\apk\debug\app-debug.apk" (
    echo ✓ ملف APK موجود
    echo المسار: app\build\outputs\apk\debug\app-debug.apk
    
    REM عرض معلومات الملف
    for %%A in ("app\build\outputs\apk\debug\app-debug.apk") do (
        echo حجم الملف: %%~zA bytes
        echo تاريخ الإنشاء: %%~tA
    )
) else (
    echo ❌ ملف APK غير موجود
    echo يجب بناء التطبيق أولاً باستخدام Android Studio أو build_simple.bat
)

echo.
echo ========================================
echo انتهاء الاختبار
echo ========================================

pause