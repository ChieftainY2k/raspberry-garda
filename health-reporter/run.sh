
echo "Starting the health reporter..."

./health-reporter.sh

while sleep 120; do
    ./health-reporter.sh
done

#sleep infinity
