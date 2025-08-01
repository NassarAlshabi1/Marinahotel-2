<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';

// التحقق من صلاحيات المستخدم
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// تحديد نطاق التاريخ الافتراضي
$today = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_category = isset($_GET['category']) ? $_GET['category'] : 'overview';

// دالة لجلب تقارير الإيرادات
function getRevenueReports($conn, $start_date, $end_date) {
    $reports = [];
    
    // إيرادات يومية
    $daily_query = "
        SELECT 
            DATE(payment_date) as date,
            SUM(amount) as daily_revenue,
            COUNT(*) as transaction_count
        FROM payment 
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY DATE(payment_date)
        ORDER BY date DESC
    ";
    $stmt = $conn->prepare($daily_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['daily'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // إيرادات حسب الغرف
    $room_query = "
        SELECT 
            b.room_number,
            r.type as room_type,
            SUM(p.amount) as room_revenue,
            COUNT(p.payment_id) as payment_count,
            AVG(p.amount) as avg_payment
        FROM payment p
        JOIN bookings b ON p.booking_id = b.booking_id
        JOIN rooms r ON b.room_number = r.room_number
        WHERE DATE(p.payment_date) BETWEEN ? AND ?
        GROUP BY b.room_number, r.type
        ORDER BY room_revenue DESC
    ";
    $stmt = $conn->prepare($room_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['by_room'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // إيرادات حسب طريقة الدفع
    $payment_method_query = "
        SELECT 
            payment_method,
            SUM(amount) as method_revenue,
            COUNT(*) as transaction_count,
            AVG(amount) as avg_amount
        FROM payment 
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY method_revenue DESC
    ";
    $stmt = $conn->prepare($payment_method_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['by_payment_method'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // إيرادات حسب نوع الخدمة
    $service_query = "
        SELECT 
            revenue_type,
            SUM(amount) as service_revenue,
            COUNT(*) as transaction_count
        FROM payment 
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY revenue_type
        ORDER BY service_revenue DESC
    ";
    $stmt = $conn->prepare($service_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['by_service'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $reports;
}

// دالة لجلب تقارير المصروفات
function getExpenseReports($conn, $start_date, $end_date) {
    $reports = [];
    
    // مصروفات يومية
    $daily_query = "
        SELECT 
            DATE(date) as date,
            SUM(amount) as daily_expense,
            COUNT(*) as transaction_count
        FROM expenses 
        WHERE DATE(date) BETWEEN ? AND ?
        GROUP BY DATE(date)
        ORDER BY date DESC
    ";
    $stmt = $conn->prepare($daily_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['daily'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // مصروفات حسب النوع
    $type_query = "
        SELECT 
            expense_type,
            SUM(amount) as type_expense,
            COUNT(*) as transaction_count,
            AVG(amount) as avg_amount
        FROM expenses 
        WHERE DATE(date) BETWEEN ? AND ?
        GROUP BY expense_type
        ORDER BY type_expense DESC
    ";
    $stmt = $conn->prepare($type_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['by_type'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // مصروفات الموردين
    $supplier_query = "
        SELECT 
            s.name as supplier_name,
            SUM(e.amount) as supplier_expense,
            COUNT(e.id) as transaction_count
        FROM expenses e
        JOIN suppliers s ON e.related_id = s.id
        WHERE e.expense_type = 'purchases' 
        AND DATE(e.date) BETWEEN ? AND ?
        GROUP BY s.id, s.name
        ORDER BY supplier_expense DESC
    ";
    $stmt = $conn->prepare($supplier_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['by_supplier'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $reports;
}

// دالة لجلب تقارير التشغيل
function getOperationalReports($conn, $start_date, $end_date) {
    $reports = [];
    
    // معدل الإشغال اليومي
    $occupancy_query = "
        SELECT 
            DATE(checkin_date) as date,
            COUNT(DISTINCT room_number) as occupied_rooms,
            (SELECT COUNT(*) FROM rooms) as total_rooms,
            ROUND((COUNT(DISTINCT room_number) / (SELECT COUNT(*) FROM rooms)) * 100, 2) as occupancy_rate
        FROM bookings 
        WHERE DATE(checkin_date) BETWEEN ? AND ?
        AND status = 'محجوزة'
        GROUP BY DATE(checkin_date)
        ORDER BY date DESC
    ";
    $stmt = $conn->prepare($occupancy_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['occupancy'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // إحصائيات النزلاء
    $guest_query = "
        SELECT 
            guest_nationality,
            COUNT(*) as guest_count,
            SUM(calculated_nights) as total_nights,
            AVG(calculated_nights) as avg_stay_duration
        FROM bookings 
        WHERE DATE(checkin_date) BETWEEN ? AND ?
        GROUP BY guest_nationality
        ORDER BY guest_count DESC
    ";
    $stmt = $conn->prepare($guest_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['guests'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // أداء الغرف
    $room_performance_query = "
        SELECT 
            r.room_number,
            r.type,
            r.price,
            COUNT(b.booking_id) as booking_count,
            SUM(b.calculated_nights) as total_nights,
            SUM(p.amount) as total_revenue,
            ROUND(SUM(p.amount) / COUNT(b.booking_id), 2) as avg_revenue_per_booking
        FROM rooms r
        LEFT JOIN bookings b ON r.room_number = b.room_number 
            AND DATE(b.checkin_date) BETWEEN ? AND ?
        LEFT JOIN payment p ON b.booking_id = p.booking_id
        GROUP BY r.room_number, r.type, r.price
        ORDER BY total_revenue DESC
    ";
    $stmt = $conn->prepare($room_performance_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['room_performance'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $reports;
}

// دالة لجلب تقارير التدفق النقدي
function getCashFlowReports($conn, $start_date, $end_date) {
    $reports = [];
    
    // حركة الصندوق اليومية
    $cash_register_query = "
        SELECT 
            date,
            opening_balance,
            total_income,
            total_expense,
            closing_balance,
            status
        FROM cash_register 
        WHERE date BETWEEN ? AND ?
        ORDER BY date DESC
    ";
    $stmt = $conn->prepare($cash_register_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['cash_register'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // المعاملات النقدية
    $cash_transactions_query = "
        SELECT 
            ct.transaction_time,
            ct.transaction_type,
            ct.amount,
            ct.description,
            cr.date as register_date
        FROM cash_transactions ct
        JOIN cash_register cr ON ct.register_id = cr.id
        WHERE DATE(ct.transaction_time) BETWEEN ? AND ?
        ORDER BY ct.transaction_time DESC
    ";
    $stmt = $conn->prepare($cash_transactions_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reports['transactions'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $reports;
}

// جلب التقارير حسب الفئة المحددة
$reports_data = [];
switch ($report_category) {
    case 'revenue':
        $reports_data = getRevenueReports($conn, $start_date, $end_date);
        break;
    case 'expenses':
        $reports_data = getExpenseReports($conn, $start_date, $end_date);
        break;
    case 'operational':
        $reports_data = getOperationalReports($conn, $start_date, $end_date);
        break;
    case 'cashflow':
        $reports_data = getCashFlowReports($conn, $start_date, $end_date);
        break;
    default:
        // نظرة عامة - جلب ملخص من كل فئة
        $reports_data['revenue'] = getRevenueReports($conn, $start_date, $end_date);
        $reports_data['expenses'] = getExpenseReports($conn, $start_date, $end_date);
        $reports_data['operational'] = getOperationalReports($conn, $start_date, $end_date);
        $reports_data['cashflow'] = getCashFlowReports($conn, $start_date, $end_date);
        break;
}

function formatCurrency($amount) {
    return number_format($amount, 0, '.', ',') . ' ريال';
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        التقارير المالية الشاملة
                    </h2>
                </div>
                <div class="card-body">
                    <!-- فلاتر التقرير -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label for="start_date" class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="end_date" class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="category" class="form-label">فئة التقرير</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="overview" <?= $report_category == 'overview' ? 'selected' : '' ?>>نظرة عامة</option>
                                        <option value="revenue" <?= $report_category == 'revenue' ? 'selected' : '' ?>>تقارير الإيرادات</option>
                                        <option value="expenses" <?= $report_category == 'expenses' ? 'selected' : '' ?>>تقارير المصروفات</option>
                                        <option value="operational" <?= $report_category == 'operational' ? 'selected' : '' ?>>التقارير التشغيلية</option>
                                        <option value="cashflow" <?= $report_category == 'cashflow' ? 'selected' : '' ?>>التدفق النقدي</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block w-100">
                                        <i class="fas fa-search me-1"></i>عرض التقرير
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="btn-group d-block w-100">
                                        <a href="../finance/unified_financial_dashboard.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                                           class="btn btn-info btn-sm">
                                            <i class="fas fa-tachometer-alt me-1"></i>الداشبورد
                                        </a>
                                        <a href="export_comprehensive_reports.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&category=<?= $report_category ?>&type=pdf" 
                                           class="btn btn-danger btn-sm">
                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                        </a>
                                        <a href="export_comprehensive_reports.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&category=<?= $report_category ?>&type=excel" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- عرض التقارير -->
                    <?php if ($report_category == 'overview'): ?>
                        <!-- نظرة عامة -->
                        <div class="row">
                            <div class="col-12">
                                <h4 class="text-primary mb-3">نظرة عامة على الأداء المالي</h4>
                            </div>
                        </div>
                        
                        <!-- ملخص الإيرادات -->
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <div class="card border-left-success">
                                    <div class="card-header">
                                        <h6 class="text-success">ملخص الإيرادات</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($reports_data['revenue']['by_payment_method'])): ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>طريقة الدفع</th>
                                                            <th>المبلغ</th>
                                                            <th>المعاملات</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach (array_slice($reports_data['revenue']['by_payment_method'], 0, 5) as $method): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($method['payment_method']) ?></td>
                                                            <td><?= formatCurrency($method['method_revenue']) ?></td>
                                                            <td><?= $method['transaction_count'] ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ملخص المصروفات -->
                            <div class="col-lg-6">
                                <div class="card border-left-danger">
                                    <div class="card-header">
                                        <h6 class="text-danger">ملخص المصروفات</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($reports_data['expenses']['by_type'])): ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>نوع المصروف</th>
                                                            <th>المبلغ</th>
                                                            <th>المعاملات</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach (array_slice($reports_data['expenses']['by_type'], 0, 5) as $type): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($type['expense_type']) ?></td>
                                                            <td><?= formatCurrency($type['type_expense']) ?></td>
                                                            <td><?= $type['transaction_count'] ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($report_category == 'revenue'): ?>
                        <!-- تقارير الإيرادات -->
                        <div class="row">
                            <div class="col-12">
                                <h4 class="text-success mb-3">تقارير الإيرادات التفصيلية</h4>
                            </div>
                        </div>
                        
                        <!-- الإيرادات اليومية -->
                        <?php if (!empty($reports_data['daily'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>الإيرادات اليومية</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>التاريخ</th>
                                                        <th>الإيراد اليومي</th>
                                                        <th>عدد المعاملات</th>
                                                        <th>متوسط المعاملة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reports_data['daily'] as $daily): ?>
                                                    <tr>
                                                        <td><?= date('Y-m-d', strtotime($daily['date'])) ?></td>
                                                        <td><?= formatCurrency($daily['daily_revenue']) ?></td>
                                                        <td><?= $daily['transaction_count'] ?></td>
                                                        <td><?= formatCurrency($daily['daily_revenue'] / $daily['transaction_count']) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- الإيرادات حسب الغرف -->
                        <?php if (!empty($reports_data['by_room'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>الإيرادات حسب الغرف</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>رقم الغرفة</th>
                                                        <th>نوع الغرفة</th>
                                                        <th>إجمالي الإيراد</th>
                                                        <th>عدد المدفوعات</th>
                                                        <th>متوسط الدفع</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reports_data['by_room'] as $room): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($room['room_number']) ?></td>
                                                        <td><?= htmlspecialchars($room['room_type']) ?></td>
                                                        <td><?= formatCurrency($room['room_revenue']) ?></td>
                                                        <td><?= $room['payment_count'] ?></td>
                                                        <td><?= formatCurrency($room['avg_payment']) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    <?php elseif ($report_category == 'expenses'): ?>
                        <!-- تقارير المصروفات -->
                        <div class="row">
                            <div class="col-12">
                                <h4 class="text-danger mb-3">تقارير المصروفات التفصيلية</h4>
                            </div>
                        </div>
                        
                        <!-- المصروفات اليومية -->
                        <?php if (!empty($reports_data['daily'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>المصروفات اليومية</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>التاريخ</th>
                                                        <th>المصروف اليومي</th>
                                                        <th>عدد المعاملات</th>
                                                        <th>متوسط المعاملة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reports_data['daily'] as $daily): ?>
                                                    <tr>
                                                        <td><?= date('Y-m-d', strtotime($daily['date'])) ?></td>
                                                        <td><?= formatCurrency($daily['daily_expense']) ?></td>
                                                        <td><?= $daily['transaction_count'] ?></td>
                                                        <td><?= formatCurrency($daily['daily_expense'] / $daily['transaction_count']) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    <?php elseif ($report_category == 'operational'): ?>
                        <!-- التقارير التشغيلية -->
                        <div class="row">
                            <div class="col-12">
                                <h4 class="text-info mb-3">التقارير التشغيلية</h4>
                            </div>
                        </div>
                        
                        <!-- معدل الإشغال -->
                        <?php if (!empty($reports_data['occupancy'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>معدل الإشغال اليومي</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>التاريخ</th>
                                                        <th>الغرف المشغولة</th>
                                                        <th>إجمالي الغرف</th>
                                                        <th>معدل الإشغال</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reports_data['occupancy'] as $occ): ?>
                                                    <tr>
                                                        <td><?= date('Y-m-d', strtotime($occ['date'])) ?></td>
                                                        <td><?= $occ['occupied_rooms'] ?></td>
                                                        <td><?= $occ['total_rooms'] ?></td>
                                                        <td>
                                                            <div class="progress">
                                                                <div class="progress-bar" style="width: <?= $occ['occupancy_rate'] ?>%">
                                                                    <?= $occ['occupancy_rate'] ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    <?php elseif ($report_category == 'cashflow'): ?>
                        <!-- تقارير التدفق النقدي -->
                        <div class="row">
                            <div class="col-12">
                                <h4 class="text-warning mb-3">تقارير التدفق النقدي</h4>
                            </div>
                        </div>
                        
                        <!-- حركة الصندوق -->
                        <?php if (!empty($reports_data['cash_register'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>حركة الصندوق اليومية</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>التاريخ</th>
                                                        <th>الرصيد الافتتاحي</th>
                                                        <th>إجمالي الدخل</th>
                                                        <th>إجمالي المصروف</th>
                                                        <th>الرصيد الختامي</th>
                                                        <th>الحالة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reports_data['cash_register'] as $cash): ?>
                                                    <tr>
                                                        <td><?= date('Y-m-d', strtotime($cash['date'])) ?></td>
                                                        <td><?= formatCurrency($cash['opening_balance']) ?></td>
                                                        <td><?= formatCurrency($cash['total_income']) ?></td>
                                                        <td><?= formatCurrency($cash['total_expense']) ?></td>
                                                        <td><?= formatCurrency($cash['closing_balance'] ?? 0) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $cash['status'] == 'open' ? 'success' : 'secondary' ?>">
                                                                <?= $cash['status'] == 'open' ? 'مفتوح' : 'مغلق' ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

@media print {
    .btn-group, .form-control, .form-select {
        display: none !important;
    }
}
</style>

<script>
// تحديث التقرير تلقائياً عند تغيير الفلاتر
document.getElementById('category').addEventListener('change', function() {
    document.querySelector('form').submit();
});
</script>

<?php include_once '../../includes/footer.php'; ?>