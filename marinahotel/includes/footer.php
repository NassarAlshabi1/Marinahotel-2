</div>

<footer class="bg-light text-center text-muted py-3 mt-5">
    <div class="container">
        <p class="mb-0">
            &copy; <?= date('Y') ?> نظام إدارة فندق مارينا - جميع الحقوق محفوظة
        </p>
        <small>تم التطوير بواسطة فريق التطوير المحلي</small>
    </div>
</footer>

<!-- تحميل الإشعارات عند بداية تحميل الصفحة -->
<script>
// التحقق من توفر Bootstrap محليا
if (typeof bootstrap === 'undefined') {
    console.warn('Bootstrap غير محمل محليا');
}

// التحقق من توفر jQuery محليا  
if (typeof $ === 'undefined') {
    console.warn('jQuery غير محمل محليا');
}

// التحقق من توفر SweetAlert2 محليا
if (typeof Swal === 'undefined') {
    console.warn('SweetAlert2 غير محمل محليا');
}

// تفعيل tooltips إذا كان Bootstrap متاح
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>

</body>
</html>
