#!/bin/bash

#check for errors
check_errors()
{
    EXITCODE=$1
    if [ $EXITCODE -ne 0 ]; then
        echo "---------------------------------------------------------------"
        echo "ERROR: there were some errors, check the ouput for details."
        echo "---------------------------------------------------------------"
        echo "Press ENTER to continue or Ctrl-C to abort."
        read
    else
        echo "OK, operation successfully completed."
    fi
}

echo "Checking if camera module is enabled and taking sample picture..."
rm -rf /tmp/testimage.jpg
raspistill -o /tmp/testimage.jpg
check_errors $?

# Install some required packages first
sudo apt update
check_errors $?
sudo apt install -y \
     apt-transport-https \
     ca-certificates \
     curl \
     wget \
     telnet \
     gnupg2 \
     software-properties-common \
     git \
     mc \
     multitail \
     htop \
     jnettop
check_errors $?

# Get the Docker signing key for packages
curl -fsSL https://download.docker.com/linux/$(. /etc/os-release; echo "$ID")/gpg | sudo apt-key add -
check_errors $?

# Add the Docker official repos
echo "deb [arch=armhf] https://download.docker.com/linux/$(. /etc/os-release; echo "$ID") \
     $(lsb_release -cs) stable" | \
    sudo tee /etc/apt/sources.list.d/docker.list
check_errors $?

# Install Docker
sudo apt update
check_errors $?

sudo apt install -y docker-ce
check_errors $?

sudo systemctl enable docker
check_errors $?

sudo systemctl start docker
check_errors $?

# Install required packages
apt install -y python python-pip
check_errors $?

# Install Docker Compose from pip
pip install docker-compose
check_errors $?

# start the containers
docker-compose up -d
check_errors $?

echo "OK, docker containers successfully started."

MYIP=$(ip route get 1 | awk '{print $NF;exit}')
echo "----------------------------------------------------------------"
echo "Installation complete. My IP address is: $MYIP"
echo "----------------------------------------------------------------"
