#LD_LIBRARY_PATH=/opt/mjpg-streamer/ /opt/mjpg-streamer/mjpg_streamer -i "input_raspicam.so -vf -hf -fps 15 -q 50 -x 640 -y 480" -o "output_http.so -p 9000 -w /opt/mjpg-streamer/www" > /dev/null 2>&1&

#sleep infinity

cd /mjpg-streamer/mjpg-streamer-experimental

LD_LIBRARY_PATH=. ./mjpg_streamer -o "output_http.so -w ./www -p $STREAM_PORT" -i "input_raspicam.so -x $STREAM_WIDTH -y $STREAM_HEIGHT -fps $STREAM_FPS"

# ---------------------------------------------------------------
# Help for input plugin..: raspicam input plugin
# ---------------------------------------------------------------
# The following parameters can be passed to this plugin:
#
# [-fps | --framerate]...: set video framerate, default 5 frame/sec
# [-x | --width ]........: width of frame capture, default 640
# [-y | --height]........: height of frame capture, default 480
# [-quality].............: set JPEG quality 0-100, default 85
# [-usestills]...........: uses stills mode instead of video mode
# [-preview].............: enable full screen preview
#
# -sh : Set image sharpness (-100 to 100)
# -co : Set image contrast (-100 to 100)
# -br : Set image brightness (0 to 100)
# -sa : Set image saturation (-100 to 100)
# -ISO : Set capture ISO
# -vs : Turn on video stabilisation
# -ev : Set EV compensation
# -ex : Set exposure mode (see raspistill notes)
# -awb : Set AWB mode (see raspistill notes)
# -ifx : Set image effect (see raspistill notes)
# -cfx : Set colour effect (U:V)
# -mm : Set metering mode (see raspistill notes)
# -rot : Set image rotation (0-359)
#-stats : Compute image stats for each picture (reduces noise)
# -drc : Dynamic range compensation level (see raspistill notes)
# -hf : Set horizontal flip
# -vf : Set vertical flip
# ---------------------------------------------------------------


#---------------------------------------------------------------
#mjpg-streamer output plugin: output_http
#The following parameters can be passed to this plugin:
#
#[-w | --www ]...........: folder that contains webpages in
#                          flat hierarchy (no subfolders)
#[-p | --port ]..........: TCP port for this HTTP server
#[-c | --credentials ]...: ask for "username:password" on connect
#[-n | --nocommands ]....: disable execution of commands
#---------------------------------------------------------------