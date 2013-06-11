rm -f config/configuration.yml config/services.yml config/connexions.yml config/config.yml config/config.inc config/connexion.inc config/_GV.php config/_GV.php.old
cp -f hudson/connexion.inc config/
cp -f hudson/_GV.php config/
php vendors.php
sudo mysql -e 'drop database ab_test;drop database db_test; drop database ab_unitTests; drop database db_unitTests;'
sudo mysql -e 'create database ab_test;create database db_test; create database ab_unitTests; create database db_unitTests;'
sudo mysql -e "GRANT ALL PRIVILEGES ON ab_unitTests.* TO 'phraseaUnitTests'@'localhost' IDENTIFIED BY 'iWvGxPE8' WITH GRANT OPTION"
sudo mysql -e "GRANT ALL PRIVILEGES ON db_unitTests.* TO 'phraseaUnitTests'@'localhost' IDENTIFIED BY 'iWvGxPE8' WITH GRANT OPTION"

sudo mysql -e "source `pwd`/hudson/fixtures.sql"
sudo mysql -e 'SET @@global.sql_mode= "";'
