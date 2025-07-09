@echo off
chcp 65001 > nul
echo ========================================
echo      بناء APK بدون Android Studio
echo ========================================
echo.

REM التحقق من Java
java -version > nul 2>&1
if %errorlevel% neq 0 (
    echo خطأ: Java غير مثبت
    echo الرجاء تحميل وتثبيت Java JDK من:
    echo https://www.oracle.com/java/technologies/javase-downloads.html
    pause
    exit /b 1
)
echo ✓ Java موجود

REM محاولة العثور على Android SDK
set ANDROID_SDK_ROOT=%USERPROFILE%\Android\Sdk
if not exist "%ANDROID_SDK_ROOT%" (
    set ANDROID_SDK_ROOT=C:\Android\Sdk
)

if exist "%ANDROID_SDK_ROOT%" (
    echo ✓ تم العثور على Android SDK: %ANDROID_SDK_ROOT%
    set ANDROID_HOME=%ANDROID_SDK_ROOT%
    echo sdk.dir=%ANDROID_SDK_ROOT:\=/% > local.properties
) else (
    echo تحذير: لم يتم العثور على Android SDK
    echo سيتم المحاولة بدون تحديد SDK_HOME
)

echo.
echo بدء عملية البناء...
echo هذا قد يستغرق عدة دقائق...
echo.

REM بناء APK باستخدام Gradle Wrapper
if exist "gradlew.bat" (
    echo استخدام Gradle Wrapper...
    
    REM تنظيف المشروع أولاً
    echo تنظيف المشروع...
    call gradlew.bat clean
    
    REM بناء APK
    echo بناء APK...
    call gradlew.bat assembleDebug
    
    echo.
    echo ========================================
    if exist "app\build\outputs\apk\debug\app-debug.apk" (
        echo ✅ تم إنشاء APK بنجاح!
        echo.
        echo المسار: app\build\outputs\apk\debug\app-debug.apk
        echo الحجم: 
        for %%A in ("app\build\outputs\apk\debug\app-debug.apk") do echo   %%~zA bytes
        echo.
        echo يمكنك الآن نسخ الملف إلى هاتفك وتثبيته
    ) else (
        echo ❌ فشل في إنشاء APK
        echo يرجى مراجعة الأخطاء أعلاه
    )
) else (
    echo ❌ Gradle Wrapper غير موجود
    echo المشروع قد يكون غير مكتمل
)

echo ========================================
pause