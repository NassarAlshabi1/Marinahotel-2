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
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';

// دالة لجلب البيانات المالية الموحدة
function getUnifiedFinancialData($conn, $start_date, $end_date) {
    $data = [];
    
    // 1. إجمالي الإيرادات من المدفوعات
    $revenue_query = "
        SELECT 
            SUM(amount) as total_revenue,
            COUNT(*) as payment_count,
            payment_method,
            revenue_type
        FROM payment 
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY payment_method, revenue_type
    ";
    $stmt = $conn->prepare($revenue_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $revenue_result = $stmt->get_result();
    
    $data['revenue'] = [];
    $data['total_revenue'] = 0;
    while ($row = $revenue_result->fetch_assoc()) {
        $data['revenue'][] = $row;
        $data['total_revenue'] += $row['total_revenue'];
    }
    
    // 2. إجمالي المصروفات
    $expenses_query = "
        SELECT 
            SUM(amount) as total_expenses,
            COUNT(*) as expense_count,
            expense_type
        FROM expenses 
        WHERE DATE(date) BETWEEN ? AND ?
        GROUP BY expense_type
    ";
    $stmt = $conn->prepare($expenses_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $expenses_result = $stmt->get_result();
    
    $data['expenses'] = [];
    $data['total_expenses'] = 0;
    while ($row = $expenses_result->fetch_assoc()) {
        $data['expenses'][] = $row;
        $data['total_expenses'] += $row['total_expenses'];
    }
    
    // 3. حركة الصندوق
    $cash_query = "
        SELECT 
            cr.date,
            cr.opening_balance,
            cr.closing_balance,
            cr.total_income,
            cr.total_expense,
            cr.status
        FROM cash_register cr
        WHERE cr.date BETWEEN ? AND ?
        ORDER BY cr.date DESC
    ";
    $stmt = $conn->prepare($cash_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $cash_result = $stmt->get_result();
    
    $data['cash_register'] = [];
    while ($row = $cash_result->fetch_assoc()) {
        $data['cash_register'][] = $row;
    }
    
    // 4. سحوبات الرواتب
    $salary_query = "
        SELECT 
            sw.amount,
            sw.date,
            e.name as employee_name
        FROM salary_withdrawals sw
        JOIN employees e ON sw.employee_id = e.id
        WHERE DATE(sw.date) BETWEEN ? AND ?
        ORDER BY sw.date DESC
    ";
    $stmt = $conn->prepare($salary_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $salary_result = $stmt->get_result();
    
    $data['salary_withdrawals'] = [];
    $data['total_salaries'] = 0;
    while ($row = $salary_result->fetch_assoc()) {
        $data['salary_withdrawals'][] = $row;
        $data['total_salaries'] += $row['amount'];
    }
    
    // 5. إحصائيات الغرف والحجوزات
    $occupancy_query = "
        SELECT 
            COUNT(DISTINCT b.room_number) as occupied_rooms,
            COUNT(DISTINCT r.room_number) as total_rooms,
            AVG(DATEDIFF(COALESCE(b.actual_checkout, NOW()), b.checkin_date)) as avg_stay_duration
        FROM rooms r
        LEFT JOIN bookings b ON r.room_number = b.room_number 
            AND b.status = 'محجوزة'
            AND DATE(b.checkin_date) BETWEEN ? AND ?
    ";
    $stmt = $conn->prepare($occupancy_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $occupancy_result = $stmt->get_result();
    $data['occupancy'] = $occupancy_result->fetch_assoc();
    
    // حساب صافي الربح
    $data['net_profit'] = $data['total_revenue'] - $data['total_expenses'] - $data['total_salaries'];
    
    return $data;
}

// جلب البيانات المالية
$financial_data = getUnifiedFinancialData($conn, $start_date, $end_date);

// دالة لتنسيق الأرقام
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
                        <i class="fas fa-chart-line me-2"></i>
                        لوحة التحكم المالية الموحدة
                    </h2>
                </div>
                <div class="card-body">
                    <!-- فلاتر التاريخ -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="report_type" class="form-label">نوع التقرير</label>
                                    <select class="form-select" id="report_type" name="report_type">
                                        <option value="summary" <?= $report_type == 'summary' ? 'selected' : '' ?>>ملخص عام</option>
                                        <option value="detailed" <?= $report_type == 'detailed' ? 'selected' : '' ?>>تفصيلي</option>
                                        <option value="revenue" <?= $report_type == 'revenue' ? 'selected' : '' ?>>الإيرادات فقط</option>
                                        <option value="expenses" <?= $report_type == 'expenses' ? 'selected' : '' ?>>المصروفات فقط</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block w-100">
                                        <i class="fas fa-search me-1"></i>عرض التقرير
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- بطاقات الملخص المالي -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                إجمالي الإيرادات
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= formatCurrency($financial_data['total_revenue']) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                إجمالي المصروفات
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= formatCurrency($financial_data['total_expenses']) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                سحوبات الرواتب
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= formatCurrency($financial_data['total_salaries']) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card border-left-<?= $financial_data['net_profit'] >= 0 ? 'success' : 'danger' ?> shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-<?= $financial_data['net_profit'] >= 0 ? 'success' : 'danger' ?> text-uppercase mb-1">
                                                صافي الربح
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= formatCurrency($financial_data['net_profit']) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- إحصائيات الإشغال -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        معدل الإشغال
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $occupancy_rate = $financial_data['occupancy']['total_rooms'] > 0 ? 
                                            ($financial_data['occupancy']['occupied_rooms'] / $financial_data['occupancy']['total_rooms']) * 100 : 0;
                                        echo number_format($occupancy_rate, 1) . '%';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        الغرف المشغولة
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $financial_data['occupancy']['occupied_rooms'] ?> / <?= $financial_data['occupancy']['total_rooms'] ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        متوسط مدة الإقامة
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($financial_data['occupancy']['avg_stay_duration'] ?? 0, 1) ?> يوم
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- التقارير التفصيلية -->
                    <?php if ($report_type == 'detailed' || $report_type == 'summary'): ?>
                    <div class="row">
                        <!-- تفاصيل الإيرادات -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">تفاصيل الإيرادات</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>طريقة الدفع</th>
                                                    <th>نوع الإيراد</th>
                                                    <th>المبلغ</th>
                                                    <th>عدد المعاملات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($financial_data['revenue'] as $revenue): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($revenue['payment_method']) ?></td>
                                                    <td><?= htmlspecialchars($revenue['revenue_type']) ?></td>
                                                    <td><?= formatCurrency($revenue['total_revenue']) ?></td>
                                                    <td><?= $revenue['payment_count'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تفاصيل المصروفات -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">تفاصيل المصروفات</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>نوع المصروف</th>
                                                    <th>المبلغ</th>
                                                    <th>عدد المعاملات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($financial_data['expenses'] as $expense): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($expense['expense_type']) ?></td>
                                                    <td><?= formatCurrency($expense['total_expenses']) ?></td>
                                                    <td><?= $expense['expense_count'] ?></td>
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

                    <!-- حركة الصندوق -->
                    <?php if ($report_type == 'detailed' && !empty($financial_data['cash_register'])): ?>
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info">حركة الصندوق اليومية</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
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
                                                <?php foreach ($financial_data['cash_register'] as $cash): ?>
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

                    <!-- أزرار التصدير والطباعة -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                <a href="export_unified_report.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&type=pdf" 
                                   class="btn btn-danger">
                                    <i class="fas fa-file-pdf me-1"></i>تصدير PDF
                                </a>
                                <a href="export_unified_report.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&type=excel" 
                                   class="btn btn-success">
                                    <i class="fas fa-file-excel me-1"></i>تصدير Excel
                                </a>
                                <button onclick="window.print()" class="btn btn-secondary">
                                    <i class="fas fa-print me-1"></i>طباعة
                                </button>
                            </div>
                        </div>
                    </div>
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
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-secondary {
    border-left: 0.25rem solid #858796 !important;
}

@media print {
    .btn-group {
        display: none !important;
    }
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}
</style>

<script>
// تحديث التقرير تلقائياً عند تغيير التواريخ
document.getElementById('start_date').addEventListener('change', function() {
    if (document.getElementById('end_date').value) {
        document.querySelector('form').submit();
    }
});

document.getElementById('end_date').addEventListener('change', function() {
    if (document.getElementById('start_date').value) {
        document.querySelector('form').submit();
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>