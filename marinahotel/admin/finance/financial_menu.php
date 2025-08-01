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
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-calculator me-2"></i>
                        النظام المالي الموحد
                    </h2>
                    <p class="mb-0 mt-2">إدارة شاملة لجميع العمليات المالية والتقارير</p>
                </div>
                <div class="card-body">
                    <!-- الإحصائيات السريعة -->
                    <div class="row mb-4">
                        <?php
                        // جلب الإحصائيات السريعة
                        $today = date('Y-m-d');
                        $this_month = date('Y-m-01');
                        
                        // إيرادات اليوم
                        $today_revenue_query = "SELECT SUM(amount) as today_revenue FROM payment WHERE DATE(payment_date) = ?";
                        $stmt = $conn->prepare($today_revenue_query);
                        $stmt->bind_param("s", $today);
                        $stmt->execute();
                        $today_revenue = $stmt->get_result()->fetch_assoc()['today_revenue'] ?? 0;
                        
                        // إيرادات الشهر
                        $month_revenue_query = "SELECT SUM(amount) as month_revenue FROM payment WHERE DATE(payment_date) >= ?";
                        $stmt = $conn->prepare($month_revenue_query);
                        $stmt->bind_param("s", $this_month);
                        $stmt->execute();
                        $month_revenue = $stmt->get_result()->fetch_assoc()['month_revenue'] ?? 0;
                        
                        // مصروفات الشهر
                        $month_expenses_query = "SELECT SUM(amount) as month_expenses FROM expenses WHERE DATE(date) >= ?";
                        $stmt = $conn->prepare($month_expenses_query);
                        $stmt->bind_param("s", $this_month);
                        $stmt->execute();
                        $month_expenses = $stmt->get_result()->fetch_assoc()['month_expenses'] ?? 0;
                        
                        // الغرف المشغولة
                        $occupied_rooms_query = "SELECT COUNT(*) as occupied FROM rooms WHERE status = 'محجوزة'";
                        $occupied_rooms = $conn->query($occupied_rooms_query)->fetch_assoc()['occupied'] ?? 0;
                        
                        $total_rooms_query = "SELECT COUNT(*) as total FROM rooms";
                        $total_rooms = $conn->query($total_rooms_query)->fetch_assoc()['total'] ?? 1;
                        
                        $occupancy_rate = ($occupied_rooms / $total_rooms) * 100;
                        ?>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                إيرادات اليوم
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($today_revenue, 0, '.', ',') ?> ريال
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                إيرادات الشهر
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($month_revenue, 0, '.', ',') ?> ريال
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
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
                                                مصروفات الشهر
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($month_expenses, 0, '.', ',') ?> ريال
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
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                معدل الإشغال
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($occupancy_rate, 1) ?>%
                                            </div>
                                            <div class="text-xs text-muted">
                                                <?= $occupied_rooms ?> من <?= $total_rooms ?> غرفة
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bed fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- قائمة الخدمات المالية -->
                    <div class="row">
                        <!-- التقارير والداشبورد -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-left-primary shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-line me-2"></i>التقارير والتحليلات
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <a href="unified_financial_dashboard.php" class="btn btn-primary btn-block">
                                                <i class="fas fa-tachometer-alt me-2"></i>
                                                الداشبورد المالي الموحد
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../reports/comprehensive_financial_reports.php" class="btn btn-info btn-block">
                                                <i class="fas fa-chart-bar me-2"></i>
                                                التقارير الشاملة
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../reports/revenue.php" class="btn btn-success btn-block">
                                                <i class="fas fa-money-bill-wave me-2"></i>
                                                تقارير الإيرادات
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../reports/occupancy.php" class="btn btn-warning btn-block">
                                                <i class="fas fa-bed me-2"></i>
                                                تقارير الإشغال
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- إدارة المصروفات والمدفوعات -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-left-danger shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">
                                        <i class="fas fa-wallet me-2"></i>إدارة المصروفات والمدفوعات
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <a href="../expenses/expenses.php" class="btn btn-danger btn-block">
                                                <i class="fas fa-receipt me-2"></i>
                                                إدارة المصروفات
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../bookings/payment.php" class="btn btn-success btn-block">
                                                <i class="fas fa-credit-card me-2"></i>
                                                إدارة المدفوعات
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../expenses/salary_withdrawals.php" class="btn btn-warning btn-block">
                                                <i class="fas fa-users me-2"></i>
                                                سحوبات الرواتب
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="cash_register.php" class="btn btn-info btn-block">
                                                <i class="fas fa-cash-register me-2"></i>
                                                إدارة الصندوق
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- التصدير والطباعة -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-left-success shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">
                                        <i class="fas fa-download me-2"></i>التصدير والطباعة
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <a href="export_unified_report.php?type=pdf&start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>" 
                                               class="btn btn-danger btn-block" target="_blank">
                                                <i class="fas fa-file-pdf me-2"></i>
                                                تصدير PDF شامل
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="export_unified_report.php?type=excel&start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>" 
                                               class="btn btn-success btn-block" target="_blank">
                                                <i class="fas fa-file-excel me-2"></i>
                                                تصدير Excel شامل
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../reports/export_comprehensive_pdf.php" class="btn btn-secondary btn-block" target="_blank">
                                                <i class="fas fa-print me-2"></i>
                                                طباعة التقارير
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../reports/invoice.php" class="btn btn-primary btn-block">
                                                <i class="fas fa-file-invoice me-2"></i>
                                                إنشاء فاتورة
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- الأدوات والإعدادات -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-left-warning shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-warning">
                                        <i class="fas fa-tools me-2"></i>الأدوات والإعدادات
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <a href="cash_reports.php" class="btn btn-info btn-block">
                                                <i class="fas fa-chart-pie me-2"></i>
                                                تقارير الصندوق
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../system_tools/backup_database.php" class="btn btn-secondary btn-block">
                                                <i class="fas fa-database me-2"></i>
                                                نسخ احتياطي
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../../system_health_report.php" class="btn btn-warning btn-block">
                                                <i class="fas fa-heartbeat me-2"></i>
                                                صحة النظام
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="../settings/index.php" class="btn btn-dark btn-block">
                                                <i class="fas fa-cog me-2"></i>
                                                الإعدادات
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- روابط سريعة -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-left-info shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info">
                                        <i class="fas fa-link me-2"></i>روابط سريعة
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="../bookings/list.php" class="btn btn-outline-primary btn-sm btn-block">
                                                <i class="fas fa-list me-1"></i>قائمة الحجوزات
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="../rooms/list.php" class="btn btn-outline-info btn-sm btn-block">
                                                <i class="fas fa-bed me-1"></i>إدارة الغرف
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="../settings/guests.php" class="btn btn-outline-success btn-sm btn-block">
                                                <i class="fas fa-users me-1"></i>إدارة النزلاء
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="../dash.php" class="btn btn-outline-secondary btn-sm btn-block">
                                                <i class="fas fa-home me-1"></i>الصفحة الرئيسية
                                            </a>
                                        </div>
                                    </div>
                                </div>
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

.btn-block {
    display: block;
    width: 100%;
}

.card-header h6 {
    margin-bottom: 0;
}

.text-xs {
    font-size: 0.7rem;
}

.font-weight-bold {
    font-weight: 700;
}

.text-uppercase {
    text-transform: uppercase;
}

.text-gray-800 {
    color: #5a5c69;
}

.text-gray-300 {
    color: #dddfeb;
}

.h5 {
    font-size: 1.25rem;
}

.no-gutters {
    margin-right: 0;
    margin-left: 0;
}

.no-gutters > .col,
.no-gutters > [class*="col-"] {
    padding-right: 0;
    padding-left: 0;
}
</style>

<?php include_once '../../includes/footer.php'; ?>