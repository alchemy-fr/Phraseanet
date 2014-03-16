composer install --dev
./bin/developer system:uninstall || exit 1
cp -f hudson/connexion.inc config/ || exit 1
cp -f hudson/_GV.php config/ || exit 1
sudo npm install -g uglify-js recess grunt-cli jake
npm install
if [ "$1" != "--no-dependencies" ]
then
./bin/developer dependencies:all --clear-cache --prefer-source || exit 1
else
echo "Dependencies retrieval discarded"
fi
sudo mysql -e 'drop database update39_test;'
sudo mysql -e 'drop database ab_test;'
sudo mysql -e 'drop database db_test;'
sudo mysql -e 'drop database ab_unitTests;'
sudo mysql -e 'drop database db_unitTests;'
sudo mysql -e 'create database update39_test;'
sudo mysql -e 'create database ab_test;'
sudo mysql -e 'create database db_test;'
sudo mysql -e 'create database ab_unitTests;'
sudo mysql -e 'create database db_unitTests;'
sudo mysql -e "GRANT ALL PRIVILEGES ON ab_unitTests.* TO 'phraseaUnitTests'@'localhost' IDENTIFIED BY 'iWvGxPE8' WITH GRANT OPTION" || exit 1
sudo mysql -e "GRANT ALL PRIVILEGES ON db_unitTests.* TO 'phraseaUnitTests'@'localhost' IDENTIFIED BY 'iWvGxPE8' WITH GRANT OPTION" || exit 1
sudo mysql -e "source `pwd`/hudson/fixtures.sql" || exit 1
sudo mysql -e 'SET @@global.sql_mode= "";' || exit 1
