<?php
// index.php - View Only
require_once './process_index.php';
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
    <?php include './includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php include './includes/alert.php'; ?>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-4">
                <?php include './includes/sidebar_multiple.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-8">
                <?php if (!empty($data) && !empty($predictions)): ?>
                    <?php include './includes/chart_multiple.php'; ?>
                    <div class="d-flex justify-content-end mt-2 mb-2">
                        <button id="downloadMlrPdf" class="btn btn-primary btn-sm">⬇️ Download PDF</button>
                    </div>
                    <?php include './includes/table_multiple.php'; ?>
                    <?php include './includes/predict_form_multiple.php'; ?>
                <?php else: ?>
                    <?php include './includes/empty_state.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include './includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($data) && !empty($predictions)): ?>
        <?php include './includes/chart_script_multiple.php'; ?>
    <?php endif; ?>
    <script>
    function submitReportForm(payload) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'generate_report.php';
        form.target = '_blank';
        for (const key in payload) {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = key;
            inp.value = payload[key];
            form.appendChild(inp);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('downloadMlrPdf');
        if (btn) {
            btn.addEventListener('click', function() {
                const canvas = document.getElementById('predictionChart');
                const table = document.getElementById('predictionTable');
                const img = canvas ? canvas.toDataURL('image/png') : '';
                const tableHtml = table ? table.outerHTML : '';
                submitReportForm({
                    report_type: 'mlr_report',
                    chart_image: img,
                    table_html: tableHtml,
                    title: 'Laporan MLR - Prediksi Omset'
                });
            });
        }
    });
    </script>
</body>
</html>