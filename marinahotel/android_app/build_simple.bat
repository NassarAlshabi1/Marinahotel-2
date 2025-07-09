@echo off
chcp 65001 > nul
echo ========================================
echo        بناء تطبيق فندق مارينا
echo ========================================
echo.

echo التحقق من متطلبات البناء...

REM التحقق من وجود Java
java -version > nul 2>&1
if %errorlevel% neq 0 (
    echo خطأ: Java غير مثبت أو غير موجود في PATH
    echo يرجى تثبيت Java JDK وإضافته إلى PATH
    pause
    exit /b 1
)

echo ✓ Java موجود

REM التحقق من وجود Android SDK
if not defined ANDROID_HOME (
    echo تحذير: ANDROID_HOME غير محدد
    echo سيتم البحث عن Android SDK في المسارات الافتراضية...
    
    REM البحث عن Android SDK في المسارات المعتادة
    if exist "%USERPROFILE%\AppData\Local\Android\Sdk" (
        set ANDROID_HOME=%USERPROFILE%\AppData\Local\Android\Sdk
        echo تم العثور على Android SDK: %ANDROID_HOME%
    ) else if exist "C:\Users\%USERNAME%\AppData\Local\Android\Sdk" (
        set ANDROID_HOME=C:\Users\%USERNAME%\AppData\Local\Android\Sdk
        echo تم العثور على Android SDK: %ANDROID_HOME%
    ) else (
        echo خطأ: لم يتم العثور على Android SDK
        echo يرجى تثبيت Android Studio أو تحديد مسار ANDROID_HOME
        pause
        exit /b 1
    )
)

echo ✓ Android SDK موجود: %ANDROID_HOME%

REM إنشاء local.properties
echo sdk.dir=%ANDROID_HOME:\=/% > local.properties
echo تم إنشاء ملف local.properties

echo.
echo بدء عملية البناء...
echo.

REM تشغيل Gradle باستخدام wrapper
if exist gradlew.bat (
    echo استخدام Gradle Wrapper...
    call gradlew.bat clean assembleDebug
) else (
    echo لم يتم العثور على Gradle Wrapper
    echo يرجى تثبيت Android Studio أو Gradle يدوياً
    pause
    exit /b 1
)

echo.
echo ========================================
echo تم الانتهاء من البناء!
echo.
if exist "app\build\outputs\apk\debug\app-debug.apk" (
    echo ✓ تم إنشاء APK بنجاح!
    echo الملف موجود في: app\build\outputs\apk\debug\app-debug.apk
) else (
    echo ❌ فشل في إنشاء APK
)
echo ========================================

pause