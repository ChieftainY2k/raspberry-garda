raspivid -n -w 640 -h 480 -b 400000 -fps 30 -vf -hf -t 0 -o - | cvlc -vvv stream:///dev/stdin --sout '#rtp{sdp=rtsp://:9000/stream}' :demux=h264
#sleep infinity

#ffprobe rtsp://streamer:9000/stream

