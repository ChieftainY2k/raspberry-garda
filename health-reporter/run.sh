
echo "Starting  the health reporter..."

sleep 10
./health-reporter.sh

while sleep 120; do
    echo "Executing the health reporter."
    ./health-reporter.sh
done

#sleep infinity
