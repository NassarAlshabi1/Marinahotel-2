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
$export_type = $_GET['type'] ?? 'pdf';

// دالة لجلب البيانات المالية الموحدة (نفس الدالة من الداشبورد)
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
    
    // 3. سحوبات الرواتب
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
    
    // 4. تفاصيل المدفوعات
    $payments_detail_query = "
        SELECT 
            p.payment_date,
            p.amount,
            p.payment_method,
            p.revenue_type,
            b.guest_name,
            b.room_number
        FROM payment p
        JOIN bookings b ON p.booking_id = b.booking_id
        WHERE DATE(p.payment_date) BETWEEN ? AND ?
        ORDER BY p.payment_date DESC
    ";
    $stmt = $conn->prepare($payments_detail_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $payments_result = $stmt->get_result();
    
    $data['payments_detail'] = [];
    while ($row = $payments_result->fetch_assoc()) {
        $data['payments_detail'][] = $row;
    }
    
    // حساب صافي الربح
    $data['net_profit'] = $data['total_revenue'] - $data['total_expenses'] - $data['total_salaries'];
    
    return $data;
}

$financial_data = getUnifiedFinancialData($conn, $start_date, $end_date);

if ($export_type == 'pdf') {
    // تصدير PDF
    require_once '../../fpdf.php';
    
    class ArabicPDF extends FPDF {
        function Header() {
            $this->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
            $this->SetFont('DejaVu','',16);
            $this->Cell(0,10,'Marina Hotel - Unified Financial Report',0,1,'C');
            $this->Ln(10);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        }
    }
    
    $pdf = new ArabicPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    $pdf->SetFont('DejaVu','',12);
    
    // معلومات التقرير
    $pdf->Cell(0,10,'Report Period: ' . $start_date . ' to ' . $end_date,0,1);
    $pdf->Cell(0,10,'Generated on: ' . date('Y-m-d H:i:s'),0,1);
    $pdf->Ln(10);
    
    // الملخص المالي
    $pdf->SetFont('DejaVu','B',14);
    $pdf->Cell(0,10,'Financial Summary',0,1);
    $pdf->SetFont('DejaVu','',12);
    
    $pdf->Cell(0,8,'Total Revenue: ' . number_format($financial_data['total_revenue']) . ' YER',0,1);
    $pdf->Cell(0,8,'Total Expenses: ' . number_format($financial_data['total_expenses']) . ' YER',0,1);
    $pdf->Cell(0,8,'Total Salaries: ' . number_format($financial_data['total_salaries']) . ' YER',0,1);
    $pdf->Cell(0,8,'Net Profit: ' . number_format($financial_data['net_profit']) . ' YER',0,1);
    $pdf->Ln(10);
    
    // تفاصيل الإيرادات
    $pdf->SetFont('DejaVu','B',12);
    $pdf->Cell(0,10,'Revenue Details',0,1);
    $pdf->SetFont('DejaVu','',10);
    
    foreach ($financial_data['revenue'] as $revenue) {
        $pdf->Cell(0,6,$revenue['payment_method'] . ' - ' . $revenue['revenue_type'] . ': ' . number_format($revenue['total_revenue']) . ' YER',0,1);
    }
    
    $pdf->Ln(5);
    
    // تفاصيل المصروفات
    $pdf->SetFont('DejaVu','B',12);
    $pdf->Cell(0,10,'Expenses Details',0,1);
    $pdf->SetFont('DejaVu','',10);
    
    foreach ($financial_data['expenses'] as $expense) {
        $pdf->Cell(0,6,$expense['expense_type'] . ': ' . number_format($expense['total_expenses']) . ' YER',0,1);
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="unified_financial_report_' . $start_date . '_to_' . $end_date . '.pdf"');
    $pdf->Output('D', 'unified_financial_report_' . $start_date . '_to_' . $end_date . '.pdf');
    
} elseif ($export_type == 'excel') {
    // تصدير Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="unified_financial_report_' . $start_date . '_to_' . $end_date . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 8px; text-align: right; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .number { text-align: left; }
        </style>
    </head>
    <body>
        <h2>تقرير مالي موحد - فندق مارينا</h2>
        <p><strong>فترة التقرير:</strong> من <?= $start_date ?> إلى <?= $end_date ?></p>
        <p><strong>تاريخ الإنشاء:</strong> <?= date('Y-m-d H:i:s') ?></p>
        
        <h3>الملخص المالي</h3>
        <table>
            <tr>
                <th>البيان</th>
                <th>المبلغ (ريال يمني)</th>
            </tr>
            <tr>
                <td>إجمالي الإيرادات</td>
                <td class="number"><?= number_format($financial_data['total_revenue']) ?></td>
            </tr>
            <tr>
                <td>إجمالي المصروفات</td>
                <td class="number"><?= number_format($financial_data['total_expenses']) ?></td>
            </tr>
            <tr>
                <td>إجمالي الرواتب</td>
                <td class="number"><?= number_format($financial_data['total_salaries']) ?></td>
            </tr>
            <tr style="background-color: #e6f3ff;">
                <td><strong>صافي الربح</strong></td>
                <td class="number"><strong><?= number_format($financial_data['net_profit']) ?></strong></td>
            </tr>
        </table>
        
        <h3>تفاصيل الإيرادات</h3>
        <table>
            <tr>
                <th>طريقة الدفع</th>
                <th>نوع الإيراد</th>
                <th>المبلغ</th>
                <th>عدد المعاملات</th>
            </tr>
            <?php foreach ($financial_data['revenue'] as $revenue): ?>
            <tr>
                <td><?= htmlspecialchars($revenue['payment_method']) ?></td>
                <td><?= htmlspecialchars($revenue['revenue_type']) ?></td>
                <td class="number"><?= number_format($revenue['total_revenue']) ?></td>
                <td class="number"><?= $revenue['payment_count'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h3>تفاصيل المصروفات</h3>
        <table>
            <tr>
                <th>نوع المصروف</th>
                <th>المبلغ</th>
                <th>عدد المعاملات</th>
            </tr>
            <?php foreach ($financial_data['expenses'] as $expense): ?>
            <tr>
                <td><?= htmlspecialchars($expense['expense_type']) ?></td>
                <td class="number"><?= number_format($expense['total_expenses']) ?></td>
                <td class="number"><?= $expense['expense_count'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h3>تفاصيل المدفوعات</h3>
        <table>
            <tr>
                <th>التاريخ</th>
                <th>اسم النزيل</th>
                <th>رقم الغرفة</th>
                <th>المبلغ</th>
                <th>طريقة الدفع</th>
                <th>نوع الإيراد</th>
            </tr>
            <?php foreach ($financial_data['payments_detail'] as $payment): ?>
            <tr>
                <td><?= date('Y-m-d', strtotime($payment['payment_date'])) ?></td>
                <td><?= htmlspecialchars($payment['guest_name']) ?></td>
                <td><?= htmlspecialchars($payment['room_number']) ?></td>
                <td class="number"><?= number_format($payment['amount']) ?></td>
                <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                <td><?= htmlspecialchars($payment['revenue_type']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <?php if (!empty($financial_data['salary_withdrawals'])): ?>
        <h3>سحوبات الرواتب</h3>
        <table>
            <tr>
                <th>التاريخ</th>
                <th>اسم الموظف</th>
                <th>المبلغ</th>
            </tr>
            <?php foreach ($financial_data['salary_withdrawals'] as $salary): ?>
            <tr>
                <td><?= date('Y-m-d', strtotime($salary['date'])) ?></td>
                <td><?= htmlspecialchars($salary['employee_name']) ?></td>
                <td class="number"><?= number_format($salary['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
?>