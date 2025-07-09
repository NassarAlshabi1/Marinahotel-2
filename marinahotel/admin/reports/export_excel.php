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

// دالة إنشاء محتوى Excel
function generateExcelContent($data, $type, $start_date, $end_date) {
    $excel_content = '';
    $filename = '';
    
    switch ($type) {
        case 'overview':
            $filename = 'نظرة_عامة_' . date('Y-m-d_H-i-s') . '.csv';
            $excel_content = generateOverviewExcel($data, $start_date, $end_date);
            break;
            
        case 'bookings':
            $filename = 'تقرير_الحجوزات_' . date('Y-m-d_H-i-s') . '.csv';
            $excel_content = generateBookingsExcel($data);
            break;
            
        case 'financial':
            $filename = 'التقرير_المالي_' . date('Y-m-d_H-i-s') . '.csv';
            $excel_content = generateFinancialExcel($data);
            break;
            
        case 'rooms':
            $filename = 'تقرير_الغرف_' . date('Y-m-d_H-i-s') . '.csv';
            $excel_content = generateRoomsExcel($data);
            break;
            
        case 'employees':
            $filename = 'تقرير_الموظفين_' . date('Y-m-d_H-i-s') . '.csv';
            $excel_content = generateEmployeesExcel($data);
            break;
    }
    
    return [$excel_content, $filename];
}

// دالة تصدير النظرة العامة
function generateOverviewExcel($data, $start_date, $end_date) {
    $csv = "فندق مارينا - نظرة عامة\n";
    $csv .= "الفترة من: " . $start_date . " إلى: " . $end_date . "\n";
    $csv .= "تاريخ التقرير: " . date('Y-m-d H:i:s') . "\n\n";
    
    // إحصائيات الحجوزات
    $csv .= "إحصائيات الحجوزات\n";
    $csv .= "النوع,العدد\n";
    $csv .= "إجمالي الحجوزات," . ($data['bookings']['total_bookings'] ?? 0) . "\n";
    $csv .= "الحجوزات النشطة," . ($data['bookings']['active_bookings'] ?? 0) . "\n";
    $csv .= "الحجوزات المكتملة," . ($data['bookings']['completed_bookings'] ?? 0) . "\n\n";
    
    // الإحصائيات المالية
    $csv .= "الإحصائيات المالية\n";
    $csv .= "النوع,المبلغ (ريال)\n";
    $csv .= "إجمالي الإيرادات," . ($data['revenue']['total_revenue'] ?? 0) . "\n";
    $csv .= "إجمالي المصروفات," . ($data['expenses']['total_expenses'] ?? 0) . "\n";
    $profit = ($data['revenue']['total_revenue'] ?? 0) - ($data['expenses']['total_expenses'] ?? 0);
    $csv .= "صافي الربح," . $profit . "\n\n";
    
    // إحصائيات الغرف
    $csv .= "إحصائيات الغرف\n";
    $csv .= "النوع,العدد\n";
    $csv .= "إجمالي الغرف," . ($data['rooms']['total_rooms'] ?? 0) . "\n";
    $csv .= "الغرف المتاحة," . ($data['rooms']['available_rooms'] ?? 0) . "\n";
    $csv .= "الغرف المشغولة," . ($data['rooms']['occupied_rooms'] ?? 0) . "\n";
    
    if (($data['rooms']['total_rooms'] ?? 0) > 0) {
        $occupancy_rate = (($data['rooms']['occupied_rooms'] ?? 0) / $data['rooms']['total_rooms']) * 100;
        $csv .= "معدل الإشغال," . number_format($occupancy_rate, 2) . "%\n\n";
    }
    
    // إحصائيات الموظفين
    $csv .= "إحصائيات الموظفين\n";
    $csv .= "النوع,العدد/المبلغ\n";
    $csv .= "إجمالي الموظفين," . ($data['employees']['total_employees'] ?? 0) . "\n";
    $csv .= "إجمالي السحوبات," . ($data['employees']['total_withdrawals'] ?? 0) . " ريال\n";
    
    return $csv;
}

// دالة تصدير الحجوزات
function generateBookingsExcel($bookings) {
    $csv = "فندق مارينا - تقرير الحجوزات التفصيلي\n";
    $csv .= "تاريخ التقرير: " . date('Y-m-d H:i:s') . "\n\n";
    
    $csv .= "رقم الحجز,اسم النزيل,رقم الغرفة,تاريخ الوصول,تاريخ المغادرة,عدد الليالي,المبلغ الإجمالي,المبلغ المدفوع,المتبقي,الحالة,رقم الهاتف,الجنسية\n";
    
    foreach ($bookings as $booking) {
        $total_amount = $booking['total_amount'] ?? 0;
        $total_paid = $booking['total_paid'] ?? 0;
        $remaining = $total_amount - $total_paid;
        
        $csv .= $booking['booking_id'] . ",";
        $csv .= '"' . str_replace('"', '""', $booking['guest_name']) . '",';
        $csv .= $booking['room_number'] . ",";
        $csv .= date('d/m/Y', strtotime($booking['checkin_date'])) . ",";
        $csv .= ($booking['checkout_date'] ? date('d/m/Y', strtotime($booking['checkout_date'])) : '-') . ",";
        $csv .= $booking['calculated_nights'] . ",";
        $csv .= $total_amount . ",";
        $csv .= $total_paid . ",";
        $csv .= $remaining . ",";
        $csv .= $booking['status'] . ",";
        $csv .= ($booking['guest_phone'] ?? '') . ",";
        $csv .= ($booking['guest_nationality'] ?? '') . "\n";
    }
    
    return $csv;
}

// دالة تصدير التقرير المالي
function generateFinancialExcel($data) {
    $csv = "فندق مارينا - التقرير المالي التفصيلي\n";
    $csv .= "تاريخ التقرير: " . date('Y-m-d H:i:s') . "\n\n";
    
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
    
    $csv .= "التاريخ,الإيرادات (ريال),المصروفات (ريال),صافي الربح (ريال)\n";
    
    $total_revenue = 0;
    $total_expenses = 0;
    
    foreach ($financial_summary as $date => $financial_data) {
        $revenue = $financial_data['revenue'] ?? 0;
        $expenses = $financial_data['expenses'] ?? 0;
        $profit = $revenue - $expenses;
        
        $total_revenue += $revenue;
        $total_expenses += $expenses;
        
        $csv .= date('d/m/Y', strtotime($date)) . ",";
        $csv .= $revenue . ",";
        $csv .= $expenses . ",";
        $csv .= $profit . "\n";
    }
    
    // إضافة الإجماليات
    $csv .= "\nالإجماليات\n";
    $csv .= "إجمالي الإيرادات," . $total_revenue . "\n";
    $csv .= "إجمالي المصروفات," . $total_expenses . "\n";
    $csv .= "صافي الربح الإجمالي," . ($total_revenue - $total_expenses) . "\n";
    
    return $csv;
}

// دالة تصدير تقرير الغرف
function generateRoomsExcel($data) {
    $csv = "فندق مارينا - تقرير الغرف\n";
    $csv .= "تاريخ التقرير: " . date('Y-m-d H:i:s') . "\n\n";
    
    $csv .= "رقم الغرفة,نوع الغرفة,الحالة,عدد الحجوزات,الإيرادات (ريال)\n";
    
    foreach ($data as $room) {
        $csv .= $room['room_number'] . ",";
        $csv .= $room['room_type'] . ",";
        $csv .= $room['status'] . ",";
        $csv .= ($room['booking_count'] ?? 0) . ",";
        $csv .= ($room['room_revenue'] ?? 0) . "\n";
    }
    
    return $csv;
}

// دالة تصدير تقرير الموظفين
function generateEmployeesExcel($data) {
    $csv = "فندق مارينا - تقرير الموظفين\n";
    $csv .= "تاريخ التقرير: " . date('Y-m-d H:i:s') . "\n\n";
    
    $csv .= "اسم الموظف,المنصب,الراتب الأساسي,إجمالي السحوبات (ريال),الرصيد المتبقي\n";
    
    foreach ($data as $employee) {
        $csv .= '"' . str_replace('"', '""', $employee['employee_name']) . '",';
        $csv .= ($employee['position'] ?? '') . ",";
        $csv .= ($employee['basic_salary'] ?? 0) . ",";
        $csv .= ($employee['total_withdrawals'] ?? 0) . ",";
        $csv .= (($employee['basic_salary'] ?? 0) - ($employee['total_withdrawals'] ?? 0)) . "\n";
    }
    
    return $csv;
}

// جلب البيانات وإنشاء التقرير
try {
    // استخدام نفس دوال جلب البيانات من صفحة التقارير الرئيسية
    include_once '../reports.php';
    
    // جلب البيانات حسب نوع التقرير
    switch ($report_type) {
        case 'overview':
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
            // جلب تقرير الغرف
            $sql = "SELECT 
                        r.room_number,
                        r.type as room_type,
                        r.status,
                        COUNT(b.booking_id) as booking_count,
                        COALESCE(SUM(p.amount), 0) as room_revenue
                    FROM rooms r
                    LEFT JOIN bookings b ON r.room_number = b.room_number 
                        AND (b.checkin_date BETWEEN ? AND ? OR b.checkout_date BETWEEN ? AND ?)
                    LEFT JOIN payment p ON b.booking_id = p.booking_id
                    GROUP BY r.room_number
                    ORDER BY room_revenue DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'employees':
            // جلب تقرير الموظفين
            $sql = "SELECT 
                        e.name as employee_name,
                        e.position,
                        e.salary as basic_salary,
                        COALESCE(SUM(sw.amount), 0) as total_withdrawals
                    FROM employees e
                    LEFT JOIN salary_withdrawals sw ON e.id = sw.employee_id 
                        AND DATE(sw.date) BETWEEN ? AND ?
                    GROUP BY e.id
                    ORDER BY e.name";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        default:
            throw new Exception('نوع تقرير غير صحيح');
    }
    
    // إنشاء محتوى الـ Excel
    list($excel_content, $filename) = generateExcelContent($data, $report_type, $start_date, $end_date);
    
    // تعيين headers للتحميل
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // إضافة BOM للدعم العربي في Excel
    echo "\xEF\xBB\xBF";
    echo $excel_content;
    
} catch (Exception $e) {
    http_response_code(500);
    echo "خطأ في إنشاء التقرير: " . $e->getMessage();
}
?>
