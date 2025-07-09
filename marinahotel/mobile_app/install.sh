#!/bin/bash

# سكريبت تثبيت تطبيق فندق مارينا على الجهاز
# يتطلب توصيل جهاز أندرويد مع تفعيل وضع المطور

echo "📱 بدء تثبيت تطبيق فندق مارينا..."

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

# التحقق من وجود adb
if ! command -v adb &> /dev/null; then
    print_error "ADB غير مثبت. يرجى تثبيت Android SDK Platform Tools"
    echo "يمكنك تحميله من: https://developer.android.com/studio/releases/platform-tools"
    exit 1
fi

# التحقق من وجود ملف APK
APK_FILES=(
    "marina-hotel-debug.apk"
    "marina-hotel-release.apk"
    "platforms/android/app/build/outputs/apk/debug/app-debug.apk"
    "platforms/android/app/build/outputs/apk/release/app-release-unsigned.apk"
)

APK_FILE=""
for file in "${APK_FILES[@]}"; do
    if [ -f "$file" ]; then
        APK_FILE="$file"
        break
    fi
done

if [ -z "$APK_FILE" ]; then
    print_error "لم يتم العثور على ملف APK. يرجى بناء التطبيق أولاً باستخدام:"
    echo "  ./build.sh"
    echo "أو:"
    echo "  cordova build android"
    exit 1
fi

print_status "تم العثور على ملف APK: $APK_FILE"

# بدء خدمة ADB
print_status "بدء خدمة ADB..."
adb start-server

# التحقق من الأجهزة المتصلة
print_status "البحث عن الأجهزة المتصلة..."
DEVICES=$(adb devices | grep -v "List of devices" | grep "device$" | wc -l)

if [ "$DEVICES" -eq 0 ]; then
    print_error "لا توجد أجهزة متصلة. يرجى:"
    echo "1. توصيل جهاز الأندرويد بكابل USB"
    echo "2. تفعيل وضع المطور في الإعدادات"
    echo "3. تفعيل تصحيح USB"
    echo "4. السماح لتصحيح USB من هذا الكمبيوتر"
    echo ""
    echo "للتحقق من الأجهزة المتصلة:"
    echo "  adb devices"
    exit 1
elif [ "$DEVICES" -eq 1 ]; then
    DEVICE=$(adb devices | grep "device$" | cut -f1)
    print_success "تم العثور على جهاز واحد: $DEVICE"
else
    print_warning "تم العثور على عدة أجهزة متصلة:"
    adb devices | grep "device$"
    echo ""
    echo "يرجى اختيار رقم الجهاز أو اتركه فارغاً للأول:"
    read -p "رقم الجهاز: " DEVICE_CHOICE
    
    if [ -n "$DEVICE_CHOICE" ]; then
        DEVICE=$DEVICE_CHOICE
    else
        DEVICE=$(adb devices | grep "device$" | cut -f1 | head -n1)
    fi
    print_status "تم اختيار الجهاز: $DEVICE"
fi

# التحقق من إصدار الأندرويد
print_status "التحقق من إصدار الأندرويد..."
ANDROID_VERSION=$(adb -s $DEVICE shell getprop ro.build.version.release)
API_LEVEL=$(adb -s $DEVICE shell getprop ro.build.version.sdk)

print_status "إصدار الأندرويد: $ANDROID_VERSION (API $API_LEVEL)"

if [ "$API_LEVEL" -lt 19 ]; then
    print_warning "إصدار الأندرويد قديم. التطبيق يتطلب API 19 (Android 4.4) أو أحدث"
    echo "هل تريد المتابعة؟ (y/N)"
    read -p "الاختيار: " CONTINUE
    if [ "$CONTINUE" != "y" ] && [ "$CONTINUE" != "Y" ]; then
        print_status "تم إلغاء التثبيت"
        exit 0
    fi
fi

# التحقق من وجود التطبيق مسبقاً
print_status "التحقق من وجود التطبيق..."
PACKAGE_NAME="com.marinahotel.app"
if adb -s $DEVICE shell pm list packages | grep -q "$PACKAGE_NAME"; then
    print_warning "التطبيق مثبت مسبقاً. سيتم تحديثه"
    
    # إلغاء تثبيت النسخة القديمة (اختياري)
    echo "هل تريد إلغاء تثبيت النسخة القديمة أولاً؟ (y/N)"
    read -p "الاختيار: " UNINSTALL
    if [ "$UNINSTALL" = "y" ] || [ "$UNINSTALL" = "Y" ]; then
        print_status "إلغاء تثبيت النسخة القديمة..."
        adb -s $DEVICE uninstall $PACKAGE_NAME
        if [ $? -eq 0 ]; then
            print_success "تم إلغاء تثبيت النسخة القديمة"
        else
            print_warning "فشل في إلغاء تثبيت النسخة القديمة"
        fi
    fi
fi

# تثبيت التطبيق
print_status "جاري تثبيت التطبيق..."
echo "هذا قد يستغرق بضع ثوان..."

adb -s $DEVICE install -r "$APK_FILE"
INSTALL_RESULT=$?

if [ $INSTALL_RESULT -eq 0 ]; then
    print_success "تم تثبيت التطبيق بنجاح! 🎉"
    
    # عرض معلومات التطبيق
    APP_VERSION=$(adb -s $DEVICE shell dumpsys package $PACKAGE_NAME | grep "versionName" | head -n1 | cut -d'=' -f2)
    print_status "إصدار التطبيق: $APP_VERSION"
    
    # اختيار تشغيل التطبيق
    echo ""
    echo "هل تريد تشغيل التطبيق الآن؟ (Y/n)"
    read -p "الاختيار: " LAUNCH
    if [ "$LAUNCH" != "n" ] && [ "$LAUNCH" != "N" ]; then
        print_status "تشغيل التطبيق..."
        adb -s $DEVICE shell am start -n "$PACKAGE_NAME/.MainActivity"
        if [ $? -eq 0 ]; then
            print_success "تم تشغيل التطبيق"
        else
            print_warning "فشل في تشغيل التطبيق تلقائياً. يمكنك تشغيله من شاشة التطبيقات"
        fi
    fi
    
    echo ""
    echo "معلومات مفيدة:"
    echo "📱 اسم التطبيق: فندق مارينا"
    echo "📦 معرف الحزمة: $PACKAGE_NAME"
    echo "🔧 لعرض سجلات التطبيق: adb logcat | grep 'Marina'"
    echo "🗑️  لإلغاء التثبيت: adb uninstall $PACKAGE_NAME"
    
else
    print_error "فشل في تثبيت التطبيق"
    echo ""
    echo "أسباب محتملة للفشل:"
    echo "1. مساحة تخزين غير كافية على الجهاز"
    echo "2. إعدادات الأمان تمنع تثبيت التطبيقات من مصادر غير معروفة"
    echo "3. توقيع التطبيق غير صالح"
    echo "4. تضارب مع إصدار مثبت مسبقاً"
    echo ""
    echo "حلول محتملة:"
    echo "1. تفعيل 'تثبيت التطبيقات من مصادر غير معروفة' في إعدادات الأمان"
    echo "2. إلغاء تثبيت النسخة القديمة: adb uninstall $PACKAGE_NAME"
    echo "3. إعادة بناء التطبيق: ./build.sh"
    echo "4. التحقق من سجلات ADB: adb logcat"
    exit 1
fi