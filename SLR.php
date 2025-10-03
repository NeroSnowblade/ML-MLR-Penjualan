<?php
session_start();

require_once 'vendor/autoload.php';

use Phpml\Regression\LeastSquares;

$host = 'localhost';
$dbname = 'sales_prediction';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle Excel import
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    $inputFileName = $_FILES['excel_file']['tmp_name'];
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        $pdo->exec("DELETE FROM sales_data");
        
        $importCount = 0;
        for ($row = 10; $row <= $highestRow; $row++) {
            $date = $worksheet->getCell('A' . $row)->getValue();
            $item_sales = (int)$worksheet->getCell('B' . $row)->getValue();
            $void = (int)$worksheet->getCell('C' . $row)->getValue();
            $discount_bill = (int)$worksheet->getCell('D' . $row)->getValue();
            $discount_item = (int)$worksheet->getCell('E' . $row)->getValue();
            $amount_redeem = (int)$worksheet->getCell('F' . $row)->getValue();
            $net_sales = (int)$worksheet->getCell('G' . $row)->getValue();
            $gross_sales = (int)$worksheet->getCell('H' . $row)->getValue();
            $pembayaran_dp = (int)$worksheet->getCell('I' . $row)->getValue();
            $omset = (int)$worksheet->getCell('J' . $row)->getValue();
            $average_sales = (int)$worksheet->getCell('K' . $row)->getValue();
            
            if (empty($date) && empty($item_sales) && empty($omset)) continue;
            if ($date == 'TOTAL') continue;
            
            if (is_numeric($date)) {
                $unix_date = ($date - 25569) * 86400;
                $date = gmdate("Y-m-d", $unix_date);
            }
            
            $stmt = $pdo->prepare("INSERT INTO sales_data (date, item_sales, void, discount_bill, discount_item, amount_redeem, net_sales, gross_sales, pembayaran_dp, omset, average_sales) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$date, $item_sales, $void, $discount_bill, $discount_item, $amount_redeem, $net_sales, $gross_sales, $pembayaran_dp, $omset, $average_sales]);
            $importCount++;
        }
        
        $_SESSION['message'] = "Data berhasil diimport! Total: {$importCount} records.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['message'] = "Error importing data: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}

// Fetch data
$stmt = $pdo->query("SELECT * FROM sales_data ORDER BY date");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Available features for prediction
$features = [
    'item_sales' => 'Item Sales',
    'void' => 'Void',
    'discount_bill' => 'Discount Bill',
    'discount_item' => 'Discount Item',
    'amount_redeem' => 'Amount Redeem',
    'net_sales' => 'Net Sales',
    'gross_sales' => 'Gross Sales',
    'pembayaran_dp' => 'Pembayaran DP',
    'average_sales' => 'Average Sales'
];

// Get selected feature (default to discount_bill)
$selectedFeature = isset($_POST['selected_feature']) ? $_POST['selected_feature'] : 'discount_bill';

$singleVarPredictions = [];
$singleVarMetrics = [];
$regressionModel = null;

if (!empty($data) && count($data) >= 3) {
    try {
        // Prepare data for single variable regression
        $samples = [];
        $targets = [];
        
        foreach ($data as $row) {
            $samples[] = [(float)$row[$selectedFeature]];
            $targets[] = (float)$row['omset'];
        }
        
        // Create and train model
        $regressionModel = new LeastSquares();
        $regressionModel->train($samples, $targets);
        
        // Make predictions
        $singleVarPredictions = $regressionModel->predict($samples);
        
        // Calculate metrics
        $actualTargets = $targets;
        $meanActual = array_sum($actualTargets) / count($actualTargets);
        
        $totalSumSquares = array_sum(array_map(function($actual) use ($meanActual) {
            return pow($actual - $meanActual, 2);
        }, $actualTargets));
        
        $residualSumSquares = array_sum(array_map(function($actual, $predicted) {
            return pow($actual - $predicted, 2);
        }, $actualTargets, $singleVarPredictions));
        
        $r2Score = $totalSumSquares > 0 ? 1 - ($residualSumSquares / $totalSumSquares) : 0;
        $rmse = sqrt($residualSumSquares / count($actualTargets));
        
        $mae = array_sum(array_map(function($actual, $predicted) {
            return abs($actual - $predicted);
        }, $actualTargets, $singleVarPredictions)) / count($actualTargets);
        
        $mape = array_sum(array_map(function($actual, $predicted) {
            return abs(($actual - $predicted) / max($actual, 1)) * 100;
        }, $actualTargets, $singleVarPredictions)) / count($actualTargets);
        
        // Calculate correlation
        $correlation = calculateCorrelation(array_column($samples, 0), $targets);
        
        $singleVarMetrics = [
            'r2_score' => $r2Score,
            'rmse' => $rmse,
            'mae' => $mae,
            'mape' => $mape,
            'correlation' => $correlation,
            'feature_name' => $features[$selectedFeature]
        ];
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error dalam perhitungan prediksi: " . $e->getMessage();
        $_SESSION['message_type'] = "warning";
    }
}

// Handle single value prediction
$newPrediction = null;
if (isset($_POST['predict_single']) && $regressionModel && isset($_POST['feature_value'])) {
    $featureValue = (float)$_POST['feature_value'];
    $newPrediction = $regressionModel->predict([[$featureValue]])[0];
}

function calculateCorrelation($x, $y) {
    $n = count($x);
    if ($n < 2) return 0;
    
    $sumX = array_sum($x);
    $sumY = array_sum($y);
    $sumXY = 0;
    $sumX2 = 0;
    $sumY2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sumXY += $x[$i] * $y[$i];
        $sumX2 += $x[$i] * $x[$i];
        $sumY2 += $y[$i] * $y[$i];
    }
    
    $numerator = ($n * $sumXY) - ($sumX * $sumY);
    $denominator = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));
    
    return $denominator != 0 ? $numerator / $denominator : 0;
}
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
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand mb-0 h1">Prediksi Penjualan - Single Variable Regression</span>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card card-hover">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Import Data Excel</h5>
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
                <div class="card mt-3 stats-card card-hover">
                    <div class="card-body">
                        <h6 class="card-title">Statistik Data</h6>
                        <p class="mb-1"><strong>Total Records:</strong> <?php echo count($data); ?></p>
                        <p class="mb-1"><strong>Periode:</strong> <?php echo date('d/m/Y', strtotime($data[0]['date'])); ?> - <?php echo date('d/m/Y', strtotime(end($data)['date'])); ?></p>
                        <p class="mb-0"><strong>Avg Omset:</strong> Rp <?php echo number_format(array_sum(array_column($data, 'omset')) / count($data), 0, ',', '.'); ?></p>
                    </div>
                </div>

                <?php if (!empty($singleVarMetrics)): ?>
                <div class="card mt-3 metrics-card card-hover">
                    <div class="card-header">
                        <h6 class="mb-0">Model Performance</h6>
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
            </div>

            <div class="col-md-8">
                <?php if (!empty($data)): ?>
                <div class="card card-hover">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Pilih Variabel untuk Prediksi Omset</h5>
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

                <?php if (!empty($singleVarPredictions)): ?>
                <div class="card mt-3 card-hover">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Prediksi Omset Berdasarkan <?php echo $features[$selectedFeature]; ?></h5>
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

                <div class="card mt-3 card-hover">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Data Training & Predictions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
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
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="card card-hover">
                    <div class="card-body text-center">
                        <div class="py-5 empty-state">
                            <h1 class="display-1">📊</h1>
                            <h5>Belum Ada Data</h5>
                            <p class="text-muted">Silakan import file Excel terlebih dahulu.</p>
                            <small class="text-info">Minimal 3 data diperlukan untuk training model.</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <small>Powered by PHP-ML | Simple Linear Regression | Bootstrap 5</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($data) && !empty($singleVarPredictions)): ?>
    <script>
        const featureValues = <?php echo json_encode(array_column($data, $selectedFeature)); ?>;
        const actualOmset = <?php echo json_encode(array_column($data, 'omset')); ?>;
        const predictedOmset = <?php echo json_encode($singleVarPredictions); ?>;
        
        // Create scatter plot data
        const scatterData = featureValues.map((x, i) => ({x: x, y: actualOmset[i]}));
        
        // Create regression line data
        const minX = Math.min(...featureValues);
        const maxX = Math.max(...featureValues);
        const minPred = predictedOmset[featureValues.indexOf(minX)];
        const maxPred = predictedOmset[featureValues.indexOf(maxX)];
        
        const ctx = document.getElementById('scatterChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Data Aktual',
                    data: scatterData,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgb(75, 192, 192)',
                    pointRadius: 6,
                    pointHoverRadius: 8
                }, {
                    label: 'Regression Line',
                    data: [{x: minX, y: minPred}, {x: maxX, y: maxPred}],
                    type: 'line',
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderWidth: 3,
                    pointRadius: 0,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '<?php echo $features[$selectedFeature]; ?> vs Omset',
                        font: { size: 16 }
                    },
                    subtitle: {
                        display: true,
                        text: 'R² = <?php echo number_format($singleVarMetrics['r2_score'], 4); ?> | Correlation = <?php echo number_format($singleVarMetrics['correlation'], 4); ?>'
                    },
                    legend: { position: 'top' }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '<?php echo $features[$selectedFeature]; ?>'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID');
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Omset (Rp)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>