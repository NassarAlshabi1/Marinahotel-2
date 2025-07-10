<?php
session_start();

// إعادة توجيه المستخدم إذا كان مسجل دخول بالفعل
if (isset($_SESSION['user_id'])) {
    header('Location: admin/dash.php');
    exit;
}

$error = '';
$success = '';

// عرض رسائل من URL parameters
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
if (isset($_GET['message'])) {
    $success = htmlspecialchars($_GET['message']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // تسجيل الدخول البسيط
    if ($username === 'admin' && $password === '1234') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_type'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['initiated'] = true;
        
        header('Location: admin/dash.php');
        exit;
    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة الفندق - تسجيل الدخول</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #1a4b88;
            --secondary-color: #f8b400;
            --accent-color: #28a745;
            --error-color: #dc3545;
            --text-color: #333;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', 'Arial', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('hotel-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            direction: rtl;
        }

        .login-container {
            width: 90%;
            max-width: 450px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }

        .logo i {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
        }

        .logo h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .logo p {
            color: var(--text-color);
            font-size: 0.9rem;
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .error-message {
            color: var(--error-color);
            margin-bottom: 1rem;
            font-weight: bold;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        .success-message {
            color: var(--accent-color);
            margin-bottom: 1rem;
            font-weight: bold;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: right;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 75, 136, 0.2);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0d3a6e;
        }

        .btn i {
            margin-left: 8px;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
                width: 95%;
            }

            .logo h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-hotel"></i>
            <h1>نظام إدارة فندق مارينا بلازا</h1>
            <p>منصة الإدارة المتكاملة لخدمات الفندق</p>
        </div>

        <h2>تسجيل الدخول للنظام</h2>

        <?php if (!empty($error)): ?>
            <div class='error-message'><i class='fas fa-exclamation-circle'></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class='success-message'><i class='fas fa-check-circle'></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> اسم المستخدم</label>
                <input type="text" id="username" name="username" required placeholder="أدخل اسم المستخدم">
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> كلمة المرور</label>
                <input type="password" id="password" name="password" required placeholder="أدخل كلمة المرور">
            </div>

            <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
