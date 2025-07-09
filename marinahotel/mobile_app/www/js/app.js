/**
 * تطبيق فندق مارينا - الملف الرئيسي
 * نظام إدارة شامل للهواتف المحمولة
 */

// متغيرات عامة
let currentUser = null;
let serverUrl = localStorage.getItem('serverUrl') || 'http://localhost';
let isOnline = navigator.onLine;
let currentPage = 'login';

// إعدادات التطبيق
const appConfig = {
    version: '1.0.0',
    name: 'فندق مارينا',
    apiEndpoint: '/marinahotel/api',
    localStoragePrefix: 'marina_app_',
    offlineData: {
        bookings: [],
        rooms: [],
        stats: {}
    }
};

/**
 * تهيئة التطبيق
 */
function initializeApp() {
    console.log('تهيئة تطبيق فندق مارينا...');
    
    // إخفاء شاشة التحميل بعد فترة
    setTimeout(() => {
        hideLoadingScreen();
    }, 2000);
    
    // تهيئة المكونات
    initializeNetworkStatus();
    initializeEventListeners();
    loadUserSession();
    loadAppSettings();
    
    // تحقق من وجود بيانات محفوظة
    loadOfflineData();
    
    console.log('تم تهيئة التطبيق بنجاح');
}

/**
 * إخفاء شاشة التحميل
 */
function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loadingScreen');
    const app = document.getElementById('app');
    
    if (loadingScreen && app) {
        loadingScreen.style.opacity = '0';
        setTimeout(() => {
            loadingScreen.style.display = 'none';
            app.style.display = 'block';
            
            // عرض صفحة تسجيل الدخول أو الرئيسية
            if (currentUser) {
                showPage('dashboard');
            } else {
                showPage('login');
            }
        }, 500);
    }
}

/**
 * تهيئة مراقبة حالة الشبكة
 */
function initializeNetworkStatus() {
    const networkStatus = document.createElement('div');
    networkStatus.id = 'networkStatus';
    networkStatus.className = 'network-status';
    document.body.appendChild(networkStatus);
    
    // مراقبة تغيير حالة الشبكة
    window.addEventListener('online', handleOnlineStatus);
    window.addEventListener('offline', handleOfflineStatus);
    
    // تحديث الحالة الحالية
    updateNetworkStatus();
}

/**
 * تحديث حالة الشبكة
 */
function updateNetworkStatus() {
    const networkStatus = document.getElementById('networkStatus');
    if (!networkStatus) return;
    
    if (navigator.onLine) {
        networkStatus.textContent = 'متصل بالإنترنت';
        networkStatus.className = 'network-status online';
        isOnline = true;
        
        // مزامنة البيانات المحلية
        syncOfflineData();
    } else {
        networkStatus.textContent = 'غير متصل - العمل في وضع عدم الاتصال';
        networkStatus.className = 'network-status offline show';
        isOnline = false;
    }
    
    // إخفاء بعد 3 ثوان إذا كان متصل
    if (navigator.onLine) {
        setTimeout(() => {
            networkStatus.classList.remove('show');
        }, 3000);
    }
}

/**
 * التعامل مع الاتصال بالإنترنت
 */
function handleOnlineStatus() {
    updateNetworkStatus();
    showToast('تم الاتصال بالإنترنت', 'success');
    syncOfflineData();
}

/**
 * التعامل مع انقطاع الإنترنت
 */
function handleOfflineStatus() {
    updateNetworkStatus();
    showToast('انقطع الاتصال بالإنترنت - سيتم العمل محلياً', 'warning');
}

/**
 * تهيئة مستمعي الأحداث
 */
function initializeEventListeners() {
    // أزرار التنقل السفلي
    const bottomNavItems = document.querySelectorAll('.bottom-nav-item');
    bottomNavItems.forEach(item => {
        item.addEventListener('click', (e) => {
            const page = e.currentTarget.getAttribute('onclick');
            if (page) {
                eval(page);
            }
        });
    });
    
    // معالجة زر الرجوع في Android
    document.addEventListener('backbutton', handleBackButton, false);
    
    // معالجة إيقاف التطبيق
    document.addEventListener('pause', handleAppPause, false);
    
    // معالجة تشغيل التطبيق
    document.addEventListener('resume', handleAppResume, false);
}

/**
 * عرض صفحة معينة
 */
function showPage(pageName) {
    // إخفاء جميع الصفحات
    const pages = document.querySelectorAll('.page');
    pages.forEach(page => {
        page.classList.remove('active');
    });
    
    // عرض الصفحة المطلوبة
    const targetPage = document.getElementById(pageName + 'Page');
    if (targetPage) {
        targetPage.classList.add('active');
        currentPage = pageName;
        
        // تحديث شريط التنقل
        updateBottomNavigation(pageName);
        
        // تحميل بيانات الصفحة
        loadPageData(pageName);
    }
}

/**
 * تحديث شريط التنقل السفلي
 */
function updateBottomNavigation(activePage) {
    const navItems = document.querySelectorAll('.bottom-nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
        
        // تحديد العنصر النشط بناءً على onclick
        const onclick = item.getAttribute('onclick');
        if (onclick && onclick.includes(activePage)) {
            item.classList.add('active');
        }
    });
}

/**
 * تحميل بيانات الصفحة
 */
function loadPageData(pageName) {
    switch (pageName) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'bookings':
            loadBookingsData();
            break;
        case 'rooms':
            loadRoomsData();
            break;
        case 'reports':
            loadReportsData();
            break;
        case 'settings':
            loadSettingsData();
            break;
    }
}

/**
 * تحميل بيانات لوحة التحكم
 */
async function loadDashboardData() {
    showLoadingOverlay();
    
    try {
        let stats;
        
        if (isOnline) {
            // جلب البيانات من الخادم
            stats = await makeAPICall('/dashboard/stats');
            
            // حفظ البيانات محلياً
            saveToLocalStorage('dashboard_stats', stats);
        } else {
            // جلب البيانات المحلية
            stats = getFromLocalStorage('dashboard_stats') || {
                totalBookings: 0,
                availableRooms: 0,
                occupiedRooms: 0,
                todayRevenue: 0
            };
        }
        
        // تحديث الواجهة
        updateDashboardUI(stats);
        
        // تحميل الأنشطة الأخيرة
        await loadRecentActivities();
        
    } catch (error) {
        console.error('خطأ في تحميل بيانات لوحة التحكم:', error);
        showToast('خطأ في تحميل البيانات', 'error');
    } finally {
        hideLoadingOverlay();
    }
}

/**
 * تحديث واجهة لوحة التحكم
 */
function updateDashboardUI(stats) {
    document.getElementById('totalBookings').textContent = stats.totalBookings || 0;
    document.getElementById('availableRooms').textContent = stats.availableRooms || 0;
    document.getElementById('occupiedRooms').textContent = stats.occupiedRooms || 0;
    document.getElementById('todayRevenue').textContent = formatCurrency(stats.todayRevenue || 0);
}

/**
 * تحميل الأنشطة الأخيرة
 */
async function loadRecentActivities() {
    try {
        let activities;
        
        if (isOnline) {
            activities = await makeAPICall('/dashboard/activities');
            saveToLocalStorage('recent_activities', activities);
        } else {
            activities = getFromLocalStorage('recent_activities') || [];
        }
        
        updateRecentActivitiesUI(activities);
        
    } catch (error) {
        console.error('خطأ في تحميل الأنشطة:', error);
    }
}

/**
 * تحديث واجهة الأنشطة الأخيرة
 */
function updateRecentActivitiesUI(activities) {
    const container = document.getElementById('recentActivities');
    
    if (!activities || activities.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">لا توجد أنشطة حديثة</p>';
        return;
    }
    
    let html = '';
    activities.forEach(activity => {
        html += `
            <div class="activity-item">
                <div class="activity-icon ${activity.type}">
                    <i class="${activity.icon}"></i>
                </div>
                <div class="activity-content">
                    <h6 class="mb-1">${activity.title}</h6>
                    <p class="mb-0 text-muted small">${activity.description}</p>
                    <small class="text-muted">${formatTime(activity.timestamp)}</small>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

/**
 * معالجة تسجيل الدخول
 */
async function handleLogin(event) {
    event.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('rememberMe').checked;
    
    if (!username || !password) {
        showToast('يرجى إدخال اسم المستخدم وكلمة المرور', 'error');
        return false;
    }
    
    showLoadingOverlay();
    
    try {
        if (isOnline) {
            // تسجيل دخول عبر الخادم
            const response = await makeAPICall('/auth/login', {
                method: 'POST',
                body: {
                    username,
                    password
                }
            });
            
            if (response.success) {
                currentUser = response.user;
                
                // حفظ بيانات المستخدم
                if (rememberMe) {
                    saveToLocalStorage('user_session', currentUser);
                }
                
                // تحديث واجهة المستخدم
                document.getElementById('currentUser').textContent = currentUser.name;
                
                showToast('تم تسجيل الدخول بنجاح', 'success');
                showPage('dashboard');
            } else {
                showToast(response.message || 'خطأ في تسجيل الدخول', 'error');
            }
        } else {
            // تسجيل دخول محلي (للطوارئ)
            const savedUser = getFromLocalStorage('user_session');
            if (savedUser && savedUser.username === username) {
                currentUser = savedUser;
                document.getElementById('currentUser').textContent = currentUser.name;
                showToast('تم تسجيل الدخول محلياً', 'warning');
                showPage('dashboard');
            } else {
                showToast('لا يمكن تسجيل الدخول بدون اتصال إنترنت', 'error');
            }
        }
        
    } catch (error) {
        console.error('خطأ في تسجيل الدخول:', error);
        showToast('خطأ في الاتصال بالخادم', 'error');
    } finally {
        hideLoadingOverlay();
    }
    
    return false;
}

/**
 * تسجيل الخروج
 */
function logout() {
    currentUser = null;
    removeFromLocalStorage('user_session');
    showToast('تم تسجيل الخروج', 'info');
    showPage('login');
    
    // مسح النماذج
    document.getElementById('loginForm').reset();
}

/**
 * تحميل جلسة المستخدم المحفوظة
 */
function loadUserSession() {
    const savedUser = getFromLocalStorage('user_session');
    if (savedUser) {
        currentUser = savedUser;
        document.getElementById('currentUser').textContent = currentUser.name;
    }
}

/**
 * تحميل بيانات الحجوزات
 */
async function loadBookingsData() {
    showLoadingOverlay();
    
    try {
        let bookings;
        
        if (isOnline) {
            bookings = await makeAPICall('/bookings');
            saveToLocalStorage('bookings_data', bookings);
        } else {
            bookings = getFromLocalStorage('bookings_data') || [];
        }
        
        updateBookingsUI(bookings);
        
    } catch (error) {
        console.error('خطأ في تحميل الحجوزات:', error);
        showToast('خطأ في تحميل الحجوزات', 'error');
    } finally {
        hideLoadingOverlay();
    }
}

/**
 * تحديث واجهة الحجوزات
 */
function updateBookingsUI(bookings) {
    const container = document.getElementById('bookingsList');
    
    if (!bookings || bookings.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-calendar-times fa-4x mb-3"></i>
                <h5>لا توجد حجوزات</h5>
                <p>لم يتم العثور على أي حجوزات</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    bookings.forEach(booking => {
        const statusClass = getBookingStatusClass(booking.status);
        html += `
            <div class="booking-item">
                <div class="booking-header">
                    <h5 class="mb-0">${booking.guest_name}</h5>
                    <span class="booking-status ${statusClass}">${booking.status}</span>
                </div>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">رقم الغرفة</small>
                        <p class="mb-1">${booking.room_number}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">رقم الهاتف</small>
                        <p class="mb-1">${booking.guest_phone}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">تاريخ الوصول</small>
                        <p class="mb-1">${formatDate(booking.checkin_date)}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">تاريخ المغادرة</small>
                        <p class="mb-1">${booking.checkout_date ? formatDate(booking.checkout_date) : 'غير محدد'}</p>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editBooking(${booking.booking_id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info me-1" onclick="viewBookingDetails(${booking.booking_id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="processPayment(${booking.booking_id})">
                        <i class="fas fa-money-bill"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

/**
 * تحميل بيانات الغرف
 */
async function loadRoomsData() {
    showLoadingOverlay();
    
    try {
        let rooms;
        
        if (isOnline) {
            rooms = await makeAPICall('/rooms');
            saveToLocalStorage('rooms_data', rooms);
        } else {
            rooms = getFromLocalStorage('rooms_data') || [];
        }
        
        updateRoomsUI(rooms);
        
    } catch (error) {
        console.error('خطأ في تحميل الغرف:', error);
        showToast('خطأ في تحميل الغرف', 'error');
    } finally {
        hideLoadingOverlay();
    }
}

/**
 * تحديث واجهة الغرف
 */
function updateRoomsUI(rooms) {
    const container = document.getElementById('roomsGrid');
    
    if (!rooms || rooms.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-bed fa-4x mb-3"></i>
                <h5>لا توجد غرف</h5>
                <p>لم يتم العثور على أي غرف</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    rooms.forEach(room => {
        const statusClass = getRoomStatusClass(room.status);
        const statusIcon = getRoomStatusIcon(room.status);
        
        html += `
            <div class="card room-card ${statusClass}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title mb-0">غرفة ${room.room_number}</h5>
                        <i class="${statusIcon} fa-lg"></i>
                    </div>
                    <p class="text-muted mb-1">${room.type}</p>
                    <p class="mb-1"><strong>السعر:</strong> ${formatCurrency(room.price)}</p>
                    <p class="mb-3"><strong>الحالة:</strong> ${room.status}</p>
                    
                    <div class="d-grid gap-2">
                        ${room.status === 'شاغرة' ? 
                            `<button class="btn btn-primary btn-sm" onclick="bookRoom('${room.room_number}')">
                                <i class="fas fa-plus me-1"></i>حجز الغرفة
                            </button>` : 
                            `<button class="btn btn-info btn-sm" onclick="viewRoomDetails('${room.room_number}')">
                                <i class="fas fa-eye me-1"></i>عرض التفاصيل
                            </button>`
                        }
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

/**
 * إنشاء تقرير
 */
async function generateReport(reportType) {
    showLoadingOverlay();
    
    try {
        let reportData;
        
        if (isOnline) {
            reportData = await makeAPICall(`/reports/${reportType}`);
        } else {
            reportData = getFromLocalStorage(`report_${reportType}`) || {};
        }
        
        displayReport(reportType, reportData);
        
    } catch (error) {
        console.error('خطأ في إنشاء التقرير:', error);
        showToast('خطأ في إنشاء التقرير', 'error');
    } finally {
        hideLoadingOverlay();
    }
}

/**
 * عرض التقرير
 */
function displayReport(reportType, data) {
    const container = document.getElementById('reportContent');
    
    let html = '';
    
    switch (reportType) {
        case 'overview':
            html = generateOverviewReport(data);
            break;
        case 'bookings':
            html = generateBookingsReport(data);
            break;
        case 'financial':
            html = generateFinancialReport(data);
            break;
        case 'rooms':
            html = generateRoomsReport(data);
            break;
    }
    
    container.innerHTML = html;
}

/**
 * عرض نافذة الحجز الجديد
 */
function showNewBookingModal() {
    const modal = new bootstrap.Modal(document.getElementById('newBookingModal'));
    
    // تحميل الغرف المتاحة
    loadAvailableRooms();
    
    modal.show();
}

/**
 * تحميل الغرف المتاحة
 */
async function loadAvailableRooms() {
    try {
        let rooms;
        
        if (isOnline) {
            rooms = await makeAPICall('/rooms/available');
        } else {
            const allRooms = getFromLocalStorage('rooms_data') || [];
            rooms = allRooms.filter(room => room.status === 'شاغرة');
        }
        
        const select = document.querySelector('#newBookingModal select[name="room_number"]');
        select.innerHTML = '<option value="">اختر الغرفة</option>';
        
        rooms.forEach(room => {
            select.innerHTML += `<option value="${room.room_number}">غرفة ${room.room_number} - ${room.type} (${formatCurrency(room.price)})</option>`;
        });
        
    } catch (error) {
        console.error('خطأ في تحميل الغرف المتاحة:', error);
    }
}

/**
 * حفظ حجز جديد
 */
async function submitNewBooking() {
    const form = document.getElementById('newBookingForm');
    const formData = new FormData(form);
    
    const bookingData = {
        guest_name: formData.get('guest_name'),
        guest_phone: formData.get('guest_phone'),
        checkin_date: formData.get('checkin_date'),
        checkout_date: formData.get('checkout_date'),
        room_number: formData.get('room_number'),
        guest_count: formData.get('guest_count')
    };
    
    // التحقق من صحة البيانات
    if (!bookingData.guest_name || !bookingData.guest_phone || !bookingData.checkin_date || !bookingData.room_number) {
        showToast('يرجى ملء جميع الحقول المطلوبة', 'error');
        return;
    }
    
    showLoadingOverlay();
    
    try {
        if (isOnline) {
            const response = await makeAPICall('/bookings', {
                method: 'POST',
                body: bookingData
            });
            
            if (response.success) {
                showToast('تم إنشاء الحجز بنجاح', 'success');
                
                // إغلاق النافذة المنبثقة
                const modal = bootstrap.Modal.getInstance(document.getElementById('newBookingModal'));
                modal.hide();
                
                // إعادة تحميل الحجوزات
                loadBookingsData();
                loadDashboardData();
            } else {
                showToast(response.message || 'خطأ في إنشاء الحجز', 'error');
            }
        } else {
            // حفظ محلي للمزامنة لاحقاً
            const offlineBookings = getFromLocalStorage('offline_bookings') || [];
            bookingData.id = Date.now(); // معرف مؤقت
            bookingData.offline = true;
            offlineBookings.push(bookingData);
            saveToLocalStorage('offline_bookings', offlineBookings);
            
            showToast('تم حفظ الحجز محلياً - سيتم المزامنة عند الاتصال', 'warning');
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('newBookingModal'));
            modal.hide();
        }
        
    } catch (error) {
        console.error('خطأ في إنشاء الحجز:', error);
        showToast('خطأ في إنشاء الحجز', 'error');
    } finally {
        hideLoadingOverlay();
    }
}

/**
 * فلترة الحجوزات
 */
function filterBookings() {
    const searchTerm = document.getElementById('searchGuest').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    
    // إخفاء/إظهار عناصر الحجوزات بناءً على الفلاتر
    const bookingItems = document.querySelectorAll('.booking-item');
    
    bookingItems.forEach(item => {
        let shouldShow = true;
        
        // فلتر البحث بالاسم
        if (searchTerm) {
            const guestName = item.querySelector('h5').textContent.toLowerCase();
            shouldShow = shouldShow && guestName.includes(searchTerm);
        }
        
        // فلتر الحالة
        if (statusFilter) {
            const status = item.querySelector('.booking-status').textContent;
            shouldShow = shouldShow && status === statusFilter;
        }
        
        // عرض أو إخفاء العنصر
        item.style.display = shouldShow ? 'block' : 'none';
    });
}

/**
 * فلترة الغرف
 */
function filterRooms() {
    const statusFilter = document.getElementById('roomStatusFilter').value;
    const typeFilter = document.getElementById('roomTypeFilter').value;
    const searchTerm = document.getElementById('roomSearch').value.toLowerCase();
    
    const roomCards = document.querySelectorAll('.room-card');
    
    roomCards.forEach(card => {
        let shouldShow = true;
        
        // فلتر الحالة
        if (statusFilter) {
            const statusText = card.querySelector('p:last-of-type strong + text()');
            // منطق فلترة الحالة
        }
        
        // فلتر النوع
        if (typeFilter) {
            const typeText = card.querySelector('.text-muted').textContent;
            shouldShow = shouldShow && typeText.includes(typeFilter);
        }
        
        // فلتر البحث
        if (searchTerm) {
            const roomNumber = card.querySelector('.card-title').textContent.toLowerCase();
            shouldShow = shouldShow && roomNumber.includes(searchTerm);
        }
        
        card.style.display = shouldShow ? 'block' : 'none';
    });
}

/**
 * اختبار الاتصال بالخادم
 */
async function testConnection() {
    const serverUrl = document.getElementById('serverUrl').value;
    const serverPort = document.getElementById('serverPort').value;
    
    if (!serverUrl) {
        showToast('يرجى إدخال عنوان الخادم', 'error');
        return;
    }
    
    showLoadingOverlay();
    
    try {
        const url = `${serverUrl}${serverPort ? ':' + serverPort : ''}/marinahotel/api/health`;
        const response = await fetch(url, { timeout: 5000 });
        
        if (response.ok) {
            showToast('تم الاتصال بالخادم بنجاح', 'success');
            
            // حفظ إعدادات الخادم
            localStorage.setItem('serverUrl', `${serverUrl}${serverPort ? ':' + serverPort : ''}`);
            serverUrl = localStorage.getItem('serverUrl');
        } else {
            showToast('فشل الاتصال بالخادم', 'error');
        }
        
    } catch (error) {
        console.error('خطأ في اختبار الاتصال:', error);
        showToast('خطأ في الاتصال بالخادم', 'error');
    } finally {
        hideLoadingOverlay();
    }
}

/**
 * مزامنة البيانات المحلية مع الخادم
 */
async function syncOfflineData() {
    if (!isOnline) return;
    
    try {
        // مزامنة الحجوزات المحلية
        const offlineBookings = getFromLocalStorage('offline_bookings') || [];
        if (offlineBookings.length > 0) {
            for (const booking of offlineBookings) {
                await makeAPICall('/bookings', {
                    method: 'POST',
                    body: booking
                });
            }
            
            // مسح البيانات المحلية بعد المزامنة
            removeFromLocalStorage('offline_bookings');
            showToast('تم مزامنة البيانات المحلية', 'success');
        }
        
    } catch (error) {
        console.error('خطأ في مزامنة البيانات:', error);
    }
}

/**
 * تحميل إعدادات التطبيق
 */
function loadAppSettings() {
    const serverUrl = localStorage.getItem('serverUrl');
    if (serverUrl) {
        const urlInput = document.getElementById('serverUrl');
        if (urlInput) {
            const [url, port] = serverUrl.split(':');
            urlInput.value = url;
            if (port && document.getElementById('serverPort')) {
                document.getElementById('serverPort').value = port;
            }
        }
    }
    
    // تحميل إعدادات أخرى
    const language = localStorage.getItem('appLanguage') || 'ar';
    const theme = localStorage.getItem('appTheme') || 'light';
    const notifications = localStorage.getItem('notifications') === 'true';
    
    const languageSelect = document.getElementById('appLanguage');
    const themeSelect = document.getElementById('appTheme');
    const notificationsCheckbox = document.getElementById('notifications');
    
    if (languageSelect) languageSelect.value = language;
    if (themeSelect) themeSelect.value = theme;
    if (notificationsCheckbox) notificationsCheckbox.checked = notifications;
    
    // تطبيق السمة
    if (theme === 'dark') {
        document.body.classList.add('dark-theme');
    }
}

/**
 * تحميل بيانات التقارير
 */
function loadReportsData() {
    // لا حاجة لتحميل خاص - التقارير تحمل عند الطلب
}

/**
 * تحميل إعدادات الصفحة
 */
function loadSettingsData() {
    loadAppSettings();
}

// ==================================================
// دوال مساعدة
// ==================================================

/**
 * الحصول على فئة CSS لحالة الحجز
 */
function getBookingStatusClass(status) {
    switch (status) {
        case 'محجوزة':
            return 'status-active';
        case 'شاغرة':
            return 'status-completed';
        case 'ملغية':
            return 'status-cancelled';
        default:
            return 'status-completed';
    }
}

/**
 * الحصول على فئة CSS لحالة الغرفة
 */
function getRoomStatusClass(status) {
    switch (status) {
        case 'شاغرة':
            return 'room-available';
        case 'محجوزة':
            return 'room-occupied';
        case 'صيانة':
            return 'room-maintenance';
        default:
            return '';
    }
}

/**
 * الحصول على أيقونة حالة الغرفة
 */
function getRoomStatusIcon(status) {
    switch (status) {
        case 'شاغرة':
            return 'fas fa-door-open text-success';
        case 'محجوزة':
            return 'fas fa-door-closed text-danger';
        case 'صيانة':
            return 'fas fa-tools text-warning';
        default:
            return 'fas fa-door-open';
    }
}

/**
 * تنسيق العملة
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-YE', {
        style: 'currency',
        currency: 'YER',
        minimumFractionDigits: 0
    }).format(amount || 0);
}

/**
 * تنسيق التاريخ
 */
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-YE');
}

/**
 * تنسيق الوقت
 */
function formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleString('ar-YE');
}

/**
 * عرض رسالة منبثقة
 */
function showToast(message, type = 'info') {
    // إنشاء عنصر الرسالة
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.top = '80px';
    toast.style.right = '20px';
    toast.style.left = '20px';
    toast.style.zIndex = '1055';
    
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // إزالة تلقائية بعد 5 ثوان
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

/**
 * عرض شاشة التحميل
 */
function showLoadingOverlay() {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="text-center text-white">
                <div class="spinner-border text-light mb-3" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p>جاري التحميل...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

/**
 * إخفاء شاشة التحميل
 */
function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * حفظ البيانات محلياً
 */
function saveToLocalStorage(key, data) {
    try {
        localStorage.setItem(appConfig.localStoragePrefix + key, JSON.stringify(data));
    } catch (error) {
        console.error('خطأ في حفظ البيانات محلياً:', error);
    }
}

/**
 * جلب البيانات المحلية
 */
function getFromLocalStorage(key) {
    try {
        const data = localStorage.getItem(appConfig.localStoragePrefix + key);
        return data ? JSON.parse(data) : null;
    } catch (error) {
        console.error('خطأ في جلب البيانات المحلية:', error);
        return null;
    }
}

/**
 * إزالة البيانات المحلية
 */
function removeFromLocalStorage(key) {
    try {
        localStorage.removeItem(appConfig.localStoragePrefix + key);
    } catch (error) {
        console.error('خطأ في إزالة البيانات المحلية:', error);
    }
}

/**
 * تحميل البيانات المحلية
 */
function loadOfflineData() {
    appConfig.offlineData.bookings = getFromLocalStorage('bookings_data') || [];
    appConfig.offlineData.rooms = getFromLocalStorage('rooms_data') || [];
    appConfig.offlineData.stats = getFromLocalStorage('dashboard_stats') || {};
}

/**
 * معالجة زر الرجوع
 */
function handleBackButton() {
    if (currentPage === 'login') {
        // إغلاق التطبيق
        navigator.app.exitApp();
    } else {
        // العودة للرئيسية
        showPage('dashboard');
    }
}

/**
 * معالجة إيقاف التطبيق
 */
function handleAppPause() {
    // حفظ البيانات الحالية
    console.log('تم إيقاف التطبيق مؤقتاً');
}

/**
 * معالجة تشغيل التطبيق
 */
function handleAppResume() {
    // تحديث البيانات
    console.log('تم استئناف التطبيق');
    updateNetworkStatus();
    
    if (currentUser && currentPage === 'dashboard') {
        loadDashboardData();
    }
}