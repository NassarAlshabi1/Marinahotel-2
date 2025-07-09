<?php
session_start();
include_once '../includes/db.php';

// التحقق من اتصال قاعدة البيانات
if (!$conn) {
    die("<div class='alert alert-danger text-center'>فشل الاتصال بقاعدة البيانات: " . mysqli_connect_error() . "</div>");
}

// جلب بيانات الغرف
$sql = "SELECT room_number, status FROM rooms ORDER BY room_number";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("<div class='alert alert-danger text-center'>خطأ في الاستعلام: " . mysqli_error($conn) . "</div>");
}

$rooms = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = $row;
    }
}

// جلب إحصائيات سريعة
$stats = [
    'total_rooms' => 0,
    'occupied_rooms' => 0,
    'available_rooms' => 0,
    'today_bookings' => 0,
    'today_payments' => 0
];

// إحصائيات الغرف
$stats['total_rooms'] = count($rooms);
foreach ($rooms as $room) {
    if ($room['status'] == 'محجوزة') {
        $stats['occupied_rooms']++;
    } else {
        $stats['available_rooms']++;
    }
}

// حجوزات اليوم
$today_bookings_query = "SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = CURDATE()";
$today_bookings_result = mysqli_query($conn, $today_bookings_query);
if ($today_bookings_result) {
    $stats['today_bookings'] = mysqli_fetch_assoc($today_bookings_result)['count'];
}

// مدفوعات اليوم
$today_payments_query = "SELECT COALESCE(SUM(amount), 0) as total FROM payment WHERE DATE(payment_date) = CURDATE()";
$today_payments_result = mysqli_query($conn, $today_payments_query);
if ($today_payments_result) {
    $stats['today_payments'] = mysqli_fetch_assoc($today_payments_result)['total'];
}

// جلب التنبيهات النشطة إذا كان الجدول موجوداً
$active_alerts = [];
$check_notes_table = mysqli_query($conn, "SHOW TABLES LIKE 'booking_notes'");
if (mysqli_num_rows($check_notes_table) > 0) {
    $alerts_query = "
        SELECT
            bn.note_id,
            bn.booking_id,
            bn.note_text,
            bn.alert_type,
            bn.created_at,
            b.guest_name,
            b.room_number
        FROM booking_notes bn
        JOIN bookings b ON bn.booking_id = b.booking_id
        WHERE bn.is_active = 1
        AND (bn.alert_until IS NULL OR bn.alert_until > NOW())
        AND b.status != 'غادر' AND b.actual_checkout IS NULL
        ORDER BY bn.alert_type = 'high' DESC, bn.alert_type = 'medium' DESC, bn.created_at DESC
        LIMIT 10
    ";
    $alerts_result = mysqli_query($conn, $alerts_query);
    if ($alerts_result && mysqli_num_rows($alerts_result) > 0) {
        while ($alert = mysqli_fetch_assoc($alerts_result)) {
            $active_alerts[] = $alert;
        }
    }
}

mysqli_close($conn);

// تضمين الهيدر
include '../includes/header.php';
?>

<style>
    /* أنماط خاصة بلوحة التحكم */
    .dashboard-container {
        padding: 20px 0;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border-left: 4px solid;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .stat-card.rooms-total {
        border-left-color: #3498db;
    }

    .stat-card.rooms-occupied {
        border-left-color: #e74c3c;
    }

    .stat-card.rooms-available {
        border-left-color: #2ecc71;
    }

    .stat-card.bookings-today {
        border-left-color: #f39c12;
    }

    .stat-card.payments-today {
        border-left-color: #9b59b6;
    }

    .stat-card .icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        opacity: 0.8;
    }

    .stat-card.rooms-total .icon { color: #3498db; }
    .stat-card.rooms-occupied .icon { color: #e74c3c; }
    .stat-card.rooms-available .icon { color: #2ecc71; }
    .stat-card.bookings-today .icon { color: #f39c12; }
    .stat-card.payments-today .icon { color: #9b59b6; }

    .stat-card .value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: #2c3e50;
    }

    .stat-card .label {
        font-size: 1rem;
        font-weight: 500;
        color: #6c757d;
    }

    /* أنماط الطوابق والغرف */
    .floor-container {
        margin-bottom: 25px;
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .floor-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .floor-rooms {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 15px;
    }

    .room-btn {
        background: white;
        border: 2px solid;
        border-radius: 10px;
        padding: 15px 10px;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        text-align: center;
        min-height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .room-btn.available-btn {
        border-color: #2ecc71;
        color: #2ecc71;
        background: linear-gradient(135deg, #fff 0%, #f0fff4 100%);
    }

    .room-btn.available-btn:hover {
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(46, 204, 113, 0.3);
    }

    .room-btn.occupied-btn {
        border-color: #e74c3c;
        color: #e74c3c;
        background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
        cursor: not-allowed;
    }

    .room-btn.occupied-btn:hover {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
    }

    /* أنماط التنبيهات */
    .alerts-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .section-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .alerts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
    }

    .alert-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        border-left: 4px solid;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .alert-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }

    .alert-card.alert-high { border-left-color: #dc3545; }
    .alert-card.alert-medium { border-left-color: #fd7e14; }
    .alert-card.alert-low { border-left-color: #198754; }

    .alert-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .alert-info strong {
        color: #2c3e50;
        font-size: 1rem;
    }

    .guest-name {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .alert-priority {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .alert-priority.alert-high { color: #dc3545; }
    .alert-priority.alert-medium { color: #fd7e14; }
    .alert-priority.alert-low { color: #198754; }

    .alert-content {
        color: #495057;
        line-height: 1.5;
        margin-bottom: 10px;
    }

    .alert-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #eee;
        padding-top: 10px;
        font-size: 0.85rem;
    }

    /* أنماط responsive */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .floor-rooms {
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 10px;
        }
        
        .room-btn {
            padding: 10px 5px;
            min-height: 50px;
            font-size: 0.9rem;
        }
        
        .alerts-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-container">
    <div class="container">
        <!-- عنوان لوحة التحكم -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="text-center">
                    <h1 class="display-5 fw-bold text-primary mb-2">
                        <i class="fas fa-tachometer-alt me-3"></i>لوحة التحكم الرئيسية
                    </h1>
                    <p class="lead text-muted">مرحباً بك في نظام إدارة فندق مارينا</p>
                </div>
            </div>
        </div>

        <!-- الإحصائيات السريعة -->
        <div class="stats-grid">
            <div class="stat-card rooms-total">
                <div class="icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="value"><?= $stats['total_rooms'] ?></div>
                <div class="label">إجمالي الغرف</div>
            </div>
            
            <div class="stat-card rooms-occupied">
                <div class="icon">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="value"><?= $stats['occupied_rooms'] ?></div>
                <div class="label">غرف محجوزة</div>
            </div>
            
            <div class="stat-card rooms-available">
                <div class="icon">
                    <i class="fas fa-door-closed"></i>
                </div>
                <div class="value"><?= $stats['available_rooms'] ?></div>
                <div class="label">غرف متاحة</div>
            </div>
            
            <div class="stat-card bookings-today">
                <div class="icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="value"><?= $stats['today_bookings'] ?></div>
                <div class="label">حجوزات اليوم</div>
            </div>
            
            <div class="stat-card payments-today">
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="value"><?= number_format($stats['today_payments'], 0) ?></div>
                <div class="label">مدفوعات اليوم (ريال)</div>
            </div>
        </div>

        <!-- التنبيهات النشطة -->
        <?php if (!empty($active_alerts)): ?>
        <div class="alerts-section">
            <h3 class="section-title">
                <i class="fas fa-bell text-warning"></i> التنبيهات النشطة
                <span class="badge bg-danger ms-2"><?= count($active_alerts) ?></span>
            </h3>
            <div class="alerts-grid">
                <?php foreach ($active_alerts as $alert): ?>
                <div class="alert-card alert-<?= $alert['alert_type'] ?>">
                    <div class="alert-header">
                        <div class="alert-info">
                            <strong>غرفة <?= htmlspecialchars($alert['room_number']) ?></strong>
                            <div class="guest-name"><?= htmlspecialchars($alert['guest_name']) ?></div>
                        </div>
                        <div class="alert-priority alert-<?= $alert['alert_type'] ?>">
                            <?php
                            $priority_icons = [
                                'high' => 'fas fa-exclamation-triangle',
                                'medium' => 'fas fa-exclamation-circle',
                                'low' => 'fas fa-info-circle'
                            ];
                            $priority_labels = [
                                'high' => 'عالي',
                                'medium' => 'متوسط',
                                'low' => 'منخفض'
                            ];
                            ?>
                            <i class="<?= $priority_icons[$alert['alert_type']] ?>"></i>
                            <span><?= $priority_labels[$alert['alert_type']] ?></span>
                        </div>
                    </div>
                    <div class="alert-content">
                        <?= htmlspecialchars($alert['note_text']) ?>
                    </div>
                    <div class="alert-footer">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i>
                            <?= date('Y-m-d H:i', strtotime($alert['created_at'])) ?>
                        </small>
                        <a href="bookings/add_note.php?booking_id=<?= $alert['booking_id'] ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> إدارة
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- عرض الغرف حسب الطوابق -->
        <div class="floor-container">
            <h3 class="section-title">
                <i class="fas fa-building"></i> حالة الغرف حسب الطوابق
            </h3>
            <div id="rooms-container">
                <!-- سيتم إنشاء هذا القسم ديناميكياً بواسطة JavaScript -->
            </div>
        </div>

        <!-- أزرار التنقل السريع -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="text-center">
                    <h4 class="mb-3">التنقل السريع</h4>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="bookings/add2.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus-circle me-2"></i>حجز جديد
                        </a>
                        <a href="bookings/list.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-list me-2"></i>قائمة الحجوزات
                        </a>
                        <a href="reports.php" class="btn btn-info btn-lg">
                            <i class="fas fa-chart-bar me-2"></i>التقارير
                        </a>
                        <a href="whatsapp_manager.php" class="btn btn-warning btn-lg">
                            <i class="fab fa-whatsapp me-2"></i>إدارة الواتساب
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// تحويل بيانات الغرف من PHP إلى JavaScript
const roomsData = <?php echo json_encode($rooms); ?>;

// تنظيم الغرف حسب الطوابق
function organizeRoomsByFloor(rooms) {
    const floors = {};
    
    rooms.forEach(room => {
        // استخراج رقم الطابق من رقم الغرفة (الرقم الأول)
        const floorNumber = room.room_number.charAt(0);
        
        if (!floors[floorNumber]) {
            floors[floorNumber] = [];
        }
        
        floors[floorNumber].push(room);
    });
    
    // ترتيب الغرف في كل طابق
    for (const floor in floors) {
        floors[floor].sort((a, b) => {
            return parseInt(a.room_number) - parseInt(b.room_number);
        });
    }
    
    return floors;
}

// عرض الغرف حسب الطوابق
function displayRoomsByFloor() {
    const roomsContainer = document.getElementById('rooms-container');
    roomsContainer.innerHTML = '';
    
    const floors = organizeRoomsByFloor(roomsData);
    
    // ترتيب الطوابق تصاعدياً
    const sortedFloors = Object.keys(floors).sort();
    
    sortedFloors.forEach(floor => {
        const floorContainer = document.createElement('div');
        floorContainer.className = 'mb-4';
        
        const floorTitle = document.createElement('div');
        floorTitle.className = 'floor-title';
        floorTitle.innerHTML = `<i class="fas fa-building me-2"></i> الطابق ${floor}`;
        
        const floorRooms = document.createElement('div');
        floorRooms.className = 'floor-rooms';
        
        floors[floor].forEach(room => {
            const roomButton = document.createElement('button');
            roomButton.className = `room-btn ${room.status === 'شاغرة' ? 'available-btn' : 'occupied-btn'}`;
            roomButton.setAttribute('data-status', room.status);
            roomButton.setAttribute('data-room-number', room.room_number);
            roomButton.onclick = function() {
                handleRoomClick(room.room_number, room.status);
            };
            roomButton.textContent = room.room_number;
            
            floorRooms.appendChild(roomButton);
        });
        
        floorContainer.appendChild(floorTitle);
        floorContainer.appendChild(floorRooms);
        roomsContainer.appendChild(floorContainer);
    });
}

// معالجة النقر على الغرفة
function handleRoomClick(roomNumber, status) {
    if (status === 'شاغرة') {
        // توجيه المستخدم إلى صفحة تسجيل الحجز
        window.location.href = `bookings/add2.php?room_number=${roomNumber}`;
    } else {
        Swal.fire({
            icon: 'info',
            title: 'غرفة محجوزة',
            text: 'هذه الغرفة محجوزة حالياً. هل تريد عرض تفاصيل الحجز؟',
            showCancelButton: true,
            confirmButtonText: 'عرض التفاصيل',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                // البحث عن الحجز وتوجيه المستخدم لصفحة التفاصيل
                window.location.href = `bookings/list.php?search=${roomNumber}`;
            }
        });
    }
}

// تهيئة العرض الأولي
document.addEventListener('DOMContentLoaded', () => {
    displayRoomsByFloor();
    
    // تحديث البيانات كل 5 دقائق
    setInterval(() => {
        location.reload();
    }, 300000);
});
</script>

<?php include '../includes/footer.php'; ?>
