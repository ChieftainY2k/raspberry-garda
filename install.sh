#!/bin/bash

#helper function
logMessage()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][install]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    EXITCODE=$1
    if [ $EXITCODE -ne 0 ]; then
        logMessage "ERROR: there were some errors, check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        read
    else
        logMessage "OK, operation successfully completed."
    fi
}

logMessage "Checking if camera module is enabled and taking sample picture..."
rm -rf /tmp/testimage.jpg
raspistill -o /tmp/testimage.jpg
check_errors $?

# Install some required packages first
logMessage "Installing packages..."
sudo apt update
check_errors $?
sudo apt install -y \
     apt-transport-https ca-certificates \
     curl wget telnet gnupg2 software-properties-common \
     git mc multitail htop jnettop python python-pip
check_errors $?

# Get the Docker signing key for packages
logMessage "Installing docker..."
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

logMessage "Installing docker-compose..."
# Install Docker Compose from pip
pip install docker-compose
check_errors $?

logMessage "Starting services..."
# start the containers
docker-compose up -d --remove-orphans
check_errors $?

MYIP=$(ip route get 1 | awk '{print $NF;exit}')
logMessage "Installation complete. My IP address is: $MYIP"
