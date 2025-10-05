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