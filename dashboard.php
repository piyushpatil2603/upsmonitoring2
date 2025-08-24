<?php
header('Content-Type: application/json');
include 'db.php';

// Get device_id from query parameter (default 1)
$device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : 1;

// Get filter from query parameter: '24h', '7d', '30d'
$filter = isset($_GET['filter']) ? $_GET['filter'] : '24h';

// Calculate timestamp for filtering
$timeLimit = '';
switch($filter){
    case '24h':
        $timeLimit = date('Y-m-d H:i:s', strtotime('-1 day'));
        break;
    case '7d':
        $timeLimit = date('Y-m-d H:i:s', strtotime('-7 days'));
        break;
    case '30d':
        $timeLimit = date('Y-m-d H:i:s', strtotime('-30 days'));
        break;
    default:
        $timeLimit = date('Y-m-d H:i:s', strtotime('-1 day'));
}

// Fetch data from UPS table with filter
$sql = "SELECT * FROM ups_data WHERE timestamp >= '$timeLimit' ORDER BY timestamp ASC";
$result = $conn->query($sql);

$data = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        $data[] = [
            'id' => $row['id'],
            'main_voltage' => $row['main_voltage'],
            'main_current' => $row['main_current'],
            'battery_voltage' => $row['battery_voltage'],
            'battery_current' => $row['battery_current'],
            'battery_level' => $row['battery_level'],
            'load_frequency' => $row['load_frequency'],
            'timestamp' => $row['timestamp']
        ];
    }
}

// Output JSON
echo json_encode($data);

$conn->close();
?>
