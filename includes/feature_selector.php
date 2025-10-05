<div class="card card-hover">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">📊 Pilih Variabel untuk Prediksi Omset</h5>
    </div>
    <div class="card-body">
        <form method="post" class="mb-3">
            <div class="row align-items-end">
                <div class="col-md-9">
                    <label for="selected_feature" class="form-label">Variabel Independen:</label>
                    <select class="form-control" id="selected_feature" name="selected_feature" required>
                        <?php foreach ($features as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $selectedFeature == $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">Analisis</button>
                </div>
            </div>
        </form>

        <?php if (!empty($singleVarPredictions)): ?>
        <div class="chart-container">
            <canvas id="scatterChart"></canvas>
        </div>
        <?php endif; ?>
    </div>
</div>