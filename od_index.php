<?php
session_start();

// Include Composer autoload
require_once 'vendor/autoload.php';

// Import PHP-ML classes
use Phpml\Regression\LeastSquares;
use Phpml\Dataset\ArrayDataset;
use Phpml\Metric\Regression;
use Phpml\Preprocessing\Normalizer;
use Phpml\Math\Statistic\Mean;
use Phpml\Math\Statistic\StandardDeviation;

// Database configuration
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
        
        // Clear existing data
        $pdo->exec("DELETE FROM sales_data");
        
        $importCount = 0;
        // Insert data starting from row 2 (skip header)
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
            
            // Skip empty rows
            if (empty($date) && empty($item_sales) && empty($omset)) continue;
            //  Skip Total Row
            if ($date == 'TOTAL') continue;
            
            // Convert Excel date to MySQL date format
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

// Fetch data for prediction
$stmt = $pdo->query("SELECT * FROM sales_data ORDER BY date");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$predictions = [];
$modelMetrics = [];
$featureImportance = [];

if (!empty($data) && count($data) >= 3) {
    try {
        // Prepare data for regression
        $samples = [];
        $targets = [];
        $featureNames = ['Item Sales', 'Void', 'Discount Bill', 'Discount Item', 'Amount Redeem', 'Net Sales', 'Gross Sales', 'Pembayaran DP', 'Average Sales'];
        
        foreach ($data as $row) {
            $samples[] = [
                (float)$row['item_sales'],
                (float)$row['void'],
                (float)$row['discount_bill'],
                (float)$row['discount_item'],
                (float)$row['amount_redeem'],
                (float)$row['net_sales'],
                (float)$row['gross_sales'],
                (float)$row['pembayaran_dp'],
                (float)$row['average_sales']
            ];
            $targets[] = (float)$row['omset'];
        }
        
        // Split data into training and testing (80-20)
        $totalSamples = count($samples);
        $trainSize = (int)($totalSamples * 0.8);
        
        $trainSamples = array_slice($samples, 0, $trainSize);
        $trainTargets = array_slice($targets, 0, $trainSize);
        $testSamples = array_slice($samples, $trainSize);
        $testTargets = array_slice($targets, $trainSize);
        
        // Create and train the model
        $regression = new LeastSquares();
        
        // Train the model
        if (!empty($trainSamples)) {
            $regression->train($trainSamples, $trainTargets);
            
            // Make predictions on all data
            $predictions = $regression->predict($samples);
            
            // Calculate model metrics
            $actualTargets = $targets;
            
            // Calculate R-squared
            $meanActual = array_sum($actualTargets) / count($actualTargets);
            $totalSumSquares = array_sum(array_map(function($actual) use ($meanActual) {
                return pow($actual - $meanActual, 2);
            }, $actualTargets));
            
            $residualSumSquares = array_sum(array_map(function($actual, $predicted) {
                return pow($actual - $predicted, 2);
            }, $actualTargets, $predictions));
            
            $r2Score = $totalSumSquares > 0 ? 1 - ($residualSumSquares / $totalSumSquares) : 0;
            
            // Calculate RMSE
            $mse = $residualSumSquares / count($actualTargets);
            $rmse = sqrt($mse);
            
            // Calculate MAE
            $mae = array_sum(array_map(function($actual, $predicted) {
                return abs($actual - $predicted);
            }, $actualTargets, $predictions)) / count($actualTargets);
            
            // Calculate MAPE
            $mape = array_sum(array_map(function($actual, $predicted) {
                return abs(($actual - $predicted) / max($actual, 1)) * 100;
            }, $actualTargets, $predictions)) / count($actualTargets);
            
            $modelMetrics = [
                'r2_score' => $r2Score,
                'rmse' => $rmse,
                'mae' => $mae,
                'mape' => $mape,
                'total_samples' => $totalSamples,
                'train_samples' => $trainSize,
                'test_samples' => $totalSamples - $trainSize
            ];
            
            // Calculate feature importance (coefficient values)
            for ($i = 0; $i < count($featureNames); $i++) {
                $featureValues = array_column($samples, $i);
                $correlation = calculateCorrelation($featureValues, $targets);
                $featureImportance[$featureNames[$i]] = $correlation;
            }
        }
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error dalam perhitungan prediksi: " . $e->getMessage();
        $_SESSION['message_type'] = "warning";
    }
}

// Function to calculate correlation coefficient
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
    <title>Prediksi Penjualan - PHP-ML Multiple Linear Regression</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand mb-0 h1">🤖 Sistem Prediksi Penjualan - PHP-ML</span>
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
                        <h5 class="mb-0">📁 Import Data Excel</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Pilih File Excel</label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                <div class="form-text">Format: Date, Item Sales, Void, Discount Bill, Discount Item, Amount Redeem, Net Sales, Gross Sales, Pembayaran DP, Omset, Average Sales</div>
                            </div>
                            <button type="submit" name="import_excel" class="btn btn-primary w-100">
                                <i class="bi bi-upload"></i> Import Data
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($data)): ?>
                <div class="card mt-3 stats-card card-hover">
                    <div class="card-body">
                        <h6 class="card-title">📈 Statistik Data</h6>
                        <p class="mb-1"><strong>Total Records:</strong> <?php echo count($data); ?></p>
                        <p class="mb-1"><strong>Periode:</strong> <?php echo date('d/m/Y', strtotime($data[0]['date'])); ?> - <?php echo date('d/m/Y', strtotime(end($data)['date'])); ?></p>
                        <p class="mb-0"><strong>Avg Omset:</strong> Rp <?php echo number_format(array_sum(array_column($data, 'omset')) / count($data), 0, ',', '.'); ?></p>
                    </div>
                </div>

                <?php if (!empty($modelMetrics)): ?>
                <div class="card mt-3 metrics-card card-hover">
                    <div class="card-header">
                        <h6 class="mb-0">🎯 Model Performance Metrics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-light">R² Score:</small>
                                <div class="fw-bold"><?php echo number_format($modelMetrics['r2_score'], 4); ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-light">RMSE:</small>
                                <div class="fw-bold"><?php echo number_format($modelMetrics['rmse'], 0); ?></div>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-light">MAE:</small>
                                <div class="fw-bold"><?php echo number_format($modelMetrics['mae'], 0); ?></div>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-light">MAPE:</small>
                                <div class="fw-bold"><?php echo number_format($modelMetrics['mape'], 2); ?>%</div>
                            </div>
                        </div>
                        <hr>
                        <small class="text-light">
                            Training: <?php echo $modelMetrics['train_samples']; ?> samples | 
                            Testing: <?php echo $modelMetrics['test_samples']; ?> samples
                        </small>
                    </div>
                </div>

                <?php if (!empty($featureImportance)): ?>
                <div class="card mt-3 feature-card card-hover">
                    <div class="card-header">
                        <h6 class="mb-0">📊 Feature Correlation</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($featureImportance as $feature => $importance): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-light"><?php echo $feature; ?>:</small>
                            <span class="badge <?php echo abs($importance) > 0.5 ? 'bg-success' : (abs($importance) > 0.3 ? 'bg-warning' : 'bg-secondary'); ?>">
                                <?php echo number_format($importance, 3); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <?php if (!empty($data) && !empty($predictions)): ?>
                <div class="card card-hover">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">📊 Grafik Prediksi vs Aktual</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="predictionChart"></canvas>
                        </div>
                    </div>
                </div>

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
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
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
                    </div>
                </div>

                <!-- Prediction Form for New Data -->
                <div class="card mt-3 card-hover">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">🔮 Prediksi Data Baru</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" id="predictionForm">
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
                        
                        <?php if (isset($_POST['predict_new']) && !empty($predictions)): ?>
                        <?php
                        $newSample = [
                            (float)$_POST['new_item_sales'],
                            (float)$_POST['new_void'],
                            (float)$_POST['new_discount_bill'],
                            (float)$_POST['new_discount_item'],
                            (float)$_POST['new_amount_redeem'],
                            (float)$_POST['new_net_sales'],
                            (float)$_POST['new_gross_sales'],
                            (float)$_POST['new_pembayaran_dp'],
                            (float)$_POST['new_average_sales']
                        ];
                        
                        $newPrediction = $regression->predict([$newSample]);
                        ?>
                        <div class="alert alert-success mt-3">
                            <h6>Hasil Prediksi:</h6>
                            <h4>Rp <?php echo number_format($newPrediction[0], 0, ',', '.'); ?></h4>
                            <small class="text-muted">Berdasarkan model dengan akurasi <?php echo number_format(100 - $modelMetrics['mape'], 1); ?>%</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="card card-hover">
                    <div class="card-body text-center">
                        <div class="py-5">
                            <h1 class="display-1">📊</h1>
                            <h5>Belum Ada Data</h5>
                            <p class="text-muted">Silakan import file Excel terlebih dahulu untuk melihat prediksi penjualan dengan PHP-ML.</p>
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
            <small>🤖 Powered by PHP-ML Library | Multiple Linear Regression | Bootstrap 5</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($data) && !empty($predictions)): ?>
    <script>
        // Prepare data for Chart.js
        const dates = <?php echo json_encode(array_map(function($row) { return date('d/m', strtotime($row['date'])); }, $data)); ?>;
        const actualSales = <?php echo json_encode(array_column($data, 'omset')); ?>;
        const predictedSales = <?php echo json_encode($predictions); ?>;

        // Create the chart
        const ctx = document.getElementById('predictionChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Omset Aktual',
                    data: actualSales,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }, {
                    label: 'Omset Prediksi',
                    data: predictedSales,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Perbandingan Omset Aktual vs Prediksi (PHP-ML)',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Tanggal'
                        }
                    },
                    y: {
                        display: true,
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
                },
                interaction: {
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
        
        // Add R² score to chart subtitle
        <?php if (!empty($modelMetrics)): ?>
        chart.options.plugins.subtitle = {
            display: true,
            text: 'R² Score: <?php echo number_format($modelMetrics['r2_score'], 4); ?> | MAPE: <?php echo number_format($modelMetrics['mape'], 2); ?>%'
        };
        chart.update();
        <?php endif; ?>
    </script>
    <?php endif; ?>
</body>
</html>