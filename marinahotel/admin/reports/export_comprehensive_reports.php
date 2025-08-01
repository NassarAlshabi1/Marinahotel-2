<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// التحقق من صلاحيات المستخدم
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$category = $_GET['category'] ?? 'overview';
$export_type = $_GET['type'] ?? 'pdf';

// دالة لجلب البيانات الشاملة
function getComprehensiveData($conn, $start_date, $end_date, $category) {
    $data = [];
    
    // البيانات الأساسية
    $summary_query = "
        SELECT 
            (SELECT SUM(amount) FROM payment WHERE DATE(payment_date) BETWEEN ? AND ?) as total_revenue,
            (SELECT SUM(amount) FROM expenses WHERE DATE(date) BETWEEN ? AND ?) as total_expenses,
            (SELECT SUM(amount) FROM salary_withdrawals WHERE DATE(date) BETWEEN ? AND ?) as total_salaries,
            (SELECT COUNT(*) FROM bookings WHERE DATE(checkin_date) BETWEEN ? AND ?) as total_bookings,
            (SELECT COUNT(DISTINCT room_number) FROM bookings WHERE status = 'محجوزة' AND DATE(checkin_date) BETWEEN ? AND ?) as occupied_rooms,
            (SELECT COUNT(*) FROM rooms) as total_rooms
    ";
    
    $stmt = $conn->prepare($summary_query);
    $stmt->bind_param("ssssssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $data['summary'] = $stmt->get_result()->fetch_assoc();
    
    // تفاصيل الإيرادات
    $revenue_detail_query = "
        SELECT 
            p.payment_date,
            p.amount,
            p.payment_method,
            p.revenue_type,
            b.guest_name,
            b.room_number,
            b.guest_nationality
        FROM payment p
        JOIN bookings b ON p.booking_id = b.booking_id
        WHERE DATE(p.payment_date) BETWEEN ? AND ?
        ORDER BY p.payment_date DESC
    ";
    $stmt = $conn->prepare($revenue_detail_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['revenue_details'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // تفاصيل المصروفات
    $expense_detail_query = "
        SELECT 
            e.date,
            e.amount,
            e.expense_type,
            e.description,
            s.name as supplier_name
        FROM expenses e
        LEFT JOIN suppliers s ON e.related_id = s.id AND e.expense_type = 'purchases'
        WHERE DATE(e.date) BETWEEN ? AND ?
        ORDER BY e.date DESC
    ";
    $stmt = $conn->prepare($expense_detail_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['expense_details'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // إحصائيات الغرف
    $room_stats_query = "
        SELECT 
            r.room_number,
            r.type,
            r.price,
            COUNT(b.booking_id) as booking_count,
            SUM(p.amount) as room_revenue,
            AVG(b.calculated_nights) as avg_nights
        FROM rooms r
        LEFT JOIN bookings b ON r.room_number = b.room_number 
            AND DATE(b.checkin_date) BETWEEN ? AND ?
        LEFT JOIN payment p ON b.booking_id = p.booking_id
        GROUP BY r.room_number, r.type, r.price
        ORDER BY room_revenue DESC
    ";
    $stmt = $conn->prepare($room_stats_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['room_stats'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // إحصائيات النزلاء
    $guest_stats_query = "
        SELECT 
            guest_nationality,
            COUNT(*) as guest_count,
            SUM(calculated_nights) as total_nights,
            AVG(calculated_nights) as avg_stay,
            SUM(p.amount) as nationality_revenue
        FROM bookings b
        LEFT JOIN payment p ON b.booking_id = p.booking_id
        WHERE DATE(b.checkin_date) BETWEEN ? AND ?
        GROUP BY guest_nationality
        ORDER BY guest_count DESC
    ";
    $stmt = $conn->prepare($guest_stats_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['guest_stats'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

$data = getComprehensiveData($conn, $start_date, $end_date, $category);

if ($export_type == 'pdf') {
    // تصدير PDF
    require_once '../../fpdf.php';
    
    class ArabicPDF extends FPDF {
        function Header() {
            $this->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
            $this->SetFont('DejaVu','B',16);
            $this->Cell(0,10,'Marina Hotel - Comprehensive Financial Report',0,1,'C');
            $this->SetFont('DejaVu','',12);
            $this->Cell(0,8,'Report Period: ' . $_GET['start_date'] . ' to ' . $_GET['end_date'],0,1,'C');
            $this->Cell(0,8,'Generated: ' . date('Y-m-d H:i:s'),0,1,'C');
            $this->Ln(10);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        }
        
        function SectionTitle($title) {
            $this->SetFont('DejaVu','B',14);
            $this->Cell(0,10,$title,0,1);
            $this->Ln(2);
        }
        
        function TableHeader($headers) {
            $this->SetFont('DejaVu','B',10);
            $this->SetFillColor(200,220,255);
            foreach($headers as $header) {
                $this->Cell(40,8,$header,1,0,'C',true);
            }
            $this->Ln();
        }
        
        function TableRow($data) {
            $this->SetFont('DejaVu','',9);
            foreach($data as $item) {
                $this->Cell(40,6,$item,1,0,'C');
            }
            $this->Ln();
        }
    }
    
    $pdf = new ArabicPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    
    // الملخص المالي
    $pdf->SectionTitle('Financial Summary');
    $pdf->SetFont('DejaVu','',12);
    $pdf->Cell(0,8,'Total Revenue: ' . number_format($data['summary']['total_revenue']) . ' YER',0,1);
    $pdf->Cell(0,8,'Total Expenses: ' . number_format($data['summary']['total_expenses']) . ' YER',0,1);
    $pdf->Cell(0,8,'Total Salaries: ' . number_format($data['summary']['total_salaries']) . ' YER',0,1);
    $pdf->Cell(0,8,'Net Profit: ' . number_format($data['summary']['total_revenue'] - $data['summary']['total_expenses'] - $data['summary']['total_salaries']) . ' YER',0,1);
    $pdf->Cell(0,8,'Total Bookings: ' . $data['summary']['total_bookings'],0,1);
    $pdf->Cell(0,8,'Occupancy Rate: ' . number_format(($data['summary']['occupied_rooms'] / $data['summary']['total_rooms']) * 100, 1) . '%',0,1);
    $pdf->Ln(10);
    
    // إحصائيات الغرف
    $pdf->SectionTitle('Room Performance');
    $pdf->TableHeader(['Room', 'Type', 'Bookings', 'Revenue', 'Avg Nights']);
    foreach(array_slice($data['room_stats'], 0, 15) as $room) {
        $pdf->TableRow([
            $room['room_number'],
            substr($room['type'], 0, 10),
            $room['booking_count'],
            number_format($room['room_revenue']),
            number_format($room['avg_nights'], 1)
        ]);
    }
    
    $pdf->AddPage();
    
    // إحصائيات النزلاء
    $pdf->SectionTitle('Guest Statistics');
    $pdf->TableHeader(['Nationality', 'Count', 'Total Nights', 'Avg Stay', 'Revenue']);
    foreach($data['guest_stats'] as $guest) {
        $pdf->TableRow([
            substr($guest['guest_nationality'], 0, 10),
            $guest['guest_count'],
            $guest['total_nights'],
            number_format($guest['avg_stay'], 1),
            number_format($guest['nationality_revenue'])
        ]);
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="comprehensive_report_' . $start_date . '_to_' . $end_date . '.pdf"');
    $pdf->Output('D', 'comprehensive_report_' . $start_date . '_to_' . $end_date . '.pdf');
    
} elseif ($export_type == 'excel') {
    // تصدير Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="comprehensive_report_' . $start_date . '_to_' . $end_date . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: right; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .number { text-align: left; }
            .section-title { font-size: 16px; font-weight: bold; margin: 20px 0 10px 0; }
        </style>
    </head>
    <body>
        <h1>تقرير مالي شامل - فندق مارينا</h1>
        <p><strong>فترة التقرير:</strong> من <?= $start_date ?> إلى <?= $end_date ?></p>
        <p><strong>تاريخ الإنشاء:</strong> <?= date('Y-m-d H:i:s') ?></p>
        
        <div class="section-title">الملخص المالي</div>
        <table>
            <tr>
                <th>البيان</th>
                <th>القيمة</th>
            </tr>
            <tr>
                <td>إجمالي الإيرادات</td>
                <td class="number"><?= number_format($data['summary']['total_revenue']) ?> ريال</td>
            </tr>
            <tr>
                <td>إجمالي المصروفات</td>
                <td class="number"><?= number_format($data['summary']['total_expenses']) ?> ريال</td>
            </tr>
            <tr>
                <td>إجمالي الرواتب</td>
                <td class="number"><?= number_format($data['summary']['total_salaries']) ?> ريال</td>
            </tr>
            <tr style="background-color: #e6f3ff;">
                <td><strong>صافي الربح</strong></td>
                <td class="number"><strong><?= number_format($data['summary']['total_revenue'] - $data['summary']['total_expenses'] - $data['summary']['total_salaries']) ?> ريال</strong></td>
            </tr>
            <tr>
                <td>إجمالي الحجوزات</td>
                <td class="number"><?= $data['summary']['total_bookings'] ?></td>
            </tr>
            <tr>
                <td>معدل الإشغال</td>
                <td class="number"><?= number_format(($data['summary']['occupied_rooms'] / $data['summary']['total_rooms']) * 100, 1) ?>%</td>
            </tr>
        </table>
        
        <div class="section-title">أداء الغرف</div>
        <table>
            <tr>
                <th>رقم الغرفة</th>
                <th>النوع</th>
                <th>السعر</th>
                <th>عدد الحجوزات</th>
                <th>الإيراد</th>
                <th>متوسط الليالي</th>
            </tr>
            <?php foreach($data['room_stats'] as $room): ?>
            <tr>
                <td><?= htmlspecialchars($room['room_number']) ?></td>
                <td><?= htmlspecialchars($room['type']) ?></td>
                <td class="number"><?= number_format($room['price']) ?></td>
                <td class="number"><?= $room['booking_count'] ?></td>
                <td class="number"><?= number_format($room['room_revenue']) ?></td>
                <td class="number"><?= number_format($room['avg_nights'], 1) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="section-title">إحصائيات النزلاء</div>
        <table>
            <tr>
                <th>الجنسية</th>
                <th>عدد النزلاء</th>
                <th>إجمالي الليالي</th>
                <th>متوسط الإقامة</th>
                <th>الإيراد</th>
            </tr>
            <?php foreach($data['guest_stats'] as $guest): ?>
            <tr>
                <td><?= htmlspecialchars($guest['guest_nationality']) ?></td>
                <td class="number"><?= $guest['guest_count'] ?></td>
                <td class="number"><?= $guest['total_nights'] ?></td>
                <td class="number"><?= number_format($guest['avg_stay'], 1) ?></td>
                <td class="number"><?= number_format($guest['nationality_revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="section-title">تفاصيل الإيرادات</div>
        <table>
            <tr>
                <th>التاريخ</th>
                <th>اسم النزيل</th>
                <th>رقم الغرفة</th>
                <th>المبلغ</th>
                <th>طريقة الدفع</th>
                <th>نوع الإيراد</th>
                <th>الجنسية</th>
            </tr>
            <?php foreach(array_slice($data['revenue_details'], 0, 100) as $payment): ?>
            <tr>
                <td><?= date('Y-m-d', strtotime($payment['payment_date'])) ?></td>
                <td><?= htmlspecialchars($payment['guest_name']) ?></td>
                <td><?= htmlspecialchars($payment['room_number']) ?></td>
                <td class="number"><?= number_format($payment['amount']) ?></td>
                <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                <td><?= htmlspecialchars($payment['revenue_type']) ?></td>
                <td><?= htmlspecialchars($payment['guest_nationality']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="section-title">تفاصيل المصروفات</div>
        <table>
            <tr>
                <th>التاريخ</th>
                <th>النوع</th>
                <th>الوصف</th>
                <th>المورد</th>
                <th>المبلغ</th>
            </tr>
            <?php foreach(array_slice($data['expense_details'], 0, 100) as $expense): ?>
            <tr>
                <td><?= date('Y-m-d', strtotime($expense['date'])) ?></td>
                <td><?= htmlspecialchars($expense['expense_type']) ?></td>
                <td><?= htmlspecialchars($expense['description']) ?></td>
                <td><?= htmlspecialchars($expense['supplier_name'] ?? 'غير محدد') ?></td>
                <td class="number"><?= number_format($expense['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </body>
    </html>
    <?php
}
?>