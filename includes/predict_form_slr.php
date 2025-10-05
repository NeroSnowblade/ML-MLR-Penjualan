<div class="card mt-3 card-hover">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">🔮 Prediksi Omset Berdasarkan <?php echo $features[$selectedFeature]; ?></h5>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="selected_feature" value="<?php echo $selectedFeature; ?>">
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label for="feature_value" class="form-label">
                        Masukkan nilai <?php echo $features[$selectedFeature]; ?>:
                    </label>
                    <input type="number" class="form-control" id="feature_value" name="feature_value" required step="0.01">
                </div>
                <div class="col-md-4">
                    <button type="submit" name="predict_single" class="btn btn-warning w-100">Prediksi</button>
                </div>
            </div>
        </form>
        
        <?php if ($newPrediction !== null): ?>
        <div class="alert alert-success mt-3">
            <h6>Hasil Prediksi Omset:</h6>
            <h4>Rp <?php echo number_format($newPrediction, 0, ',', '.'); ?></h4>
            <small class="text-muted">
                Berdasarkan <?php echo $features[$selectedFeature]; ?> = <?php echo number_format($_POST['feature_value'], 0, ',', '.'); ?>
                <br>Model Accuracy: <?php echo number_format(100 - $singleVarMetrics['mape'], 1); ?>%
            </small>
        </div>
        <?php endif; ?>
    </div>
</div>