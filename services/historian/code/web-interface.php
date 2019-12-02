<?php
/**
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 *
 */

require(__DIR__ . "/bootstrap.php");

function getGraphData()
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
        and (timestamp > '" . (time() - 3600 * 12) . "')
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
                //"backgroundColor" => "red",
                "data" => [],
            ];
        }

        if (abs($payload['timestamp'] - $graphDataSensors[$sensorName]['lastTimestamp']) < 60 * 5) {
            continue;
        }

        $graphDataSensors[$sensorName]['data'][] = [
            "x" => $payload['local_time'],
            "y" => $payload['sensor_reading']['celcius'],
        ];
        $graphDataSensors[$sensorName]['lastTimestamp'] = $payload['timestamp'];

    }

    //convert to datasets table
    $graphDatasets = [];
    foreach ($graphDataSensors as $sensor) {
        $graphDatasets[] = $sensor;
    }

    //assign background colors to each dataset
    for ($i = 0; isset($graphDatasets[$i]); $i++) {
        $graphDatasets[$i]['backgroundColor'] = [
            '#ffbbbb', '#bbffbb', '#bbbbff', '#efefef', '#ffffbb', "#bbffff", 'yellow', 'red', 'blue'
        ][$i];
    }

    //print_r($graphDatasets); exit;

    return $graphDatasets;
}


$graphDatasets = getGraphData();

?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.min.js"></script>
</head>
<body>

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