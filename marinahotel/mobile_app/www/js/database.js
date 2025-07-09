/**
 * قاعدة البيانات المحلية لتطبيق فندق مارينا
 * تستخدم IndexedDB و localStorage للعمل بدون اتصال
 */

class MarinaLocalDB {
    constructor() {
        this.dbName = 'MarinaHotelDB';
        this.dbVersion = 1;
        this.db = null;
        this.stores = {
            bookings: 'bookings',
            rooms: 'rooms',
            payments: 'payments',
            guests: 'guests',
            activities: 'activities',
            notifications: 'notifications',
            settings: 'settings',
            whatsapp_queue: 'whatsapp_queue',
            sync_queue: 'sync_queue'
        };
    }

    /**
     * تهيئة قاعدة البيانات
     */
    async initialize() {
        try {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(this.dbName, this.dbVersion);

                request.onerror = () => {
                    console.error('خطأ في فتح قاعدة البيانات:', request.error);
                    reject(request.error);
                };

                request.onsuccess = () => {
                    this.db = request.result;
                    console.log('تم تهيئة قاعدة البيانات المحلية بنجاح');
                    resolve(this.db);
                };

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    this.createTables(db);
                };
            });
        } catch (error) {
            console.error('خطأ في تهيئة قاعدة البيانات:', error);
            throw error;
        }
    }

    /**
     * إنشاء الجداول
     */
    createTables(db) {
        // جدول الحجوزات
        if (!db.objectStoreNames.contains(this.stores.bookings)) {
            const bookingsStore = db.createObjectStore(this.stores.bookings, { keyPath: 'booking_id', autoIncrement: true });
            bookingsStore.createIndex('guest_name', 'guest_name', { unique: false });
            bookingsStore.createIndex('guest_phone', 'guest_phone', { unique: false });
            bookingsStore.createIndex('room_number', 'room_number', { unique: false });
            bookingsStore.createIndex('status', 'status', { unique: false });
            bookingsStore.createIndex('checkin_date', 'checkin_date', { unique: false });
        }

        // جدول الغرف
        if (!db.objectStoreNames.contains(this.stores.rooms)) {
            const roomsStore = db.createObjectStore(this.stores.rooms, { keyPath: 'room_number' });
            roomsStore.createIndex('type', 'type', { unique: false });
            roomsStore.createIndex('status', 'status', { unique: false });
            roomsStore.createIndex('floor', 'floor', { unique: false });
        }

        // جدول المدفوعات
        if (!db.objectStoreNames.contains(this.stores.payments)) {
            const paymentsStore = db.createObjectStore(this.stores.payments, { keyPath: 'payment_id', autoIncrement: true });
            paymentsStore.createIndex('booking_id', 'booking_id', { unique: false });
            paymentsStore.createIndex('payment_date', 'payment_date', { unique: false });
            paymentsStore.createIndex('method', 'method', { unique: false });
        }

        // جدول النزلاء
        if (!db.objectStoreNames.contains(this.stores.guests)) {
            const guestsStore = db.createObjectStore(this.stores.guests, { keyPath: 'guest_id', autoIncrement: true });
            guestsStore.createIndex('phone', 'phone', { unique: true });
            guestsStore.createIndex('name', 'name', { unique: false });
        }

        // جدول الأنشطة
        if (!db.objectStoreNames.contains(this.stores.activities)) {
            const activitiesStore = db.createObjectStore(this.stores.activities, { keyPath: 'activity_id', autoIncrement: true });
            activitiesStore.createIndex('type', 'type', { unique: false });
            activitiesStore.createIndex('timestamp', 'timestamp', { unique: false });
        }

        // جدول الإشعارات
        if (!db.objectStoreNames.contains(this.stores.notifications)) {
            const notificationsStore = db.createObjectStore(this.stores.notifications, { keyPath: 'notification_id', autoIncrement: true });
            notificationsStore.createIndex('type', 'type', { unique: false });
            notificationsStore.createIndex('read', 'read', { unique: false });
            notificationsStore.createIndex('created_at', 'created_at', { unique: false });
        }

        // جدول الإعدادات
        if (!db.objectStoreNames.contains(this.stores.settings)) {
            const settingsStore = db.createObjectStore(this.stores.settings, { keyPath: 'key' });
        }

        // جدول طابور الواتساب
        if (!db.objectStoreNames.contains(this.stores.whatsapp_queue)) {
            const whatsappStore = db.createObjectStore(this.stores.whatsapp_queue, { keyPath: 'queue_id', autoIncrement: true });
            whatsappStore.createIndex('status', 'status', { unique: false });
            whatsappStore.createIndex('phone', 'phone', { unique: false });
            whatsappStore.createIndex('created_at', 'created_at', { unique: false });
        }

        // جدول طابور المزامنة
        if (!db.objectStoreNames.contains(this.stores.sync_queue)) {
            const syncStore = db.createObjectStore(this.stores.sync_queue, { keyPath: 'sync_id', autoIncrement: true });
            syncStore.createIndex('action', 'action', { unique: false });
            syncStore.createIndex('status', 'status', { unique: false });
            syncStore.createIndex('created_at', 'created_at', { unique: false });
        }

        console.log('تم إنشاء جداول قاعدة البيانات');
    }

    /**
     * إدراج بيانات في جدول
     */
    async insert(storeName, data) {
        try {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([storeName], 'readwrite');
                const store = transaction.objectStore(storeName);
                const request = store.add(data);

                request.onsuccess = () => {
                    resolve(request.result);
                };

                request.onerror = () => {
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error(`خطأ في إدراج البيانات في ${storeName}:`, error);
            throw error;
        }
    }

    /**
     * تحديث بيانات في جدول
     */
    async update(storeName, data) {
        try {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([storeName], 'readwrite');
                const store = transaction.objectStore(storeName);
                const request = store.put(data);

                request.onsuccess = () => {
                    resolve(request.result);
                };

                request.onerror = () => {
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error(`خطأ في تحديث البيانات في ${storeName}:`, error);
            throw error;
        }
    }

    /**
     * جلب جميع البيانات من جدول
     */
    async getAll(storeName) {
        try {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([storeName], 'readonly');
                const store = transaction.objectStore(storeName);
                const request = store.getAll();

                request.onsuccess = () => {
                    resolve(request.result);
                };

                request.onerror = () => {
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error(`خطأ في جلب البيانات من ${storeName}:`, error);
            throw error;
        }
    }

    /**
     * جلب بيانات محددة بالمفتاح
     */
    async get(storeName, key) {
        try {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([storeName], 'readonly');
                const store = transaction.objectStore(storeName);
                const request = store.get(key);

                request.onsuccess = () => {
                    resolve(request.result);
                };

                request.onerror = () => {
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error(`خطأ في جلب البيانات من ${storeName}:`, error);
            throw error;
        }
    }

    /**
     * حذف بيانات من جدول
     */
    async delete(storeName, key) {
        try {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([storeName], 'readwrite');
                const store = transaction.objectStore(storeName);
                const request = store.delete(key);

                request.onsuccess = () => {
                    resolve(request.result);
                };

                request.onerror = () => {
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error(`خطأ في حذف البيانات من ${storeName}:`, error);
            throw error;
        }
    }

    /**
     * البحث في جدول بفهرس
     */
    async getByIndex(storeName, indexName, value) {
        try {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([storeName], 'readonly');
                const store = transaction.objectStore(storeName);
                const index = store.index(indexName);
                const request = index.getAll(value);

                request.onsuccess = () => {
                    resolve(request.result);
                };

                request.onerror = () => {
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error(`خطأ في البحث في ${storeName}:`, error);
            throw error;
        }
    }

    /**
     * مسح جدول كامل
     */
    async clear(storeName) {
        try {
            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([storeName], 'readwrite');
                const store = transaction.objectStore(storeName);
                const request = store.clear();

                request.onsuccess = () => {
                    resolve(request.result);
                };

                request.onerror = () => {
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error(`خطأ في مسح ${storeName}:`, error);
            throw error;
        }
    }

    // =====================================
    // دوال مخصصة للكيانات
    // =====================================

    /**
     * حفظ حجز محلياً
     */
    async saveBooking(booking) {
        booking.synced = false;
        booking.created_at = new Date().toISOString();
        return await this.insert(this.stores.bookings, booking);
    }

    /**
     * جلب جميع الحجوزات
     */
    async getBookings() {
        return await this.getAll(this.stores.bookings);
    }

    /**
     * تحديث حجز
     */
    async updateBooking(booking) {
        booking.updated_at = new Date().toISOString();
        booking.synced = false;
        return await this.update(this.stores.bookings, booking);
    }

    /**
     * البحث عن حجوزات بالنزيل
     */
    async getBookingsByGuest(guestName) {
        return await this.getByIndex(this.stores.bookings, 'guest_name', guestName);
    }

    /**
     * البحث عن حجوزات بالغرفة
     */
    async getBookingsByRoom(roomNumber) {
        return await this.getByIndex(this.stores.bookings, 'room_number', roomNumber);
    }

    /**
     * حفظ غرفة محلياً
     */
    async saveRoom(room) {
        room.synced = false;
        room.updated_at = new Date().toISOString();
        return await this.update(this.stores.rooms, room);
    }

    /**
     * جلب جميع الغرف
     */
    async getRooms() {
        return await this.getAll(this.stores.rooms);
    }

    /**
     * جلب الغرف المتاحة
     */
    async getAvailableRooms() {
        return await this.getByIndex(this.stores.rooms, 'status', 'شاغرة');
    }

    /**
     * حفظ دفعة محلياً
     */
    async savePayment(payment) {
        payment.synced = false;
        payment.created_at = new Date().toISOString();
        return await this.insert(this.stores.payments, payment);
    }

    /**
     * جلب مدفوعات حجز
     */
    async getPaymentsByBooking(bookingId) {
        return await this.getByIndex(this.stores.payments, 'booking_id', bookingId);
    }

    /**
     * حفظ نزيل
     */
    async saveGuest(guest) {
        guest.created_at = new Date().toISOString();
        return await this.insert(this.stores.guests, guest);
    }

    /**
     * البحث عن نزيل بالهاتف
     */
    async getGuestByPhone(phone) {
        const guests = await this.getByIndex(this.stores.guests, 'phone', phone);
        return guests.length > 0 ? guests[0] : null;
    }

    /**
     * تسجيل نشاط
     */
    async logActivity(activity) {
        activity.timestamp = new Date().toISOString();
        activity.synced = false;
        return await this.insert(this.stores.activities, activity);
    }

    /**
     * جلب الأنشطة الأخيرة
     */
    async getRecentActivities(limit = 10) {
        const activities = await this.getAll(this.stores.activities);
        return activities
            .sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
            .slice(0, limit);
    }

    /**
     * حفظ إشعار
     */
    async saveNotification(notification) {
        notification.created_at = new Date().toISOString();
        notification.read = false;
        return await this.insert(this.stores.notifications, notification);
    }

    /**
     * جلب الإشعارات غير المقروءة
     */
    async getUnreadNotifications() {
        return await this.getByIndex(this.stores.notifications, 'read', false);
    }

    /**
     * تحديد إشعار كمقروء
     */
    async markNotificationRead(notificationId) {
        const notification = await this.get(this.stores.notifications, notificationId);
        if (notification) {
            notification.read = true;
            return await this.update(this.stores.notifications, notification);
        }
    }

    /**
     * حفظ إعداد
     */
    async saveSetting(key, value) {
        return await this.update(this.stores.settings, { key, value, updated_at: new Date().toISOString() });
    }

    /**
     * جلب إعداد
     */
    async getSetting(key) {
        const setting = await this.get(this.stores.settings, key);
        return setting ? setting.value : null;
    }

    /**
     * إضافة رسالة واتساب للطابور
     */
    async queueWhatsAppMessage(phone, message, bookingId = null) {
        const queueItem = {
            phone,
            message,
            booking_id: bookingId,
            status: 'pending',
            attempts: 0,
            created_at: new Date().toISOString()
        };
        return await this.insert(this.stores.whatsapp_queue, queueItem);
    }

    /**
     * جلب رسائل الواتساب المعلقة
     */
    async getPendingWhatsAppMessages() {
        return await this.getByIndex(this.stores.whatsapp_queue, 'status', 'pending');
    }

    /**
     * تحديث حالة رسالة واتساب
     */
    async updateWhatsAppMessageStatus(messageId, status, error = null) {
        const message = await this.get(this.stores.whatsapp_queue, messageId);
        if (message) {
            message.status = status;
            message.updated_at = new Date().toISOString();
            if (error) message.error = error;
            if (status === 'failed') message.attempts += 1;
            return await this.update(this.stores.whatsapp_queue, message);
        }
    }

    /**
     * إضافة عنصر للمزامنة
     */
    async addToSyncQueue(action, tableName, data, recordId = null) {
        const syncItem = {
            action, // 'create', 'update', 'delete'
            table_name: tableName,
            data,
            record_id: recordId,
            status: 'pending',
            attempts: 0,
            created_at: new Date().toISOString()
        };
        return await this.insert(this.stores.sync_queue, syncItem);
    }

    /**
     * جلب عناصر المزامنة المعلقة
     */
    async getPendingSyncItems() {
        return await this.getByIndex(this.stores.sync_queue, 'status', 'pending');
    }

    /**
     * تحديث حالة عنصر المزامنة
     */
    async updateSyncItemStatus(syncId, status, error = null) {
        const item = await this.get(this.stores.sync_queue, syncId);
        if (item) {
            item.status = status;
            item.updated_at = new Date().toISOString();
            if (error) item.error = error;
            if (status === 'failed') item.attempts += 1;
            return await this.update(this.stores.sync_queue, item);
        }
    }

    /**
     * مسح البيانات المزامنة المكتملة
     */
    async clearCompletedSyncItems() {
        const allItems = await this.getAll(this.stores.sync_queue);
        const completedItems = allItems.filter(item => item.status === 'completed');
        
        for (const item of completedItems) {
            await this.delete(this.stores.sync_queue, item.sync_id);
        }
    }

    /**
     * جلب إحصائيات قاعدة البيانات
     */
    async getStats() {
        const stats = {};
        
        for (const storeName of Object.values(this.stores)) {
            const data = await this.getAll(storeName);
            stats[storeName] = data.length;
        }
        
        return stats;
    }

    /**
     * تصدير البيانات
     */
    async exportData() {
        const exportData = {};
        
        for (const storeName of Object.values(this.stores)) {
            exportData[storeName] = await this.getAll(storeName);
        }
        
        return exportData;
    }

    /**
     * استيراد البيانات
     */
    async importData(data) {
        for (const [storeName, records] of Object.entries(data)) {
            if (this.stores[storeName]) {
                await this.clear(storeName);
                for (const record of records) {
                    await this.insert(storeName, record);
                }
            }
        }
    }

    /**
     * نسخ احتياطي للبيانات المهمة
     */
    async createBackup() {
        const backupData = {
            timestamp: new Date().toISOString(),
            version: this.dbVersion,
            data: await this.exportData()
        };
        
        // حفظ في localStorage كنسخة احتياطية
        localStorage.setItem('marina_backup', JSON.stringify(backupData));
        
        return backupData;
    }

    /**
     * استعادة من النسخة الاحتياطية
     */
    async restoreFromBackup() {
        const backupString = localStorage.getItem('marina_backup');
        if (!backupString) {
            throw new Error('لا توجد نسخة احتياطية متاحة');
        }
        
        const backup = JSON.parse(backupString);
        await this.importData(backup.data);
        
        return backup;
    }
}

// إنشاء مثيل عام من قاعدة البيانات
const marinaDB = new MarinaLocalDB();

// تهيئة قاعدة البيانات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await marinaDB.initialize();
        console.log('تم تهيئة قاعدة البيانات المحلية');
    } catch (error) {
        console.error('خطأ في تهيئة قاعدة البيانات:', error);
    }
});

// تصدير للاستخدام العام
window.marinaDB = marinaDB;