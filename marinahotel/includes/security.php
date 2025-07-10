THIS SHOULD BE A LINTER ERROR<?php
/**
 * ملف الحماية والأمان
 * يحتوي على وظائف حماية CSRF وفلترة المدخلات وحماية الجلسات
 * 
 * @package Marina Hotel Management System
 * @version 2.0.0
 * @author فريق التطوير المحلي
 */

// منع الوصول المباشر للملف
if (!defined('ACCESS_ALLOWED')) {
    define('ACCESS_ALLOWED', true);
}

/**
 * فئة الحماية الرئيسية
 */
class SecurityManager {
    
    private static $instance = null;
    private $sessionSecure = true;
    private $csrfTokenName = 'csrf_token';
    private $sessionTimeout = 3600; // ساعة واحدة
    
    /**
     * الحصول على مثيل واحد من الفئة (Singleton Pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تهيئة إعدادات الحماية
     */
    public function __construct() {
        $this->initializeSession();
        $this->setupSecurityHeaders();
        $this->checkSessionTimeout();
    }
    
    /**
     * تهيئة الجلسة الآمنة
     */
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // إعدادات الجلسة الآمنة
            ini_set('session.cookie_secure', $this->sessionSecure ? '1' : '0');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            
            // تحديد مدة انتهاء الجلسة
            ini_set('session.gc_maxlifetime', $this->sessionTimeout);
            ini_set('session.cookie_lifetime', $this->sessionTimeout);
            
            session_start();
            
            // تجديد ID الجلسة دورياً لمنع Session Fixation
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) { // 30 دقيقة
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * إعداد رؤوس الحماية
     */
    private function setupSecurityHeaders() {
        // منع إدراج الصفحة في إطار (Clickjacking Protection)
        header('X-Frame-Options: DENY');
        
        // منع استنتاج نوع المحتوى
        header('X-Content-Type-Options: nosniff');
        
        // تمكين حماية XSS المدمجة في المتصفح
        header('X-XSS-Protection: 1; mode=block');
        
        // سياسة الأمان للمحتوى
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'");
        
        // إخفاء إصدار الخادم
        header_remove('X-Powered-By');
        
        // سياسة الإحالة
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // منع التخزين المؤقت للصفحات الحساسة
        if (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * فحص انتهاء صلاحية الجلسة
     */
    private function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive >= $this->sessionTimeout) {
                $this->destroySession();
                $this->redirectToLogin('انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى');
            }
        }
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * إنشاء CSRF Token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION[$this->csrfTokenName])) {
            $_SESSION[$this->csrfTokenName] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$this->csrfTokenName];
    }
    
    /**
     * التحقق من صحة CSRF Token
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION[$this->csrfTokenName])) {
            return false;
        }
        
        return hash_equals($_SESSION[$this->csrfTokenName], $token);
    }
    
    /**
     * إنشاء حقل CSRF مخفي للنماذج
     */
    public function getCSRFField() {
        $token = $this->generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * فلترة وتنظيف المدخلات
     */
    public function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return $this->sanitizeInput($item, $type);
            }, $input);
        }
        
        // إزالة المسافات الزائدة
        $input = trim($input);
        
        switch ($type) {
            case 'string':
                // تنظيف النصوص العامة
                $input = filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
                break;
                
            case 'email':
                // تنظيف البريد الإلكتروني
                $input = filter_var($input, FILTER_SANITIZE_EMAIL);
                break;
                
            case 'int':
                // تنظيف الأرقام الصحيحة
                $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                $input = (int) $input;
                break;
                
            case 'float':
                // تنظيف الأرقام العشرية
                $input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $input = (float) $input;
                break;
                
            case 'url':
                // تنظيف الروابط
                $input = filter_var($input, FILTER_SANITIZE_URL);
                break;
                
            case 'phone':
                // تنظيف أرقام الهواتف
                $input = preg_replace('/[^0-9+\-\s\(\)]/', '', $input);
                break;
                
            case 'name':
                // تنظيف الأسماء (حروف عربية وإنجليزية ومسافات فقط)
                $input = preg_replace('/[^a-zA-Z\u0600-\u06FF\s]/', '', $input);
                $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
                break;
                
            case 'date':
                // تنظيف التواريخ
                $input = preg_replace('/[^0-9\-\/]/', '', $input);
                break;
                
            case 'sql':
                // حماية من SQL Injection
                global $conn;
                if (isset($conn)) {
                    $input = mysqli_real_escape_string($conn, $input);
                }
                $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
                break;
                
            default:
                // التنظيف الافتراضي
                $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        
        return $input;
    }
    
    /**
     * التحقق من صحة المدخلات
     */
    public function validateInput($input, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = isset($input[$field]) ? $input[$field] : '';
            
            foreach ($fieldRules as $rule => $ruleValue) {
                switch ($rule) {
                    case 'required':
                        if ($ruleValue && empty($value)) {
                            $errors[$field][] = "حقل {$field} مطلوب";
                        }
                        break;
                        
                    case 'min':
                        if (strlen($value) < $ruleValue) {
                            $errors[$field][] = "حقل {$field} يجب أن يكون على الأقل {$ruleValue} أحرف";
                        }
                        break;
                        
                    case 'max':
                        if (strlen($value) > $ruleValue) {
                            $errors[$field][] = "حقل {$field} يجب ألا يزيد عن {$ruleValue} أحرف";
                        }
                        break;
                        
                    case 'email':
                        if ($ruleValue && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "حقل {$field} يجب أن يكون بريد إلكتروني صحيح";
                        }
                        break;
                        
                    case 'numeric':
                        if ($ruleValue && !is_numeric($value)) {
                            $errors[$field][] = "حقل {$field} يجب أن يكون رقماً";
                        }
                        break;
                        
                    case 'phone':
                        if ($ruleValue && !preg_match('/^[\d\+\-\s\(\)]{10,15}$/', $value)) {
                            $errors[$field][] = "حقل {$field} يجب أن يكون رقم هاتف صحيح";
                        }
                        break;
                        
                    case 'date':
                        if ($ruleValue && !strtotime($value)) {
                            $errors[$field][] = "حقل {$field} يجب أن يكون تاريخ صحيح";
                        }
                        break;
                        
                    case 'match':
                        if (isset($input[$ruleValue]) && $value !== $input[$ruleValue]) {
                            $errors[$field][] = "حقل {$field} غير متطابق";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * حماية من هجمات Brute Force
     */
    public function checkBruteForce($identifier, $maxAttempts = 5, $timeWindow = 900) {
        $key = 'login_attempts_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
        }
        
        $attempts = $_SESSION[$key];
        
        // إعادة تعيين العداد إذا انقضى الوقت المحدد
        if (time() - $attempts['last_attempt'] > $timeWindow) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
            return true;
        }
        
        // فحص إذا تم تجاوز العدد المسموح
        if ($attempts['count'] >= $maxAttempts) {
            return false;
        }
        
        return true;
    }
    
    /**
     * تسجيل محاولة فاشلة
     */
    public function recordFailedAttempt($identifier) {
        $key = 'login_attempts_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
        }
        
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();
    }
    
    /**
     * مسح محاولات تسجيل الدخول
     */
    public function clearLoginAttempts($identifier) {
        $key = 'login_attempts_' . md5($identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * تشفير كلمة المرور
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 1024,
            'time_cost' => 2,
            'threads' => 2
        ]);
    }
    
    /**
     * التحقق من كلمة المرور
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * إنشاء كلمة مرور عشوائية آمنة
     */
    public function generateSecurePassword($length = 12) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $password;
    }
    
    /**
     * تسجيل أحداث الأمان
     */
    public function logSecurityEvent($event, $details = []) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details,
            'session_id' => session_id()
        ];
        
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($log, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * فحص الصلاحيات
     */
    public function checkPermission($permission) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['permissions'])) {
            return false;
        }
        
        $userPermissions = $_SESSION['permissions'];
        
        if (in_array('admin', $userPermissions) || in_array($permission, $userPermissions)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * التحقق من عنوان IP المسموح
     */
    public function isIPAllowed($ip = null) {
        if ($ip === null) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        // قائمة عناوين IP المحظورة (يمكن تخصيصها)
        $blockedIPs = [
            // أضف عناوين IP المحظورة هنا
        ];
        
        // قائمة عناوين IP المسموحة (إذا كانت فارغة فجميع العناوين مسموحة)
        $allowedIPs = [
            // أضف عناوين IP المسموحة هنا إذا أردت تقييد الوصول
        ];
        
        // فحص القائمة المحظورة
        if (in_array($ip, $blockedIPs)) {
            return false;
        }
        
        // فحص القائمة المسموحة (إذا كانت محددة)
        if (!empty($allowedIPs) && !in_array($ip, $allowedIPs)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * إنهاء الجلسة بأمان
     */
    public function destroySession() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * إعادة توجيه لصفحة تسجيل الدخول
     */
    public function redirectToLogin($message = '') {
        if (!empty($message)) {
            $_SESSION['login_message'] = $message;
        }
        
        $loginUrl = '/marinahotel/login.php';
        header("Location: $loginUrl");
        exit();
    }
    
    /**
     * فحص التوقيع الرقمي للملفات المهمة
     */
    public function verifyFileIntegrity($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $checksumFile = $filePath . '.checksum';
        
        if (!file_exists($checksumFile)) {
            // إنشاء checksum للملف
            $checksum = hash_file('sha256', $filePath);
            file_put_contents($checksumFile, $checksum);
            return true;
        }
        
        $storedChecksum = file_get_contents($checksumFile);
        $currentChecksum = hash_file('sha256', $filePath);
        
        return hash_equals($storedChecksum, $currentChecksum);
    }
    
    /**
     * تنظيف البيانات القديمة من الجلسات
     */
    public function cleanupOldSessions() {
        $sessionPath = session_save_path();
        if (empty($sessionPath)) {
            $sessionPath = sys_get_temp_dir();
        }
        
        $files = glob($sessionPath . '/sess_*');
        $maxLifetime = $this->sessionTimeout;
        
        foreach ($files as $file) {
            if (filemtime($file) + $maxLifetime < time()) {
                unlink($file);
            }
        }
    }
}

// إنشاء مثيل من إدارة الحماية
$security = SecurityManager::getInstance();

// دوال مساعدة للوصول السريع

/**
 * إنشاء CSRF token
 */
function csrf_token() {
    global $security;
    return $security->generateCSRFToken();
}

/**
 * التحقق من CSRF token
 */
function verify_csrf_token($token) {
    global $security;
    return $security->validateCSRFToken($token);
}

/**
 * إنشاء حقل CSRF للنماذج
 */
function csrf_field() {
    global $security;
    return $security->getCSRFField();
}

/**
 * تنظيف المدخلات
 */
function clean_input($input, $type = 'string') {
    global $security;
    return $security->sanitizeInput($input, $type);
}

/**
 * التحقق من الصلاحيات
 */
function has_permission($permission) {
    global $security;
    return $security->checkPermission($permission);
}

/**
 * تسجيل حدث أمني
 */
function log_security_event($event, $details = []) {
    global $security;
    $security->logSecurityEvent($event, $details);
}

/**
 * فحص طلب POST مع حماية CSRF
 */
function verify_post_request() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die('Method Not Allowed');
    }
    
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        log_security_event('csrf_validation_failed', [
            'url' => $_SERVER['REQUEST_URI'],
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        ]);
        http_response_code(403);
        die('CSRF Token Validation Failed');
    }
    
    return true;
}

/**
 * التحقق من تسجيل الدخول
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        global $security;
        $security->redirectToLogin('يجب تسجيل الدخول أولاً');
    }
}

/**
 * التحقق من صلاحية محددة
 */
function require_permission($permission) {
    require_login();
    
    if (!has_permission($permission)) {
        http_response_code(403);
        log_security_event('permission_denied', [
            'required_permission' => $permission,
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        die('ليس لديك صلاحية للوصول إلى هذه الصفحة');
    }
}

/**
 * معالجة رفع الملفات بأمان
 */
function secure_file_upload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
    global $security;
    
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'لم يتم اختيار ملف'];
    }
    
    // فحص حجم الملف
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'حجم الملف كبير جداً'];
    }
    
    // فحص نوع الملف
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'error' => 'نوع الملف غير مسموح'];
    }
    
    // فحص إضافي لنوع الملف باستخدام MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf'
    ];
    
    if (!isset($allowedMimes[$fileExt]) || $mimeType !== $allowedMimes[$fileExt]) {
        return ['success' => false, 'error' => 'نوع الملف غير صحيح'];
    }
    
    // إنشاء اسم ملف آمن
    $safeName = bin2hex(random_bytes(16)) . '.' . $fileExt;
    $uploadDir = __DIR__ . '/../uploads/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $safeName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true,
            'filename' => $safeName,
            'path' => $uploadPath,
            'original_name' => $file['name']
        ];
    }
    
    return ['success' => false, 'error' => 'فشل في رفع الملف'];
}

// تنظيف الجلسات القديمة كل ساعة
if (random_int(1, 100) === 1) {
    $security->cleanupOldSessions();
}

// تسجيل بداية الجلسة
if (!isset($_SESSION['session_logged'])) {
    log_security_event('session_started', [
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    $_SESSION['session_logged'] = true;
}
?>
