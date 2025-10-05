<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } else {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan!';
            } else {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email sudah terdaftar!';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'user')");
                    $stmt->execute([$username, $hashed_password, $email, $full_name]);
                    
                    $success = 'Registrasi berhasil! Silakan login.';
                }
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
    <title>Register - Sales Prediction System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            max-width: 500px;
            width: 100%;
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(43, 71, 56, 0.3);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--accent-sage) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .register-header h3 {
            margin: 0;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .register-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        .register-body {
            padding: 2.5rem;
        }
        .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 0.3rem rgba(74, 124, 89, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--accent-sage) 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent-green) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(74, 124, 89, 0.4);
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-sage);
        }
        .login-link a {
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 0.85rem;
            color: var(--accent-olive);
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h3>📝 Registrasi</h3>
                <p>Buat Akun Baru</p>
            </div>
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                               required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required>
                        <small class="text-muted">Minimal 4 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-register w-100">Daftar</button>
                </form>

                <div class="login-link">
                    <p class="mb-0">Sudah punya akun? <a href="login.php">Login di sini</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>