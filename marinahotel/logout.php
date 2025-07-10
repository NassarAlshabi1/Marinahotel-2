<?php
session_start();
session_destroy();
header('Location: login.php?message=' . urlencode('تم تسجيل الخروج بنجاح'));
exit;
?>
