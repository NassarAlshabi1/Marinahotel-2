@echo off
chcp 65001 > nul
echo ========================================
echo    بناء APK يدوياً - بدون مشاكل Gradle
echo ========================================
echo.

REM حذف ملفات Gradle المتضررة
echo تنظيف ملفات Gradle...
if exist "%USERPROFILE%\.gradle\wrapper\dists\gradle-6.1.1-all" (
    rmdir /s /q "%USERPROFILE%\.gradle\wrapper\dists\gradle-6.1.1-all"
    echo تم حذف ملفات Gradle المتضررة
)

if exist ".gradle" (
    rmdir /s /q ".gradle"
    echo تم حذف مجلد .gradle المحلي
)

echo.
echo إعادة تحميل وبناء المشروع...
echo.

REM بناء المشروع من جديد
if exist "gradlew.bat" (
    echo بدء التحميل والبناء...
    call gradlew.bat --no-daemon clean assembleDebug
    
    if %errorlevel% equ 0 (
        echo.
        echo ========================================
        echo ✅ تم البناء بنجاح!
        
        if exist "app\build\outputs\apk\debug\app-debug.apk" (
            echo.
            echo تفاصيل الملف:
            echo المسار: app\build\outputs\apk\debug\app-debug.apk
            dir "app\build\outputs\apk\debug\app-debug.apk"
            echo.
            echo يمكنك الآن نسخ هذا الملف إلى هاتفك وتثبيته
        )
    ) else (
        echo ❌ فشل البناء
    )
) else (
    echo ❌ ملف gradlew.bat غير موجود
)

echo ========================================
pause