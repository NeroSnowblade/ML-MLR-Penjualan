<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">Sistem Prediksi Penjualan - Multiple Regression</span>
        <div class="d-flex align-items-center">
            <span class="text-white me-3">
                👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <span class="badge bg-warning text-dark ms-1">Admin</span>
                <?php endif; ?>
            </span>
            <a href="SLR.php" class="btn btn-outline-light btn-sm me-2">Single Variable</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>