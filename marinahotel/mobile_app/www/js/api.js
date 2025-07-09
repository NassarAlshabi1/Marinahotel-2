/**
 * ملف API للتواصل مع خادم فندق مارينا
 */

class MarinaAPI {
    constructor() {
        this.baseURL = localStorage.getItem('serverUrl') || 'http://localhost';
        this.apiEndpoint = '/marinahotel/api';
        this.timeout = 10000; // 10 ثوان
        this.retryAttempts = 3;
        this.retryDelay = 1000; // ثانية واحدة
    }

    /**
     * تحديث عنوان الخادم
     */
    updateServerURL(url) {
        this.baseURL = url;
        localStorage.setItem('serverUrl', url);
    }

    /**
     * الحصول على عنوان API كامل
     */
    getFullURL(endpoint) {
        return `${this.baseURL}${this.apiEndpoint}${endpoint}`;
    }

    /**
     * إجراء طلب HTTP مع معالجة الأخطاء وإعادة المحاولة
     */
    async makeRequest(endpoint, options = {}) {
        const url = this.getFullURL(endpoint);
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            timeout: this.timeout
        };

        const finalOptions = { ...defaultOptions, ...options };

        // إضافة Authorization header إذا كان المستخدم مسجل دخول
        if (currentUser && currentUser.token) {
            finalOptions.headers['Authorization'] = `Bearer ${currentUser.token}`;
        }

        // تحويل البيانات إلى JSON إذا لزم الأمر
        if (finalOptions.body && typeof finalOptions.body === 'object') {
            finalOptions.body = JSON.stringify(finalOptions.body);
        }

        let lastError;
        
        for (let attempt = 1; attempt <= this.retryAttempts; attempt++) {
            try {
                console.log(`طلب API - المحاولة ${attempt}: ${finalOptions.method} ${url}`);
                
                const response = await this.fetchWithTimeout(url, finalOptions);
                
                // التحقق من حالة الاستجابة
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log('استجابة API ناجحة:', data);
                
                return data;

            } catch (error) {
                lastError = error;
                console.error(`فشل طلب API - المحاولة ${attempt}:`, error);

                // عدم إعادة المحاولة في الحالات التالية
                if (error.name === 'AbortError' || 
                    error.message.includes('401') || 
                    error.message.includes('403')) {
                    break;
                }

                // انتظار قبل إعادة المحاولة
                if (attempt < this.retryAttempts) {
                    await this.delay(this.retryDelay * attempt);
                }
            }
        }

        throw lastError;
    }

    /**
     * fetch مع timeout
     */
    async fetchWithTimeout(url, options) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), options.timeout);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }

    /**
     * تأخير لإعادة المحاولة
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // ========================================
    // دوال API المحددة
    // ========================================

    /**
     * تسجيل الدخول
     */
    async login(username, password) {
        return await this.makeRequest('/auth/login', {
            method: 'POST',
            body: { username, password }
        });
    }

    /**
     * تسجيل الخروج
     */
    async logout() {
        return await this.makeRequest('/auth/logout', {
            method: 'POST'
        });
    }

    /**
     * التحقق من صحة الجلسة
     */
    async validateSession() {
        return await this.makeRequest('/auth/validate');
    }

    /**
     * جلب إحصائيات لوحة التحكم
     */
    async getDashboardStats() {
        return await this.makeRequest('/dashboard/stats');
    }

    /**
     * جلب الأنشطة الأخيرة
     */
    async getRecentActivities() {
        return await this.makeRequest('/dashboard/activities');
    }

    /**
     * جلب جميع الحجوزات
     */
    async getBookings(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `/bookings?${queryString}` : '/bookings';
        return await this.makeRequest(endpoint);
    }

    /**
     * جلب حجز محدد
     */
    async getBooking(bookingId) {
        return await this.makeRequest(`/bookings/${bookingId}`);
    }

    /**
     * إنشاء حجز جديد
     */
    async createBooking(bookingData) {
        return await this.makeRequest('/bookings', {
            method: 'POST',
            body: bookingData
        });
    }

    /**
     * تحديث حجز
     */
    async updateBooking(bookingId, bookingData) {
        return await this.makeRequest(`/bookings/${bookingId}`, {
            method: 'PUT',
            body: bookingData
        });
    }

    /**
     * حذف حجز
     */
    async deleteBooking(bookingId) {
        return await this.makeRequest(`/bookings/${bookingId}`, {
            method: 'DELETE'
        });
    }

    /**
     * جلب جميع الغرف
     */
    async getRooms(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `/rooms?${queryString}` : '/rooms';
        return await this.makeRequest(endpoint);
    }

    /**
     * جلب الغرف المتاحة
     */
    async getAvailableRooms(checkIn, checkOut) {
        const params = { status: 'شاغرة' };
        if (checkIn) params.checkin = checkIn;
        if (checkOut) params.checkout = checkOut;
        
        const queryString = new URLSearchParams(params).toString();
        return await this.makeRequest(`/rooms/available?${queryString}`);
    }

    /**
     * جلب غرفة محددة
     */
    async getRoom(roomNumber) {
        return await this.makeRequest(`/rooms/${roomNumber}`);
    }

    /**
     * تحديث حالة غرفة
     */
    async updateRoomStatus(roomNumber, status) {
        return await this.makeRequest(`/rooms/${roomNumber}/status`, {
            method: 'PUT',
            body: { status }
        });
    }

    /**
     * معالجة دفعة
     */
    async processPayment(bookingId, paymentData) {
        return await this.makeRequest(`/payments/process`, {
            method: 'POST',
            body: {
                booking_id: bookingId,
                ...paymentData
            }
        });
    }

    /**
     * جلب تاريخ المدفوعات
     */
    async getPaymentHistory(bookingId) {
        return await this.makeRequest(`/payments/history/${bookingId}`);
    }

    /**
     * جلب تقرير محدد
     */
    async getReport(reportType, filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `/reports/${reportType}?${queryString}` : `/reports/${reportType}`;
        return await this.makeRequest(endpoint);
    }

    /**
     * تصدير تقرير
     */
    async exportReport(reportType, format, filters = {}) {
        const queryString = new URLSearchParams({ format, ...filters }).toString();
        const endpoint = `/reports/${reportType}/export?${queryString}`;
        
        const response = await this.makeRequest(endpoint);
        return response;
    }

    /**
     * إرسال رسالة واتساب
     */
    async sendWhatsApp(phoneNumber, message, bookingId = null) {
        return await this.makeRequest('/whatsapp/send', {
            method: 'POST',
            body: {
                phone: phoneNumber,
                message: message,
                booking_id: bookingId
            }
        });
    }

    /**
     * جلب حالة رسائل الواتساب
     */
    async getWhatsAppStatus() {
        return await this.makeRequest('/whatsapp/status');
    }

    /**
     * جلب طابور رسائل الواتساب
     */
    async getWhatsAppQueue() {
        return await this.makeRequest('/whatsapp/queue');
    }

    /**
     * إعادة إرسال رسالة واتساب
     */
    async retryWhatsAppMessage(messageId) {
        return await this.makeRequest(`/whatsapp/retry/${messageId}`, {
            method: 'POST'
        });
    }

    /**
     * جلب الإشعارات
     */
    async getNotifications(limit = 20) {
        return await this.makeRequest(`/notifications?limit=${limit}`);
    }

    /**
     * تحديد إشعار كمقروء
     */
    async markNotificationRead(notificationId) {
        return await this.makeRequest(`/notifications/${notificationId}/read`, {
            method: 'PUT'
        });
    }

    /**
     * جلب إعدادات النظام
     */
    async getSystemSettings() {
        return await this.makeRequest('/settings');
    }

    /**
     * تحديث إعدادات النظام
     */
    async updateSystemSettings(settings) {
        return await this.makeRequest('/settings', {
            method: 'PUT',
            body: settings
        });
    }

    /**
     * فحص صحة الخادم
     */
    async healthCheck() {
        return await this.makeRequest('/health');
    }

    /**
     * رفع ملف
     */
    async uploadFile(file, type = 'general') {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type);

        return await this.makeRequest('/upload', {
            method: 'POST',
            body: formData,
            headers: {
                // إزالة Content-Type للسماح للمتصفح بتعيينه تلقائياً مع boundary
                'Accept': 'application/json'
            }
        });
    }

    /**
     * تحديث معلومات المستخدم
     */
    async updateProfile(userData) {
        return await this.makeRequest('/auth/profile', {
            method: 'PUT',
            body: userData
        });
    }

    /**
     * تغيير كلمة المرور
     */
    async changePassword(oldPassword, newPassword) {
        return await this.makeRequest('/auth/change-password', {
            method: 'PUT',
            body: {
                old_password: oldPassword,
                new_password: newPassword
            }
        });
    }

    /**
     * البحث في النظام
     */
    async search(query, type = 'all') {
        return await this.makeRequest(`/search?q=${encodeURIComponent(query)}&type=${type}`);
    }

    /**
     * جلب احصائيات متقدمة
     */
    async getAdvancedStats(period = '30d') {
        return await this.makeRequest(`/analytics/stats?period=${period}`);
    }

    /**
     * تسجيل نشاط
     */
    async logActivity(activity) {
        return await this.makeRequest('/activities/log', {
            method: 'POST',
            body: activity
        });
    }

    /**
     * مزامنة البيانات المحلية
     */
    async syncOfflineData(data) {
        return await this.makeRequest('/sync', {
            method: 'POST',
            body: data
        });
    }
}

// إنشاء مثيل عام من API
const marinaAPI = new MarinaAPI();

/**
 * دالة مساعدة لإجراء طلبات API
 */
async function makeAPICall(endpoint, options = {}) {
    try {
        return await marinaAPI.makeRequest(endpoint, options);
    } catch (error) {
        console.error('خطأ في طلب API:', error);
        
        // معالجة أخطاء محددة
        if (error.message.includes('401')) {
            // انتهت صلاحية الجلسة
            currentUser = null;
            removeFromLocalStorage('user_session');
            showToast('انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى', 'warning');
            showPage('login');
        } else if (error.message.includes('Network')) {
            // مشكلة في الشبكة
            showToast('مشكلة في الاتصال بالشبكة', 'error');
        } else {
            // خطأ عام
            showToast('حدث خطأ في الاتصال بالخادم', 'error');
        }
        
        throw error;
    }
}

/**
 * فحص حالة الاتصال بالخادم
 */
async function checkServerConnection() {
    try {
        await marinaAPI.healthCheck();
        return true;
    } catch (error) {
        return false;
    }
}

/**
 * تحديث إعدادات الخادم
 */
function updateServerSettings(url, port) {
    const fullURL = port ? `${url}:${port}` : url;
    marinaAPI.updateServerURL(fullURL);
}

// تصدير للاستخدام العام
window.marinaAPI = marinaAPI;
window.makeAPICall = makeAPICall;
window.checkServerConnection = checkServerConnection;
window.updateServerSettings = updateServerSettings;