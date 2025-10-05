<div class="card mt-3 card-hover">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">🔮 Prediksi Data Baru</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Item Sales</label>
                    <input type="number" class="form-control" name="new_item_sales" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Void</label>
                    <input type="number" class="form-control" name="new_void" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Discount Bill</label>
                    <input type="number" class="form-control" name="new_discount_bill" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Discount Item</label>
                    <input type="number" class="form-control" name="new_discount_item" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Amount Redeem</label>
                    <input type="number" class="form-control" name="new_amount_redeem" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Net Sales</label>
                    <input type="number" class="form-control" name="new_net_sales" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gross Sales</label>
                    <input type="number" class="form-control" name="new_gross_sales" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Pembayaran DP</label>
                    <input type="number" class="form-control" name="new_pembayaran_dp" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Average Sales</label>
                    <input type="number" class="form-control" name="new_average_sales" required>
                </div>
            </div>
            <button type="submit" name="predict_new" class="btn btn-warning">
                🔮 Prediksi Omset
            </button>
        </form>
        
        <?php if ($newPrediction !== null): ?>
        <div class="alert alert-success mt-3">
            <h6>Hasil Prediksi:</h6>
            <h4>Rp <?php echo number_format($newPrediction, 0, ',', '.'); ?></h4>
            <small class="text-muted">Berdasarkan model dengan akurasi <?php echo number_format(100 - $modelMetrics['mape'], 1); ?>%</small>
        </div>
        <?php endif; ?>
    </div>
</div>