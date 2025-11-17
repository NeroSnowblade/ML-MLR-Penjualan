<?php
// generate_report.php - generate PDF report from posted chart image and table HTML
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

// Basic validation
$reportType = isset($_POST['report_type']) ? $_POST['report_type'] : 'report';
$chartData = isset($_POST['chart_image']) ? $_POST['chart_image'] : '';
$tableHtml = isset($_POST['table_html']) ? $_POST['table_html'] : '';
$title = isset($_POST['title']) ? $_POST['title'] : 'Laporan Prediksi';

// sanitize a little (this app is local; further hardening may be needed)
$reportType = preg_replace('/[^a-zA-Z0-9_\-]/', '', $reportType);

// Build HTML for PDF
$style = <<<STYLE
<style>
body { font-family: DejaVu Sans, sans-serif; color: #222; }
.header { text-align: center; margin-bottom: 10px; }
.title { font-size: 20px; font-weight: 700; }
.metrics { margin-top: 6px; font-size: 12px; color: #555; }
.chart { text-align: center; margin: 10px 0; }
.table-container { margin-top: 10px; }
.table-container table { width: 100%; border-collapse: collapse; }
.table-container th, .table-container td { border: 1px solid #ddd; padding: 6px; font-size: 12px; }
.table-container th { background: #f7f7f7; }
</style>
STYLE;

$chartImgHtml = '';
if ($chartData) {
    // If data url like 'data:image/png;base64,...' then keep as-is
    $chartImgHtml = '<div class="chart"><img src="' . htmlspecialchars($chartData) . '" style="max-width:100%;height:auto;"/></div>';
}

$cleanTable = $tableHtml ?: '<p>Tidak ada data tabel.</p>';

$html = '<!doctype html><html><head><meta charset="utf-8">' . $style . '</head><body>';
$html .= '<div class="header"><div class="title">' . htmlspecialchars($title) . '</div></div>';
$html .= $chartImgHtml;
$html .= '<div class="table-container">' . $cleanTable . '</div>';
$html .= '</body></html>';

try {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $output = $dompdf->output();

    $filename = $reportType . '_' . date('Ymd_His') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $output;
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error generating PDF: ' . $e->getMessage();
    exit();
}
