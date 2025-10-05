<?php
// process_slr.php - Business Logic untuk Single Linear Regression

session_start();
require_once 'auth_check.php';
require_once 'config.php';
require_once 'vendor/autoload.php';

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
        $samples = [];
        $targets = [];
        
        foreach ($data as $row) {
            $samples[] = [(float)$row[$selectedFeature]];
            $targets[] = (float)$row['omset'];
        }
        
        $regressionModel = new LeastSquares();
        $regressionModel->train($samples, $targets);
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