<?php


namespace SwarmWatcher;


use Exception;

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
     * ReportAnalyzer constructor.
     * @param $collectedHealthReportsRootPath string
     * @throws Exception
     */
    function __construct($collectedHealthReportsRootPath)
    {
        $this->collectedHealthReportsRootPath = $collectedHealthReportsRootPath;

        //@TODO validate the input
        if (empty(getenv("KD_SYSTEM_NAME"))) {
            throw new Exception("Empty environment variable KD_SYSTEM_NAME");
        }
        if (empty(getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"))) {
            throw new Exception("Empty environment variable KD_EMAIL_NOTIFICATION_RECIPIENT");
        }

    }

    /**
     * @param $msg
     */
    function log($msg)
    {
        $output[] = "[".date("Y-m-d H:i:s")."][".basename(__CLASS__)."] ".$msg."\n";
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
     * @return string
     */
    public function showServiceReportNgrok($serviceReportPayload)
    {
        $output[] = "<ul>";
        $output[] = "<li> url: <a href='http://".$serviceReportPayload['ngrok_url']."'>".$serviceReportPayload['ngrok_url']."</a>";
        $output[] = "</ul>";

        return join("", $output);
    }

    /**
     * @param $serviceReportPayload
     * @return string
     */
    public function showServiceReportKerberos($serviceReportPayload)
    {
        $output[] = "<ul>";
        $videoStreamInfo = $serviceReportPayload['video_stream'];
        $output[] = "<watch>";
        if (!empty($videoStreamInfo)) {
            $output[] = "<li>";
            $output[] = "video stream: ".$videoStreamInfo;
            if (strpos($videoStreamInfo, "Stream #0:0: Video: mjpeg") === false) {
                $output[] = "<span class='warning'>video format is invalid</span>";
            }
        }
        $output[] = "</watch>";
        $output[] = "</ul>";

        return join("", $output);
    }

    /**
     * @param $serviceReportPayload
     * @return string
     */
    public function showServiceReportThermometer($serviceReportPayload)
    {
        $output[] = "<ul>";
        foreach ($serviceReportPayload['sensors'] as $sensorReport) {
            $output[] = "<li>sensor: (<b>".$sensorReport['sensor_name']."</b>) ".$sensorReport['sensor_name_original']."<br>";
            $output[] = "<ul>";
            $output[] = "<li>reading: <b>".$sensorReport['sensor_reading']['celcius']."</b>'C<br>";
            $output[] = "<li>raw reading: ".$sensorReport['sensor_reading']['raw']."";
            $output[] = "</ul>";
        }
        $output[] = "</ul>";

        return join("", $output);
    }

    /**
     * @param $serviceReportPayload
     * @return string
     */
    public function showServiceReportHistorian($serviceReportPayload)
    {
        $output[] = "<ul>";
        $output[] = "<li>db:";
        if (!empty($serviceReportPayload['database_file_size'])) {
            $output[] = "<b>".number_format($serviceReportPayload['database_file_size'] / 1024 / 1024, 2, '.', '')." MB</b>";
        } else {
            $output[] = "<span class='notice'>no size</span>";
        }
        $output[] = " , ";
        if (!empty($serviceReportPayload['history_entries_count'])) {
            $output[] = "<b>".$serviceReportPayload['history_entries_count']."</b> entries.";
        } else {
            $output[] = "<span class='notice'>no entries</span>";
        }
        $output[] = "<li>oldest item at: ";
        if (!empty($serviceReportPayload['oldest_item_timestamp'])) {
            $output[] = "".date("Y-m-d H:i:s", $serviceReportPayload['oldest_item_timestamp'])." (".$this->ago($serviceReportPayload['oldest_item_timestamp'])." ago)";
        } else {
            $output[] = "<span class='notice'>empty</span>";
        }
        $output[] = "</ul>";

        return join("", $output);
    }

    /**
     * @param array $report
     * @return string
     */
    public function showGardaReport($report)
    {
        $payload = $report['payload'];
        $version = $payload['version'];
        $systemName = $payload['system_name'];
        //$minutesAgo = floor((time() - $payload['timestamp']) / (60));

        $output[] = "<b class='reportName'>$systemName</b> ".(getenv("KD_SYSTEM_NAME") == $systemName ? " (THIS GARDA)" : "")."<hr>";

        //$output[] = "raport received at: <b>".date("Y-m-d H:i:s", $report['timestamp'])."</b><br>";

        $output[] = "time: <b>".$payload['local_time']."</b> ";
        $output[] = "(".$this->ago($payload['timestamp'])." ago)";
        if ((time() - $payload['timestamp']) > 1200) {
            $output[] = "<watch>";
            $output[] = "<span class='warning'>outdated</span>";
            $output[] = "</watch>";
        }
        $output[] = "<br>";
        $output[] = "topic: ".$report['topic']."<br>";
        $output[] = "cpu temp: <b>".$payload['cpu_temp']." C</b><br>";
        $output[] = "uptime: <b>".(floor($payload['uptime_seconds'] / (3600 * 24)))." days</b><br>";
        $diskSpaceGB = $payload['disk_space_available_kb'] / (1024 * 1024);
        $output[] = "disk space avail: <b>".(number_format($diskSpaceGB, 2, '.', ''))." GB</b>";
        if ($diskSpaceGB < 1) {
            $output[] = "<span class='warning'>low disk space</span>";
        }
        $output[] = "<br>";

        if (!empty($payload['services']['ngrok']['report']['ngrok_url'])) {
            $ngrokUrl = "http://".$payload['services']['ngrok']['report']['ngrok_url']."";
            $output[] = "ngrok url: <a href='".$ngrokUrl."'>".$ngrokUrl."</a><br>";
            $videoStreamUrl = $ngrokUrl."/video";
            $output[] = "video stream: <a href='".$videoStreamUrl."'>".$videoStreamUrl."</a><br>";
        }

        if ($version == 1) {

            $videoStreamInfo = $payload['video_stream'];
            $output[] = "video stream: ".$videoStreamInfo;
            if (strpos($videoStreamInfo, "Stream #0:0: Video: mjpeg") === false) {
                $output[] = "<watch>";
                $output[] = "<span class='warning'>video format is invalid</span>";
                $output[] = "</watch>";
            }

        } elseif ($version == 2) {

            foreach ($payload['services'] as $serviceName => $serviceReportFullData) {
                $output[] = "<div class='service'>";
                //report meta-data
                $output[] = "<watch>";
                $output[] = "<b><u>".$serviceName."</u></b> (".($serviceReportFullData['is_enabled'] == 1 ? "enabled" : "<span class='notice'>disabled</span>").")<br>";
                $output[] = "</watch>";
                if (!empty($serviceReportFullData['report']['timestamp'])) {
                    $output[] = "at: ".date("Y-m-d H:i:s", $serviceReportFullData['report']['timestamp'])." (".$this->ago($serviceReportFullData['report']['timestamp'])." ago)";
                    if ((time() - $serviceReportFullData['report']['timestamp']) > 1200) {
                        $output[] = "<watch>";
                        $output[] = "<span class='warning'>outdated</span>";
                        $output[] = "</watch>";
                    }
                }
                //service-specific info
                switch ($serviceName) {
                    case "ngrok":
                        $output[] = $this->showServiceReportNgrok($serviceReportFullData['report']);
                        break;
                    case "kerberos":
                        $output[] = $this->showServiceReportKerberos($serviceReportFullData['report']);
                        break;
                    case "thermometer":
                        $output[] = $this->showServiceReportThermometer($serviceReportFullData['report']);
                        break;
                    case "historian":
                        $output[] = $this->showServiceReportHistorian($serviceReportFullData['report']);
                        break;
                }
                $output[] = "</div>";
            }

        } else {
            $output[] = "ERROR: unsupported raport payload version $version";
        }

        return join("", $output);
    }

    /**
     *
     */
    public function getSwarmReportsAsWebPage()
    {

        $output[] = "
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
                throw new Exception("Cannot remove report file.");
            }
        }

        //scan all collected report files, visualize
        $reportFiles = glob($this->collectedHealthReportsRootPath."/*.json");
        foreach ($reportFiles as $fileName) {
            //$output[] = "<div style='font-size:11px; margin:5px; border: solid 1px black; padding:5px; display: inline-block; min-width:200px; min-height: 100px; vertical-align: top'>";
            $output[] = "<div class='report'>";
            $output[] = "<report>";
            $fileContent = file_get_contents($fileName);
            if (empty($fileContent)) {
                //error
                $output[] = "ERROR: Cannot get content from $fileName";
            } else {
                $report = json_decode($fileContent, true);
                if ($report === false) {
                    $output[] = "ERROR: invalid json from $fileName";
                } else {
                    $output[] = $this->showGardaReport($report);
                }
            }
            $output[] = "</report>";

            $output[] = "<hr>report file: ".basename($fileName)."<br>";
            $output[] = "[<a href='?delete=".basename($fileName, '.json')."'>delete</a>]";

            $output[] = "</div>";
        }

        $output[] = "
            </body>
            
            </html>
        ";

        return join("", $output);
    }

}


