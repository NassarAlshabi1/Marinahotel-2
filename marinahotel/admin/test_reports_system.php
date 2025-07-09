<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    exit("يجب تسجيل الدخول كمدير لتشغيل هذا الاختبار");
}

// دالة اختبار الاتصال بقاعدة البيانات
function testDatabaseConnection($conn) {
    try {
        $result = $conn->query("SELECT 1");
        return $result ? "✅ نجح" : "❌ فشل";
    } catch (Exception $e) {
        return "❌ فشل: " . $e->getMessage();
    }
}

// دالة اختبار وجود الجداول المطلوبة
function testRequiredTables($conn) {
    $required_tables = ['bookings', 'payment', 'expenses', 'rooms', 'employees', 'salary_withdrawals'];
    $results = [];
    
    foreach ($required_tables as $table) {
        try {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $results[$table] = $result && $result->num_rows > 0 ? "✅" : "❌";
        } catch (Exception $e) {
            $results[$table] = "❌";
        }
    }
    
    return $results;
}

// دالة اختبار وجود الملفات المطلوبة
function testRequiredFiles() {
    $required_files = [
        'reports.php' => '../admin/reports.php',
        'export_excel.php' => '../admin/reports/export_excel.php',
        'export_pdf.php' => '../admin/reports/export_pdf.php',
        'bootstrap.css' => '../assets/css/bootstrap-complete.css',
        'fontawesome.css' => '../assets/css/fontawesome.min.css',
        'jquery.js' => '../assets/js/jquery.min.js',
        'bootstrap.js' => '../assets/js/bootstrap-full.js',
        'chart.js' => '../assets/js/chart.min.js'
    ];
    
    $results = [];
    foreach ($required_files as $name => $path) {
        $results[$name] = file_exists($path) ? "✅" : "❌";
    }
    
    return $results;
}

// دالة اختبار دوال التقارير
function testReportFunctions($conn) {
    $results = [];
    
    // اختبار دالة إحصائيات الحجوزات
    try {
        include_once 'reports.php';
        $bookings_stats = getBookingsStats($conn, date('Y-m-01'), date('Y-m-d'));
        $results['getBookingsStats'] = is_array($bookings_stats) ? "✅" : "❌";
    } catch (Exception $e) {
        $results['getBookingsStats'] = "❌";
    }
    
    // اختبار دالة إحصائيات الإيرادات
    try {
        $revenue_stats = getRevenueStats($conn, date('Y-m-01'), date('Y-m-d'));
        $results['getRevenueStats'] = is_array($revenue_stats) ? "✅" : "❌";
    } catch (Exception $e) {
        $results['getRevenueStats'] = "❌";
    }
    
    // اختبار دالة إحصائيات الغرف
    try {
        $rooms_stats = getRoomsStats($conn);
        $results['getRoomsStats'] = is_array($rooms_stats) ? "✅" : "❌";
    } catch (Exception $e) {
        $results['getRoomsStats'] = "❌";
    }
    
    return $results;
}

// تشغيل الاختبارات
$db_test = testDatabaseConnection($conn);
$tables_test = testRequiredTables($conn);
$files_test = testRequiredFiles();
$functions_test = testReportFunctions($conn);

include_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-vial me-2"></i>اختبار نظام التقارير
                    </h2>
                    <p class="mb-0">فحص شامل لجميع مكونات نظام التقارير</p>
                </div>
                <div class="card-body">
                    
                    <!-- اختبار الاتصال بقاعدة البيانات -->
                    <div class="section mb-4">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-database me-2"></i>اختبار قاعدة البيانات
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <td><strong>الاتصال بقاعدة البيانات</strong></td>
                                    <td><?= $db_test ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- اختبار الجداول المطلوبة -->
                    <div class="section mb-4">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-table me-2"></i>اختبار الجداول المطلوبة
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <?php foreach ($tables_test as $table => $status): ?>
                                <tr>
                                    <td><strong>جدول <?= $table ?></strong></td>
                                    <td><?= $status ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- اختبار الملفات المطلوبة -->
                    <div class="section mb-4">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-file-code me-2"></i>اختبار الملفات المطلوبة
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <?php foreach ($files_test as $file => $status): ?>
                                <tr>
                                    <td><strong><?= $file ?></strong></td>
                                    <td><?= $status ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- اختبار الدوال -->
                    <div class="section mb-4">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-cogs me-2"></i>اختبار دوال التقارير
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <?php foreach ($functions_test as $function => $status): ?>
                                <tr>
                                    <td><strong><?= $function ?>()</strong></td>
                                    <td><?= $status ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- اختبار عينة من البيانات -->
                    <div class="section mb-4">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-chart-bar me-2"></i>عينة من البيانات
                        </h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0">إحصائيات سريعة</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        try {
                                            $total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
                                            $total_payments = $conn->query("SELECT COUNT(*) as count FROM payment")->fetch_assoc()['count'];
                                            $total_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
                                            
                                            echo "<p><strong>إجمالي الحجوزات:</strong> $total_bookings</p>";
                                            echo "<p><strong>إجمالي المدفوعات:</strong> $total_payments</p>";
                                            echo "<p><strong>إجمالي الغرف:</strong> $total_rooms</p>";
                                        } catch (Exception $e) {
                                            echo "<p class='text-danger'>خطأ في جلب البيانات: " . $e->getMessage() . "</p>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0">اختبار التصدير</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>روابط اختبار التصدير:</strong></p>
                                        <div class="btn-group-vertical w-100">
                                            <a href="reports/export_excel.php?report_type=overview&start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-d') ?>" 
                                               class="btn btn-success btn-sm" target="_blank">
                                                <i class="fas fa-file-excel me-1"></i>اختبار تصدير Excel
                                            </a>
                                            <a href="reports/export_pdf.php?report_type=overview&start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-d') ?>" 
                                               class="btn btn-danger btn-sm mt-2" target="_blank">
                                                <i class="fas fa-file-pdf me-1"></i>اختبار تصدير PDF
                                            </a>
                                            <a href="reports.php" class="btn btn-primary btn-sm mt-2">
                                                <i class="fas fa-chart-line me-1"></i>صفحة التقارير الرئيسية
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نتيجة الاختبار الإجمالية -->
                    <div class="section">
                        <h4 class="text-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>نتيجة الاختبار
                        </h4>
                        <?php
                        $all_tests = array_merge([$db_test], array_values($tables_test), array_values($files_test), array_values($functions_test));
                        $passed_tests = count(array_filter($all_tests, function($test) { return strpos($test, '✅') !== false; }));
                        $total_tests = count($all_tests);
                        $success_rate = ($passed_tests / $total_tests) * 100;
                        
                        if ($success_rate == 100) {
                            $badge_class = 'bg-success';
                            $message = 'جميع الاختبارات نجحت! النظام جاهز للاستخدام.';
                        } elseif ($success_rate >= 80) {
                            $badge_class = 'bg-warning';
                            $message = 'معظم الاختبارات نجحت. قد تحتاج لبعض التعديلات الطفيفة.';
                        } else {
                            $badge_class = 'bg-danger';
                            $message = 'عدة اختبارات فشلت. يجب مراجعة النظام قبل الاستخدام.';
                        }
                        ?>
                        
                        <div class="alert alert-info">
                            <h5 class="mb-3">
                                <span class="badge <?= $badge_class ?> fs-6">
                                    <?= $passed_tests ?>/<?= $total_tests ?> (<?= number_format($success_rate, 1) ?>%)
                                </span>
                                نسبة نجاح الاختبارات
                            </h5>
                            <p class="mb-0"><?= $message ?></p>
                        </div>
                    </div>

                    <!-- معلومات إضافية -->
                    <div class="section mt-4">
                        <h4 class="text-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>معلومات النظام
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <td><strong>إصدار PHP</strong></td>
                                    <td><?= PHP_VERSION ?></td>
                                </tr>
                                <tr>
                                    <td><strong>إصدار MySQL</strong></td>
                                    <td><?= $conn->server_info ?></td>
                                </tr>
                                <tr>
                                    <td><strong>المستخدم الحالي</strong></td>
                                    <td><?= $_SESSION['user_name'] ?? 'غير محدد' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>تاريخ الاختبار</strong></td>
                                    <td><?= date('Y-m-d H:i:s') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>
                <div class="card-footer text-center">
                    <a href="reports.php" class="btn btn-primary me-2">
                        <i class="fas fa-chart-bar me-1"></i>انتقل لصفحة التقارير
                    </a>
                    <a href="dash.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.section {
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.section:last-child {
    border-bottom: none;
}

.table td:first-child {
    width: 60%;
}

.table td:last-child {
    width: 40%;
    text-align: center;
    font-size: 1.2em;
}

.btn-group-vertical .btn {
    text-align: right;
}

.alert {
    border-radius: 10px;
}

.badge {
    font-size: 1em;
}
</style>

<?php include_once '../includes/footer.php'; ?>