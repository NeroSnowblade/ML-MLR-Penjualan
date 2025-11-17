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

$predictions = [];
$modelMetrics = [];
$featureImportance = [];
$regression = null;

if (!empty($data) && count($data) >= 3) {
    try {
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
        
        $totalSamples = count($samples);
        $trainSize = (int)($totalSamples * 0.8);
        
        $trainSamples = array_slice($samples, 0, $trainSize);
        $trainTargets = array_slice($targets, 0, $trainSize);
        
        $regression = new LeastSquares();
        
        if (!empty($trainSamples)) {
            $regression->train($trainSamples, $trainTargets);
            $predictions = $regression->predict($samples);
            
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
    
    $newPrediction = $regression->predict([$newSample])[0];
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