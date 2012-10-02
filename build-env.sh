rm -f config/services.yml config/connexions.yml config/config.yml config/config.inc config/connexion.inc config/_GV.php config/_GV.php.old
cp -f hudson/connexion.inc config/
cp -f hudson/_GV.php config/
curl -s http://getcomposer.org/installer | php
php composer.phar install --dev
sudo mysql -e 'drop database ab_test;drop database db_test; drop database ab_unitTests; drop database db_unitTests;'
sudo mysql -e 'create database ab_test;create database db_test; create database ab_unitTests; create database db_unitTests;'
sudo mysql -e "GRANT ALL PRIVILEGES ON ab_unitTests.* TO 'phraseaUnitTests'@'localhost' IDENTIFIED BY 'iWvGxPE8' WITH GRANT OPTION"
sudo mysql -e "GRANT ALL PRIVILEGES ON db_unitTests.* TO 'phraseaUnitTests'@'localhost' IDENTIFIED BY 'iWvGxPE8' WITH GRANT OPTION"

if [ "$1" = "faac" ]
then
    sudo mysql -e "source `pwd`/hudson/fixtures-faac.sql"
else
    echo "Loading fixture with lib_vo_aacenc, use 'build-env.sh faac' to load with faac library"
    sudo mysql -e "source `pwd`/hudson/fixtures.sql"
fi

sudo mysql -e 'SET GLOBAL time_zone = "+02:00";'
sudo mysql -e 'SET @@global.sql_mode= "";'
