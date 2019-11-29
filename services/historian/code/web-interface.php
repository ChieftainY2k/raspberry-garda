<?php
/**
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 *
 * @use logger object
 */

require(__DIR__ . "/bootstrap.php");

echo "Historian web interface.<br><br>";

//select strftime('%Y-%m-%d %H:%M:%S',datetime(timestamp,'unixepoch')), topic from mqtt_events order by timestamp asc;

$databaseFile = "/data-historian/mqtt-history.sqlite";

//@TODO use db adapter layer, not PDO directly
$pdo = new \PDO("sqlite:" . $databaseFile);
if (empty($pdo)) {
    throw new Exception("Cannot create PDO instance");
}

//$sql = "select strftime('%Y-%m-%d %H:%M:%S',datetime(timestamp,'unixepoch')), topic from mqtt_events order by timestamp asc;";
$sql = "
    select 
        strftime('%Y-%m-%d %H:%M:%S',datetime(timestamp,'unixepoch')) date, 
        topic 
    from mqtt_events
    where timestamp < '" . (time() - 60*5) . "'
    order by timestamp asc
";
//

//$stmt = $pdo->prepare($sql);
//$result = $->execute($stmt);

$result = $pdo->query($sql);
$result->setFetchMode(PDO::FETCH_ASSOC);
$rows = $result->fetchAll();

$graph = new \JpGraph\JpGraph();

print_r($rows);
