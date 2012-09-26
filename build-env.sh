cp -f hudson/*.yml config/
curl -s http://getcomposer.org/installer | php
php composer.phar install --dev
sudo mysql -e 'drop database ab_test;drop database db_test; drop database ab_unitTests; drop database db_unitTests;'
sudo mysql -e 'create database ab_test;create database db_test; create database ab_unitTests; create database db_unitTests;'
sudo mysql -e "GRANT ALL PRIVILEGES ON ab_unitTests.* TO 'phraseaUnitTests'@'localhost' IDENTIFIED BY 'iWvGxPE8' WITH GRANT OPTION"
sudo mysql -e "GRANT ALL PRIVILEGES ON db_unitTests.* TO 'phraseaUnitTests'@'localhost' IDENTIFIED BY 'iWvGxPE8' WITH GRANT OPTION"
sudo mysql -e "source `pwd`/hudson/fixtures.sql"
sudo mysql -e 'SET GLOBAL time_zone = "+02:00";'
sudo mysql -e 'SET @@global.sql_mode= "";'
