<div class="card mt-3 card-hover">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">📋 Data Training & Predictions</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="slrTable" class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th><?php echo $features[$selectedFeature]; ?></th>
                        <th>Omset Aktual</th>
                        <th>Omset Prediksi</th>
                        <th>Akurasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $i => $row): ?>
                    <?php 
                    $predValue = isset($singleVarPredictions[$i]) ? $singleVarPredictions[$i] : 0;
                    $accuracy = (1 - abs($predValue - $row['omset']) / max($row['omset'], 1)) * 100;
                    $accuracy = max(0, $accuracy);
                    ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                        <td><?php echo number_format($row[$selectedFeature], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($row['omset'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($predValue, 0, ',', '.'); ?></td>
                        <td>
                            <span class="badge <?php echo $accuracy > 80 ? 'bg-success' : ($accuracy > 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                <?php echo number_format($accuracy, 1); ?>%
                            </span>
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
            var table = $('#slrTable').DataTable({
                "lengthMenu": [10, 25, 50],
                "pageLength": 10,
                "ordering": false
            });
        });
        </script>
    </div>
</div>