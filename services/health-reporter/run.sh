echo "Starting  the health reporter..."

# fix permissions
chmod u+x /code/health-reporter.sh

sleep 10
/code/health-reporter.sh

while sleep 120; do
    echo "Executing the health reporter."
    /code/health-reporter.sh
done

#sleep infinity
