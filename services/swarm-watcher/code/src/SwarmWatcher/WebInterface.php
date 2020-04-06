<?php


namespace SwarmWatcher;


use Exception;

/**
 *
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
    public function visualizeServiceReportNgrok($serviceReportPayload)
    {
        if (!empty($serviceReportPayload['ngrok_url'])) {
            $output[] = "<ul>";
            $output[] = "<li>url: <watch id='ngrokUrl'><a href='http://".$serviceReportPayload['ngrok_url']."'>".$serviceReportPayload['ngrok_url']."</a></watch></li>";
            $output[] = "</ul>";
        } else {
            $output[] = "<watch id='ngrokNoUrl'><span class='notice'>no url</span></watch>";
        }

        return join("", $output);
    }

    /**
     * @param $serviceReportPayload
     * @return string
     */
    public function visualizeServiceReportKerberos($serviceReportPayload)
    {
        if (!empty($serviceReportPayload['video_stream'])) {

            $output[] = "<ul>";
            $videoStreamInfo = $serviceReportPayload['video_stream'];
            $output[] = "<watch id='kerberosVideoStream'>";
            if (!empty($videoStreamInfo)) {
                $output[] = "<li>";
                $output[] = "video stream: ".$videoStreamInfo;
                if (strpos($videoStreamInfo, "Stream #0:0: Video: mjpeg") === false) {
                    $output[] = "<span class='warning'>video format is invalid</span>";
                }
            }
            $output[] = "</watch>";
            $output[] = "</ul>";
        } else {
            $output[] = "<watch id='kerberosNoStream'><span class='notice'>no stream</span></watch>";
        }

        return join("", $output);
    }

    /**
     * @param $serviceReportPayload
     * @return string
     */
    public function visualizeServiceReportThermometer($serviceReportPayload)
    {
        $output[] = "";
        if (!empty($serviceReportPayload['sensors'])) {
            $output[] = "<ul>";
            foreach ($serviceReportPayload['sensors'] as $sensorReport) {

                $output[] = "<li>";
                $output[] = "    <watch id='thermoSensorName'>sensor: (<b>".$sensorReport['sensor_name']."</b>) ".$sensorReport['sensor_name_original']."</watch><br>";
                $output[] = "    <ul>";
                $output[] = "        <li>reading: <b>".$sensorReport['sensor_reading']['celcius']."</b>'C<br>";
                $output[] = "        <li>raw reading: ".$sensorReport['sensor_reading']['raw']."";
                $output[] = "    </ul>";
                $output[] = "</li>";
            }
            $output[] = "</ul>";
        } else {
            $output[] = "<watch id='thermoNoSensors'><span class='notice'>no sensors</span></watch>";
        }

        return join("", $output);
    }

    /**
     * @param $serviceReportPayload
     * @return string
     */
    public function visualizeServiceReportHistorian($serviceReportPayload)
    {
        $output[] = "<ul>";
        $output[] = "<li>db:";
        if (!empty($serviceReportPayload['database_file_size'])) {
            $output[] = "<b>".number_format($serviceReportPayload['database_file_size'] / 1024 / 1024, 2, '.', '')." MB</b>";
        } else {
            $output[] = "<watch id='historianNoSqlFileSize'><span class='notice'>no size</span></watch>";
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
    public function getGardaReportHtml($report)
    {
        $payload = $report['payload'];
        $version = $payload['version'];
        $systemName = $payload['system_name'];
        //$minutesAgo = floor((time() - $payload['timestamp']) / (60));

        $output[] = "<report id='".basename($systemName)."'>";
        $output[] = "<b class='reportName'>$systemName</b> ".(getenv("KD_SYSTEM_NAME") == $systemName ? " (THIS GARDA)" : "")."<hr>";

        //$output[] = "raport received at: <b>".date("Y-m-d H:i:s", $report['timestamp'])."</b><br>";

        $output[] = "raport created at: <b>".$payload['local_time']."</b> ";
        $output[] = "(".$this->ago($payload['timestamp'])." ago)";
        if ((time() - $payload['timestamp']) > 1200) {
            $output[] = "<watch id='reportOutdated'>";
            $output[] = "<span class='warning'>outdated</span>";
            $output[] = "</watch>";
        }
        $output[] = "<br>";
        $output[] = "topic: ".$report['topic']."<br>";
        $output[] = "cpu temp: <b>".$payload['cpu_temp']." C</b>";
        if ($payload['cpu_temp'] > 70) {
            $output[] = "<watch id='cpuTemperature'><span class='warning'>high CPU temp.</span></watch>";
        }
        $output[] = "<br>";

        $output[] = "<watch id='bootTime'>";
        $output[] = "started at: ";
        if (!empty($payload['uptime_boot_local_time'])) {
            $output[] = "<b>".$payload['uptime_boot_local_time']."</b> (local time)";
        } else {
            $output[] = "<span class='notice'>unknown boot time</span>";
        }
        $output[] = "<br>";
        $output[] = "</watch>";

        $output[] = "uptime: <b>".(floor($payload['uptime_seconds'] / (3600 * 24)))." days</b><br>";

        $diskSpaceGB = $payload['disk_space_available_kb'] / (1024 * 1024);
        $output[] = "disk space avail: <b>".(number_format($diskSpaceGB, 2, '.', ''))." GB</b>";
        if ($diskSpaceGB < 1.0) {
            $output[] = "<watch id='lowDiskSpace'><span class='warning'>low disk space</span></watch>";
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
            $output[] = "<watch id='kerberosVideoStream'>";
            $output[] = "video stream: ".$videoStreamInfo;
            if (strpos($videoStreamInfo, "Stream #0:0: Video: mjpeg") === false) {
                $output[] = "<span class='warning'>video format is invalid</span>";
            }
            $output[] = "</watch>";

        } elseif ($version == 2) {

            //scan all services
            foreach ($payload['services'] as $serviceName => $serviceReportFullData) {
                $output[] = "<service id='".$serviceName."'>";
                $output[] = "<div class='service'>";
                //report meta-data
                $output[] = "<watch id='serviceIsEnabled-".$serviceName."'>";
                $output[] = "<b><u>".$serviceName."</u></b> (".(!empty($serviceReportFullData['is_enabled']) ? "enabled" : "<span class='notice'>disabled</span>").")<br>";
                $output[] = "</watch>";
                if (!empty($serviceReportFullData['report']['timestamp'])) {
                    $output[] = "at: ".date("Y-m-d H:i:s", $serviceReportFullData['report']['timestamp'])." (".$this->ago($serviceReportFullData['report']['timestamp'])." ago)";
                    if ((time() - $serviceReportFullData['report']['timestamp']) > 1200) {
                        $output[] = "<watch id='serviceReportIsOutdated'>";
                        $output[] = "<span class='warning'>outdated</span>";
                        $output[] = "</watch>";
                    }
                }
                //service-specific info
                if (!empty($serviceReportFullData['report'])) {

                    switch ($serviceName) {
                        case "ngrok":
                            $output[] = $this->visualizeServiceReportNgrok($serviceReportFullData['report']);
                            break;
                        case "kerberos":
                            $output[] = $this->visualizeServiceReportKerberos($serviceReportFullData['report']);
                            break;
                        case "thermometer":
                            $output[] = $this->visualizeServiceReportThermometer($serviceReportFullData['report']);
                            break;
                        case "historian":
                            $output[] = $this->visualizeServiceReportHistorian($serviceReportFullData['report']);
                            break;
                    }
                } else {
                    $output[] = "<watch id='noServiceReport-".$serviceName."'><span class='notice'>no report</span></watch>";
                }
                $output[] = "</service>";
                $output[] = "</div>";
            }

        } else {
            $output[] = "<watch id='unsupportedReportVersion'><span class='warning'>ERROR: unsupported raport payload version $version</span></watch>";
        }

        $output[] = "</report>";

        return join("", $output);
    }

    /**
     *
     */
    public function getSwarmReportHtml()
    {

        $output[] = "
            <html>
            <head>
                <title>Swarm Watcher (".htmlspecialchars(getenv("KD_SYSTEM_NAME")).")</title>
            </head>
            <style>
            
                .reportName { font-size:15px; }
                
                .reportContainer {
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
                
                ul { padding-left: 10px; margin-top:1px; margin-bottom: 0px; }
                
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
            $output[] = "<div class='reportContainer'>";
            $fileContent = file_get_contents($fileName);
            if (empty($fileContent)) {
                //error
                $output[] = "ERROR: Cannot get content from $fileName";
            } else {
                $report = json_decode($fileContent, true);
                if ($report === false) {
                    $output[] = "ERROR: invalid json from $fileName";
                } else {
                    $output[] = $this->getGardaReportHtml($report);
                }
            }

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


