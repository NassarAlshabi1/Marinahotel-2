<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php?error=ليس لديك صلاحية للوصول إلى هذه الصفحة");
    exit();
}

// معالجة التواريخ
$today = date('Y-m-d');
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // بداية الشهر الحالي
$end_date = $_GET['end_date'] ?? $today;
$report_type = $_GET['report_type'] ?? 'overview';

// جلب البيانات حسب نوع التقرير
function getReportData($conn, $start_date, $end_date, $report_type) {
    $data = [];
    
    try {
        switch ($report_type) {
            case 'overview':
                // إحصائيات عامة
                $data = [
                    'bookings' => getBookingsStats($conn, $start_date, $end_date),
                    'revenue' => getRevenueStats($conn, $start_date, $end_date),
                    'expenses' => getExpensesStats($conn, $start_date, $end_date),
                    'rooms' => getRoomsStats($conn),
                    'employees' => getEmployeesStats($conn, $start_date, $end_date)
                ];
                break;
                
            case 'bookings':
                $data = getDetailedBookings($conn, $start_date, $end_date);
                break;
                
            case 'financial':
                $data = getFinancialReport($conn, $start_date, $end_date);
                break;
                
            case 'rooms':
                $data = getRoomsReport($conn, $start_date, $end_date);
                break;
                
            case 'employees':
                $data = getEmployeesReport($conn, $start_date, $end_date);
                break;
        }
    } catch (Exception $e) {
        error_log("خطأ في جلب بيانات التقرير: " . $e->getMessage());
        $data = [];
    }
    
    return $data;
}

// دالة إحصائيات الحجوزات
function getBookingsStats($conn, $start_date, $end_date) {
    $sql = "SELECT 
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'محجوزة' THEN 1 END) as active_bookings,
                COUNT(CASE WHEN status = 'شاغرة' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN DATE(checkin_date) = CURDATE() THEN 1 END) as today_checkins
            FROM bookings 
            WHERE checkin_date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// دالة إحصائيات الإيرادات
function getRevenueStats($conn, $start_date, $end_date) {
    $sql = "SELECT 
                COALESCE(SUM(amount), 0) as total_revenue,
                COUNT(*) as payment_count,
                COALESCE(AVG(amount), 0) as avg_payment
            FROM payment 
            WHERE DATE(payment_date) BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// دالة إحصائيات المصروفات
function getExpensesStats($conn, $start_date, $end_date) {
    $sql = "SELECT 
                COALESCE(SUM(amount), 0) as total_expenses,
                COUNT(*) as expense_count,
                expense_type,
                SUM(amount) as category_total
            FROM expenses 
            WHERE DATE(date) BETWEEN ? AND ?
            GROUP BY expense_type";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    $total = 0;
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $expenses['by_category'][] = $row;
        $total += $row['category_total'];
        $count++;
    }
    
    $expenses['total_expenses'] = $total;
    $expenses['expense_count'] = $count;
    
    return $expenses;
}

// دالة إحصائيات الغرف
function getRoomsStats($conn) {
    $sql = "SELECT 
                COUNT(*) as total_rooms,
                COUNT(CASE WHEN status = 'شاغرة' THEN 1 END) as available_rooms,
                COUNT(CASE WHEN status = 'محجوزة' THEN 1 END) as occupied_rooms,
                COUNT(CASE WHEN status = 'صيانة' THEN 1 END) as maintenance_rooms
            FROM rooms";
    
    return $conn->query($sql)->fetch_assoc();
}

// دالة إحصائيات الموظفين
function getEmployeesStats($conn, $start_date, $end_date) {
    $sql = "SELECT 
                COUNT(DISTINCT e.id) as total_employees,
                COALESCE(SUM(sw.amount), 0) as total_withdrawals
            FROM employees e
            LEFT JOIN salary_withdrawals sw ON e.id = sw.employee_id 
                AND DATE(sw.date) BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// دالة الحجوزات التفصيلية
function getDetailedBookings($conn, $start_date, $end_date) {
    $sql = "SELECT 
                b.*,
                COALESCE(SUM(p.amount), 0) as total_paid,
                (b.room_price * b.calculated_nights) as total_amount
            FROM bookings b
            LEFT JOIN payment p ON b.booking_id = p.booking_id
            WHERE b.checkin_date BETWEEN ? AND ?
            GROUP BY b.booking_id
            ORDER BY b.checkin_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

// دالة التقرير المالي
function getFinancialReport($conn, $start_date, $end_date) {
    // الإيرادات اليومية
    $revenue_sql = "SELECT 
                        DATE(payment_date) as date,
                        SUM(amount) as daily_revenue
                    FROM payment 
                    WHERE DATE(payment_date) BETWEEN ? AND ?
                    GROUP BY DATE(payment_date)
                    ORDER BY date";
    
    $stmt = $conn->prepare($revenue_sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $daily_revenue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // المصروفات اليومية
    $expenses_sql = "SELECT 
                        DATE(date) as date,
                        SUM(amount) as daily_expenses
                    FROM expenses 
                    WHERE DATE(date) BETWEEN ? AND ?
                    GROUP BY DATE(date)
                    ORDER BY date";
    
    $stmt = $conn->prepare($expenses_sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $daily_expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'daily_revenue' => $daily_revenue,
        'daily_expenses' => $daily_expenses
    ];
}

// جلب البيانات
$report_data = getReportData($conn, $start_date, $end_date, $report_type);

include_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- عنوان الصفحة -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">
                    <i class="fas fa-chart-bar me-2"></i>التقارير والإحصائيات
                </h2>
                <div class="btn-group">
                    <button type="button" class="btn btn-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i>تصدير Excel
                    </button>
                    <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf me-1"></i>تصدير PDF
                    </button>
                    <button type="button" class="btn btn-info" onclick="printReport()">
                        <i class="fas fa-print me-1"></i>طباعة
                    </button>
                </div>
            </div>

            <!-- فلاتر التقرير -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>فلاتر التقرير</h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="reportForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">نوع التقرير</label>
                                <select class="form-select" name="report_type" id="report_type">
                                    <option value="overview" <?= $report_type == 'overview' ? 'selected' : '' ?>>نظرة عامة</option>
                                    <option value="bookings" <?= $report_type == 'bookings' ? 'selected' : '' ?>>تقرير الحجوزات</option>
                                    <option value="financial" <?= $report_type == 'financial' ? 'selected' : '' ?>>التقرير المالي</option>
                                    <option value="rooms" <?= $report_type == 'rooms' ? 'selected' : '' ?>>تقرير الغرف</option>
                                    <option value="employees" <?= $report_type == 'employees' ? 'selected' : '' ?>>تقرير الموظفين</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">من تاريخ</label>
                                <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">إلى تاريخ</label>
                                <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>تطبيق الفلتر
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                    <i class="fas fa-undo me-1"></i>إعادة تعيين
                                </button>
                            </div>
                        </div>
                        
                        <!-- اختصارات التاريخ -->
                        <div class="mt-3">
                            <label class="form-label">اختصارات سريعة:</label>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('today')">اليوم</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('week')">هذا الأسبوع</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('month')">هذا الشهر</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('quarter')">هذا الربع</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('year')">هذا العام</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- محتوى التقرير -->
            <div id="reportContent">
                <?php if ($report_type === 'overview'): ?>
                    <!-- نظرة عامة -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= number_format($report_data['bookings']['total_bookings']) ?></h4>
                                            <p class="mb-0">إجمالي الحجوزات</p>
                                        </div>
                                        <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= number_format($report_data['revenue']['total_revenue']) ?> ريال</h4>
                                            <p class="mb-0">إجمالي الإيرادات</p>
                                        </div>
                                        <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?= number_format($report_data['expenses']['total_expenses'] ?? 0) ?> ريال</h4>
                                            <p class="mb-0">إجمالي المصروفات</p>
                                        </div>
                                        <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <?php $profit = $report_data['revenue']['total_revenue'] - ($report_data['expenses']['total_expenses'] ?? 0); ?>
                                            <h4><?= number_format($profit) ?> ريال</h4>
                                            <p class="mb-0">صافي الربح</p>
                                        </div>
                                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تفاصيل إضافية -->
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bed me-2"></i>حالة الغرف</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h3 class="text-success"><?= $report_data['rooms']['available_rooms'] ?></h3>
                                                <p class="mb-0">غرف متاحة</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h3 class="text-danger"><?= $report_data['rooms']['occupied_rooms'] ?></h3>
                                                <p class="mb-0">غرف مشغولة</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="progress mt-3">
                                        <?php $occupancy_rate = ($report_data['rooms']['total_rooms'] > 0) ? 
                                              ($report_data['rooms']['occupied_rooms'] / $report_data['rooms']['total_rooms']) * 100 : 0; ?>
                                        <div class="progress-bar bg-success" style="width: <?= $occupancy_rate ?>%">
                                            <?= number_format($occupancy_rate, 1) ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">معدل الإشغال</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>الموظفين</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h3 class="text-primary"><?= $report_data['employees']['total_employees'] ?></h3>
                                                <p class="mb-0">إجمالي الموظفين</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h3 class="text-warning"><?= number_format($report_data['employees']['total_withdrawals']) ?></h3>
                                                <p class="mb-0">إجمالي السحوبات</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($report_type === 'bookings'): ?>
                    <!-- تقرير الحجوزات -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>تقرير الحجوزات التفصيلي</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="bookingsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>رقم الحجز</th>
                                            <th>اسم النزيل</th>
                                            <th>رقم الغرفة</th>
                                            <th>تاريخ الوصول</th>
                                            <th>تاريخ المغادرة</th>
                                            <th>عدد الليالي</th>
                                            <th>المبلغ الإجمالي</th>
                                            <th>المبلغ المدفوع</th>
                                            <th>المتبقي</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $booking): ?>
                                        <tr>
                                            <td><?= $booking['booking_id'] ?></td>
                                            <td><?= htmlspecialchars($booking['guest_name']) ?></td>
                                            <td><?= $booking['room_number'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($booking['checkin_date'])) ?></td>
                                            <td><?= $booking['checkout_date'] ? date('d/m/Y', strtotime($booking['checkout_date'])) : '-' ?></td>
                                            <td><?= $booking['calculated_nights'] ?></td>
                                            <td><?= number_format($booking['total_amount']) ?> ريال</td>
                                            <td><?= number_format($booking['total_paid']) ?> ريال</td>
                                            <td><?= number_format($booking['total_amount'] - $booking['total_paid']) ?> ريال</td>
                                            <td>
                                                <span class="badge bg-<?= $booking['status'] == 'محجوزة' ? 'success' : 'secondary' ?>">
                                                    <?= $booking['status'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($report_type === 'financial'): ?>
                    <!-- التقرير المالي -->
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>الإيرادات اليومية</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>المصروفات اليومية</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="expensesChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- جدول مفصل -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-table me-2"></i>البيانات المالية التفصيلية</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>الإيرادات</th>
                                            <th>المصروفات</th>
                                            <th>صافي الربح</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // دمج البيانات حسب التاريخ
                                        $financial_summary = [];
                                        
                                        foreach ($report_data['daily_revenue'] as $revenue) {
                                            $date = $revenue['date'];
                                            $financial_summary[$date]['revenue'] = $revenue['daily_revenue'];
                                        }
                                        
                                        foreach ($report_data['daily_expenses'] as $expense) {
                                            $date = $expense['date'];
                                            $financial_summary[$date]['expenses'] = $expense['daily_expenses'];
                                        }
                                        
                                        foreach ($financial_summary as $date => $data):
                                            $revenue = $data['revenue'] ?? 0;
                                            $expenses = $data['expenses'] ?? 0;
                                            $profit = $revenue - $expenses;
                                        ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($date)) ?></td>
                                            <td class="text-success"><?= number_format($revenue) ?> ريال</td>
                                            <td class="text-danger"><?= number_format($expenses) ?> ريال</td>
                                            <td class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= number_format($profit) ?> ريال
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>

            <!-- أزرار العودة -->
            <div class="mt-4 text-center">
                <a href="dash.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>العودة للوحة التحكم
                </a>
            </div>
        </div>
    </div>
</div>

<!-- CSS محلي للتقارير -->
<style>
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .table th {
        font-weight: 600;
        border-top: none;
    }
    
    .progress {
        height: 10px;
        border-radius: 5px;
    }
    
    .opacity-75 {
        opacity: 0.75;
    }
    
    @media print {
        .btn, .card-header, .no-print {
            display: none !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>

<!-- JavaScript محلي -->
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/bootstrap-full.js"></script>
<script src="../assets/js/chart.min.js"></script>

<script>
// دوال التصدير
function exportToExcel() {
    const reportType = document.getElementById('report_type').value;
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    const url = `reports/export_excel.php?report_type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
    window.open(url, '_blank');
}

function exportToPDF() {
    const reportType = document.getElementById('report_type').value;
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    const url = `reports/export_pdf.php?report_type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
    window.open(url, '_blank');
}

function printReport() {
    window.print();
}

// دالة إعادة تعيين الفلاتر
function resetFilters() {
    document.querySelector('select[name="report_type"]').value = 'overview';
    document.querySelector('input[name="start_date"]').value = '<?= date('Y-m-01') ?>';
    document.querySelector('input[name="end_date"]').value = '<?= date('Y-m-d') ?>';
    document.getElementById('reportForm').submit();
}

// دالة تعيين نطاقات التاريخ
function setDateRange(range) {
    const today = new Date();
    let startDate, endDate;
    
    switch(range) {
        case 'today':
            startDate = endDate = today.toISOString().split('T')[0];
            break;
        case 'week':
            const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
            startDate = weekStart.toISOString().split('T')[0];
            endDate = new Date().toISOString().split('T')[0];
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = new Date().toISOString().split('T')[0];
            break;
        case 'quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            startDate = new Date(today.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
            endDate = new Date().toISOString().split('T')[0];
            break;
        case 'year':
            startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            endDate = new Date().toISOString().split('T')[0];
            break;
    }
    
    document.querySelector('input[name="start_date"]').value = startDate;
    document.querySelector('input[name="end_date"]').value = endDate;
}

// رسوم بيانية للتقرير المالي
<?php if ($report_type === 'financial' && !empty($report_data['daily_revenue'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    // رسم الإيرادات
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($report_data['daily_revenue'], 'date')) ?>,
            datasets: [{
                label: 'الإيرادات اليومية',
                data: <?= json_encode(array_column($report_data['daily_revenue'], 'daily_revenue')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // رسم المصروفات
    const expensesCtx = document.getElementById('expensesChart').getContext('2d');
    new Chart(expensesCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($report_data['daily_expenses'], 'date')) ?>,
            datasets: [{
                label: 'المصروفات اليومية',
                data: <?= json_encode(array_column($report_data['daily_expenses'], 'daily_expenses')) ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
<?php endif; ?>
</script>

<?php include_once '../includes/footer.php'; ?>
