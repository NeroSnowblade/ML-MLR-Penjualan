<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, email, full_name, role, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_active'] == 1) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Update last login
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Redirect to index
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Akun Anda tidak aktif. Hubungi administrator.';
                }
            } else {
                $error = 'Username/Email atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sales Prediction System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            max-width: 450px;
            width: 100%;
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(43, 71, 56, 0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            color: var(--secondary-cream);
            padding: 2rem;
            text-align: center;
        }
        .login-header h3 {
            margin: 0;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        .login-body {
            padding: 2.5rem;
        }
        .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 0.3rem rgba(74, 124, 89, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            color: var(--secondary-cream);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent-green) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(43, 71, 56, 0.4);
        }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-sage);
        }
        .register-link a {
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h3>Login</h3>
                <p>Sales Prediction System</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username atau Email</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-login w-100">Login</button>
                </form>

                <div class="register-link">
                    <p class="mb-0">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>