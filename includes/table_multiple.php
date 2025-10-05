<div class="card mt-3 card-hover">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📋 Data dan Prediksi</h5>
        <?php if (!empty($modelMetrics)): ?>
        <span class="badge bg-light text-dark">
            Model Accuracy: <?php echo number_format(100 - $modelMetrics['mape'], 1); ?>%
        </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="predictionTable" class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Omset Aktual</th>
                        <th>Omset Prediksi</th>
                        <th>Selisih</th>
                        <th>Akurasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $i => $row): ?>
                    <?php 
                    $predValue = isset($predictions[$i]) ? $predictions[$i] : 0;
                    $difference = $predValue - $row['omset'];
                    $accuracy = (1 - abs($difference) / max($row['omset'], 1)) * 100;
                    $accuracy = max(0, $accuracy);
                    ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                        <td>Rp <?php echo number_format($row['omset'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($predValue, 0, ',', '.'); ?></td>
                        <td class="<?php echo $difference > 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $difference > 0 ? '+' : ''; ?>Rp <?php echo number_format($difference, 0, ',', '.'); ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $accuracy > 80 ? 'bg-success' : ($accuracy > 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                <?php echo number_format($accuracy, 1); ?>%
                            </span>
                        </td>
                        <td>
                            <?php if ($accuracy > 80): ?>
                                <span class="badge bg-success">Excellent</span>
                            <?php elseif ($accuracy > 60): ?>
                                <span class="badge bg-warning">Good</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Need Improvement</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- DataTables JS/CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var table = $('#predictionTable').DataTable({
                "lengthMenu": [10, 25, 50],
                "pageLength": 10,
                "ordering": false
            });
        });
        </script>
    </div>
</div>