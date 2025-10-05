<script>
const dates = <?php echo json_encode(array_map(function($row) { return date('d/m', strtotime($row['date'])); }, $data)); ?>;
const actualSales = <?php echo json_encode(array_column($data, 'omset')); ?>;
const predictedSales = <?php echo json_encode($predictions); ?>;

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
                text: 'Perbandingan Omset Aktual vs Prediksi',
                font: { size: 16 }
            },
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            x: {
                display: true,
                title: { display: true, text: 'Tanggal' }
            },
            y: {
                display: true,
                title: { display: true, text: 'Omset (Rp)' },
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        },
        interaction: { intersect: false }
    }
});

<?php if (!empty($modelMetrics)): ?>
chart.options.plugins.subtitle = {
    display: true,
    text: 'R² Score: <?php echo number_format($modelMetrics['r2_score'], 4); ?> | MAPE: <?php echo number_format($modelMetrics['mape'], 2); ?>%'
};
chart.update();
<?php endif; ?>
</script>