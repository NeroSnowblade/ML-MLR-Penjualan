<?php
// index.php - View Only
require_once 'process_index.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediksi Penjualan - Multiple Linear Regression</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php include 'includes/alert.php'; ?>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-4">
                <?php include 'includes/sidebar_multiple.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-8">
                <?php if (!empty($data) && !empty($predictions)): ?>
                    <?php include 'includes/chart_multiple.php'; ?>
                    <?php include 'includes/table_multiple.php'; ?>
                    <?php include 'includes/predict_form_multiple.php'; ?>
                <?php else: ?>
                    <?php include 'includes/empty_state.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($data) && !empty($predictions)): ?>
        <?php include 'includes/chart_script_multiple.php'; ?>
    <?php endif; ?>
</body>
</html>