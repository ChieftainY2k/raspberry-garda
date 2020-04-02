<?php


namespace SwarmWatcher;


/**
 * Analyze report collection and produce readable report with new warnings and warnings that disappeared
 *
 * @TODO use logger object
 * @TODO use SPL for files and directories
 * @TODO use objects/factories for better testing
 */
class WebInterface
{

    /**
     * @var string
     */
    private $collectedHealthReportsRootPath;

    /**
     * @var string
     */
    private $localCacheRootPath;

    /**
     * ReportAnalyzer constructor.
     * @param $collectedHealthReportsRootPath string
     * @param $localCacheRootPath string
     * @throws \Exception
     */
    function __construct($collectedHealthReportsRootPath, $localCacheRootPath)
    {
        $this->collectedHealthReportsRootPath = $collectedHealthReportsRootPath;
        $this->localCacheRootPath = $localCacheRootPath;

        //@TODO validate the input
        if (empty(getenv("KD_SYSTEM_NAME"))) {
            throw new \Exception("Empty environment variable KD_SYSTEM_NAME");
        }
        if (empty(getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"))) {
            throw new \Exception("Empty environment variable KD_EMAIL_NOTIFICATION_RECIPIENT");
        }

    }

    /**
     * @param $msg
     */
    function log($msg)
    {
        echo "[".date("Y-m-d H:i:s")."][".basename(__CLASS__)."] ".$msg."\n";
    }

    /**
     * @param $timestamp
     * @return string
     */
    public function ago($timestamp)
    {
        $daysAgo = floor((time() - $timestamp) / (3600 * 24));
        $hoursAgo = floor(((time() - $timestamp) - ($daysAgo * 3600 * 24)) / (3600));
        $minutesAgo = floor(((time() - $timestamp) - ($daysAgo * 3600 * 24) - ($hoursAgo * 3600)) / (60));

        return "<b>".$daysAgo."</b>d <b>".$hoursAgo."</b>h <b>".$minutesAgo."</b>m";
    }

    ///**
    // * @param $text
    // * @return string
    // */
    //public function warning($text)
    //{
    //    return "<span class='warning'>$text</span>";
    //}

    /**
     * @param $serviceReportPayload
     */
    public function showServiceReportNgrok($serviceReportPayload)
    {
        echo "<ul>";
        echo "<li> url: <a href='http://".$serviceReportPayload['ngrok_url']."'>".$serviceReportPayload['ngrok_url']."</a>";
        echo "</ul>";
    }

    /**
     * @param $serviceReportPayload
     */
    public function showServiceReportKerberos($serviceReportPayload)
    {
        echo "<ul>";
        $videoStreamInfo = $serviceReportPayload['video_stream'];
        if (!empty($videoStreamInfo)) {
            echo "<li>";
            echo "<watch>";
            echo "video stream: ".$videoStreamInfo;
            if (strpos($videoStreamInfo, "Stream #0:0: Video: mjpeg") === false) {
                echo "<span class='warning'>video format is invalid</span>";
            }
            echo "</watch>";
        }
        echo "</ul>";
    }

    /**
     * @param $serviceReportPayload
     */
    public function showServiceReportThermometer($serviceReportPayload)
    {
        echo "<ul>";
        foreach ($serviceReportPayload['sensors'] as $sensorReport) {
            echo "<li>sensor: (<b>".$sensorReport['sensor_name']."</b>) ".$sensorReport['sensor_name_original']."<br>";
            echo "<ul>";
            echo "<li>reading: <b>".$sensorReport['sensor_reading']['celcius']."</b>'C<br>";
            echo "<li>raw reading: ".$sensorReport['sensor_reading']['raw']."";
            echo "</ul>";
        }
        echo "</ul>";
    }

    /**
     * @param $serviceReportPayload
     */
    public function showServiceReportHistorian($serviceReportPayload)
    {
        echo "<ul>";
        echo "<li>db:";
        if (!empty($serviceReportPayload['database_file_size'])) {
            echo "<b>".number_format($serviceReportPayload['database_file_size'] / 1024 / 1024, 2, '.', '')." MB</b>";
        } else {
            echo "<span class='notice'>no size</span>";
        }
        echo " , ";
        if (!empty($serviceReportPayload['history_entries_count'])) {
            echo "<b>".$serviceReportPayload['history_entries_count']."</b> entries.";
        } else {
            echo "<span class='notice'>no entries</span>";
        }
        echo "<br>";
        echo "<li>oldest item at: ";
        if (!empty($serviceReportPayload['oldest_item_timestamp'])) {
            echo "".date("Y-m-d H:i:s", $serviceReportPayload['oldest_item_timestamp'])." (".$this->ago($serviceReportPayload['oldest_item_timestamp'])." ago)";
        } else {
            echo "<span class='notice'>empty</span>";
        }
        echo "</ul>";
    }

    /**
     * @param array $report
     */
    public function showGardaReport($report)
    {
        $payload = $report['payload'];
        $version = $payload['version'];
        $systemName = $payload['system_name'];
        //$minutesAgo = floor((time() - $payload['timestamp']) / (60));

        echo "<b class='reportName'>$systemName</b> ".(getenv("KD_SYSTEM_NAME") == $systemName ? " (THIS GARDA)" : "")."<hr>";

        //echo "raport received at: <b>".date("Y-m-d H:i:s", $report['timestamp'])."</b><br>";

        echo "time: <b>".$payload['local_time']."</b> ";
        echo "(".$this->ago($payload['timestamp'])." ago)";
        if ((time() - $payload['timestamp']) > 1200) {
            echo "<watch>";
            echo "<span class='warning'>report is old</span>";
            echo "</watch>";
        }
        echo "<br>";
        echo "topic: ".$report['topic']."<br>";
        echo "cpu temp: <b>".$payload['cpu_temp']." C</b><br>";
        echo "uptime: <b>".(floor($payload['uptime_seconds'] / (3600 * 24)))." days</b><br>";
        echo "disk space avail: <b>".(number_format($payload['disk_space_available_kb'] / (1024 * 1024), 2, '.', ''))." GB</b><br>";

        if (!empty($payload['services']['ngrok']['report']['ngrok_url'])) {
            $ngrokUrl = "http://".$payload['services']['ngrok']['report']['ngrok_url']."";
            echo "ngrok url: <a href='".$ngrokUrl."'>".$ngrokUrl."</a><br>";
            $videoStreamUrl = $ngrokUrl."/video";
            echo "video stream: <a href='".$videoStreamUrl."'>".$videoStreamUrl."</a><br>";
        }

        if ($version == 1) {

            $videoStreamInfo = $payload['video_stream'];
            echo "video stream: ".$videoStreamInfo;
            if (strpos($videoStreamInfo, "Stream #0:0: Video: mjpeg") === false) {
                echo "<watch>";
                echo "<span class='warning'>video format is invalid</span>";
                echo "</watch>";
            }

        } elseif ($version == 2) {

            foreach ($payload['services'] as $serviceName => $serviceReportFullData) {
                echo "<div class='service'>";
                //report meta-data
                echo "<watch>";
                echo "<b><u>".$serviceName."</u></b> (".($serviceReportFullData['is_enabled'] == 1 ? "enabled" : "<span class='notice'>disabled</span>").")<br>";
                echo "</watch>";
                if (!empty($serviceReportFullData['report']['timestamp'])) {
                    echo "at: ".date("Y-m-d H:i:s", $serviceReportFullData['report']['timestamp'])." (".$this->ago($serviceReportFullData['report']['timestamp'])." ago)";
                    if ((time() - $serviceReportFullData['report']['timestamp']) > 1200) {
                        echo "<watch>";
                        echo "<span class='notice'>old</span>";
                        echo "</watch>";
                    }
                }
                //service-specific info
                switch ($serviceName) {
                    case "ngrok":
                        $this->showServiceReportNgrok($serviceReportFullData['report']);
                        break;
                    case "kerberos":
                        $this->showServiceReportKerberos($serviceReportFullData['report']);
                        break;
                    case "thermometer":
                        $this->showServiceReportThermometer($serviceReportFullData['report']);
                        break;
                    case "historian":
                        $this->showServiceReportHistorian($serviceReportFullData['report']);
                        break;
                }
                echo "</div>";
            };

        } else {
            echo "ERROR: unsupported raport payload version $version";
        }
    }

    /**
     *
     */
    public function showReportsAsWebPage()
    {

        echo "
            <html>
            <head>
                <title>Swarm Watcher (".htmlspecialchars(getenv("KD_SYSTEM_NAME")).")</title>
            </head>
            <style>
            
                .reportName { font-size:15px; }
                
                .report {
                    font-family: Arial;
                    display: inline-block;
                    width:300px;
                    min-height:350px;
                    border: 1px solid black;
                    border-radius: 3px;
                    margin: 1px;
                    padding: 5px;
                    background: #efffff;
                    color: black;
                    font-size:11px;
                    vertical-align:top; 
                }
                
                .service {
                    border: 1px solid #aaa;
                    border-radius: 3px;
                    margin: 1px;
                    padding: 2px;
                    background: #efefef;
                    color: black;
                    font-size:11px;
                    //vertical-align:top; 
                }
                
                .notice {
                    display: inline-block;
                    //border: 1px solid #aaa;
                    //border-radius: 3px;
                    margin: 1px;
                    padding: 1px;
                    //background: yellow;
                    //color: black;
                    color: brown;
                }
                
                .warning {
                    display: inline-block;
                    border: 1px solid #aaa;
                    border-radius: 3px;
                    margin: 1px;
                    padding: 1px;
                    background: #ffaaaa;
                    color: black;
                }
                
                ul { padding-left: 10px; margin-top:1px;  }
                
            </style>
            <body>
        ";

        //delete a report file
        if (!empty($_GET['delete'])) {
            $fileToDelete = $this->collectedHealthReportsRootPath."/".$_GET['delete'].'.json';
            if (file_exists($fileToDelete) and (!unlink($fileToDelete))) {
                throw new \Exception("Cannot remove report file.");
            }
        }

        //scan all collected report files, visualize
        $reportFiles = glob($this->collectedHealthReportsRootPath."/*.json");
        foreach ($reportFiles as $fileName) {
            //echo "<div style='font-size:11px; margin:5px; border: solid 1px black; padding:5px; display: inline-block; min-width:200px; min-height: 100px; vertical-align: top'>";
            echo "<div class='report'>";
            echo "<report>";
            $fileContent = file_get_contents($fileName);
            if (empty($fileContent)) {
                //error
                echo "ERROR: Cannot get content from $fileName";
            } else {
                $report = json_decode($fileContent, true);
                if ($report === false) {
                    echo "ERROR: invalid json from $fileName";
                } else {
                    $this->showGardaReport($report);
                }
            }
            echo "</report>";

            echo "<hr>report file: ".basename($fileName)."<br>";
            echo "[<a href='?delete=".basename($fileName, '.json')."'>delete</a>]";

            echo "</div>";
        }

        echo "
            </body>
            
            </html>
        ";
    }

}


