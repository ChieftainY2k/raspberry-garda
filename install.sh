#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][install]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: there were some errors, check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        read
    else
        log_message "OK, operation successfully completed."
    fi
}

ipAddress=$(ip route get 1 | awk '{print $NF;exit}')
availableDiskSpaceKb=$(df | grep /dev/root | awk '{print $4/1}')
log_message "Starting installation. My IP address: $ipAddress , available disk space: $availableDiskSpaceKb kb."

#logMessage "Updating the firmware..."
#rpi-update
#check_errors $?

log_message "Updating packages..."
sudo apt-get update -y
check_errors $?
sudo apt-get upgrade -y
check_errors $?

log_message "Checking if camera module is enabled and taking sample picture..."
raspistill -o /tmp/$(date +%s).jpg
check_errors $?


# Install some required packages first
log_message "Installing packages..."
sudo apt install -y \
     apt-transport-https ca-certificates \
     curl wget telnet gnupg2 software-properties-common \
     git mc multitail htop jnettop python python-pip joe pydf \
     build-essential libssl-dev libffi-dev python-dev
check_errors $?

# Get the Docker signing key for packages
log_message "Installing docker..."
curl -fsSL https://download.docker.com/linux/$(. /etc/os-release; echo "$ID")/gpg | sudo apt-key add -
check_errors $?
echo "deb [arch=armhf] https://download.docker.com/linux/$(. /etc/os-release; echo "$ID") \
     $(lsb_release -cs) stable" | \
    sudo tee /etc/apt/sources.list.d/docker.list
check_errors $?
sudo apt update
check_errors $?
sudo apt install -y docker-ce
check_errors $?
sudo systemctl enable docker
check_errors $?
sudo systemctl start docker
check_errors $?

log_message "Installing docker-compose..."
# Install Docker Compose from pip
pip install docker-compose
check_errors $?

log_message "Starting services..."
# start the containers
COMPOSE_HTTP_TIMEOUT=200 docker-compose up -d --remove-orphans
check_errors $?

#logMessage "Removing usunsed images..."
#docker rmi -f $(docker images -aq) 2>&1 /dev/null
#check_errors $?

availableDiskSpaceKb=$(df | grep /dev/root | awk '{print $4/1}')
log_message "Installation complete. My IP address: $ipAddress , available disk space: $availableDiskSpaceKb kb."
log_message "Run 'docker-compose logs -f' to see services logs."

