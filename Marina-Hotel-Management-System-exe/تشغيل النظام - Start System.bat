@echo off
chcp 65001 >nul
echo.
echo ===============================================
echo    نظام إدارة فندق مارينا بلازا
echo    Marina Plaza Hotel Management System
echo ===============================================
echo.
echo جاري تشغيل النظام... Starting System...
echo.

REM Check if the executable exists
if not exist "phpdesktop-chrome.exe" (
    echo خطأ: لم يتم العثور على الملف التنفيذي
    echo Error: Executable file not found
    echo.
    echo تأكد من وجود ملف phpdesktop-chrome.exe في نفس المجلد
    echo Make sure phpdesktop-chrome.exe exists in the same folder
    pause
    exit /b 1
)

REM Start the application
start "" "phpdesktop-chrome.exe"

echo تم تشغيل النظام بنجاح!
echo System started successfully!
echo.
echo معلومات تسجيل الدخول الافتراضية:
echo Default login information:
echo اسم المستخدم / Username: admin
echo كلمة المرور / Password: 1234
echo.
echo يمكنك إغلاق هذه النافذة الآن
echo You can close this window now
echo.
pause