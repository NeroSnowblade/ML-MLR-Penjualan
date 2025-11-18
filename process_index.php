<?php
// process_index.php - Business Logic untuk Multiple Linear Regression

session_start();
require_once './auth_check.php';
require_once './config.php';
require_once './vendor/autoload.php';

use Phpml\Regression\LeastSquares;

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
            $gratuity_dp = (int)$worksheet->getCell('J' . $row)->getValue();
            $omset = (int)$worksheet->getCell('K' . $row)->getValue();
            $average_sales = (int)$worksheet->getCell('L' . $row)->getValue();
            
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

$predictions = [];
$modelMetrics = [];
$featureImportance = [];
$regression = null;

if (!empty($data) && count($data) >= 3) {
    try {
        $samples = [];
        $targets = [];
        $featureNames = ['Item Sales', 'Void', 'Discount Bill', 'Discount Item', 'Amount Redeem', 'Net Sales', 'Gross Sales', 'Pembayaran DP', 'Average Sales'];
        
        // Normalize samples: replace any zero values with 1 to avoid singular matrix errors
        $normalize = function ($v) {
            $f = (float)$v;
            return ($f === 0.0) ? 1.0 : $f;
        };

        foreach ($data as $row) {
            $samples[] = [
                $normalize($row['item_sales']),
                $normalize($row['void']),
                $normalize($row['discount_bill']),
                $normalize($row['discount_item']),
                $normalize($row['amount_redeem']),
                $normalize($row['net_sales']),
                $normalize($row['gross_sales']),
                $normalize($row['pembayaran_dp']),
                $normalize($row['average_sales'])
            ];
            $targets[] = (float)$row['omset'];
        }

        // Detect and remove constant features (zero variance) to avoid X'X singularity
        $numFeatures = count($samples[0]);
        $keptIndices = [];
        $removedFeatureNames = [];
        for ($i = 0; $i < $numFeatures; $i++) {
            $col = array_column($samples, $i);
            $unique = array_unique($col);
            if (count($unique) <= 1) {
                $removedFeatureNames[] = $featureNames[$i];
            } else {
                $keptIndices[] = $i;
            }
        }

        // Build reduced samples matrix using only kept indices
        $samplesReduced = array_map(function($row) use ($keptIndices) {
            $r = [];
            foreach ($keptIndices as $idx) {
                $r[] = $row[$idx];
            }
            return $r;
        }, $samples);

        if (!empty($removedFeatureNames)) {
            $_SESSION['message'] = 'Fitur konstan dihilangkan: ' . implode(', ', $removedFeatureNames) . '.';
            $_SESSION['message_type'] = 'warning';
        }

        $totalSamples = count($samplesReduced);
        $trainSize = (int)($totalSamples * 0.8);

        $trainSamples = array_slice($samplesReduced, 0, $trainSize);
        $trainTargets = array_slice($targets, 0, $trainSize);

        $regression = new LeastSquares();

        if (!empty($trainSamples) && count($keptIndices) > 0) {
            try {
                $regression->train($trainSamples, $trainTargets);
                $predictions = $regression->predict($samplesReduced);
            } catch (Exception $e) {
                // If still singular, try tiny jitter on diagonal by adding small noise to samples
                foreach ($samplesReduced as &$r) {
                    for ($j = 0; $j < count($r); $j++) {
                        $r[$j] = $r[$j] + (mt_rand(-5,5) / 100000.0);
                    }
                }
                unset($r);
                // retry
                $regression = new LeastSquares();
                $regression->train($trainSamples, $trainTargets);
                $predictions = $regression->predict($samplesReduced);
                $_SESSION['message'] = 'Beberapa fitur bernilai konstan dihilangkan: ' . implode(', ', $removedFeatureNames) . '. (Jitter applied)';
                $_SESSION['message_type'] = 'warning';
            }

            $actualTargets = $targets;
            $meanActual = array_sum($actualTargets) / count($actualTargets);
            
            $totalSumSquares = array_sum(array_map(function($actual) use ($meanActual) {
                return pow($actual - $meanActual, 2);
            }, $actualTargets));
            
            $residualSumSquares = array_sum(array_map(function($actual, $predicted) {
                return pow($actual - $predicted, 2);
            }, $actualTargets, $predictions));
            
            $r2Score = $totalSumSquares > 0 ? 1 - ($residualSumSquares / $totalSumSquares) : 0;
            $mse = $residualSumSquares / count($actualTargets);
            $rmse = sqrt($mse);
            
            $mae = array_sum(array_map(function($actual, $predicted) {
                return abs($actual - $predicted);
            }, $actualTargets, $predictions)) / count($actualTargets);
            
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

// Handle new prediction
$newPrediction = null;
if (isset($_POST['predict_new']) && !empty($predictions) && $regression) {
    // Build new sample and apply same normalization + feature removal
    $rawNew = [
        $_POST['new_item_sales'],
        $_POST['new_void'],
        $_POST['new_discount_bill'],
        $_POST['new_discount_item'],
        $_POST['new_amount_redeem'],
        $_POST['new_net_sales'],
        $_POST['new_gross_sales'],
        $_POST['new_pembayaran_dp'],
        $_POST['new_average_sales']
    ];

    $normalizedNew = array_map(function($v) use ($normalize) {
        return $normalize($v);
    }, $rawNew);

    if (!empty($keptIndices)) {
        $reducedNew = [];
        foreach ($keptIndices as $idx) {
            $reducedNew[] = isset($normalizedNew[$idx]) ? $normalizedNew[$idx] : 1.0;
        }
        $newPrediction = $regression->predict([$reducedNew])[0];
    } else {
        // If no features kept, cannot predict
        $newPrediction = null;
        $_SESSION['message'] = 'Tidak ada fitur valid untuk melakukan prediksi baru.';
        $_SESSION['message_type'] = 'warning';
    }
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