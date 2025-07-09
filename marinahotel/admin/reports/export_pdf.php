<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    exit("غير مصرح لك بالوصول لهذه الصفحة");
}

// معالجة المعاملات
$report_type = $_GET['report_type'] ?? 'overview';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// استخدام نفس دوال جلب البيانات من صفحة التقارير الرئيسية
include_once '../reports.php';

// جلب البيانات حسب نوع التقرير
$data = getReportData($conn, $start_date, $end_date, $report_type);

// تعيين عنوان التقرير
$report_titles = [
    'overview' => 'نظرة عامة',
    'bookings' => 'تقرير الحجوزات',
    'financial' => 'التقرير المالي',
    'rooms' => 'تقرير الغرف',
    'employees' => 'تقرير الموظفين'
];

$report_title = $report_titles[$report_type] ?? 'تقرير';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $report_title ?> - فندق مارينا</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            direction: rtl;
            text-align: right;
            line-height: 1.6;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #007bff;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header h2 {
            color: #666;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .report-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .report-info table {
            width: 100%;
        }
        
        .report-info td {
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: #007bff;
            color: white;
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 8px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #007bff;
            color: white;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .text-center { text-align: center; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #ffc107; }
        .text-primary { color: #007bff; }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            color: white;
        }
        
        .badge-success { background: #28a745; }
        .badge-secondary { background: #6c757d; }
        
        @media print {
            .no-print { display: none !important; }
            .section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏨 فندق مارينا</h1>
        <h2><?= $report_title ?></h2>
        <p>تقرير مُنشأ في: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <div class="report-info">
        <table>
            <tr>
                <td><strong>نوع التقرير:</strong></td>
                <td><?= $report_title ?></td>
                <td><strong>من تاريخ:</strong></td>
                <td><?= date('d/m/Y', strtotime($start_date)) ?></td>
            </tr>
            <tr>
                <td><strong>إلى تاريخ:</strong></td>
                <td><?= date('d/m/Y', strtotime($end_date)) ?></td>
                <td><strong>عدد الأيام:</strong></td>
                <td><?= (strtotime($end_date) - strtotime($start_date)) / 86400 + 1 ?> يوم</td>
            </tr>
        </table>
    </div>

    <?php if ($report_type === 'overview'): ?>
        <!-- نظرة عامة -->
        <div class="section">
            <div class="section-title">📊 الإحصائيات العامة</div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($data['bookings']['total_bookings']) ?></div>
                    <div class="stat-label">إجمالي الحجوزات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($data['revenue']['total_revenue']) ?></div>
                    <div class="stat-label">إجمالي الإيرادات (ريال)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($data['expenses']['total_expenses'] ?? 0) ?></div>
                    <div class="stat-label">إجمالي المصروفات (ريال)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($data['revenue']['total_revenue'] - ($data['expenses']['total_expenses'] ?? 0)) ?></div>
                    <div class="stat-label">صافي الربح (ريال)</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">🏨 إحصائيات الغرف</div>
            <table>
                <tr>
                    <th>النوع</th>
                    <th>العدد</th>
                    <th>النسبة</th>
                </tr>
                <tr>
                    <td>إجمالي الغرف</td>
                    <td><?= $data['rooms']['total_rooms'] ?></td>
                    <td>100%</td>
                </tr>
                <tr>
                    <td>الغرف المتاحة</td>
                    <td class="text-success"><?= $data['rooms']['available_rooms'] ?></td>
                    <td><?= $data['rooms']['total_rooms'] > 0 ? number_format(($data['rooms']['available_rooms'] / $data['rooms']['total_rooms']) * 100, 1) : 0 ?>%</td>
                </tr>
                <tr>
                    <td>الغرف المشغولة</td>
                    <td class="text-danger"><?= $data['rooms']['occupied_rooms'] ?></td>
                    <td><?= $data['rooms']['total_rooms'] > 0 ? number_format(($data['rooms']['occupied_rooms'] / $data['rooms']['total_rooms']) * 100, 1) : 0 ?>%</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">👥 إحصائيات الموظفين</div>
            <table>
                <tr>
                    <th>البيان</th>
                    <th>القيمة</th>
                </tr>
                <tr>
                    <td>إجمالي الموظفين</td>
                    <td><?= $data['employees']['total_employees'] ?></td>
                </tr>
                <tr>
                    <td>إجمالي السحوبات</td>
                    <td><?= number_format($data['employees']['total_withdrawals']) ?> ريال</td>
                </tr>
                <tr>
                    <td>متوسط السحب للموظف</td>
                    <td><?= $data['employees']['total_employees'] > 0 ? number_format($data['employees']['total_withdrawals'] / $data['employees']['total_employees']) : 0 ?> ريال</td>
                </tr>
            </table>
        </div>

    <?php elseif ($report_type === 'bookings'): ?>
        <!-- تقرير الحجوزات -->
        <div class="section">
            <div class="section-title">📅 تقرير الحجوزات التفصيلي</div>
            <table>
                <thead>
                    <tr>
                        <th>رقم الحجز</th>
                        <th>اسم النزيل</th>
                        <th>رقم الغرفة</th>
                        <th>تاريخ الوصول</th>
                        <th>عدد الليالي</th>
                        <th>المبلغ الإجمالي</th>
                        <th>المدفوع</th>
                        <th>المتبقي</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_amount = 0;
                    $total_paid = 0;
                    foreach ($data as $booking): 
                        $booking_total = $booking['total_amount'] ?? 0;
                        $booking_paid = $booking['total_paid'] ?? 0;
                        $remaining = $booking_total - $booking_paid;
                        $total_amount += $booking_total;
                        $total_paid += $booking_paid;
                    ?>
                    <tr>
                        <td><?= $booking['booking_id'] ?></td>
                        <td><?= htmlspecialchars($booking['guest_name']) ?></td>
                        <td><?= $booking['room_number'] ?></td>
                        <td><?= date('d/m/Y', strtotime($booking['checkin_date'])) ?></td>
                        <td><?= $booking['calculated_nights'] ?></td>
                        <td><?= number_format($booking_total) ?></td>
                        <td class="text-success"><?= number_format($booking_paid) ?></td>
                        <td class="<?= $remaining > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($remaining) ?></td>
                        <td>
                            <span class="badge <?= $booking['status'] == 'محجوزة' ? 'badge-success' : 'badge-secondary' ?>">
                                <?= $booking['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="5">الإجماليات</td>
                        <td><?= number_format($total_amount) ?></td>
                        <td class="text-success"><?= number_format($total_paid) ?></td>
                        <td class="<?= ($total_amount - $total_paid) > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($total_amount - $total_paid) ?></td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php elseif ($report_type === 'financial'): ?>
        <!-- التقرير المالي -->
        <div class="section">
            <div class="section-title">💰 التقرير المالي التفصيلي</div>
            
            <?php
            // دمج البيانات حسب التاريخ
            $financial_summary = [];
            
            foreach ($data['daily_revenue'] as $revenue) {
                $date = $revenue['date'];
                $financial_summary[$date]['revenue'] = $revenue['daily_revenue'];
            }
            
            foreach ($data['daily_expenses'] as $expense) {
                $date = $expense['date'];
                $financial_summary[$date]['expenses'] = $expense['daily_expenses'];
            }
            
            $total_revenue = 0;
            $total_expenses = 0;
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الإيرادات (ريال)</th>
                        <th>المصروفات (ريال)</th>
                        <th>صافي الربح (ريال)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($financial_summary as $date => $financial_data): 
                        $revenue = $financial_data['revenue'] ?? 0;
                        $expenses = $financial_data['expenses'] ?? 0;
                        $profit = $revenue - $expenses;
                        $total_revenue += $revenue;
                        $total_expenses += $expenses;
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($date)) ?></td>
                        <td class="text-success"><?= number_format($revenue) ?></td>
                        <td class="text-danger"><?= number_format($expenses) ?></td>
                        <td class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($profit) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td>الإجماليات</td>
                        <td class="text-success"><?= number_format($total_revenue) ?></td>
                        <td class="text-danger"><?= number_format($total_expenses) ?></td>
                        <td class="<?= ($total_revenue - $total_expenses) >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($total_revenue - $total_expenses) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php elseif ($report_type === 'rooms'): ?>
        <!-- تقرير الغرف -->
        <div class="section">
            <div class="section-title">🛏️ تقرير أداء الغرف</div>
            <table>
                <thead>
                    <tr>
                        <th>رقم الغرفة</th>
                        <th>نوع الغرفة</th>
                        <th>الحالة</th>
                        <th>عدد الحجوزات</th>
                        <th>الإيرادات (ريال)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_bookings = 0;
                    $total_revenue = 0;
                    foreach ($data as $room): 
                        $total_bookings += $room['booking_count'] ?? 0;
                        $total_revenue += $room['room_revenue'] ?? 0;
                    ?>
                    <tr>
                        <td><?= $room['room_number'] ?></td>
                        <td><?= $room['room_type'] ?></td>
                        <td>
                            <span class="badge <?= $room['status'] == 'شاغرة' ? 'badge-success' : 'badge-secondary' ?>">
                                <?= $room['status'] ?>
                            </span>
                        </td>
                        <td><?= $room['booking_count'] ?? 0 ?></td>
                        <td><?= number_format($room['room_revenue'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="3">الإجماليات</td>
                        <td><?= number_format($total_bookings) ?></td>
                        <td><?= number_format($total_revenue) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php elseif ($report_type === 'employees'): ?>
        <!-- تقرير الموظفين -->
        <div class="section">
            <div class="section-title">👤 تقرير الموظفين والسحوبات</div>
            <table>
                <thead>
                    <tr>
                        <th>اسم الموظف</th>
                        <th>المنصب</th>
                        <th>الراتب الأساسي</th>
                        <th>إجمالي السحوبات</th>
                        <th>الرصيد المتبقي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_salaries = 0;
                    $total_withdrawals = 0;
                    foreach ($data as $employee): 
                        $salary = $employee['basic_salary'] ?? 0;
                        $withdrawals = $employee['total_withdrawals'] ?? 0;
                        $balance = $salary - $withdrawals;
                        $total_salaries += $salary;
                        $total_withdrawals += $withdrawals;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($employee['employee_name']) ?></td>
                        <td><?= $employee['position'] ?? '-' ?></td>
                        <td><?= number_format($salary) ?></td>
                        <td class="text-warning"><?= number_format($withdrawals) ?></td>
                        <td class="<?= $balance >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($balance) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="2">الإجماليات</td>
                        <td><?= number_format($total_salaries) ?></td>
                        <td class="text-warning"><?= number_format($total_withdrawals) ?></td>
                        <td class="<?= ($total_salaries - $total_withdrawals) >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($total_salaries - $total_withdrawals) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php endif; ?>

    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام إدارة فندق مارينا | التاريخ: <?= date('d/m/Y H:i:s') ?> | الصفحة <span id="pageNum"></span></p>
    </div>

    <!-- زر طباعة -->
    <div class="no-print" style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <button onclick="window.print()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">
            🖨️ طباعة التقرير
        </button>
        <button onclick="window.close()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            ❌ إغلاق
        </button>
    </div>

    <script>
        // طباعة تلقائية عند فتح الصفحة
        window.onload = function() {
            // تأخير قصير للسماح للصفحة بالتحميل بالكامل
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
