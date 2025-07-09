<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// معالجة إعادة الإرسال
if (isset($_POST['retry_message'])) {
    $message_id = intval($_POST['message_id']);
    
    // جلب تفاصيل الرسالة
    $sql = "SELECT * FROM whatsapp_messages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($message = $result->fetch_assoc()) {
        if (attempt_immediate_send($message['phone'], $message['message'])) {
            update_message_status($message_id, 'sent');
            $_SESSION['success'] = 'تم إرسال الرسالة بنجاح';
        } else {
            increment_retry_count($message_id);
            $_SESSION['error'] = 'فشل في إرسال الرسالة';
        }
    }
    
    header('Location: whatsapp_manager.php');
    exit;
}

// جلب إحصائيات الرسائل
$stats_sql = "
    SELECT 
        status,
        COUNT(*) as count,
        MAX(created_at) as latest
    FROM whatsapp_messages 
    GROUP BY status
";
$stats_result = $conn->query($stats_sql);
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['status']] = $row;
}

// جلب الرسائل المعلقة
$pending_sql = "
    SELECT wm.*, b.guest_name 
    FROM whatsapp_messages wm
    LEFT JOIN bookings b ON wm.booking_id = b.booking_id
    WHERE wm.status = 'pending'
    ORDER BY wm.created_at DESC
    LIMIT 50
";
$pending_result = $conn->query($pending_sql);

// جلب آخر الرسائل المرسلة
$sent_sql = "
    SELECT wm.*, b.guest_name 
    FROM whatsapp_messages wm
    LEFT JOIN bookings b ON wm.booking_id = b.booking_id
    WHERE wm.status = 'sent'
    ORDER BY wm.sent_at DESC
    LIMIT 20
";
$sent_result = $conn->query($sent_sql);

// جلب الرسائل الفاشلة
$failed_sql = "
    SELECT wm.*, b.guest_name 
    FROM whatsapp_messages wm
    LEFT JOIN bookings b ON wm.booking_id = b.booking_id
    WHERE wm.status = 'failed'
    ORDER BY wm.created_at DESC
    LIMIT 20
";
$failed_result = $conn->query($failed_sql);
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title mb-0">
                    <i class="fab fa-whatsapp me-2"></i> إدارة رسائل الواتساب
                </h1>
            </div>
            <div class="card-body">
                <!-- إحصائيات سريعة -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="fas fa-clock me-2"></i> معلقة
                                </h5>
                                <h2 class="display-4"><?= $stats['pending']['count'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="fas fa-check me-2"></i> مرسلة
                                </h5>
                                <h2 class="display-4"><?= $stats['sent']['count'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="fas fa-times me-2"></i> فاشلة
                                </h5>
                                <h2 class="display-4"><?= $stats['failed']['count'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="fas fa-cog me-2"></i> العمليات
                                </h5>
                                <button onclick="processQueue()" class="btn btn-light btn-sm">
                                    <i class="fas fa-play"></i> معالجة الطابور
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- التبويبات -->
                <ul class="nav nav-tabs" id="whatsappTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                            <i class="fas fa-clock"></i> الرسائل المعلقة
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab">
                            <i class="fas fa-check"></i> الرسائل المرسلة
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="failed-tab" data-bs-toggle="tab" data-bs-target="#failed" type="button" role="tab">
                            <i class="fas fa-times"></i> الرسائل الفاشلة
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="whatsappTabsContent">
                    <!-- الرسائل المعلقة -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>رقم الهاتف</th>
                                    <th>اسم العميل</th>
                                    <th>الرسالة</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>المحاولات</th>
                                    <th>الإجراءات</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                                    <?php while ($message = $pending_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($message['phone']) ?></td>
                                            <td><?= htmlspecialchars($message['guest_name'] ?? 'غير محدد') ?></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($message['message']) ?>">
                                                    <?= htmlspecialchars(substr($message['message'], 0, 50)) ?>...
                                                </div>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-warning"><?= $message['retry_count'] ?></span>
                                            </td>
                                            <td>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                    <button type="submit" name="retry_message" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-redo"></i> إعادة المحاولة
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">لا توجد رسائل معلقة</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- الرسائل المرسلة -->
                    <div class="tab-pane fade" id="sent" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>رقم الهاتف</th>
                                    <th>اسم العميل</th>
                                    <th>الرسالة</th>
                                    <th>تاريخ الإرسال</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($sent_result && $sent_result->num_rows > 0): ?>
                                    <?php while ($message = $sent_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($message['phone']) ?></td>
                                            <td><?= htmlspecialchars($message['guest_name'] ?? 'غير محدد') ?></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($message['message']) ?>">
                                                    <?= htmlspecialchars(substr($message['message'], 0, 50)) ?>...
                                                </div>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($message['sent_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">لا توجد رسائل مرسلة</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- الرسائل الفاشلة -->
                    <div class="tab-pane fade" id="failed" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>رقم الهاتف</th>
                                    <th>اسم العميل</th>
                                    <th>الرسالة</th>
                                    <th>رسالة الخطأ</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>الإجراءات</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($failed_result && $failed_result->num_rows > 0): ?>
                                    <?php while ($message = $failed_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($message['phone']) ?></td>
                                            <td><?= htmlspecialchars($message['guest_name'] ?? 'غير محدد') ?></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($message['message']) ?>">
                                                    <?= htmlspecialchars(substr($message['message'], 0, 30)) ?>...
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-danger"><?= htmlspecialchars($message['error_message'] ?? 'خطأ غير محدد') ?></small>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></td>
                                            <td>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                    <button type="submit" name="retry_message" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-redo"></i> إعادة المحاولة
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">لا توجد رسائل فاشلة</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function processQueue() {
    Swal.fire({
        title: 'معالجة طابور الرسائل',
        text: 'هل تريد معالجة جميع الرسائل المعلقة؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، معالجة',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            // إظهار مؤشر التحميل
            Swal.fire({
                title: 'جاري المعالجة...',
                text: 'يرجى الانتظار',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // تشغيل معالج الطابور
            fetch('../process_whatsapp_queue.php')
                .then(response => response.text())
                .then(data => {
                    // استخراج النتائج من النص المعاد
                    const lines = data.split('\n');
                    let processed = 0, sent = 0, invalid = 0, pending = 0;
                    
                    lines.forEach(line => {
                        if (line.includes('تم معالجة:')) {
                            processed = parseInt(line.match(/\d+/)[0]);
                        } else if (line.includes('تم إرسال:')) {
                            sent = parseInt(line.match(/\d+/)[0]);
                        } else if (line.includes('رسائل غير صالحة:')) {
                            invalid = parseInt(line.match(/\d+/)[0]);
                        } else if (line.includes('رسائل معلقة:')) {
                            pending = parseInt(line.match(/\d+/)[0]);
                        }
                    });
                    
                    const resultText = `تمت المعالجة بنجاح:
• تم معالجة: ${processed} رسالة
• تم إرسال: ${sent} رسالة  
• رسائل غير صالحة: ${invalid}
• رسائل معلقة: ${pending}`;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'تمت المعالجة',
                        html: resultText.replace(/\n/g, '<br>'),
                        confirmButtonText: 'موافق',
                        width: 600
                    }).then(() => {
                        location.reload();
                    });
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'حدث خطأ أثناء المعالجة',
                        confirmButtonText: 'موافق'
                    });
                });
        }
    });
}

// تحديث تلقائي كل دقيقة
setInterval(() => {
    location.reload();
}, 60000);
</script>

<?php require_once '../includes/footer.php'; ?>