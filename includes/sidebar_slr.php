<!-- Import Card -->
<div class="card card-hover">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">📁 Import Data Excel</h5>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="excel_file" class="form-label">Pilih File Excel</label>
                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
            </div>
            <button type="submit" name="import_excel" class="btn btn-primary w-100">Import Data</button>
        </form>
    </div>
</div>

<?php if (!empty($data)): ?>
<!-- Statistics Card -->
<div class="card mt-3 stats-card card-hover">
    <div class="card-body">
        <h6 class="card-title">📈 Statistik Data</h6>
        <p class="mb-1"><strong>Total Records:</strong> <?php echo count($data); ?></p>
        <p class="mb-1"><strong>Periode:</strong> <?php echo date('d/m/Y', strtotime($data[0]['date'])); ?> - <?php echo date('d/m/Y', strtotime(end($data)['date'])); ?></p>
        <p class="mb-0"><strong>Avg Omset:</strong> Rp <?php echo number_format(array_sum(array_column($data, 'omset')) / count($data), 0, ',', '.'); ?></p>
    </div>
</div>

<?php if (!empty($singleVarMetrics)): ?>
<!-- Metrics Card -->
<div class="card mt-3 metrics-card card-hover">
    <div class="card-header">
        <h6 class="mb-0">🎯 Model Performance</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3 p-2">
            <small><strong>Variable:</strong> <?php echo $singleVarMetrics['feature_name']; ?></small>
        </div>
        <div class="row">
            <div class="col-6">
                <small class="text-light">R² Score:</small>
                <div class="fw-bold"><?php echo number_format($singleVarMetrics['r2_score'], 4); ?></div>
            </div>
            <div class="col-6">
                <small class="text-light">Correlation:</small>
                <div class="fw-bold"><?php echo number_format($singleVarMetrics['correlation'], 4); ?></div>
            </div>
            <div class="col-6 mt-2">
                <small class="text-light">RMSE:</small>
                <div class="fw-bold"><?php echo number_format($singleVarMetrics['rmse'], 0); ?></div>
            </div>
            <div class="col-6 mt-2">
                <small class="text-light">MAPE:</small>
                <div class="fw-bold"><?php echo number_format($singleVarMetrics['mape'], 2); ?>%</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>