#!/usr/bin/env bash
# install php 7
# install mysql
# install git
# mysql tune
# download from git
# install composers packages
# start tests

#PHP install
sudo apt-get install python-software-properties &&
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y &&
sudo apt-get update &&
sudo apt-get remove php5-common -y &&
sudo apt-get install php7.0 php7.0-mysql -y &&
#MYSQL install
sudo apt-get install mysql-server &&
sudo apt-get install mysql-client &&
#download sources form GIT repo
sudo apt-get install git &&

#composer get additional packages

#install db scheme
mysql -uroot -pqwer1234 -e "CREATE DATABASE xsolla2;" &&

#insert test data
mysql --user="root" --password="qwer1234" --database="xsolla2" --execute="insert into user(username,password,apikey) values('user2','password','apikey');"&&

#run






