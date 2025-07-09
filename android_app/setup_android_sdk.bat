@echo off
chcp 65001 > nul
echo ========================================
echo      إعداد Android SDK بدون Android Studio
echo ========================================
echo.

REM إنشاء مجلد SDK
set SDK_DIR=%USERPROFILE%\Android\Sdk
if not exist "%SDK_DIR%" mkdir "%SDK_DIR%"

REM تحميل Command Line Tools
echo تحميل Android Command Line Tools...
echo الرجاء تحميل الملف التالي يدوياً:
echo https://developer.android.com/studio/#command-tools
echo.
echo بعد التحميل:
echo 1. فك ضغط الملف
echo 2. انسخ محتويات المجلد إلى: %SDK_DIR%\cmdline-tools\latest\
echo 3. قم بتشغيل هذا الملف مرة أخرى

pause

REM التحقق من وجود SDK Manager
if exist "%SDK_DIR%\cmdline-tools\latest\bin\sdkmanager.bat" (
    echo تم العثور على SDK Manager
    echo تثبيت المكونات المطلوبة...
    
    cd /d "%SDK_DIR%\cmdline-tools\latest\bin"
    
    REM قبول الرخص
    echo y | sdkmanager --licenses
    
    REM تثبيت المكونات الأساسية
    sdkmanager "platform-tools"
    sdkmanager "build-tools;30.0.3"
    sdkmanager "platforms;android-30"
    
    echo تم الانتهاء من تثبيت SDK
) else (
    echo SDK Manager غير موجود
    echo يرجى اتباع الخطوات المذكورة أعلاه
)

REM إنشاء متغيرات البيئة
echo إعداد متغيرات البيئة...
setx ANDROID_HOME "%SDK_DIR%"
setx PATH "%PATH%;%SDK_DIR%\platform-tools;%SDK_DIR%\cmdline-tools\latest\bin"

echo.
echo ========================================
echo تم الانتهاء من الإعداد
echo يرجى إعادة تشغيل Command Prompt
echo ========================================

pause