#!/usr/bin/env bash
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

git clone https://github.com/eugenkyky/test_repo.git xsolla &&
#composer get additional packages
cd xsolla &&
php composer.phar install &&

#install db scheme
mysql -uroot -pqwer1234 -e "CREATE DATABASE xsolla2;" &&

#create schema
vendor/bin/doctrine orm:schema-tool:create

#insert test data
mysql --user="root" --password="qwer1234" --database="xsolla2" --execute="insert into user(username,password,apikey) values('user','password','apikey');"&&

#sozdat' papky polzovatelya
mkdir xsolla/users_files/user

#run
#php -S 127.0.0.1:80 web/index.php
#es





