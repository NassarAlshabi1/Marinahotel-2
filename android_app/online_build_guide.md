# بناء APK عبر الإنترنت - بدون تثبيت أي برامج

## الطريقة الأولى: GitHub Actions (مجاني)

### الخطوات:
1. ارفع مشروع الأندرويد إلى GitHub
2. أنشئ ملف `.github/workflows/build.yml` بالمحتوى التالي:

```yaml
name: Build APK
on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3
    
    - name: Set up JDK 11
      uses: actions/setup-java@v3
      with:
        java-version: '11'
        distribution: 'temurin'
        
    - name: Grant execute permission for gradlew
      run: chmod +x gradlew
      
    - name: Build Debug APK
      run: ./gradlew assembleDebug
      
    - name: Upload APK
      uses: actions/upload-artifact@v3
      with:
        name: app-debug
        path: app/build/outputs/apk/debug/app-debug.apk
```

3. ادفع الكود إلى GitHub
4. سيتم بناء APK تلقائياً ويمكنك تحميله من تبويب Actions

---

## الطريقة الثانية: AppCenter (Microsoft)

### الخطوات:
1. اذهب إلى: https://appcenter.ms
2. أنشئ حساب مجاني
3. أنشئ تطبيق جديد (Android)
4. اربط مع GitHub repository
5. اختر فرع البناء
6. سيتم بناء APK تلقائياً

---

## الطريقة الثالثة: استخدام Docker

### إنشاء ملف Docker:
```dockerfile
FROM openjdk:11-jdk

# تثبيت Android SDK
ENV ANDROID_SDK_ROOT /opt/android-sdk
RUN mkdir -p ${ANDROID_SDK_ROOT}/cmdline-tools && \
    wget -q https://dl.google.com/android/repository/commandlinetools-linux-7583922_latest.zip && \
    unzip commandlinetools-linux-7583922_latest.zip -d ${ANDROID_SDK_ROOT}/cmdline-tools && \
    mv ${ANDROID_SDK_ROOT}/cmdline-tools/cmdline-tools ${ANDROID_SDK_ROOT}/cmdline-tools/latest

ENV PATH ${PATH}:${ANDROID_SDK_ROOT}/cmdline-tools/latest/bin:${ANDROID_SDK_ROOT}/platform-tools

# قبول الرخص
RUN yes | sdkmanager --licenses

# تثبيت المكونات المطلوبة
RUN sdkmanager "platform-tools" "build-tools;30.0.3" "platforms;android-30"

WORKDIR /app
COPY . .

RUN chmod +x ./gradlew
RUN ./gradlew assembleDebug

CMD ["cp", "app/build/outputs/apk/debug/app-debug.apk", "/output/"]
```

### تشغيل Docker:
```bash
docker build -t marina-hotel-apk .
docker run -v $(pwd)/output:/output marina-hotel-apk
```

---

## أيهم تفضل؟
- **GitHub Actions**: مجاني، سهل، يحتاج حساب GitHub
- **AppCenter**: واجهة أنيقة، مجاني، ميزات إضافية
- **Docker**: يعمل محلياً، لا يحتاج إنترنت بعد الإعداد