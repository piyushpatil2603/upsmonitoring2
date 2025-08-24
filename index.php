<?php
session_start();
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    header("Location: login.php");
    exit;
}

// Get device ID from URL
$device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : 1;

// Default time filter: last 24 hours
$defaultFilter = '24h';
?>

<!DOCTYPE html>
<html>
<head>
    <title>UPS Monitoring Dashboard - Device <?php echo $device_id; ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- Back Button -->
    <div style="margin-bottom: 10px;">
        <a href="devices.php" class="back-button">&larr; Back to Devices</a>
    </div>

    <!-- Logout -->
    <div class="logout-container">
        <form method="post" action="logout.php">
            <input type="submit" value="Logout">
        </form>
    </div>

    <h1>UPS Monitoring - Device <?php echo $device_id; ?></h1>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="card">Main Voltage: <span id="currentVoltage">--</span> V</div>
        <div class="card">Battery Level: <span id="currentBattery">--</span> %</div>
        <div class="card">Load Frequency: <span id="currentFrequency">--</span> Hz</div>
    </div>

    <!-- Historical Filter -->
    <div style="text-align:center; margin-bottom:20px;">
        <button onclick="setFilter('24h')">Last 24 Hours</button>
        <button onclick="setFilter('7d')">Last 7 Days</button>
        <button onclick="setFilter('30d')">Last 30 Days</button>
    </div>

    <!-- Graphs -->
    <div class="dashboard">
        <div class="card"><canvas id="mainVoltageChart"></canvas></div>
        <div class="card"><canvas id="mainCurrentChart"></canvas></div>
        <div class="card"><canvas id="batteryVoltageChart"></canvas></div>
        <div class="card"><canvas id="batteryCurrentChart"></canvas></div>
        <div class="card"><canvas id="batteryLevelChart"></canvas></div>
        <div class="card"><canvas id="loadFrequencyChart"></canvas></div>
    </div>

<script>
let charts = {};
let deviceId = <?php echo $device_id; ?>;
let filter = '<?php echo $defaultFilter; ?>';

const voltageMin = 210;
const voltageMax = 240;

function setFilter(f) {
    filter = f;
    fetchAndUpdateCharts();
}

function fetchAndUpdateCharts() {
    fetch(`getData.php?device_id=${deviceId}&filter=${filter}`)
    .then(res => res.json())
    .then(data => {
        if(data.length === 0) return;

        const timestamps = data.map(r => r.timestamp);

        // Update summary cards
        const latest = data[data.length-1];
        document.getElementById('currentVoltage').innerText = latest.main_voltage;
        document.getElementById('currentBattery').innerText = latest.battery_level;
        document.getElementById('currentFrequency').innerText = latest.load_frequency;

        function createOrUpdateChart(id, label, dataset, color, alertMin=null, alertMax=null) {
            dataset = dataset.map(v => parseFloat(v) || 0);
            let pointColors = dataset.map(v => {
                if(alertMin !== null && alertMax !== null) return (v < alertMin || v > alertMax) ? 'red' : color;
                return color;
            });

            if(charts[id]){
                charts[id].data.labels = timestamps;
                charts[id].data.datasets[0].data = dataset;
                charts[id].data.datasets[0].pointBackgroundColor = pointColors;
                charts[id].update();
            } else {
                charts[id] = new Chart(document.getElementById(id), {
                    type: 'line',
                    data: {
                        labels: timestamps,
                        datasets: [{
                            label: label,
                            data: dataset,
                            borderColor: color,
                            pointBackgroundColor: pointColors,
                            fill: false,
                            tension: 0.2
                        }]
                    },
                    options: {
                        responsive: true,
                        animation: { duration: 1000, easing: 'easeInOutQuart' },
                        scales: {
                            x: { display: true, title: { display: true, text: 'Timestamp' } },
                            y: { display: true, title: { display: true, text: label } }
                        }
                    }
                });
            }
        }

        createOrUpdateChart('mainVoltageChart', 'Main Voltage (V)', data.map(r => r.main_voltage), 'blue', voltageMin, voltageMax);
        createOrUpdateChart('mainCurrentChart', 'Main Current (A)', data.map(r => r.main_current), 'green');
        createOrUpdateChart('batteryVoltageChart', 'Battery Voltage (V)', data.map(r => r.battery_voltage), 'red');
        createOrUpdateChart('batteryCurrentChart', 'Battery Current (A)', data.map(r => r.battery_current), 'orange');
        createOrUpdateChart('batteryLevelChart', 'Battery Level (%)', data.map(r => r.battery_level), 'purple');
        createOrUpdateChart('loadFrequencyChart', 'Load Frequency (Hz)', data.map(r => r.load_frequency), 'brown');
    })
    .catch(err => console.error(err));
}

fetchAndUpdateCharts();
setInterval(fetchAndUpdateCharts, 5000);
</script>
</body>
</html>
