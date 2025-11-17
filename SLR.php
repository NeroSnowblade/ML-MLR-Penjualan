<?php
// SLR.php - View Only
require_once './process_slr.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Single Variable Prediction - Sales Forecasting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-light">
    <?php include './includes/navbar_slr.php'; ?>

    <div class="container mt-4">
        <?php include './includes/alert.php'; ?>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-4">
                <?php include './includes/sidebar_slr.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-8">
                <?php if (!empty($data)): ?>
                    <?php include './includes/feature_selector.php'; ?>
                    
                    <?php if (!empty($singleVarPredictions)): ?>
                        <?php include './includes/chart_slr.php'; ?>
                        <div class="d-flex justify-content-end mt-2 mb-2">
                            <button id="downloadSlrPdf" class="btn btn-primary btn-sm">⬇️ Download PDF</button>
                        </div>
                        <?php include './includes/predict_form_slr.php'; ?>
                        <?php include './includes/table_slr.php'; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php include './includes/empty_state.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include './includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($data) && !empty($singleVarPredictions)): ?>
        <?php include './includes/chart_script_slr.php'; ?>
    <?php endif; ?>
    <script>
    function submitReportForm(payload) {
        // Create form to POST and open in new tab
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
        const btn = document.getElementById('downloadSlrPdf');
        if (btn) {
            btn.addEventListener('click', function() {
                const canvas = document.getElementById('scatterChart');
                const table = document.getElementById('slrTable');
                const img = canvas ? canvas.toDataURL('image/png') : '';
                const tableHtml = table ? table.outerHTML : '';
                submitReportForm({
                    report_type: 'slr_report',
                    chart_image: img,
                    table_html: tableHtml,
                    title: 'Laporan SLR - Prediksi Omset'
                });
            });
        }
    });
    </script>
</body>
</html>