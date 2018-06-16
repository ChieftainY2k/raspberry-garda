
echo "Starting the health reporter..."

sleep 10
./health-reporter.sh

while sleep 120; do
    ./health-reporter.sh
done

#sleep infinity
