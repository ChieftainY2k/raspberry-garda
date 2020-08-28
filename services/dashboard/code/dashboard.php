<html>
<head>
    <title>Dashboard (<?php echo htmlspecialchars(getenv("KD_SYSTEM_NAME")); ?>)</title>
</head>
<style>

    .service {
        display: inline-block;
        border: 1px solid #aaa;
        border-radius: 3px;
        margin: 1px;
        padding: 5px;
        background: #ffffaa;
        color: black;
        min-width: 100px;
        min-height: 140px;
        vertical-align: top;
    }

    .serviceName {
        margin: 1px;
        padding: 5px;
        color: brown;
    }

</style>

<div>
    System name: <?php echo htmlspecialchars(getenv("KD_SYSTEM_NAME")); ?>
</div>

<div class="service">
    <span class="serviceName">Configurator</span>
    <ul>
        <li><a href="/configurator">User interface</a></li>
    </ul>
</div>

<div class="service">
    <span class="serviceName">Kerberos</span>
    <ul>
        <li>Service is <?php echo getenv("KD_KERBEROS_ENABLED") == 1 ? "enabled" : "disabled"; ?>
        <li><a href="/kerberos/dashboard">User interface</a></li>
        <li><a href="/kerberos/stream">Video stream</a></li>
    </ul>
</div>

<div class="service">
    <span class="serviceName">Email notifications</span>
    <ul>
        <li>Service is <?php echo getenv("KD_EMAIL_NOTIFICATION_ENABLED") == 1 ? "enabled" : "disabled"; ?>
    </ul>
</div>

<div class="service">
    <span class="serviceName">MQTT server</span>
    <ul>
        <li>MQTT bridge is <?php echo getenv("KD_MQTT_BRIDGE_ENABLED") == 1 ? "enabled" : "disabled"; ?>
    </ul>
</div>

<div class="service">
    <span class="serviceName">Swarm watcher</span>
    <ul>
        <li>Service is <?php echo getenv("KD_SWARM_WATCHER_ENABLED") == 1 ? "enabled" : "disabled"; ?>
        <li><a href="/swarmwatcher">User interface</a></li>
    </ul>
</div>

<div class="service">
    <span class="serviceName">Ngrok tunnel</span>
    <ul>
        <li>Service is <?php echo getenv("KD_NGROK_ENABLED") == 1 ? "enabled" : "disabled"; ?>
    </ul>
</div>

<div class="service">
    <span class="serviceName">Thermometer</span>
    <ul>
        <li>Service is <?php echo getenv("KD_THERMOMETER_ENABLED") == 1 ? "enabled" : "disabled"; ?>
    </ul>
</div>

<div class="service">
    <span class="serviceName">Historian</span>
    <ul>
        <li>Service is <?php echo getenv("KD_HISTORIAN_ENABLED") == 1 ? "enabled" : "disabled"; ?>
        <li><a href="/historian">User interface</a></li>
    </ul>
</div>

<div class="service">
    <span class="serviceName">File browser</span>
    <ul>
        <li>Service is <?php echo getenv("KD_FILEBROWSER_ENABLED") == 1 ? "enabled" : "disabled"; ?>
        <li><a href="/filebrowser">User interface</a></li>
    </ul>
</div>

<div style="margin-top: 20px">
    Last system report:<br><br>
    <pre>
    <?php echo (json_encode(json_decode(file_get_contents("/data-health-reporter/system-health-report.json")), JSON_PRETTY_PRINT)); ?>
    </pre>
</div>

<body>
