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
    $sql = "
    select
        strftime('%Y-%m-%d %H:%M:%S',datetime(timestamp,'unixepoch')) date,
        topic, payload
    from mqtt_events
    where 
        (
            (topic1='remote' and topic3='thermometer')
        )
        and (timestamp > '" . (time() - 3600 * 24 * 2) . "')
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
        $sensorName = $payload['sensor_name'];

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

        //
        //    remote/Thermo/thermometer/2_powietrze/readingArray
        //    (
        //        [system_name] => Thermo
        //    [timestamp] => 1575279411
        //    [local_time] => 2019-12-02 10:36:51
        //    [sensor_name] => 2_powietrze
        //    [sensor_name_original] => 28-0516a038b5ff
        //    [sensor_reading] => Array
        //    (
        //        [celcius] => 18.062
        //            [raw] => 21 01 4b 46 7f ff 0c 10 1e : crc=1e YES
        //21 01 4b 46 7f ff 0c 10 1e t=18062
        //
        //        )
        //
        //)


    }

    //print_r($graphDataSensors);
    //exit;
    //
    //$graphDatasets[] = [
    //    "label" => "powietrze 1",
    //    "data" => $graphDataPoints,
    //];

    //convert to datasets table
    $graphDatasets = [];
    foreach ($graphDataSensors as $sensor) {
        $graphDatasets[] = $sensor;
    }

    //assign background colors
    for ($i = 0; isset($graphDatasets[$i]); $i++) {
        $graphDatasets[$i]['backgroundColor'] = [
            '#ffbbbb', '#bbffbb', '#bbbbff', '#efefef', '#ffffbb', "#bbffff"
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

<div style="width:95%; height: 95%; border-color: #efefef; padding: 10px;">
    <canvas id="myChart" width="100%" height="100%"></canvas>
</div>

<script>
    //var graphDataPoints = <?php //echo json_encode($graphDataPoints); ?>//;
    var graphDatasets = <?php echo json_encode($graphDatasets); ?>;
    //var graphDataLabels = <?php //echo json_encode($graphDataLabels); ?>//;
</script>

<script>
    var ctx = document.getElementById('myChart');

    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: graphDatasets
            // datasets: [
            //     {
            //         label: 'Temperature',
            //         data: graphDataPoints,
            //         // data: [{
            //         //     x: "2019-12-02 01:00:00",
            //         //     y: 1
            //         // }, {
            //         //     x: "2019-12-02 01:00:30",
            //         //     y: 10
            //         // }, {
            //         //     x: "2019-12-02 01:02:00",
            //         //     y: 5
            //         // }
            //         // ],
            //         //borderWidth: 1
            //     }]
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
                    time: {
                        unit: 'second'
                    }
                }]
            }
        }
    });

    // var myChart = new Chart(ctx, {
    //     type: 'line',
    //     data: {
    //         // labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
    //         labels: graphDataLabels,
    //         datasets: [{
    //             label: 'Temperature',
    //             data: graphDataPoints,
    //             //data: [12, 19, 3, 5, 2, 3],
    //             // backgroundColor: [
    //             //     'rgba(255, 99, 132, 0.2)',
    //             //     'rgba(54, 162, 235, 0.2)',
    //             //     'rgba(255, 206, 86, 0.2)',
    //             //     'rgba(75, 192, 192, 0.2)',
    //             //     'rgba(153, 102, 255, 0.2)',
    //             //     'rgba(255, 159, 64, 0.2)'
    //             // ],
    //             // borderColor: [
    //             //     'rgba(255, 99, 132, 1)',
    //             //     'rgba(54, 162, 235, 1)',
    //             //     'rgba(255, 206, 86, 1)',
    //             //     'rgba(75, 192, 192, 1)',
    //             //     'rgba(153, 102, 255, 1)',
    //             //     'rgba(255, 159, 64, 1)'
    //             // ],
    //             borderWidth: 1
    //         }]
    //     },
    //     options: {
    //         // responsive: false,
    //         maintainAspectRatio: false,
    //         scales: {
    //             yAxes: [{
    //                 ticks: {
    //                     beginAtZero: true
    //                 }
    //             }]
    //         }
    //     }
    // });
</script>

</body>

</html>