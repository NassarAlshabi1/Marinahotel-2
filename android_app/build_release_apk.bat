@echo off
chcp 65001 > nul
echo ========================================
echo    بناء APK للإنتاج (Release)
echo ========================================
echo.

echo التحقق من المتطلبات...

REM التحقق من Java
java -version > nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Java غير مثبت
    echo الرجاء تحميل Java JDK من:
    echo https://www.oracle.com/java/technologies/javase-downloads.html
    pause
    exit /b 1
)
echo ✓ Java موجود

REM إنشاء keystore إذا لم يكن موجوداً
if not exist "app\marina-hotel-key.jks" (
    echo إنشاء مفتاح التوقيع...
    echo الرجاء إدخال المعلومات التالية:
    keytool -genkey -v -keystore app\marina-hotel-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias marina-hotel-key
    
    if %errorlevel% neq 0 (
        echo فشل في إنشاء مفتاح التوقيع
        pause
        exit /b 1
    )
)

echo.
echo بناء APK للإنتاج...
echo هذا قد يستغرق 5-10 دقائق...
echo.

REM بناء Release APK
if exist "gradlew.bat" (
    echo تنظيف المشروع...
    call gradlew.bat clean
    
    echo بناء Release APK...
    call gradlew.bat assembleRelease
    
    echo.
    echo ========================================
    if exist "app\build\outputs\apk\release\app-release.apk" (
        echo ✅ تم إنشاء Release APK بنجاح!
        echo.
        echo المسار: app\build\outputs\apk\release\app-release.apk
        echo الحجم: 
        for %%A in ("app\build\outputs\apk\release\app-release.apk") do echo   %%~zA bytes
        echo.
        echo هذا الإصدار مُحسَّن للأداء ومجهز للنشر
    ) else (
        echo ❌ فشل في إنشاء Release APK
        echo جاري إنشاء Debug APK كبديل...
        call gradlew.bat assembleDebug
        
        if exist "app\build\outputs\apk\debug\app-debug.apk" (
            echo ✅ تم إنشاء Debug APK بنجاح!
            echo المسار: app\build\outputs\apk\debug\app-debug.apk
        )
    )
) else (
    echo ❌ Gradle Wrapper غير موجود
)

echo ========================================
pause