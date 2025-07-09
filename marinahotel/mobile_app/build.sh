#!/bin/bash

# سكريبت بناء تطبيق فندق مارينا
# يقوم بجميع خطوات البناء تلقائياً

echo "🏨 بدء بناء تطبيق فندق مارينا..."

# ألوان للإخراج
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# دالة لطباعة الرسائل الملونة
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# التحقق من وجود Node.js
if ! command -v node &> /dev/null; then
    print_error "Node.js غير مثبت. يرجى تثبيت Node.js أولاً"
    exit 1
fi

# التحقق من وجود npm
if ! command -v npm &> /dev/null; then
    print_error "npm غير مثبت. يرجى تثبيت npm أولاً"
    exit 1
fi

# التحقق من وجود Cordova
if ! command -v cordova &> /dev/null; then
    print_warning "Cordova غير مثبت. جاري التثبيت..."
    npm install -g cordova
    if [ $? -ne 0 ]; then
        print_error "فشل في تثبيت Cordova"
        exit 1
    fi
    print_success "تم تثبيت Cordova بنجاح"
fi

# تثبيت التبعيات
print_status "جاري تثبيت التبعيات..."
npm install
if [ $? -ne 0 ]; then
    print_error "فشل في تثبيت التبعيات"
    exit 1
fi
print_success "تم تثبيت التبعيات بنجاح"

# التحقق من منصة الأندرويد
print_status "التحقق من منصة الأندرويد..."
if cordova platform list | grep -q "android"; then
    print_success "منصة الأندرويد موجودة"
else
    print_status "إضافة منصة الأندرويد..."
    cordova platform add android
    if [ $? -ne 0 ]; then
        print_error "فشل في إضافة منصة الأندرويد"
        exit 1
    fi
    print_success "تم إضافة منصة الأندرويد بنجاح"
fi

# التحقق من الإضافات المطلوبة
print_status "التحقق من الإضافات المطلوبة..."

plugins=(
    "cordova-plugin-whitelist"
    "cordova-plugin-statusbar"
    "cordova-plugin-device"
    "cordova-plugin-splashscreen"
    "cordova-plugin-network-information"
    "cordova-plugin-file"
    "cordova-plugin-file-transfer"
    "cordova-plugin-inappbrowser"
    "cordova-plugin-camera"
    "cordova-plugin-vibration"
    "cordova-plugin-dialogs"
    "cordova-plugin-toast"
)

for plugin in "${plugins[@]}"; do
    if cordova plugin list | grep -q "$plugin"; then
        print_success "الإضافة $plugin موجودة"
    else
        print_status "إضافة $plugin..."
        cordova plugin add "$plugin"
        if [ $? -ne 0 ]; then
            print_warning "فشل في إضافة $plugin (قد تكون مضافة مسبقاً)"
        else
            print_success "تم إضافة $plugin بنجاح"
        fi
    fi
done

# التحقق من متطلبات النظام
print_status "التحقق من متطلبات النظام..."
cordova requirements

# تحديد نوع البناء
BUILD_TYPE="debug"
if [ "$1" = "release" ]; then
    BUILD_TYPE="release"
    print_status "بناء إنتاج (Release)"
else
    print_status "بناء تطوير (Debug)"
fi

# تنظيف البناء السابق
print_status "تنظيف البناء السابق..."
cordova clean android

# بناء التطبيق
print_status "جاري بناء التطبيق..."
if [ "$BUILD_TYPE" = "release" ]; then
    cordova build android --release
else
    cordova build android
fi

if [ $? -ne 0 ]; then
    print_error "فشل في بناء التطبيق"
    exit 1
fi

print_success "تم بناء التطبيق بنجاح! 🎉"

# العثور على ملف APK
if [ "$BUILD_TYPE" = "release" ]; then
    APK_PATH="platforms/android/app/build/outputs/apk/release/app-release-unsigned.apk"
    FINAL_APK="marina-hotel-release.apk"
else
    APK_PATH="platforms/android/app/build/outputs/apk/debug/app-debug.apk"
    FINAL_APK="marina-hotel-debug.apk"
fi

if [ -f "$APK_PATH" ]; then
    # نسخ ملف APK للمجلد الرئيسي
    cp "$APK_PATH" "$FINAL_APK"
    print_success "ملف APK جاهز: $FINAL_APK"
    
    # عرض معلومات الملف
    FILE_SIZE=$(du -h "$FINAL_APK" | cut -f1)
    print_status "حجم الملف: $FILE_SIZE"
    
    if [ "$BUILD_TYPE" = "release" ]; then
        print_warning "ملاحظة: هذا بناء إنتاج غير موقع. للنشر الرسمي، تحتاج لتوقيع التطبيق."
        echo ""
        echo "لتوقيع التطبيق:"
        echo "1. إنشاء keystore:"
        echo "   keytool -genkey -v -keystore marina-hotel.keystore -alias marinahotel -keyalg RSA -keysize 2048 -validity 10000"
        echo ""
        echo "2. توقيع APK:"
        echo "   jarsigner -verbose -sigalg SHA1withRSA -digestalg SHA1 -keystore marina-hotel.keystore $FINAL_APK marinahotel"
        echo ""
        echo "3. محاذاة ZIP:"
        echo "   zipalign -v 4 $FINAL_APK marina-hotel-signed.apk"
    fi
else
    print_error "لم يتم العثور على ملف APK في: $APK_PATH"
    exit 1
fi

# خيارات إضافية
echo ""
echo "خيارات إضافية:"
echo "  📱 تثبيت على جهاز متصل: ./install.sh"
echo "  🖥️  تشغيل في المحاكي: cordova emulate android"
echo "  🌐 تشغيل في المتصفح: cordova serve"
echo "  📊 عرض معلومات المشروع: cordova info"

print_success "اكتمل البناء! يمكنك الآن تثبيت التطبيق أو توزيعه."