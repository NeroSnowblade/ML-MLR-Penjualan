<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">Prediksi Penjualan - Single Variable Regression</span>
        <div class="d-flex align-items-center">
            <span class="text-white me-3">
                👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <span class="badge bg-warning text-dark ms-1">Admin</span>
                <?php endif; ?>
            </span>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">Multiple Variable</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>