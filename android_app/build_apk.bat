@echo off
echo ========================================
echo        بناء تطبيق فندق مارينا
echo ========================================
echo.

echo جاري تنظيف المشروع...
call gradlew clean

echo.
echo جاري بناء APK للتطوير...
call gradlew assembleDebug

echo.
echo جاري بناء APK للإنتاج...
call gradlew assembleRelease

echo.
echo ========================================
echo تم الانتهاء من البناء!
echo.
echo ملفات APK موجودة في:
echo - app\build\outputs\apk\debug\app-debug.apk
echo - app\build\outputs\apk\release\app-release-unsigned.apk
echo ========================================

pause
