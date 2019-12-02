<?php
/**
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 *
 */

require(__DIR__ . "/bootstrap.php");

/**
 * @param int $timeWindowHours
 * @param int $granulationMinutes
 * @return array
 * @throws Exception
 */
function getGraphData($timeWindowHours, $granulationMinutes)
{
    //echo "Historian web interface.<br><br>";

    //select strftime('%Y-%m-%d %H:%M:%S',datetime(timestamp,'unixepoch')), topic from mqtt_events order by timestamp asc;

    $databaseFile = "/data-historian/mqtt-history.sqlite";

    //@TODO use db adapter layer, not PDO directly
    $pdo = new \PDO("sqlite:" . $databaseFile);
    if (empty($pdo)) {
        throw new Exception("Cannot create PDO instance");
    }

    //$sql = "select strftime('%Y-%m-%d %H:%M:%S',datetime(timestamp,'unixepoch')), topic from mqtt_events order by timestamp asc;";

    //@TODO pagination
    //@TODO support both remote and local readings
    $sql = "
    select *
    from mqtt_events
    where 
        (
            (topic1='remote' and topic3='thermometer')
        )
        and (timestamp > '" . (time() - (3600 * $timeWindowHours)) . "') 
    order by timestamp desc
    ";
    //

    $result = $pdo->query($sql);
    if (empty($result)) {
        throw new Exception("Cannot execute query " . $sql);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $events = $result->fetchAll();

    $graphDataSensors = [];
    $colorsTable = ['green', 'red', 'blue', 'orange', 'pink', 'darkgrey', 'yellow', 'brown', 'cyan',];

    $lastTimestamp = null;
    foreach ($events as $event) {
        //print_r($row['topic']);
        $payload = (json_decode(gzuncompress($event['payload']), true));

        $isRemote = ($event['topic1'] == "remote");
        $sensorName = $payload['system_name'] . "(" . $payload['sensor_name'] . ")";

        //init sensor table
        if (!isset($graphDataSensors[$sensorName])) {
            $graphDataSensors[$sensorName] = [
                "label" => $sensorName,
                "lastTimestamp" => null,
                "fill" => false,
                "backgroundColor" => $colorsTable[count($graphDataSensors)], //pick a color from color table
                "data" => [],
            ];
        }

        if (abs($payload['timestamp'] - $graphDataSensors[$sensorName]['lastTimestamp']) < (60 * $granulationMinutes)) {
            continue;
        }

        $graphDataSensors[$sensorName]['data'][] = [
            "x" => $payload['local_time'],
            "y" => $payload['sensor_reading']['celcius'],
        ];
        $graphDataSensors[$sensorName]['lastTimestamp'] = $payload['timestamp'];

    }

    //convert to chartjs datasets table
    $graphDatasets = [];
    foreach ($graphDataSensors as $sensor) {
        $graphDatasets[] = $sensor;
    }

    return $graphDatasets;
}

$timeWindowHours = intval($_GET['timeWindowHours'] ?? 24);
$granulationMinutes = intval($_GET['granulationMinutes'] ?? 10);

$graphDatasets = getGraphData($timeWindowHours, $granulationMinutes);

?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.min.js"></script>
</head>
<body>

<div style="width:95%; border: solid 1px #aaaaaa; background: #efefef; padding: 10px; vertical-align: middle">
    <form style="display: inline">
        Show last <input type="text" name="timeWindowHours" value="<?php echo htmlspecialchars($timeWindowHours) ?>"> hour(s),
        every <input type="text" name="granulationMinutes" value="<?php echo htmlspecialchars($granulationMinutes) ?>"> minute(s)
        <input type="submit" value="OK">
    </form>
</div>
<div style="width:95%; height: 95%; border: solid 1px #aaaaaa; padding: 1px;">
    <canvas id="myChart" width="100%" height="100%"></canvas>
</div>

<script>
    var graphDatasets = <?php echo json_encode($graphDatasets); ?>;
</script>

<script>
    var ctx = document.getElementById('myChart');

    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: graphDatasets
        },
        options: {
            // responsive: false,
            maintainAspectRatio: false,
            display: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }],
                xAxes: [{
                    type: 'time',
                    display: false,
                }]
            }
        }
    });
</script>

</body>

</html>