#!/bin/bash

# سكريبت نسخ الأصول من النظام الأساسي إلى التطبيق المحمول
echo "📂 نسخ الأصول من النظام الأساسي..."

# المجلدات
SOURCE_DIR="../"
TARGET_DIR="www/"

# إنشاء المجلدات إذا لم تكن موجودة
mkdir -p "${TARGET_DIR}css"
mkdir -p "${TARGET_DIR}js"

# نسخ ملفات CSS
echo "📄 نسخ ملفات CSS..."
if [ -f "${SOURCE_DIR}assets/css/bootstrap-complete.css" ]; then
    cp "${SOURCE_DIR}assets/css/bootstrap-complete.css" "${TARGET_DIR}css/bootstrap.min.css"
    echo "✅ تم نسخ Bootstrap CSS"
fi

if [ -f "${SOURCE_DIR}assets/css/fontawesome.min.css" ]; then
    cp "${SOURCE_DIR}assets/css/fontawesome.min.css" "${TARGET_DIR}css/fontawesome.min.css"
    echo "✅ تم نسخ FontAwesome CSS"
fi

# نسخ ملفات JavaScript
echo "📄 نسخ ملفات JavaScript..."
if [ -f "${SOURCE_DIR}assets/js/bootstrap-full.js" ]; then
    cp "${SOURCE_DIR}assets/js/bootstrap-full.js" "${TARGET_DIR}js/bootstrap.bundle.min.js"
    echo "✅ تم نسخ Bootstrap JS"
fi

if [ -f "${SOURCE_DIR}assets/js/jquery.min.js" ]; then
    cp "${SOURCE_DIR}assets/js/jquery.min.js" "${TARGET_DIR}js/jquery.min.js"
    echo "✅ تم نسخ jQuery"
fi

if [ -f "${SOURCE_DIR}assets/js/chart.min.js" ]; then
    cp "${SOURCE_DIR}assets/js/chart.min.js" "${TARGET_DIR}js/chart.min.js"
    echo "✅ تم نسخ Chart.js"
fi

echo "✅ تم الانتهاء من نسخ الأصول"