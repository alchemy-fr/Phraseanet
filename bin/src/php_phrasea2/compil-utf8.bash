#!/bin/sh

# ---------------- VARIABLES A CONFIGURER --------------


PHP5="/usr"
mysql="/usr/include/mysql"
libMysql="/usr/lib/mysql"

apache_module="/usr/lib/apache2/modules"

extensiondir="/usr/lib/php5/20060613+lfs"

# ------------ FIN DES VARIABLES A CONFIGURER -----------





phpinclude="$PHP5/include/php5"
main="$phpinclude/main"
Zend="$phpinclude/Zend"
TSRM="$phpinclude/TSRM"
regex="$phpinclude/regex"

version=`cat ./_VERSION.h | egrep -e "#define[[:blank:]]*PHDOTVERSION" | tr -d '\r' | sed  -e "s/#define[[:blank:]]*PHDOTVERSION[[:blank:]]*\(.*\)[[:space:]]*$/\1/"`
outfile=php_phrasea2-utf8_`echo $version`.so

echo
echo ================================================
echo version : $version
echo outfile : $outfile
echo ================================================


curdir=`pwd`
listofiles=""

rm -R out
mkdir out

echo
echo "=========== Compilation des sources dans ../phrasea_engine ..." 
cd ./phrasea_engine
for file in  *.cpp
do
	basefile=`echo $file | cut -f1 -d "."`

	listofiles=`echo $listofiles out/$basefile.o`
	if [ -e ${basefile}.cpp ]; then
		echo "Compilation du fichier [ $file ]"
		cmd="g++ -Wno-write-strings -fPIC -DCOMPILE_DL_PHRASEA2=1 -DMYSQLENCODE=utf8 -c -o $curdir/out/$basefile.o -x c++ $basefile.cpp -I/usr/include/ -I${phpinclude} -I${main} -I${Zend} -I${TSRM} -I${regex} -I${mysql}"
		#echo $cmd
		`$cmd`
	fi
done

#echo $listofile

#exit

echo
echo "=========== Compilation des sources dans ../php_phrasea2 ..." 
cd ..
for file in  *.cpp
do
	basefile=`echo $file | cut -f1 -d "."`

	listofiles=`echo $listofiles out/$basefile.o`
	if [ -e ${basefile}.cpp ]; then
		echo "Compilation du fichier [ $file ]"
		cmd="g++ -Wno-write-strings -fPIC -DCOMPILE_DL_PHRASEA2=1 -DMYSQLENCODE=utf8 -c -o $curdir/out/$basefile.o -x c++ $basefile.cpp -I/usr/include/ -I${phpinclude} -I${main} -I${Zend} -I${TSRM} -I${regex} -I${mysql}"
		#cmd="g++ -DCOMPILE_DL_PHRASEA2 -I. -I/home/gaulier/src/phrasea2 -DPHP_ATOM_INC -I/home/gaulier/src/phrasea2/include -I/home/gaulier/src/phrasea2/main -I/home/gaulier/src/phrasea2 -I/usr/include/php5 -I/usr/include/php5/main -I/usr/include/php5/TSRM -I/usr/include/php5/Zend -I/usr/include/php5/ext -I/usr/include/php5/ext/date/lib -D_LARGEFILE_SOURCE -D_FILE_OFFSET_BITS=64 -I/usr/include/mysql -Wno-write-strings -DHAVE_CONFIG_H -g -O2 -c /home/gaulier/src/phrasea2/phrasea2.cpp  -fPIC -DPIC -o .libs/phrasea2.o"
		#echo $cmd
		`$cmd`
	fi
done


cd $curdir
if [ -e out/phrasea2.o ]; then
	echo
	echo "=========== Edition des liens..."

	cmd="g++ -shared -flat_namespace  -o ./out/$outfile $listofiles -lmysqlclient -L${libMysql} -L${apache_module}"
	cmd="g++ -shared  $listofiles      -Wl,-soname -Wl,phrasea2.so -o ./out/$outfile -lmysqlclient"
	echo $cmd
	`$cmd`
	
	if [ -e ./out/$outfile ]; then
	
		chmod 755 ./out/$outfile
		echo extension generee dans : $curdir/out/$outfile
		
#		cp $curdir/php.ini $curdir/out/php.ini
#		sed s/_EXT_/$outfile/ $curdir/test.php > ./out/test.php 
		
#		echo
#		echo =========== execution de 'test.php' ===============
#		cd out
#		$PHP5/bin/php -c ./php.ini -f ./test.php
#		cd ..
#		echo ===================================================
#		echo
		
#		cp ./out/$outfile $extensiondir/phrasea2.utf.so
#               cp ./out/$outfile $extensiondir/$outfile
                cp ./out/$outfile $extensiondir/phrasea2.so
		/etc/init.d/apache2 restart
	else
		echo
		echo "!!!!!!!!!!! Erreur de link"
	fi
else
	echo
	echo "!!!!!!!!!!! Erreur de compilation"
fi
